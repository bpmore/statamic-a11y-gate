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
            });

            async function check() {
                state.checking = true;
                state.failed = null;

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
                    state.failed = e.response?.data?.message ?? 'The check could not be run.';
                    state.result = null;
                } finally {
                    state.checking = false;
                }
            }

            return { state, check };
        },

        // Checked on a button rather than on every keystroke. A check renders the
        // whole page through the site's templates, and firing that on each
        // character typed would make the editor feel broken and hammer the site.
        template: `
            <ui-panel>
                <ui-panel-header>
                    <ui-heading text="Accessibility" />
                    <ui-button
                        size="sm"
                        variant="ghost"
                        :text="state.checking ? 'Checking' : 'Check this page'"
                        :disabled="state.checking"
                        @click="check"
                    />
                </ui-panel-header>

                <div class="p-4 space-y-4">
                    <ui-skeleton v-if="state.checking" class="h-16 w-full" />

                    <ui-description v-else-if="state.failed" :text="state.failed" />

                    <template v-else-if="state.result">
                        <template v-if="state.result.outcome === 'could-not-check'">
                            <ui-badge color="red" text="The checks could not run" />
                            <ui-description :text="state.result.reason + '. Nothing is known about this page either way.'" />
                        </template>

                        <ui-description
                            v-else-if="state.result.outcome === 'not-applicable'"
                            :text="'Not checked: ' + state.result.reason + '.'"
                        />

                        <template v-else>
                            <div v-if="state.result.errors.length">
                                <ui-badge color="red" :text="state.result.errors.length + ' to fix before publishing'" />
                                <ul class="mt-3 space-y-3">
                                    <li v-for="(finding, i) in state.result.errors" :key="'e' + i">
                                        <ui-heading size="sm" :text="finding.cta" />
                                        <ui-description :text="finding.message" />
                                        <ui-description v-if="finding.pointer" :text="finding.pointer" />
                                    </li>
                                </ul>
                            </div>

                            <ui-badge v-else color="emerald" text="Nothing found that would stop a publish" />

                            <div v-if="state.result.warnings.length">
                                <ui-badge color="amber" :text="state.result.warnings.length + ' worth a look'" />
                                <ul class="mt-3 space-y-3">
                                    <li v-for="(finding, i) in state.result.warnings" :key="'w' + i">
                                        <ui-heading size="sm" :text="finding.cta" />
                                        <ui-description :text="finding.message" />
                                    </li>
                                </ul>
                            </div>
                        </template>

                        <ui-description :text="state.result.limits" />
                    </template>

                    <ui-description
                        v-else
                        text="This checks the page as it stands in this form, including changes you have not saved."
                    />
                </div>
            </ui-panel>
        `,
    });
})();
