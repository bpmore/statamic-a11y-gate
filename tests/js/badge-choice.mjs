// The badge choice is pure logic, so it can be exercised without a browser.
// Lifted verbatim from the panel rather than restated: a copy would pass while
// the file it claims to test said something else.
import { readFileSync } from 'fs';

const src = readFileSync(new URL('../../resources/js/a11y-panel.js', import.meta.url), 'utf8');

// The assignment in the catch, not the reset above it: matching the first
// `state.notYet =` in the file picked up `false` and every case passed as red.
const notYetExpr = src.match(/state\.notYet = (e\.response.+);/)[1];
// Anchored on the badge element, not the first :color/:text in the file: the
// button above it has a :text of its own, and matching that passed nothing.
const badge = src.match(/<ui-badge\s+:color=([\s\S]*?)\/>/)[0];
const colorExpr = badge.match(/:color="(.+?)"/)[1];
const textExpr = badge.match(/:text="(.+?)"/)[1];

const decide = new Function('e', `
    const state = {};
    state.notYet = ${notYetExpr};
    return { color: ${colorExpr}, text: ${textExpr} };
`);

const cases = [
    ['never saved (422)', { response: { status: 422 } }, 'amber', 'Not checked yet'],
    ['entry gone (404)', { response: { status: 404 } }, 'red', 'Could not check'],
    ['forbidden (403)', { response: { status: 403 } }, 'red', 'Could not check'],
    ['server error (500)', { response: { status: 500 } }, 'red', 'Could not check'],
    ['network, no response', {}, 'red', 'Could not check'],
];

let bad = 0;
for (const [label, err, color, text] of cases) {
    const got = decide(err);
    const ok = got.color === color && got.text === text;
    if (!ok) bad++;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${label}: ${got.color} "${got.text}"`);
}
console.log(bad === 0 ? `\nall ${cases.length} passed` : `\n${bad} FAILED`);
process.exit(bad === 0 ? 0 : 1);
