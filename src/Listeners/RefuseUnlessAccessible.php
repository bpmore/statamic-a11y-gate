<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Listeners;

use Bpmore\A11yGate\Gate\GateResult;
use Bpmore\A11yGate\Gate\GateSettings;
use Bpmore\A11yGate\Gate\PublishGate;
use Illuminate\Validation\ValidationException;
use Psr\Log\LoggerInterface;
use Statamic\Events\EntrySaving;

/**
 * The gate. Statamic binds this to `EntrySaving` from the type hint on `handle`,
 * so there is no registration to keep in step with the class name.
 *
 * **It throws rather than returning false, and that is the decision this class
 * is really about.** Returning `false` from an `EntrySaving` listener does stop
 * the save: `Entry::save()` returns early, verified by running it. What it does
 * not do is say why. The control panel's save endpoint answers `'saved' => false`
 * with no message attached, and the publish action turns that into a red
 * "Couldn't publish entry" toast. An author would be told the editor is broken
 * rather than told their page has a problem, which is the exact failure this
 * product exists to prevent.
 *
 * A `ValidationException` carries the findings and comes back as a 422, which
 * the control panel already handles: its publish form reads `message` and
 * `errors` off a 422 and raises the message as a red toast. Read out of the
 * compiled control-panel JavaScript that ships in the vendor package, which an
 * earlier decision entry wrongly recorded as absent.
 *
 * **What the author actually sees is one line**, and this is a known limit
 * rather than a finished job. Laravel builds `message` from the first validation
 * error, so the summary reaches the toast and the rest arrive under a key that
 * matches no field in the blueprint, which means nothing renders them yet. The
 * first line is therefore written to be useful alone. The panel that lists every
 * finding is the next piece of work, and until it exists this addon tells an
 * author how many problems there are and shows them one.
 *
 * The cost of throwing is that the refusal is an exception in every context,
 * including a script or an import that saves entries outside the control panel.
 * That is accepted: a gate that fails loudly outside the interface is doing its
 * job, and a silent `false` return in a deploy script is how a broken page
 * reaches production with nobody told.
 */
final class RefuseUnlessAccessible
{
    /**
     * Rendering a page runs the site's templates, and a template can save an
     * entry. Without this, that save would be gated, which would render another
     * page, and the first sign of trouble would be a stack overflow during a
     * publish. Nothing observed doing it: the guard is cheap and the failure it
     * prevents is not.
     */
    private static bool $checking = false;

    public function __construct(
        private readonly PublishGate $gate,
        private readonly GateSettings $settings,
        private readonly LoggerInterface $log,
    ) {}

    public function handle(EntrySaving $event): void
    {
        if (self::$checking) {
            return;
        }

        self::$checking = true;

        try {
            $result = $this->gate->inspect($event->entry);
        } finally {
            self::$checking = false;
        }

        if (! $result->shouldRefuse()) {
            return;
        }

        if (! $this->settings->refuses()) {
            // Warn mode still says what it found. A mode that stayed quiet would
            // be indistinguishable from the addon not being installed.
            $this->log->warning('Accessibility Gate would have refused this entry: '.$this->summary($result), [
                'entry' => $event->entry->id(),
                'findings' => array_map(fn ($v) => $v->toArray(), $result->violations),
                'reason' => $result->reason,
            ]);

            return;
        }

        throw ValidationException::withMessages([
            'a11y_gate' => $this->lines($result),
        ])->status(422);
    }

    /**
     * The refusal, as the author reads it.
     *
     * Every line names the problem in plain language and what to do about it,
     * because the rule id is meaningless to the person who has to fix it. Nothing
     * here says the page is compliant, accessible, or a percentage of either: the
     * gate reports what ran and what it found, and a page it lets through has not
     * been proven clean.
     *
     * @return array<int, string>
     */
    private function lines(GateResult $result): array
    {
        if ($result->couldNotBeChecked()) {
            return [
                'This entry was not saved, because its accessibility checks could not run: '.$result->reason.'.',
                'Nothing is known about this page either way. Fix the page so it renders, then save again.',
            ];
        }

        $lines = [$this->summary($result)];

        foreach ($result->errors() as $violation) {
            $lines[] = $violation->message.' '.$violation->cta.
                ($violation->pointer !== '' ? " ({$violation->pointer})" : '');
        }

        $warnings = count($result->warnings());

        if ($warnings > 0) {
            $lines[] = $warnings === 1
                ? 'There is also 1 warning, which does not stop this save.'
                : "There are also {$warnings} warnings, which do not stop this save.";
        }

        // Gaps in the author's own content, and nothing else. The count of which
        // checks ran is an auditor's number: it is in the panel's data and in the
        // docs, and it was in this message until somebody read it as an author
        // and asked what they were supposed to do with it.
        foreach ($result->notices as $notice) {
            $lines[] = $notice;
        }

        return $lines;
    }

    private function summary(GateResult $result): string
    {
        if ($result->couldNotBeChecked()) {
            return 'the checks could not run: '.$result->reason;
        }

        $count = count($result->errors());

        return $count === 1
            ? 'This entry was not saved. One accessibility problem has to be fixed first.'
            : "This entry was not saved. {$count} accessibility problems have to be fixed first.";
    }
}
