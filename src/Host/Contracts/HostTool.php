<?php

namespace Sveda\Client\Host\Contracts;

use Sveda\Client\Host\HostCallContext;

interface HostTool
{
    public const MODE_READ = 'read';

    public const MODE_WRITE = 'write';

    public const MODE_DELETE = 'delete';

    public function name(): string;

    public function description(): string;

    /**
     * @return array<string, mixed>
     */
    public function schema(): array;

    public function mode(): string;

    public function domain(): string;

    public function handle(array $arguments, ?HostCallContext $context = null): mixed;
}
