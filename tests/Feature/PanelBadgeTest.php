<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

/**
 * The panel's own logic, run rather than read.
 *
 * This exists because the panel went quiet twice in one day and no test in this
 * suite could have caught either. First it drew nothing at all when a check
 * failed, because an empty message defaulted with `??` stayed empty and the
 * template tested it for truth. Then it drew the failure as the same small grey
 * line as the idle hint, in the same place, and was reported on a live site as
 * the button doing nothing while the request was returning 422 with the right
 * sentence in the body.
 *
 * The endpoint had tests throughout. The panel is the half an author actually
 * looks at, and it had none.
 *
 * A whole JavaScript toolchain for one file would be worse than the problem: the
 * addon has no npm, no build step and no bundle on purpose, and the reasoning is
 * in the decision log. So the badge choice, which is pure logic, is exercised by
 * a plain node script that pulls the expressions out of the panel source and
 * runs them against fabricated errors. It asserts nothing about how anything
 * looks. It asserts that "never saved" and "something went wrong" cannot end up
 * wearing the same badge without somebody noticing.
 */
it('gives an unsaved entry a different badge from a real failure', function () {
    $node = (new Process(['which', 'node']))->run() === 0
        ? trim((new Process(['which', 'node']))->mustRun()->getOutput())
        : null;

    if ($node === null) {
        $this->markTestSkipped('node is not on this machine, so the panel check cannot run');
    }

    $process = new Process([$node, __DIR__.'/../js/badge-choice.mjs']);
    $process->run();

    // The script's own output, not a summary of it. A failure here should read
    // as the failing case rather than as "exit code 1".
    expect($process->isSuccessful())->toBeTrue($process->getOutput().$process->getErrorOutput());
});
