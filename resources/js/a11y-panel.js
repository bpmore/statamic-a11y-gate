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
        // Statamic hands every fieldtype component `id`, `config`, `value`,
        // `meta`, `handle` and a few more, read out of the control panel's own
        // bundle rather than assumed. `config` is the field's config from the
        // blueprint, and the listener that places this field stamps the
        // collection and blueprint handles into it. That is what lets a page be
        // checked before its first save: the create screen sends no entry
        // reference, so without those two the panel has nothing to name.
        props: ['config'],

        setup(props) {
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
                        // Undefined on the create screen, where the publish
                        // container has no reference to give. The endpoint reads
                        // that as "build the entry" rather than as an error.
                        reference: container.reference?.value,
                        values: container.values.value,

                        // Only used when there is no reference. Sent every time
                        // anyway, because a request whose shape changes with the
                        // screen is a second thing to keep in step.
                        collection: props.config?.collection,
                        blueprint: props.config?.blueprint,
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

            // What the gate said when it refused a save, drawn where the author
            // is already looking.
            //
            // Statamic's save pipeline does two things with a 422: it puts the
            // response's `errors` straight onto the publish container, keyed
            // exactly as the endpoint sent them, and it raises `message` as a
            // toast. Both were read in `ui-C4XItfuR.js` rather than assumed.
            //
            // The toast is no use to us. It appears bottom-left, far from the
            // form, and its words are not ours to choose:
            // `Statamic\Exceptions\ValidationException::summarize()` overrides
            // Laravel's and returns "The given data was invalid." for every
            // failure in the control panel, so the refusal's own sentences are
            // thrown away before they reach it. An author who has just been
            // stopped from publishing is told the data was invalid, in the
            // corner, with no idea what to fix.
            //
            // The errors are ours though, under the key the listener sets. So
            // the refusal is drawn in this panel, beside the button that would
            // have told them the same thing had they pressed it first.
            const refusal = Vue.computed(() => {
                const errors = container.errors?.value ?? {};

                return errors.a11y_gate ?? [];
            });

            // A fresh check is newer information than the save that was refused,
            // and leaving a refusal on screen after the author has fixed the page
            // and checked it again would be this panel lying about the present.
            const showRefusal = Vue.computed(
                () => refusal.value.length > 0 && ! state.result && ! state.failed && ! state.checking
            );

            return { state, refusal, showRefusal, check };
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
        // Anything that stopped the checks from running is drawn as `ui-alert`,
        // and it took three goes to get there. It was a bare `ui-description`,
        // which is the same small grey line as the idle hint sitting in the same
        // place: pressing the button swapped one grey sentence for another, and
        // it was reported on a live site as the button doing nothing at all,
        // with the request confirmed at 422 and the right sentence in the body.
        // Then it was a badge above that line, which is a small word in a small
        // pill and was missed the same way.
        //
        // `ui-alert` colours the message itself rather than putting a marker
        // near it, which is what was actually asked for. It also carries `role`
        // and `aria-live`, so the result is announced rather than only shown.
        // An accessibility addon whose own result is available to some readers
        // and not others would be an embarrassment, and this is Statamic's
        // component doing it rather than us.
        //
        // Whether the check did not run because the gate could not render the
        // page or because the request never got an answer is a distinction for
        // whoever maintains this. To the author both mean the same thing and both
        // are `error`: nothing is known about this page.
        //
        // Except an entry nobody has saved yet, which is `warning`. Error is for
        // something being wrong, and nothing is wrong: they have not pressed save.
        // Shouting there would be this addon overstating what it knows, on a
        // screen whose entire job is not doing that.
        //
        // A clean result keeps its quiet green badge. Nothing to fix does not
        // need the eye dragged to it, and an alert for good news would train
        // somebody to ignore the ones that matter.
        template: `
            <div class="space-y-3">
                <ui-alert
                    v-if="showRefusal"
                    variant="error"
                    :heading="refusal[0]"
                    :text="refusal.slice(1).join(' ')"
                />

                <div>
                    <ui-button
                        size="sm"
                        :text="state.checking ? 'Checking' : 'Check this page'"
                        :disabled="state.checking"
                        @click="check"
                    />
                </div>

                <ui-skeleton v-if="state.checking" class="h-16 w-full" />

                <ui-alert
                    v-else-if="state.failed"
                    :variant="state.notYet ? 'warning' : 'error'"
                    :text="state.failed"
                />

                <template v-else-if="state.result">
                    <ui-alert
                        v-if="state.result.outcome === 'could-not-check'"
                        variant="error"
                        :text="'The checks could not run: ' + state.result.reason + '. Nothing is known about this page either way.'"
                    />

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
