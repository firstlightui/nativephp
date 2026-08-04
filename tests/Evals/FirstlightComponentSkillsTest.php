<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

function firstlightSkillTask(string $skillPath): Closure
{
    return function (string $scenario) use ($skillPath): string {
        $skill = file_get_contents(dirname(__DIR__, 2).'/'.$skillPath);

        if ($skill === false) {
            throw new RuntimeException("Unable to read skill [{$skillPath}].");
        }

        $prompt = <<<PROMPT
        Apply the following Firstlight repository skill to the scenario.
        Treat the skill as authoritative. Do not call tools or inspect files.
        Return only the JSON object requested by the scenario, without Markdown.

        <skill>
        {$skill}
        </skill>

        <scenario>
        {$scenario}
        </scenario>
        PROMPT;

        $process = new Process([
            'codex',
            'exec',
            '--ephemeral',
            '--ignore-user-config',
            '--skip-git-repo-check',
            '--sandbox',
            'read-only',
            '--color',
            'never',
            '--model',
            'gpt-5.6-terra',
            '--config',
            'model_reasoning_effort="low"',
            '-',
        ], '/private/tmp', timeout: 180);
        $process->setInput($prompt);
        $process->mustRun();

        return trim($process->getOutput());
    };
}

it('keeps an adapted search field inside the Firstlight public API', function (): void {
    expect(firstlightSkillTask('.agents/skills/firstlight-create-component/SKILL.md'))
        ->prompt(<<<'PROMPT'
        Add the alpha SearchField quickly. The official NativePHP text-input
        primitive has already been audited and proven to express every approved
        SearchField prop, @change and @submit event, accessibility semantic, and
        focused editing state without a custom renderer. Return JSON with string
        keys main_package, showcase_package, public_tag, implementation_path,
        state_class; boolean reads_alpha_design; and an events array. Event strings
        must retain their @ prefix. Choose the path the skill requires.
        PROMPT)
        ->repeat(5)
        ->toBeJson()
        ->toContain('firstlightui/nativephp', 'firstlightui/showcase', 'firstlight:search-field', '@change', '@submit')
        ->toMatch('/"state_class"\s*:\s*"focused(?:-|_| )text"/i')
        ->toMatch('/"implementation_path"\s*:\s*"adapter"/')
        ->toMatch('/"reads_alpha_design"\s*:\s*true/');
});

it('preserves native focused editing in the iOS skill', function (): void {
    expect(firstlightSkillTask('.agents/skills/firstlight-ios-component/SKILL.md'))
        ->prompt(<<<'PROMPT'
        Implement iOS TextField after the official NativePHP primitive has been
        audited and proven to express the full approved Firstlight contract and
        genuine focused editing behaviour. Return JSON with string keys
        main_package, showcase_package, implementation_path, state_class; booleans
        creates_placeholder_swift_files, preserves_cursor, preserves_marked_text;
        and a sync_modes array.
        PROMPT)
        ->repeat(5)
        ->toBeJson()
        ->toContain('firstlightui/nativephp', 'firstlightui/showcase', 'adapter', 'live', 'blur', 'debounce')
        ->toMatch('/"state_class"\s*:\s*"focused(?:-|_| )text"/i')
        ->toMatch('/"creates_placeholder_swift_files"\s*:\s*false/')
        ->toMatch('/"preserves_cursor"\s*:\s*true/')
        ->toMatch('/"preserves_marked_text"\s*:\s*true/');
});

it('allows idiomatic Material composition in the Android skill', function (): void {
    expect(firstlightSkillTask('.agents/skills/firstlight-android-component/SKILL.md'))
        ->prompt(<<<'PROMPT'
        Implement Android Stepper. Material 3 has no single named Stepper but does
        have genuine minus button, value, and plus button primitives which have
        already been audited and proven to map the full approved contract. Return
        JSON with string keys main_package, showcase_package, implementation_path,
        state_class; booleans allows_native_composition,
        stops_for_no_named_control, server_authoritative; and an events array.
        PROMPT)
        ->repeat(5)
        ->toBeJson()
        ->toContain('firstlightui/nativephp', 'firstlightui/showcase', 'discrete', '@change')
        ->toMatch('/"allows_native_composition"\s*:\s*true/')
        ->toMatch('/"stops_for_no_named_control"\s*:\s*false/')
        ->toMatch('/"server_authoritative"\s*:\s*true/');
});

it('reviews adapters and state classes against the alpha contract', function (): void {
    expect(firstlightSkillTask('.agents/skills/firstlight-review-component/SKILL.md'))
        ->prompt(<<<'PROMPT'
        Review an adapted SearchField and a continuous Slider for alpha readiness.
        Return JSON with string keys main_package and showcase_package; booleans
        reads_alpha_design, validates_adapter_mapping,
        distinguishes_state_classes, requires_showcase_states,
        blocks_alpha_claim_without_catalogue_review; and a verdict_shape array
        listing the permitted verdict words.
        PROMPT)
        ->repeat(5)
        ->toBeJson()
        ->toContain('firstlightui/nativephp', 'firstlightui/showcase', 'PASS', 'FAIL', 'BLOCKED')
        ->toMatch('/"reads_alpha_design"\s*:\s*true/')
        ->toMatch('/"validates_adapter_mapping"\s*:\s*true/')
        ->toMatch('/"distinguishes_state_classes"\s*:\s*true/')
        ->toMatch('/"requires_showcase_states"\s*:\s*true/')
        ->toMatch('/"blocks_alpha_claim_without_catalogue_review"\s*:\s*true/');
});
