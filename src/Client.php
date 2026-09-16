<?php

namespace Veda\Client;

use Veda\Client\Contracts\Transporter;
use Veda\Client\Resources\Chat;
use Veda\Client\Resources\Documents;
use Veda\Client\Resources\Embed;
use Veda\Client\Resources\Histories;

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
