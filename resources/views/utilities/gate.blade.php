{{--
    What this addon checks and what it cannot, in one place.

    It lives here rather than under every result because a sentence repeated on
    every save is a sentence nobody reads. Somebody who wants to know what the
    tool is worth comes looking once; the person writing a blog post should be
    told what to fix and left alone.

    Plain HTML and Statamic's own type classes. No widget is invented here: it is
    a page of prose, and prose is the one thing that does not need a component.
--}}

<div class="max-w-3xl space-y-8 text-gray-800 dark:text-gray-200">

    <section class="space-y-3">
        <h2 class="text-lg font-medium">What happens when you press publish</h2>
        <p>
            The page is built exactly as a visitor would see it, and read for the
            problems listed below. If anything on that list is wrong, the entry is
            not saved and you are told what to fix.
        </p>
        <p>
            Warnings do not stop a publish. They are worth a look and they are not
            worth blocking your work over.
        </p>
    </section>

    <section class="space-y-3">
        <h2 class="text-lg font-medium">What it checks on every page</h2>
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
                <strong>Embedded videos.</strong> That each one has a title.
            </li>
            <li>
                <strong>Control size.</strong> That nothing you can tap is smaller
                than 24 by 24 pixels, where the size is set on the page itself.
            </li>
        </ul>
    </section>

    <section class="space-y-3">
        <h2 class="text-lg font-medium">What it cannot check, and why</h2>
        <p>
            This reads the finished page. That is enough for the list above and not
            enough for everything, and the gaps are worth knowing rather than
            guessing at.
        </p>
        <ul class="list-disc space-y-2 ps-5">
            <li>
                <strong>Anything your theme decides.</strong> Sizes and colours set
                in the site's stylesheet are invisible here. Colour contrast is the
                big one: it is not checked at all.
            </li>
            <li>
                <strong>Whether a description is any good.</strong> It can tell that
                an image has a description. It cannot tell whether the description
                matches the picture.
            </li>
            <li>
                <strong>Whether a video has captions.</strong> The captions live
                with the video, not on your page. When a page has a video, you are
                asked to confirm it, because only a person can.
            </li>
            <li>
                <strong>Anything that needs a keyboard or a real browser.</strong>
                Focus order, whether a menu can be opened without a mouse, whether
                something moves when it should not.
            </li>
        </ul>
    </section>

    <section class="space-y-3">
        <h2 class="text-lg font-medium">Two checks your developer can switch on</h2>
        <p>
            Both need the site's templates to mark something up, because neither
            leaves a trace in the finished page. They are listed in
            <code>config/a11y-gate.php</code>.
        </p>
        <ul class="list-disc space-y-2 ps-5">
            <li>Links pointing at a page that has not been published yet.</li>
            <li>The reading grade of a plain-language summary.</li>
        </ul>
    </section>

    <section class="space-y-3">
        <h2 class="text-lg font-medium">What a clean result means</h2>
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
    </section>

</div>
