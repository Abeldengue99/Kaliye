const fs = require('fs');
fs.writeFileSync('chunk.js', fs.readFileSync('test_syntax.js', 'utf8').split('\n').slice(1238, 1372).join('\n'));
