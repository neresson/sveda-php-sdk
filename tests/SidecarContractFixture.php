<?php

namespace Sveda\Client\Tests;

use PHPUnit\Framework\TestCase;

trait SidecarContractFixture
{
    /**
     * @return array<string, mixed>
     */
    protected function sidecarContract(): array
    {
        $paths = [
            dirname(__DIR__).'/contracts/sidecar.v1.json',
            dirname(__DIR__, 2).'/sveda/packages/protocol/contracts/sidecar.v1.json',
        ];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $decoded = json_decode((string) file_get_contents($path), true);
                TestCase::assertIsArray($decoded);

                return $decoded;
            }
        }

        TestCase::fail('sidecar.v1.json contract fixture not found');
    }
}
