<?php

namespace Sveda\Client;

use Sveda\Client\Contracts\Transporter;
use Sveda\Client\Resources\Chat;
use Sveda\Client\Resources\Documents;
use Sveda\Client\Resources\Embed;
use Sveda\Client\Resources\Histories;

final class Client
{
    public function __construct(
        private readonly Transporter $transporter,
    ) {}

    public function chat(): Chat
    {
        return new Chat($this->transporter);
    }

    public function embed(): Embed
    {
        return new Embed($this->transporter);
    }

    public function histories(): Histories
    {
        return new Histories($this->transporter);
    }

    public function documents(): Documents
    {
        return new Documents($this->transporter);
    }
}
