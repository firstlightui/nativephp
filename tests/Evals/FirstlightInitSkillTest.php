<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

/**
 * Runs the shipped firstlight-init skill, with the plugin index it reads, against an app description
 * and returns the starter plan the model proposes.
 */
function firstlightInitPlan(): Closure
{
    return function (string $description): string {
        $root = dirname(__DIR__, 2);
        $skill = file_get_contents($root.'/resources/boost/skills/firstlight-init/SKILL.md');
        $index = file_get_contents($root.'/resources/starter/plugins.json');

        $prompt = <<<PROMPT
        Apply the following skill to the app description. Treat the skill as authoritative.
        Do not call tools or inspect files. The file vendor/firstlightui/nativephp/resources/starter/plugins.json
        holds the plugin index given below. You have no NativePHP docs tools.

        The developer has already confirmed your restatement of the app, and accepts every free plugin
        you include but has not answered about any paid plugin yet. Return only this JSON object, without Markdown:
        {"layout": "tabs" or "stack", "tabs": [labels], "recipes": [recipe ids such as "how-to/build-a-form-screen"],
        "included": [packages], "offered_paid": [packages], "unreviewed": [packages], "not_needed": [packages],
        "community_notice_shown": true or false, "android_min_sdk": number, "app_id": string}

        <skill>
        {$skill}
        </skill>

        <plugin-index>
        {$index}
        </plugin-index>

        <app-description>
        {$description}
        </app-description>
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
        ], sys_get_temp_dir(), timeout: 180);
        $process->setInput($prompt);
        $process->mustRun();

        return trim($process->getOutput());
    };
}

it('locks a private journal and only offers the paid unlock prompt', function (): void {
    expect(firstlightInitPlan())
        ->prompt('Daybook: a private journal for my own entries, with a photo on some entries. Nobody else should see it if they pick up my phone.')
        ->repeat(5)
        ->toBeJson()
        ->toContain('how-to/build-an-app-lock-screen', 'firstlightui/app-guard')
        ->toMatch('/"included"\s*:\s*\[[^\]]*"firstlightui\/app-guard"/')
        ->toMatch('/"offered_paid"\s*:\s*\[[^\]]*"nativephp\/mobile-biometrics"/')
        ->toMatch('/"included"\s*:\s*\[(?![^\]]*"nativephp\/mobile-(biometrics|secure-storage|camera)")[^\]]*\]/');
});

it('includes the network plugin for offline field work and leaves out unreleased plugins', function (): void {
    expect(firstlightInitPlan())
        ->prompt('Field technicians at Acme see the day\'s jobs, add notes and photos, and keep working in basements with no signal. They also want a home-screen shortcut to today\'s jobs.')
        ->repeat(5)
        ->toBeJson()
        ->toContain('how-to/show-connectivity')
        ->toMatch('/"included"\s*:\s*\[[^\]]*"nativephp\/mobile-network"/')
        ->toMatch('/"app_id"\s*:\s*"[a-z][a-z0-9]*(\.[a-z][a-z0-9]*)+"/')
        ->toMatch('/^(?![\s\S]*firstlightui\/app-shortcuts)/')
        ->toMatch('/"included"\s*:\s*\[(?![^\]]*"partek\/home-screen-shortcuts")[^\]]*\]/');
});

it('offers the community charts plugin with its notice for a stats app', function (): void {
    expect(firstlightInitPlan())
        ->prompt('A running log: record each run, see a list of runs, and see charts of weekly distance and pace over time.')
        ->repeat(5)
        ->toBeJson()
        ->toContain('smronju/nativephp-charts')
        ->toMatch('/"community_notice_shown"\s*:\s*true/')
        ->toMatch('/"included"\s*:\s*\[(?![^\]]*"jvdluk\/pro-vibration")[^\]]*\]/');
});

it('uses one stack and no plugins for a single-list utility', function (): void {
    expect(firstlightInitPlan())
        ->prompt('A packing list for one trip at a time: a checklist of items I add, tick off and delete. Everything stays on the phone.')
        ->repeat(5)
        ->toBeJson()
        ->toMatch('/"layout"\s*:\s*"stack"/')
        ->toMatch('/"offered_paid"\s*:\s*\[\s*\]/')
        ->toMatch('/"android_min_sdk"\s*:\s*29\b/');
});
