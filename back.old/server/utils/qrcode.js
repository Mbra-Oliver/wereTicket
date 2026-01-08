const QRCode = require('qrcode');

/**
 * Génère un QR code pour une inscription
 */
async function generateQRCode(data) {
  try {
    const qrCodeData = JSON.stringify(data);
    const qrCodeUrl = await QRCode.toDataURL(qrCodeData, {
      width: 200,
      margin: 2,
      color: {
        dark: '#000000',
        light: '#FFFFFF'
      }
    });
    return qrCodeUrl;
  } catch (error) {
    console.error('Erreur génération QR code:', error);
    throw error;
  }
}

/**
 * Génère un QR code SVG
 */
async function generateQRCodeSVG(data) {
  try {
    const qrCodeData = JSON.stringify(data);
    const qrCodeSvg = await QRCode.toString(qrCodeData, {
      type: 'svg',
      width: 200,
      margin: 2
    });
    return qrCodeSvg;
  } catch (error) {
    console.error('Erreur génération QR code SVG:', error);
    throw error;
  }
}

module.exports = {
  generateQRCode,
  generateQRCodeSVG
};

