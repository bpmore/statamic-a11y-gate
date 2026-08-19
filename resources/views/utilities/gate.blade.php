{{--
    What this addon checks and what it cannot, in one place.

    It lives here rather than under every result because a sentence repeated on
    every save is a sentence nobody reads. Somebody who wants to know what the
    tool is worth comes looking once; the person writing a blog post should be
    told what to fix and left alone.

    **This markup is compiled as a Vue template, not printed as HTML.** A utility
    view is handed to `DynamicHtmlRenderer`, which does
    `defineComponent({ template: html })`, so Statamic's own components resolve
    here exactly as they do in the control panel's own pages. Read out of the
    unminified build rather than guessed, and it is why this page uses
    `ui-header` and `ui-card-panel` instead of a div with borrowed Tailwind
    classes. The first version did the latter and looked like a document dropped
    on the page beside Statamic's other utilities, which is the giveaway of an
    addon that reached for its own styles.

    Two consequences worth knowing before editing this file. Blade's `{{ }}` and
    Vue's `{{ }}` are the same characters, so neither is used: every word here is
    static text. And an unclosed tag is a Vue compile error at runtime rather
    than a Blade one, so it shows up as a blank page instead of an exception.
--}}

<ui-header title="Accessibility Gate" icon="clipboard-check" />

<div class="space-y-6">

    <ui-card-panel heading="What happens when you press publish">
        <div class="space-y-3">
            <p>
                The page is built exactly as a visitor would see it, and read for the
                problems listed below. If anything on that list is wrong, the entry
                is not saved and you are told what to fix.
            </p>
            <p>
                That is what a new site does. A site can set this to report instead,
                under Addons, Accessibility Gate, Settings, and then the same findings
                go to the log and the save goes through. Reporting is meant for the
                first few weeks on a site with a backlog, not as somewhere to stay.
            </p>
            <p>
                Anything that would fail WCAG 2.2 AA stops the publish. There are
                four exceptions, and each one is an exception for a reason rather
                than a softening.
            </p>
            <ul class="list-disc space-y-2 ps-5">
                <li>
                    <strong>A link that goes nowhere.</strong> Broken, and no
                    accessibility standard covers it, and a page half-written usually
                    has one. You are told; you are not stopped.
                </li>
                <li>
                    <strong>A link to a page that is not published yet.</strong> If
                    two pages link to each other, blocking both means neither can
                    ever go live. Somebody staging a launch would be stuck with no
                    way out.
                </li>
                <li>
                    <strong>Link text that is vague rather than plainly wrong.</strong>
                    "Learn more about us" is built entirely out of words that could
                    describe anything, while "Learn more about the weir" is fine. That
                    verdict rests on a list of words rather than on the page, so it is
                    worth saying and not worth stopping you over.
                </li>
                <li>
                    <strong>A plain-language summary that reads too hard.</strong>
                    The score counts sentence length and syllables. It cannot tell
                    jargon made of short words from plain English, so blocking on it
                    would be blocking on a guess.
                </li>
            </ul>
        </div>
    </ui-card-panel>

    <ui-card-panel heading="What it checks on every page">
        <ul class="list-disc space-y-2 ps-5">
            <li>
                <strong>Headings.</strong> That the page has one main heading and
                that the headings below it do not skip a level. Screen reader users
                move through a page by its headings.
            </li>
            <li>
                <strong>Link and button text.</strong> That every link and button
                says where it goes or what it does. "Click here" and a bare web
                address do not.
            </li>
            <li>
                <strong>Image descriptions.</strong> That every image has one, or is
                marked as decoration.
            </li>
            <li>
                <strong>Embedded frames.</strong> That each one has a title. Videos
                are the common case, but a map, a form or anything else in a frame
                is checked the same way.
            </li>
            <li>
                <strong>Control size.</strong> That nothing you can tap is smaller
                than 24 by 24 pixels, where the size is set on the page itself. A
                developer can raise that to 44 in the addon's config file.
            </li>
        </ul>
    </ui-card-panel>

    <ui-card-panel heading="What it cannot check, and why">
        <div class="space-y-3">
            <p>
                This reads the finished page. That is enough for the list above and
                not enough for everything, and the gaps are worth knowing rather than
                guessing at.
            </p>
            <ul class="list-disc space-y-2 ps-5">
                <li>
                    <strong>Anything your theme decides.</strong> Sizes and colours
                    set in the site's stylesheet are invisible here. Colour contrast
                    is the big one: it is not checked at all.
                </li>
                <li>
                    <strong>Whether a description is any good.</strong> It can tell
                    that an image has a description. It cannot tell whether the
                    description matches the picture.
                </li>
                <li>
                    <strong>Whether a video has captions.</strong> The captions live
                    with the video, not on your page. When a page has a video, you
                    are asked to confirm it, because only a person can.
                </li>
                <li>
                    <strong>Anything that needs a keyboard or a real browser.</strong>
                    Focus order, whether a menu can be opened without a mouse,
                    whether something moves when it should not.
                </li>
            </ul>
        </div>
    </ui-card-panel>

    <ui-card-panel heading="Where this panel appears">
        <div class="space-y-3">
            <p>
                It is a field, so it shows up wherever somebody has added it to
                an entry form. There is also a setting that adds it to every
                checked collection for you, under Addons, Accessibility Gate,
                Settings.
            </p>
            <p>
                A collection with no public pages never gets it, because there
                would be nothing for it to check.
            </p>
        </div>
    </ui-card-panel>

    <ui-card-panel heading="Two checks your developer can switch on">
        <div class="space-y-3">
            <p>
                Both need the site's templates to mark something up, because
                neither leaves a trace in the finished page. They are listed in
                the addon's settings, under Addons, Accessibility Gate.
            </p>
            <ul class="list-disc space-y-2 ps-5">
                <li>Links pointing at a page that has not been published yet.</li>
                <li>The reading grade of a plain-language summary.</li>
            </ul>
        </div>
    </ui-card-panel>

    <ui-card-panel heading="What a clean result means">
        <div class="space-y-3">
            <p>
                It means nothing on the list above was found wrong with this page. A
                page it finds nothing wrong with has not been proven accessible, and
                nothing this addon produces is a statement of conformance with any
                standard.
            </p>
            <p>
                A person still has to use the page. A gate that refuses the obvious
                problems is worth having, and it is not the same thing as an audit.
            </p>
        </div>
    </ui-card-panel>

</div>
