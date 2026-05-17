/**
 * Runs moveAccentToPosition from the production csv-editor.js via a sandboxed
 * vm context, so any change to the production file is directly tested.
 *
 * Usage: node moveAccentToPosition.js <jsonEncodedRawText> <charPosition>
 * Output: JSON-encoded result string written to stdout.
 */

const vm = require('vm');
const fs = require('fs');
const path = require('path');

const src = fs.readFileSync(path.join(__dirname, '../../resources/js/csv-editor.js'), 'utf8');

const mockWindow = {};

vm.runInNewContext(src, {
    window: mockWindow,
    document: { addEventListener: () => {} },
    Alpine: { store: () => {} },
    Livewire: { hook: () => {} },
    requestAnimationFrame: () => {},
});

const [rawText, charPosition] = process.argv.slice(2);
process.stdout.write(
    JSON.stringify(mockWindow.csvAccentMode.moveAccentToPosition(JSON.parse(rawText), parseInt(charPosition, 10)))
);
