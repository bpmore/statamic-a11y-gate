/**
 * The accessibility panel, as a fieldtype an author drags into a blueprint.
 *
 * A plain script rather than a built bundle, and that is not laziness. Statamic
 * aliases Vue to `vue/dist/vue.esm-bundler.js` and puts it on `window.Vue`, so
 * the runtime template compiler is present and a `template` string works. That
 * removes npm, vite and a build step from an addon whose product is one PHP
 * class and a list of rules. Read out of the control panel's own build rather
 * than assumed: `vendor/statamic/cms/resources/dist-dev` ships unminified, and
 * every API used here was read there.
 *
 * Everything visible is Statamic's own component library. Nothing is
 * hand-rolled, because a bespoke control inside an accessibility product is the
 * worst possible place to invent one, and any defect in `ui-panel` is then
 * Statamic's to fix rather than ours to have shipped.
 */
(function () {
    // Provided by the publish container: `values` is the form as it stands right
    // now, and `reference` is "entry::<id>". Both are refs. Taken from the
    // container rather than parsed out of the page URL, which would break the
    // first time a route changed with no test to catch it.
    const { injectPublishContext } = window.__STATAMIC__.ui;

    Statamic.$components.register('accessibility_panel-fieldtype', {
        setup() {
            const container = injectPublishContext();

            const state = Vue.reactive({
                checking: false,
                result: null,
                failed: null,

                // Whether the check did not run because something went wrong, or
                // because it was never going to run yet. See the badge below.
                notYet: false,
            });

            async function check() {
                state.checking = true;
                state.failed = null;
                state.notYet = false;

                // Statamic's axios instance, which already carries the CSRF
                // token and the headers that make a 422 come back as JSON.
                const axios = Statamic.$app.config.globalProperties.$axios;

                try {
                    const { data } = await axios.post(cp_url('a11y-gate/check'), {
                        reference: container.reference.value,
                        values: container.values.value,
                    });

                    state.result = data;
                } catch (e) {
                    // Said out loud rather than swallowed into an empty result.
                    // A panel that showed nothing after a failed request would
                    // look exactly like a page with nothing wrong with it, which
                    // is the one mistake this whole product refuses to make.
                    //
                    // `||` and not `??`, which is what it was and is why the
                    // panel did exactly that. A bare `abort(404)` answers with
                    // `{"message": ""}`; `??` only steps in for null, so `failed`
                    // became the empty string, and the template below tests it for
                    // truth, so the failure branch never drew. Pressing Check on
                    // an unsaved entry showed a spinner and then the idle text
                    // again. The endpoint now sends a reason for every failure as
                    // well: both halves, because either one alone leaves the other
                    // free to go quiet.
                    //
                    // 422 is the endpoint saying the check was never going to
                    // run, not that anything went wrong: today that means an
                    // entry nobody has saved yet. Nothing is broken and nothing
                    // about the page is known either, so it earns a badge that
                    // says neither. Every other status is a real failure.
                    state.notYet = e.response?.status === 422;
                    state.failed = e.response?.data?.message || 'The check could not be run.';
                    state.result = null;
                } finally {
                    state.checking = false;
                }
            }

            return { state, check };
        },

        // No panel or header of its own. A field is already drawn inside the
        // publish form's own card and given the display name from the blueprint,
        // so wrapping this in `ui-panel` produced two nested cards and the word
        // "Accessibility" twice.
        //
        // Badge text is kept to a few words on purpose: `ui-badge` is
        // `inline-flex whitespace-nowrap`, so a sentence in one does not wrap, it
        // runs out of the sidebar. The sentence belongs in `ui-description`,
        // which does wrap.
        //
        // Every button and badge sits in its own `div`. Both are `inline-flex`,
        // and `space-y` only puts margin between block-level children, so without
        // the wrappers the result badge lands on the same line as the button and
        // reads as part of it.
        //
        // Everything here is written for the person writing the page, and the
        // test for whether a line belongs is whether they could do something
        // about it. "4 of 5 checks ran in full" fails that test, and so does
        // "a size set in a stylesheet needs a real browser": true, worth an
        // auditor knowing, and not theirs to fix from an entry screen. Both are
        // still in the response this panel receives, and neither is drawn.
        //
        // What is drawn is a gap in their own content that only they can settle,
        // which today means one thing: whether the video they embedded has
        // captions.
        //
        // Nothing here explains the tool. That account was a paragraph under
        // every result, then a link under every result, and both were the same
        // answer to the same question nobody asked: an author pressing a button
        // wants to know about their page. The page under Tools says it properly,
        // and somebody who wants it goes and reads it.
        //
        // Checked on a button rather than on every keystroke. A check renders the
        // whole page through the site's templates, and firing that on each
        // character typed would make the editor feel broken and hammer the site.
        //
        // A failed request wears the same red badge as a gate that could not
        // check, and that is not decoration. It was a bare `ui-description`,
        // which is the same small grey line as the idle hint sitting in the same
        // place: pressing the button swapped one grey sentence for another, and
        // it was reported as the button doing nothing at all. Watched happening
        // on a live site with the request confirmed at 422 and the right message
        // in the body, which is the worst version of this bug, because
        // everything was working.
        //
        // Whether the check did not run because the gate could not render the
        // page or because the request never got an answer is a distinction for
        // whoever maintains this. To the author both mean the same thing and must
        // look the same: nothing is known about this page.
        //
        // Red is for something being wrong. An entry nobody has saved yet is not
        // wrong, so it gets amber and "Not checked yet". Red there would be this
        // addon overstating what it knows, on a screen whose entire job is not
        // doing that. Amber is already the warnings colour and the two cannot
        // appear together, because one is a result and the other is the absence
        // of one.
        template: `
            <div class="space-y-3">
                <div>
                    <ui-button
                        size="sm"
                        :text="state.checking ? 'Checking' : 'Check this page'"
                        :disabled="state.checking"
                        @click="check"
                    />
                </div>

                <ui-skeleton v-if="state.checking" class="h-16 w-full" />

                <template v-else-if="state.failed">
                    <div>
                        <ui-badge
                            :color="state.notYet ? 'amber' : 'red'"
                            :text="state.notYet ? 'Not checked yet' : 'Could not check'"
                        />
                    </div>
                    <ui-description :text="state.failed" />
                </template>

                <template v-else-if="state.result">
                    <template v-if="state.result.outcome === 'could-not-check'">
                        <div><ui-badge color="red" text="Could not check" /></div>
                        <ui-description :text="'The checks could not run: ' + state.result.reason + '. Nothing is known about this page either way.'" />
                    </template>

                    <ui-description
                        v-else-if="state.result.outcome === 'not-applicable'"
                        :text="'Not checked: ' + state.result.reason + '.'"
                    />

                    <template v-else>
                        <div v-if="state.result.errors.length" class="space-y-3">
                            <div><ui-badge color="red" :text="state.result.errors.length === 1 ? '1 to fix' : state.result.errors.length + ' to fix'" /></div>
                            <div v-for="(finding, i) in state.result.errors" :key="'e' + i">
                                <ui-heading size="sm" :text="finding.cta" />
                                <ui-description :text="finding.message" />
                                <ui-description v-if="finding.pointer" :text="finding.pointer" />
                            </div>
                        </div>

                        <div v-else><ui-badge color="emerald" text="Nothing to fix" /></div>

                        <div v-if="state.result.warnings.length" class="space-y-3">
                            <div><ui-badge color="amber" :text="state.result.warnings.length === 1 ? '1 to look at' : state.result.warnings.length + ' to look at'" /></div>
                            <div v-for="(finding, i) in state.result.warnings" :key="'w' + i">
                                <ui-heading size="sm" :text="finding.cta" />
                                <ui-description :text="finding.message" />
                            </div>
                        </div>
                    </template>

                    <div v-if="state.result.notices.length" class="space-y-2">
                        <ui-description v-for="(notice, i) in state.result.notices" :key="'n' + i" :text="notice" />
                    </div>

                </template>

                <ui-description
                    v-else
                    text="Checks this page as it stands here, including changes you have not saved."
                />
            </div>
        `,
    });
})();
