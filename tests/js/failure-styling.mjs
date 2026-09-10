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

// The refusal the gate raised when it stopped a save. Statamic's save pipeline
// puts a 422's `errors` on the publish container under the key the endpoint
// sent, and raises `message` as a toast whose words Statamic hard-codes to "The
// given data was invalid". So the container is the only place the refusal's own
// sentences survive, and reading the wrong key there fails silently: no error,
// no warning, an author simply never told why they were stopped.
const readsRefusal = /errors\.a11y_gate/.test(src);
if (!readsRefusal) bad++;
console.log(`${readsRefusal ? 'ok  ' : 'FAIL'} reads errors.a11y_gate off the publish container`);

// Whichever answer is newest is the one on screen, and it has to run both ways.
// A refusal left up after the page was fixed describes a past that is no longer
// true. A refusal hidden behind an older check result is worse: the save really
// was just refused, and the panel says nothing about it.
const supersededByAFreshCheck = /showRefusal[\s\S]*?state\.result[\s\S]*?state\.failed/.test(src);
if (!supersededByAFreshCheck) bad++;
console.log(`${supersededByAFreshCheck ? 'ok  ' : 'FAIL'} a fresh check supersedes an older refusal`);

const supersededByAFreshRefusal = /Vue\.watch\(refusal[\s\S]*?state\.result = null/.test(src);
if (!supersededByAFreshRefusal) bad++;
console.log(`${supersededByAFreshRefusal ? 'ok  ' : 'FAIL'} a fresh refusal supersedes an older check`);

// The link under a finding to the W3C's page on the criterion it cites. Once
// for errors and once for warnings, and each must be told apart from text:
// underlined, coloured for both themes, keyboard-focusable with a visible
// ring, and announcing the new tab. Checked by reading the source because a
// link that quietly loses its underline is exactly what no PHP test would see.
const links = src.match(/<a v-if="finding\.reference"[^>]*>[\s\S]*?<\/a>/g) ?? [];
const linkCount = links.length === 2;
if (!linkCount) bad++;
console.log(`${linkCount ? 'ok  ' : 'FAIL'} a criterion link under errors and under warnings (found ${links.length})`);

for (const [what, re] of [
    ['opens the reference url', /:href="finding\.reference\.url"/],
    ['is underlined', /class="[^"]*\bunderline\b/],
    ['has a colour for each theme', /text-blue-700 dark:text-blue-300/],
    ['shows a focus ring', /focus:focus-outline/],
    ['announces the new tab', /<span class="sr-only">[^<]*opens in a new tab[^<]*<\/span>/],
    ['names the criterion', /WCAG \{\{ finding\.reference\.number \}\} \{\{ finding\.reference\.name \}\}/],
    ['does not hand the opener to w3.org', /rel="noopener"/],
]) {
    const ok = links.length > 0 && links.every((a) => re.test(a));
    if (!ok) bad++;
    console.log(`${ok ? 'ok  ' : 'FAIL'} every criterion link ${what}`);
}

// Every image in the panel has words that stand in for it. This addon refuses
// exactly this fault on the pages it checks, and its own panel is the last
// place that should carry one. Checked over the whole file rather than over
// the one image there is today, so the next one has to answer for itself too.
const images = src.match(/<img\b[^>]*>/g) ?? [];
for (const img of images) {
    const described = /\s:?alt="[^"]+"/.test(img);
    if (!described) bad++;
    console.log(`${described ? 'ok  ' : 'FAIL'} an image in the panel has words that stand in for it: ${img.slice(0, 60)}`);
}

// The mark is a picture somebody else supplied, so it is drawn only where the
// gate is not reporting a problem of its own. An alert is the gate's own
// voice, in the control panel's colours, and nobody puts their name on one.
const markInAlert = /<ui-alert[^>]*ext\.mark/.test(src);
if (markInAlert) bad++;
console.log(`${markInAlert ? 'FAIL' : 'ok  '} no mark is drawn on a block reporting a problem`);

// The all-clear badge has to say what it was an all-clear about. A11y Docs
// registers a block that renders directly under it, and that block can be
// reporting a PDF a screen reader cannot open while this gate, which only ever
// reads the rendered HTML, found nothing. Green "Nothing to fix" above red
// "1 with problems" is two verdicts with nothing to tell them apart, and it
// shipped that way.
const clearBadge = src.match(/<ui-badge color="emerald" text="([^"]*)"/);
const scoped = clearBadge !== null && /\bpage\b/.test(clearBadge[1]) && clearBadge[1] !== 'Nothing to fix';
if (!scoped) bad++;
console.log(`${scoped ? 'ok  ' : 'FAIL'} the all-clear badge names what it checked: ${clearBadge ? clearBadge[1] : 'no badge found'}`);

console.log(bad === 0 ? '\nall passed' : `\n${bad} FAILED`);
process.exit(bad === 0 ? 0 : 1);
