// How a failed check is styled is pure logic, so it can be exercised without a
// browser. Lifted verbatim from the panel rather than restated: a copy would
// pass while the file it claims to test said something else.
import { readFileSync } from 'fs';

const src = readFileSync(new URL('../../resources/js/a11y-panel.js', import.meta.url), 'utf8');

// The assignment in the catch, not the reset above it: matching the first
// `state.notYet =` in the file picked up `false` and every case passed as red.
const notYetExpr = src.match(/state\.notYet = (e\.response.+);/)[1];
// Anchored on the alert bound to `state.failed`, not the first :variant in the
// file: the panel draws a second alert for a gate that could not render, and
// the button above both has a :text of its own. Matching loosely passed nothing.
const alert = src.match(/<ui-alert\s+v-else-if="state\.failed"([\s\S]*?)\/>/)[0];
const variantExpr = alert.match(/:variant="(.+?)"/)[1];

const decide = new Function('e', `
    const state = {};
    state.notYet = ${notYetExpr};
    return ${variantExpr};
`);

// `warning` is amber and `error` is red, and only one case is allowed to be
// amber: an entry nobody has saved yet is not a fault.
const cases = [
    ['never saved (422)', { response: { status: 422 } }, 'warning'],
    ['entry gone (404)', { response: { status: 404 } }, 'error'],
    ['forbidden (403)', { response: { status: 403 } }, 'error'],
    ['server error (500)', { response: { status: 500 } }, 'error'],
    ['network, no response', {}, 'error'],
];

let bad = 0;
for (const [label, err, variant] of cases) {
    const got = decide(err);
    const ok = got === variant;
    if (!ok) bad++;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${label}: ${got} (wanted ${variant})`);
}
// The request the panel sends. Checked here because nothing on the PHP side
// can: the endpoint's own tests post whatever they like, so they would keep
// passing while the panel quietly stopped sending half of it. Dropping
// `collection` would not break anything visibly. It would just put every author
// on a new page back to being told to save first, on a screen where saving
// means publishing and the gate refuses the publish.
const payload = src.match(/axios\.post\(cp_url\('a11y-gate\/check'\), \{([\s\S]*?)\n {20}\}\)/)[1];

for (const key of ['reference', 'values', 'collection', 'blueprint']) {
    const sent = new RegExp(`^\\s*${key}:`, 'm').test(payload);
    if (!sent) bad++;
    console.log(`${sent ? 'ok  ' : 'FAIL'} sends ${key}`);
}

console.log(bad === 0 ? '\nall passed' : `\n${bad} FAILED`);
process.exit(bad === 0 ? 0 : 1);
