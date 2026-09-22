<?php

namespace Sveda\Client\Host;

use Sveda\Client\Client;
use Sveda\Client\Exceptions\AuthenticationException;
use Sveda\Client\Exceptions\ErrorException;
use Sveda\Client\Exceptions\TransporterException;
use Sveda\Client\Factory;

final class HostManager
{
    public string $baseUrl = '';

    public string $hostApiKey = '';

    public string $mcpPath = Constants::DEFAULT_MCP_PATH;

    public string $mcpUrl = '';

    public string $serverName = 'Host Application';

    public string $serverVersion = '0.1.0';

    public string $instructions = '';

    public string $mcpAbility = Constants::DEFAULT_MCP_ABILITY;

    public int $tokenTtlSeconds = 3600;

    public string $visitorPrefix = 'host';

    public McpTokenStore $tokenStore;

    /** @var callable(mixed): bool|null */
    private $authorizeUsing = null;

    /** @var callable(mixed): void|null */
    private $afterAuthenticateUsing = null;

    /** @var callable|null */
    private $resolveToolsUsing = null;

    /** @var callable(mixed): (string|null)|null */
    private $policyUsing = null;

    /** @var callable(mixed): string|null */
    private $visitorIdUsing = null;

    /** @var callable(mixed): string|null */
    private $mintTokenUsing = null;

    /** @var callable(string): (array{user: mixed}|null)|null */
    private $verifyBearerTokenUsing = null;

    private bool $mintTokenUsingConfigured = false;

    public function __construct(
        string $baseUrl = '',
        string $hostApiKey = '',
        ?McpTokenStore $tokenStore = null,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->hostApiKey = trim($hostApiKey);
        $this->tokenStore = $tokenStore ?? new McpTokenStore();
    }

    public function authorizeUsing(callable $callback): self
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function afterAuthenticateUsing(callable $callback): self
    {
        $this->afterAuthenticateUsing = $callback;

        return $this;
    }

    public function resolveToolsUsing(callable $callback): self
    {
        $this->resolveToolsUsing = $callback;

        return $this;
    }

    public function policyUsing(callable $callback): self
    {
        $this->policyUsing = $callback;

        return $this;
    }

    public function visitorIdUsing(callable $callback): self
    {
        $this->visitorIdUsing = $callback;

        return $this;
    }

    public function mintTokenUsing(callable $callback): self
    {
        $this->mintTokenUsing = $callback;
        $this->mintTokenUsingConfigured = true;

        return $this;
    }

    public function verifyBearerTokenUsing(callable $callback): self
    {
        $this->verifyBearerTokenUsing = $callback;

        return $this;
    }

    public function authorize(mixed $user): bool
    {
        if ($this->authorizeUsing === null) {
            return true;
        }

        return (bool) ($this->authorizeUsing)($user);
    }

    public function afterAuthenticate(mixed $user): void
    {
        if ($this->afterAuthenticateUsing !== null) {
            ($this->afterAuthenticateUsing)($user);
        }
    }

    /**
     * @return list<object>
     */
    public function resolveTools(mixed $user = null): array
    {
        if ($this->resolveToolsUsing === null) {
            return [];
        }

        $tools = $this->invokeToolsCallback($user);
        if (! is_array($tools)) {
            return [];
        }

        $resolved = [];
        foreach ($tools as $tool) {
            if (is_object($tool) && method_exists($tool, 'name')) {
                $resolved[] = $tool;
            }
        }

        return $resolved;
    }

    public function policyFor(mixed $user): ?string
    {
        if ($this->policyUsing === null) {
            return null;
        }

        $value = ($this->policyUsing)($user);
        if ($value === null) {
            return null;
        }

        $policy = trim((string) $value);

        return $policy === '' ? null : $policy;
    }

    public function visitorId(mixed $user): string
    {
        if ($this->visitorIdUsing !== null) {
            return (string) ($this->visitorIdUsing)($user);
        }

        $id = is_array($user) && isset($user['id']) ? (string) $user['id'] : 'anonymous';

        return $this->visitorPrefix.'-'.$id;
    }

    public function mintMcpToken(mixed $user): string
    {
        if ($this->mintTokenUsing !== null) {
            return (string) ($this->mintTokenUsing)($user);
        }

        return $this->defaultMintMcpToken($user);
    }

