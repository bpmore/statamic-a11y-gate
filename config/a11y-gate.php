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
