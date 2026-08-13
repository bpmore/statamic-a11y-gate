<?php

declare(strict_types=1);

use Bpmore\A11yGate\Accessibility\StaticAccessibilityChecker;
use Bpmore\A11yGate\Accessibility\Violation;

/**
 * The bare-web-address boundary, edge by edge.
 *
 * The corpus pins the decision (link-dotted-name and link-text-is-web-address).
 * These pin the shapes around it, because the rule used to read "Node.js" as a
 * web address and refuse the publish, and the fix must not free real addresses
 * along with it.
 */
function linkTextFindings(string $text): array
{
    $html = '<html lang="en"><body><h1>Docs</h1><a href="/somewhere">'.$text.'</a></body></html>';

    return array_map(
        fn (Violation $v) => [$v->rule, $v->severity],
        (new StaticAccessibilityChecker)->check($html),
    );
}

it('passes a product name that ends in dot-letters', function () {
    // The false refusal that motivated the change: each of these names its
    // destination, and each was being refused as a bare web address.
    foreach (['Node.js', 'Vue.js', 'ASP.NET'] as $name) {
        expect(linkTextFindings($name))->toBe([], "'{$name}' should pass");
    }
});

it('still refuses link text that is a real address', function () {
    // The mutation check: freeing "Node.js" must not free these.
    foreach (['https://example.com', 'www.example.com', 'example.com/reports/2026'] as $address) {
        expect(linkTextFindings($address))
            ->toBe([['link-unclear', Violation::ERROR]], "'{$address}' should refuse");
    }
});

it('passes a bare domain with no path, the accepted cost', function () {
    // "example.com" at least names where the link goes. Letting it through is
    // the price of never refusing "Node.js", and it is the cheaper mistake.
    expect(linkTextFindings('example.com'))->toBe([]);
});
