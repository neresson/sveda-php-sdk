<?php

namespace Veda\Client\Resources\Concerns;

use Veda\Client\Contracts\Transporter;

trait Transportable
{
    public function __construct(
        protected readonly Transporter $transporter,
    ) {}
}
