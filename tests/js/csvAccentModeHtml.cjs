/**
 * Loads the real resources/js/csv-editor.js in a sandbox (with minimal DOM
 * stubs) and prints the output of window.csvAccentMode[fn](input) as JSON.
 *
 * Usage: node csvAccentModeHtml.cjs <buildHtml|sanitizeRichText> '<json string>'
 */
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const [, , functionName, jsonInput] = process.argv;
const source = fs.readFileSync(path.join(__dirname, '../../resources/js/csv-editor.js'), 'utf8');

const sandbox = {
    document: { addEventListener() {}, documentElement: { style: { setProperty() {} } } },
    HTMLMediaElement: { HAVE_ENOUGH_DATA: 4 },
};
sandbox.window = sandbox;
vm.createContext(sandbox);
vm.runInContext(source, sandbox);

process.stdout.write(JSON.stringify(sandbox.csvAccentMode[functionName](JSON.parse(jsonInput))));
