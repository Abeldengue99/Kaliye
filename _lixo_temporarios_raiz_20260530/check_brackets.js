const fs = require('fs');
const js = fs.readFileSync('test_syntax.js', 'utf8');

let braces = 0;
let parens = 0;
let brackets = 0;
let inString = false;
let inStringChar = '';
let inLineComment = false;
let inBlockComment = false;

for (let i = 0; i < js.length; i++) {
    const c = js[i];
    const next = js[i+1];
    const prev = js[i-1];
    
    if (inLineComment) {
        if (c === '\n') inLineComment = false;
        continue;
    }
    if (inBlockComment) {
        if (c === '*' && next === '/') {
            inBlockComment = false;
            i++;
        }
        continue;
    }
    if (inString) {
        if (c === '\\') { i++; continue; } // skip escaped char
        if (c === inStringChar) {
            inString = false;
        }
        continue;
    }
    
    if (c === '/' && next === '/') { inLineComment = true; i++; continue; }
    if (c === '/' && next === '*') { inBlockComment = true; i++; continue; }
    if (c === '"' || c === "'" || c === "`") { inString = true; inStringChar = c; continue; }
    
    if (c === '{') braces++;
    if (c === '}') braces--;
    if (c === '(') parens++;
    if (c === ')') parens--;
    if (c === '[') brackets++;
    if (c === ']') brackets--;
}

console.log("Braces:", braces, "Parens:", parens, "Brackets:", brackets);
