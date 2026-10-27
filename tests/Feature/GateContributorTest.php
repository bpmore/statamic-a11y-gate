<?php

declare(strict_types=1);

use Bpmore\A11yGate\Contracts\ContributedResult;
use Bpmore\A11yGate\Contracts\GateContributor;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;

/**
 * Other addons' checks in the same publish: one refusal, every list in it,
 * each under its own key.
 */
beforeEach(function () {
    $this->withStandardFakeViews();

    Collection::make('pages')->routes('/{slug}')->save();
});

final class FakeContributor implements GateContributor
{
    public static ?Closure $answer = null;

    public function key(): string
    {
        return 'house_style';
    }

    public function label(): string
    {
        return 'House Style';
    }

    public function inspect(EntryContract $entry): ContributedResult
    {
        return (self::$answer)($entry);
    }
}

function contributes(Closure $answer): void
{
    FakeContributor::$answer = $answer;
    app()->tag(FakeContributor::class, GateContributor::TAG);
}

function contributedPage(string $body): EntryContract
{
    test()->viewShouldReturnRaw('default', $body);

    return Entry::make()->collection('pages')->slug('weir')->published(true)->data(['title' => 'The weir']);
}

function refusedWith(EntryContract $entry): ?array
{
    try {
        $entry->save();
    } catch (ValidationException $e) {
        return $e->errors();
    }

    return null;
}

const CLEAN = '<html lang="en"><body><h1>The weir</h1><p>Words.</p></body></html>';
const BROKEN = '<html lang="en"><body><h1>The weir</h1><img src="/a.jpg"></body></html>';

it('refuses once with both lists when both find a problem', function () {
    contributes(fn () => ContributedResult::blocks(['Spell our name our way.']));

    $errors = refusedWith(contributedPage(BROKEN));

    expect(array_keys($errors))->toBe(['a11y_gate', 'house_style'])
        ->and($errors['house_style'])->toBe(['Spell our name our way.'])
        ->and($errors['a11y_gate'][0])->toBe('This entry was not saved. One accessibility problem has to be fixed first.');
});

it('refuses for a contributor alone when the page itself is fine', function () {
    contributes(fn () => ContributedResult::blocks(['Spell our name our way.']));

    expect(refusedWith(contributedPage(CLEAN)))->toBe(['house_style' => ['Spell our name our way.']]);
});

it('saves when every contributor lets it through', function () {
    contributes(fn () => ContributedResult::passes());

    expect(refusedWith(contributedPage(CLEAN)))->toBeNull();
});

it('logs a contributor that throws and leaves it out, without stopping the save', function () {
    contributes(fn () => throw new RuntimeException('its config is broken'));
    Log::shouldReceive('error')->once()->withArgs(fn (string $message) => str_contains($message, 'its config is broken'));

    expect(refusedWith(contributedPage(CLEAN)))->toBeNull();
});

it('keeps a blocking contributor blocking in warn mode, which is about its own checks only', function () {
    config()->set('statamic-a11y-gate.mode', 'warn');
    app()->forgetInstance(Bpmore\A11yGate\Gate\GateSettings::class);
    contributes(fn () => ContributedResult::blocks(['Spell our name our way.']));

    expect(refusedWith(contributedPage(BROKEN)))->toBe(['house_style' => ['Spell our name our way.']]);
});

it('runs exactly as before with nobody signed up', function () {
    expect(array_keys(refusedWith(contributedPage(BROKEN))))->toBe(['a11y_gate'])
        ->and(refusedWith(contributedPage(CLEAN)))->toBeNull();
});
