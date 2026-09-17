<?php

namespace Sveda\Client;

use GuzzleHttp\Client as GuzzleClient;
use Sveda\Client\Contracts\Transporter;
use Sveda\Client\Transporters\GuzzleTransporter;

final class Factory
{
    private string $baseUri = '';

    private ?string $hostApiKey = null;

    private ?string $embedToken = null;

    private ?Transporter $transporter = null;

    private int $timeout = 30;

    private int $connectTimeout = 5;

    /**
     * @var array<string, string>
     */
    private array $headers = [];

    public static function factory(): self
    {
        return new self;
    }

    public function withBaseUri(string $baseUri): self
    {
        $this->baseUri = rtrim($baseUri, '/');

        return $this;
    }

    public function withHostApiKey(?string $hostApiKey): self
    {
        $this->hostApiKey = $hostApiKey !== null && $hostApiKey !== '' ? $hostApiKey : null;

        return $this;
    }

    public function withEmbedToken(?string $embedToken): self
    {
        $this->embedToken = $embedToken !== null && $embedToken !== '' ? $embedToken : null;

        return $this;
    }

    public function withTransporter(Transporter $transporter): self
    {
        $this->transporter = $transporter;

        return $this;
    }

    public function withHttpHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function withTimeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }

    public function withConnectTimeout(int $seconds): self
    {
        $this->connectTimeout = $seconds;

        return $this;
    }

    public function make(): Client
    {
        $transporter = $this->transporter ?? new GuzzleTransporter(
            client: new GuzzleClient,
            baseUri: $this->baseUri,
            defaultHeaders: $this->resolveAuthHeaders(),
            timeout: $this->timeout,
            connectTimeout: $this->connectTimeout,
        );

        return new Client($transporter);
    }

    /**
     * @return array<string, string>
     */
    private function resolveAuthHeaders(): array
    {
        $headers = $this->headers;

        if ($this->hostApiKey !== null) {
            $headers['Authorization'] = 'Bearer '.$this->hostApiKey;
        }

        if ($this->embedToken !== null) {
            $headers['X-Sveda-Embed-Token'] = $this->embedToken;
        }

        return $headers;
    }
}
