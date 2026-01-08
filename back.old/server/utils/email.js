const nodemailer = require('nodemailer');

// Configuration du transporteur email
// TODO: Configurer avec SendGrid ou autre service
let transporter;

if (process.env.EMAIL_SERVICE === 'sendgrid') {
  // Configuration SendGrid
  transporter = nodemailer.createTransport({
    host: 'smtp.sendgrid.net',
    port: 587,
    auth: {
      user: 'apikey',
      pass: process.env.SENDGRID_API_KEY
    }
  });
} else {
  // Configuration SMTP standard
  transporter = nodemailer.createTransport({
    host: process.env.SMTP_HOST || 'smtp.gmail.com',
    port: process.env.SMTP_PORT || 587,
    secure: false,
    auth: {
      user: process.env.SMTP_USER,
      pass: process.env.SMTP_PASSWORD
    }
  });
}

/**
 * Envoie un email
 */
async function sendEmail({ to, subject, html, text }) {
  try {
    const info = await transporter.sendMail({
      from: process.env.EMAIL_FROM || 'noreply@eventmanager.com',
      to,
      subject,
      html,
      text
    });
    return { success: true, messageId: info.messageId };
  } catch (error) {
    console.error('Erreur envoi email:', error);
    throw error;
  }
}

/**
 * Email de confirmation d'inscription
 */
async function sendRegistrationConfirmation(registration, event) {
  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0ea5e9; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .qr-code { text-align: center; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
      </style>
    </head>
    <body>
      <div class="container">
        <div class="header">
          <h1>${event.name}</h1>
        </div>
        <div class="content">
          <h2>Confirmation d'inscription</h2>
          <p>Bonjour ${registration.first_name} ${registration.last_name},</p>
          <p>Votre inscription à l'événement <strong>${event.name}</strong> a été confirmée.</p>
          <p><strong>Date:</strong> ${new Date(event.start_date).toLocaleDateString('fr-FR')}</p>
          <div class="qr-code">
            <p>Votre code d'accès:</p>
            <img src="${registration.qr_code}" alt="QR Code" />
          </div>
          <p>Présentez ce QR code à l'entrée de l'événement.</p>
        </div>
        <div class="footer">
          <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </div>
      </div>
    </body>
    </html>
  `;

  return sendEmail({
    to: registration.email,
    subject: `Confirmation d'inscription - ${event.name}`,
    html
  });
}

module.exports = {
  sendEmail,
  sendRegistrationConfirmation
};

