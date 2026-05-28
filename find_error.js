const fs = require('fs');
const acorn = require('acorn');
const code = fs.readFileSync('test_syntax.js', 'utf8');
const lines = code.split('\n');

for (let i = 1; i <= lines.length; i++) {
    const chunk = lines.slice(0, i).join('\n');
    try {
        acorn.parse(chunk, {ecmaVersion: 'latest'});
    } catch (e) {
        if (e.message.includes('Unexpected token')) {
            if (e.loc.line < i) {
                console.log("Syntax error starts to be unrecoverable around line " + e.loc.line);
                console.log(lines[e.loc.line - 2]);
                console.log(lines[e.loc.line - 1]);
                console.log(lines[e.loc.line]);
                process.exit(0);
            }
        }
    }
}
console.log("Not found this way.");
