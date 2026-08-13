<?php

declare(strict_types=1);

return [

    /*
    |---------------------------------------------------------------------------
    | Mode
    |---------------------------------------------------------------------------
    |
    | 'refuse' stops the save. 'warn' lets it through and writes the findings to
    | the log instead.
    |
    | Warn mode is a migration path, not a setting to leave on. A team adopting
    | this mid-project may have a hundred failing entries on day one, and a gate
    | that blocks all of them on install gets uninstalled by lunchtime. Turn it
    | off once the backlog is clear: an addon in warn mode is a checker, and
    | there are free ones.
    |
    */

    'mode' => env('A11Y_GATE_MODE', 'refuse'),

    /*
    |---------------------------------------------------------------------------
    | Collections
    |---------------------------------------------------------------------------
    |
    | Handles of the collections to gate. Empty means every collection.
    |
    | Narrow this and the entries you leave out are not checked at all. That is a
    | legitimate choice for a collection with no public pages, and a silent hole
    | for anything else.
    |
    */

    'collections' => [],

    /*
    |---------------------------------------------------------------------------
    | Add the panel to blueprints
    |---------------------------------------------------------------------------
    |
    | The Accessibility panel appears in the sidebar of every gated collection
    | that has pages, without editing a single blueprint file.
    |
    | It uses the same mechanism Statamic uses for `slug` and `date`, which are
    | not in your yaml either. The field behaves like a native one and it
    | disappears cleanly if this addon is removed, leaving nothing behind for
    | somebody to delete.
    |
    | On by default, and it was off until somebody pointed out what that meant.
    | The gate refuses a publish on every collection from the moment this addon
    | is installed. With no panel, the only thing an author sees is Statamic's
    | own "The given data was invalid" in the corner of the screen: stopped, with
    | nowhere to look. Findings do come back with the refusal, and the panel is
    | the only thing that draws them.
    |
    | Turning it off is still supported and still reasonable, for a site that
    | places the field by hand or does not want it on every form. Just know that
    | a refusal is then unreadable unless the field is somewhere.
    |
    | Adding the field to a blueprint yourself still works and takes precedence:
    | your placement is kept and you do not get a second copy.
    |
    */

    'add_panel_to_blueprints' => true,

    /*
    |---------------------------------------------------------------------------
    | Opt-in checks
    |---------------------------------------------------------------------------
    |
    | Two checks cannot work on rendered HTML alone, because what they look for
    | leaves no trace in the finished page. They are off unless your templates
    | stamp the attribute they read, and they are listed here rather than
    | repeated on every entry, which is the only place a standing "this was not
    | checked" notice would ever be read.
    |
    | 'a11y.link.unpublished'   reads data-a11y-unpublished-link="true"
    |                           on anything wrapping a link to a page that is
    |                           not live yet. A link to a draft looks like any
    |                           other link once the page is built.
    |
    | 'a11y.text.reading_level' reads data-a11y-reading-grade="9.4" on a
    |                           plain-language summary. On the finished page a
    |                           summary is just more text, and nothing marks out
    |                           which words were meant to be the plain ones.
    |
    | Add a key here once your templates stamp it. Findings arrive either way:
    | a page that carries the markup is checked whether or not this list mentions
    | it. What this controls is whether an entry that carries none of it is told
    | so.
    |
    */

    'opt_in_checks' => [],

    /*
    |---------------------------------------------------------------------------
    | Standard
    |---------------------------------------------------------------------------
    |
    | 'wcag22aa' or 'wcag22aaa'. One rule reads it: the minimum touch target,
    | which is 24 CSS pixels at AA and 44 at AAA.
    |
    | Setting AAA does not make this addon check AAA. Most AAA criteria are
    | judged on meaning by a person, and nothing here can do that.
    |
    */

    'standard' => 'wcag22aa',

];
