<?php

declare(strict_types=1);

// No base TestCase and no framework boot. The checker takes HTML and returns
// findings, so its tests need neither, and a suite that boots nothing cannot
// grow a dependency on something booted.
