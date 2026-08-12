<?php

declare(strict_types=1);

use Bpmore\A11yGate\Gate\GateSettings;

it('fails closed when the mode is not one it recognises', function () {
    // A typo in a config file must never be the reason a gate stopped gating.
    // Nothing in the control panel would show that it had, and the site would
    // look protected while publishing anything.
    expect(GateSettings::fromConfig(['mode' => 'warn '])->refuses())->toBeTrue();
    expect(GateSettings::fromConfig(['mode' => 'report'])->refuses())->toBeTrue();
    expect(GateSettings::fromConfig([])->refuses())->toBeTrue();
});

it('warns only when asked exactly', function () {
    expect(GateSettings::fromConfig(['mode' => 'warn'])->refuses())->toBeFalse();
});

it('gates every collection when none are named', function () {
    $settings = GateSettings::fromConfig(['collections' => []]);

    expect($settings->gates('pages'))->toBeTrue();
    expect($settings->gates('articles'))->toBeTrue();
});

it('gates only the collections named', function () {
    $settings = GateSettings::fromConfig(['collections' => ['pages']]);

    expect($settings->gates('pages'))->toBeTrue();
    expect($settings->gates('articles'))->toBeFalse();
});

it('carries the opt-in checks the site has integrated', function () {
    // Without this the setting is a config key nothing reads, and the two
    // opt-in checks stay silent on a site that went to the trouble of stamping
    // for them. Found by mutation: emptying this list broke no test.
    expect(GateSettings::fromConfig(['opt_in_checks' => ['a11y.text.reading_level']])->optedIn)
        ->toBe(['a11y.text.reading_level']);

    expect(GateSettings::fromConfig([])->optedIn)->toBe([]);
});

it('reads the target size from the standard, and nothing else from it', function () {
    // The one setting that changes a finding. AAA raises the minimum touch
    // target to 44 pixels; it does not make this addon check AAA, and the label
    // has to keep saying so.
    expect(GateSettings::fromConfig(['standard' => 'wcag22aaa'])->standard->targetSize)->toBe(44);
    expect(GateSettings::fromConfig(['standard' => 'wcag22aa'])->standard->targetSize)->toBe(24);
    expect(GateSettings::fromConfig(['standard' => 'nonsense'])->standard->targetSize)->toBe(24);
});