    public function defaultMintMcpToken(mixed $user): string
    {
        $userId = is_array($user) && isset($user['email'])
            ? (string) $user['email']
            : (is_array($user) && isset($user['id']) ? (string) $user['id'] : 'anonymous');
        $this->tokenStore->revokeForUser($userId);

        return $this->tokenStore->mint($userId, $this->mcpAbility, $this->tokenTtlSeconds);
    }

    public function mcpPublicUrl(?string $requestOrigin = null): string
    {
        if ($this->mcpUrl !== '') {
            return $this->mcpUrl;
        }

        $origin = rtrim((string) $requestOrigin, '/');
        $path = str_starts_with($this->mcpPath, '/') ? $this->mcpPath : '/'.$this->mcpPath;
        if ($origin === '') {
            return $path;
        }

        return $origin.$path;
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->hostApiKey !== '';
    }

    public function hostClient(): Client
    {
        return Factory::factory()
            ->withBaseUri($this->baseUrl)
            ->withHostApiKey($this->hostApiKey)
            ->make();
    }

    /**
     * @return array{origin: string, token: string, expires_in: int, appearance: array<string, mixed>|null}
     */
    public function startSession(mixed $user, ?string $requestOrigin = null): array
    {
        if (! $this->isConfigured()) {
            throw new ErrorException('Sveda host is not configured.', 404);
        }

        $mcpToken = $this->mintMcpToken($user);
        $visitorId = $this->visitorId($user);
        $hostMcpUrl = $this->mcpPublicUrl($requestOrigin);

        $payload = [
            'visitor_id' => $visitorId,
            'host_mcp_url' => $hostMcpUrl,
            'host_mcp_token' => $mcpToken,
        ];
        $policy = $this->policyFor($user);
        if ($policy !== null) {
            $payload['policy'] = $policy;
        }

        try {
            $response = $this->hostClient()->embed()->createToken($payload);
        } catch (AuthenticationException|ErrorException|TransporterException $exception) {
            throw new ErrorException($exception->getMessage(), 502);
        }

        if ($response->token === '') {
            throw new ErrorException('Sidecar returned an empty embed token.', 502);
        }

        return [
            'origin' => $this->baseUrl,
            'token' => $response->token,
            'expires_in' => $response->expiresIn,
            'appearance' => $response->appearance,
        ];
    }

    /**
     * @return array{user: mixed}|null
     */
    public function authenticateBearerToken(string $plainToken): ?array
    {
        if (trim($plainToken) === '') {
            return null;
        }

        if ($this->verifyBearerTokenUsing !== null) {
            $verified = ($this->verifyBearerTokenUsing)($plainToken);
            if ($verified === null) {
                return null;
            }
            if (is_array($verified) && array_key_exists('user', $verified)) {
                return $verified;
            }

            return ['user' => $verified];
        }

        $record = $this->tokenStore->verify($plainToken, $this->mcpAbility);
        if ($record === null) {
            return null;
        }

        return ['user' => ['id' => $record['user_id']]];
    }

    /**
     * @return array<string, mixed>
     */
    public function describe(mixed $user = null): array
    {
        return HostManifest::build($this, $user);
    }

    /**
     * @return array{
     *     resolve_tools: bool,
     *     policy: bool,
     *     authorize: bool,
     *     visitor_id: bool,
     *     mint_token: bool
     * }
     */
    public function registeredHooks(): array
    {
        return [
            'resolve_tools' => $this->resolveToolsUsing !== null,
            'policy' => $this->policyUsing !== null,
            'authorize' => $this->authorizeUsing !== null,
            'visitor_id' => $this->visitorIdUsing !== null,
            'mint_token' => $this->mintTokenUsingConfigured,
        ];
    }

    /**
     * @return list<mixed>
     */
    private function invokeToolsCallback(mixed $user): array
    {
        $callback = $this->resolveToolsUsing;
        if (! is_callable($callback)) {
            return [];
        }

        if ($user === null) {
            try {
                $tools = $callback();
            } catch (\ArgumentCountError) {
                $tools = $callback(null);
            }
        } else {
            $tools = $callback($user);
        }

        return is_array($tools) ? $tools : [];
    }
}
