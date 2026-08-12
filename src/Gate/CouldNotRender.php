<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Gate;

use RuntimeException;

/**
 * The entry did not come back as a page.
 *
 * Thrown rather than returned as an empty string, because an empty string is
 * exactly what a clean page would look like to a checker with no rules: zero
 * findings. The gate has to be able to tell "this page has nothing wrong with
 * it" from "there was no page".
 */
class CouldNotRender extends RuntimeException {}
