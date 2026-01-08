const path = require('path');
console.log('__dirname:', __dirname);
console.log('Resolved start.js:', require.resolve('./start.js'));
const fs = require('fs');
const content = fs.readFileSync('./start.js', 'utf8');
console.log('First 200 chars:', content.substring(0, 200));
console.log('Contains server/server:', content.includes('server/server'));
