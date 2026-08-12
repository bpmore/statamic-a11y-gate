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
    | Opt-in checks
    |---------------------------------------------------------------------------
    |
    | Two checks cannot work on rendered HTML alone, because what they look for
    | leaves no trace in the finished page. They are off unless your templates
    | stamp the attribute they read, and they are listed here rather than
    | repeated on every entry, which is the only place a standing "this was not
    | checked" notice would ever be read.
    |
    | 'a11y.link.unpublished'   reads data-windrow-unpublished-link="true"
    |                           on anything wrapping a link to a page that is
    |                           not live yet. A link to a draft looks like any
    |                           other link once the page is built.
    |
    | 'a11y.text.reading_level' reads data-windrow-reading-grade="9.4" on a
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
