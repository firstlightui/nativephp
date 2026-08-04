<?php

declare(strict_types=1);

namespace FirstlightUI\Documentation;

use Symfony\Component\Process\Process;

class CaptureCommandRunner
{
    /** @param list<string> $command
     *  @return array{exitCode: int, stdout: string, stderr: string}
     */
    public function run(array $command, ?string $cwd = null): array
    {
        $process = new Process($command, $cwd, timeout: 1800);
        $process->run();

        return [
            'exitCode' => $process->getExitCode() ?? 1,
            'stdout' => $process->getOutput(),
            'stderr' => $process->getErrorOutput(),
        ];
    }
}
