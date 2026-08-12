<?php

declare(strict_types=1);

use Bpmore\A11yGate\Tests\TestCase;

// Only the gate's tests boot Statamic. The checker takes HTML and returns
// findings, so its tests need no framework, and a suite that boots nothing for
// them cannot grow a dependency on something booted.
uses(TestCase::class)->in('Feature');
