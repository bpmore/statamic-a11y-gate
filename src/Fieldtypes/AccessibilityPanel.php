<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Fieldtypes;

use Statamic\Fields\Fieldtype;

/**
 * The panel, as a field an author drags into a blueprint.
 *
 * A fieldtype rather than something injected into the publish form, because it
 * is the extension point Statamic offers inside the entry screen. Where the
 * panel appears is then the site's decision rather than this addon's, and a
 * sidebar section is the obvious home.
 *
 * It stores nothing. `process()` and `preProcess()` return null so a blueprint
 * carrying this field never writes a key into the entry's file: findings left in
 * the content directory would be a stale copy of an answer that changes every
 * time the page does.
 *
 * The handle is derived from the class name, so this is `accessibility_panel`
 * and its Vue component is `accessibility_panel-fieldtype`. Renaming the class
 * renames the field and breaks every blueprint using it.
 */
class AccessibilityPanel extends Fieldtype
{
    protected $icon = 'shield';

    protected static $title = 'Accessibility';

    public function preProcess($data)
    {
        return null;
    }

    public function process($data)
    {
        return null;
    }

    public function augment($value)
    {
        return null;
    }
}
