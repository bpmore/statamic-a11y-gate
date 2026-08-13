<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

/**
 * The panel's own logic, run rather than read.
 *
 * This exists because the panel went quiet three times in one day and no test in
 * this suite could have caught any of them. First it drew nothing at all when a
 * check failed, because an empty message defaulted with `??` stayed empty and the
 * template tested it for truth. Then it drew the failure as the same small grey
 * line as the idle hint, in the same place, and was reported on a live site as
 * the button doing nothing while the request was returning 422 with the right
 * sentence in the body. Then it put a small word in a small pill above that line,
 * and that was missed too.
 *
 * The endpoint had tests throughout. The panel is the half an author actually
 * looks at, and it had none.
 *
 * A whole JavaScript toolchain for one file would be worse than the problem: the
 * addon has no npm, no build step and no bundle on purpose, and the reasoning is
 * in the decision log. So the part that is pure logic, which of the alert
 * variants a failure gets, is exercised by a plain node script that pulls the
 * expressions out of the panel source and runs them against fabricated errors.
 *
 * It asserts nothing about how anything looks, and that limit is the reason the
 * defect above shipped three times: no test here can tell you a thing is too
 * quiet to notice. What it can tell you is that "never saved" and "something went
 * wrong" cannot quietly collapse into the same treatment.
 */
it('does not let an unsaved entry and a real failure look the same', function () {
    $node = (new Process(['which', 'node']))->run() === 0
        ? trim((new Process(['which', 'node']))->mustRun()->getOutput())
        : null;

    if ($node === null) {
        $this->markTestSkipped('node is not on this machine, so the panel check cannot run');
    }

    $process = new Process([$node, __DIR__.'/../js/failure-styling.mjs']);
    $process->run();

    // The script's own output, not a summary of it. A failure here should read
    // as the failing case rather than as "exit code 1".
    expect($process->isSuccessful())->toBeTrue($process->getOutput().$process->getErrorOutput());
});
