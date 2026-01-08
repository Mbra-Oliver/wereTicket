const express = require('express');
const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY);
const { v4: uuidv4 } = require('uuid');
const pool = require('../config/database');
const { authenticate } = require('../middleware/auth');

const router = express.Router();

// Créer un paiement Stripe
router.post('/create-intent', async (req, res) => {
  try {
    const { amount, currency, registration_id, event_id, promo_code_id } = req.body;

    if (!amount || amount <= 0) {
      return res.status(400).json({ error: 'Montant invalide' });
    }

    // Calculer le montant final avec le code promo si fourni
    let finalAmount = amount;
    let discountAmount = 0;

    if (promo_code_id) {
      const [promoCodes] = await pool.execute(
        'SELECT * FROM promo_codes WHERE id = ? AND event_id = ? AND status = "active"',
        [promo_code_id, event_id]
      );

      if (promoCodes.length > 0) {
        const promoCode = promoCodes[0];
        const now = new Date();
        
        // Vérifier la validité
        if ((!promoCode.valid_from || new Date(promoCode.valid_from) <= now) &&
            (!promoCode.valid_until || new Date(promoCode.valid_until) >= now) &&
            (!promoCode.max_uses || promoCode.used_count < promoCode.max_uses)) {
          
          if (promoCode.discount_type === 'percentage') {
            discountAmount = (amount * promoCode.discount_value) / 100;
          } else {
            discountAmount = Math.min(promoCode.discount_value, amount);
          }
          finalAmount = Math.max(0, amount - discountAmount);
        }
      }
    }

    // Créer la commande
    const orderNumber = `ORD-${Date.now()}-${uuidv4().substring(0, 8).toUpperCase()}`;
    const [orderResult] = await pool.execute(
      `INSERT INTO orders (
        event_id, registration_id, order_number, total_amount, currency,
        payment_status, promo_code_id, discount_amount, created_at
      ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, NOW())`,
      [event_id, registration_id || null, orderNumber, finalAmount, currency || 'EUR', promo_code_id || null, discountAmount]
    );

    const orderId = orderResult.insertId;

    // Créer le PaymentIntent Stripe
    const paymentIntent = await stripe.paymentIntents.create({
      amount: Math.round(finalAmount * 100), // Convertir en centimes
      currency: currency.toLowerCase() || 'eur',
      metadata: {
        order_id: orderId.toString(),
        registration_id: registration_id?.toString() || '',
        event_id: event_id.toString(),
        order_number: orderNumber
      },
      automatic_payment_methods: {
        enabled: true
      }
    });

    // Mettre à jour la commande avec l'ID Stripe
    await pool.execute(
      'UPDATE orders SET stripe_payment_intent_id = ? WHERE id = ?',
      [paymentIntent.id, orderId]
    );

    // Incrémenter le compteur d'utilisation du code promo
    if (promo_code_id) {
      await pool.execute(
        'UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?',
        [promo_code_id]
      );
    }

    res.json({
      client_secret: paymentIntent.client_secret,
      order_id: orderId,
      order_number: orderNumber,
      amount: finalAmount,
      discount_amount: discountAmount
    });
  } catch (error) {
    console.error('Erreur création paiement:', error);
    res.status(500).json({ error: 'Erreur lors de la création du paiement' });
  }
});

// Webhook Stripe
router.post('/webhook', express.raw({ type: 'application/json' }), async (req, res) => {
  const sig = req.headers['stripe-signature'];
  let event;

  try {
    event = stripe.webhooks.constructEvent(req.body, sig, process.env.STRIPE_WEBHOOK_SECRET);
  } catch (err) {
    console.error('Erreur webhook Stripe:', err.message);
    return res.status(400).send(`Webhook Error: ${err.message}`);
  }

  const connection = await pool.getConnection();
  await connection.beginTransaction();

  try {
    if (event.type === 'payment_intent.succeeded') {
      const paymentIntent = event.data.object;
      const { order_id, registration_id, event_id } = paymentIntent.metadata;

      // Mettre à jour le statut de la commande
      await connection.execute(
        `UPDATE orders SET payment_status = 'paid', paid_at = NOW() 
         WHERE id = ? OR stripe_payment_intent_id = ?`,
        [order_id, paymentIntent.id]
      );

      // Si une inscription est associée, mettre à jour son statut
      if (registration_id) {
        await connection.execute(
          'UPDATE registrations SET status = "confirmed" WHERE id = ?',
          [registration_id]
        );
      }

      // Envoyer l'email de confirmation si nécessaire
      if (registration_id && event_id) {
        try {
          const [registrations] = await connection.execute(
            'SELECT * FROM registrations WHERE id = ?',
            [registration_id]
          );
          const [events] = await connection.execute(
            'SELECT * FROM events WHERE id = ?',
            [event_id]
          );

          if (registrations.length && events.length) {
            const { sendRegistrationConfirmation } = require('../utils/email');
            await sendRegistrationConfirmation(registrations[0], events[0]);
          }
        } catch (emailError) {
          console.error('Erreur envoi email confirmation:', emailError);
          // Ne pas faire échouer le webhook si l'email échoue
        }
      }
    } else if (event.type === 'payment_intent.payment_failed') {
      const paymentIntent = event.data.object;
      const { order_id } = paymentIntent.metadata;

      await connection.execute(
        `UPDATE orders SET payment_status = 'failed' 
         WHERE id = ? OR stripe_payment_intent_id = ?`,
        [order_id, paymentIntent.id]
      );
    } else if (event.type === 'charge.refunded') {
      const charge = event.data.object;
      const paymentIntentId = charge.payment_intent;

      await connection.execute(
        `UPDATE orders SET payment_status = 'refunded' 
         WHERE stripe_payment_intent_id = ?`,
        [paymentIntentId]
      );
    }

    await connection.commit();
    connection.release();

    res.json({ received: true });
  } catch (error) {
    await connection.rollback();
    connection.release();
    console.error('Erreur traitement webhook:', error);
    res.status(500).json({ error: 'Erreur traitement webhook' });
  }
});

// Obtenir le statut d'une commande
router.get('/orders/:orderId', authenticate, async (req, res) => {
  try {
    const { orderId } = req.params;

    const [orders] = await pool.execute(
      `SELECT o.*, e.name as event_name, e.organizer_id
       FROM orders o
       JOIN events e ON o.event_id = e.id
       WHERE o.id = ? AND e.organizer_id = ?`,
      [orderId, req.user.organizer_id]
    );

    if (!orders.length) {
      return res.status(404).json({ error: 'Commande non trouvée' });
    }

    res.json({ order: orders[0] });
  } catch (error) {
    console.error('Erreur récupération commande:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

module.exports = router;

