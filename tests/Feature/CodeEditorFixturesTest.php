<?php

const CODE_EDITOR_FIXTURES = __DIR__.'/../fixtures/code-editor/editing.json';

const CODE_EDITOR_RULES = [
    'return-carry', 'return-json-open', 'return-json-split', 'return-yaml-colon',
    'indent', 'outdent', 'toggle-comment', 'insert', 'insert-json-closer', 'utf16',
];

const CODE_EDITOR_LANGUAGES = ['plain', 'json', 'yaml', 'env'];

function codeEditorFixtures(): array
{
    $fixtures = json_decode((string) file_get_contents(CODE_EDITOR_FIXTURES), true, flags: JSON_THROW_ON_ERROR);

    return $fixtures['cases'];
}

function codeEditorMarkedTextIsValid(string $text): bool
{
    $cursors = mb_substr_count($text, '‸');
    $starts = mb_substr_count($text, '«');
    $ends = mb_substr_count($text, '»');

    if ($cursors === 1) {
        return $starts === 0 && $ends === 0;
    }

    return $cursors === 0 && $starts === 1 && $ends === 1
        && mb_strpos($text, '«') < mb_strpos($text, '»');
}

it('describes every case with a known rule, language, indent, and operation', function () {
    foreach (codeEditorFixtures() as $case) {
        expect(array_keys($case))->toBe(['rule', 'name', 'language', 'indent', 'operation', 'before', 'after'])
            ->and($case['rule'])->toBeIn(CODE_EDITOR_RULES)
            ->and($case['language'])->toBeIn(CODE_EDITOR_LANGUAGES)
            ->and($case['indent'])->toBeIn([2, 4])
            ->and(preg_match('/^(return|indent|outdent|toggle-comment|insert:.)$/u', $case['operation']))->toBe(1, $case['name']);
    }
});

it('marks exactly one cursor or one ordered selection on both sides', function () {
    foreach (codeEditorFixtures() as $case) {
        expect(codeEditorMarkedTextIsValid($case['before']))->toBeTrue("{$case['name']} before")
            ->and(codeEditorMarkedTextIsValid($case['after']))->toBeTrue("{$case['name']} after");
    }
});

it('uses unique case names', function () {
    $names = array_column(codeEditorFixtures(), 'name');

    expect($names)->toBe(array_values(array_unique($names)));
});

it('covers every editing rule and language', function () {
    $cases = codeEditorFixtures();

    expect(array_values(array_unique(array_column($cases, 'rule'))))->toEqualCanonicalizing(CODE_EDITOR_RULES)
        ->and(array_values(array_unique(array_column($cases, 'language'))))->toEqualCanonicalizing(CODE_EDITOR_LANGUAGES);
});

it('describes every scroll-anchor case with in-range lines', function () {
    $fixtures = json_decode((string) file_get_contents(__DIR__.'/../fixtures/code-editor/scroll-anchor.json'), true, flags: JSON_THROW_ON_ERROR);
    $names = [];

    foreach ($fixtures['cases'] as $case) {
        expect(array_keys($case))->toBe(['name', 'before', 'after', 'anchor_line', 'expected_line'])
            ->and($case['anchor_line'])->toBeInt()->toBeLessThan(count(explode("\n", $case['before'])))
            ->and($case['expected_line'] === null || $case['expected_line'] < count(explode("\n", $case['after'])))->toBeTrue($case['name']);
        $names[] = $case['name'];
    }

    expect($names)->toBe(array_values(array_unique($names)))
        ->and(array_filter(array_column($fixtures['cases'], 'expected_line'), 'is_null'))->not->toBeEmpty();
});
