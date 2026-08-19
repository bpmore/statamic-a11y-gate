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
 * `errors` off a 422, puts `errors` on the publish container and raises
 * `message` as a red toast. Read out of the compiled control-panel JavaScript
 * that ships in the vendor package, which an earlier decision entry wrongly
 * recorded as absent.
 *
 * **The toast never says any of this, and cannot.** An earlier version of this
 * comment claimed Laravel builds `message` from the first validation error, so
 * the summary reached the toast. It does not.
 * `Statamic\Exceptions\ValidationException::summarize()` overrides Laravel's and
 * returns "The given data was invalid." for every validation failure in the
 * control panel, so every sentence below is thrown away before it gets there.
 * Watched happening on a live site, then found in vendor source.
 *
 * What the author reads is the panel, which draws `errors.a11y_gate` in full.
 * The lines below are therefore written to be read as a list rather than as one
 * useful summary, and the first is still written to stand alone in case
 * something else ever surfaces it.
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
            //
            // Both halves out loud: what the gate would have done, and what
            // actually happened to the entry. This line used to say the second
            // half wrong, claiming the entry was not saved immediately before
            // saving it.
            $this->log->warning(
                'Accessibility Gate would have refused this entry. It was saved because the mode is set to report. '
                .$this->problem($result),
                [
                    'entry' => $event->entry->id(),
                    'findings' => array_map(fn ($v) => $v->toArray(), $result->violations),
                    'reason' => $result->reason,
                ]
            );

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

        $lines = ['This entry was not saved. '.$this->problem($result)];

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

    /**
     * What the gate found, and nothing about what was done with it.
     *
     * The verdict belongs to the caller, because only the caller knows the
     * mode. This used to return the verdict too, and the log line in warn mode
     * read "Accessibility Gate would have refused this entry: This entry was
     * not saved." about an entry that is saved on the next line of execution.
     * It contradicted its own first clause, and warn mode's log line is warn
     * mode's entire output.
     *
     * Sentence-cased in both branches so either caller can put a full stop in
     * front of it.
     */
    private function problem(GateResult $result): string
    {
        if ($result->couldNotBeChecked()) {
            return 'The checks could not run: '.$result->reason;
        }

        $count = count($result->errors());

        return $count === 1
            ? 'One accessibility problem has to be fixed first.'
            : "{$count} accessibility problems have to be fixed first.";
    }
}
