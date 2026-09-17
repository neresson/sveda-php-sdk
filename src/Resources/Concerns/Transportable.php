<?php

namespace Sveda\Client\Resources\Concerns;

use Sveda\Client\Contracts\Transporter;

trait Transportable
{
    public function __construct(
        protected readonly Transporter $transporter,
    ) {}
}
