<?php

namespace Sveda\Client\Host;

use Sveda\Client\Host\Contracts\HostTool;

final class HostManifest
{
    /**
     * @return array<string, mixed>
     */
    public static function toolToMcpArray(object $tool): array
    {
        $name = self::toolAttribute($tool, 'name');
        $mode = self::toolAttribute($tool, 'mode', HostTool::MODE_READ);
        $meta = [
            'domain' => self::toolAttribute($tool, 'domain', 'other'),
            'mode' => $mode,
        ];
        $confirmation = self::confirmation($tool);
        if ($confirmation !== null) {
            $meta['confirmation'] = $confirmation;
        }

        return [
            'name' => $name,
            'title' => $name,
            'description' => self::toolAttribute($tool, 'description'),
            'inputSchema' => HostSchema::buildInputSchema($tool),
            'annotations' => HostSchema::toolAnnotations($mode),
            '_meta' => $meta,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function build(HostManager $host, mixed $user = null): array
    {
        $authenticated = $user !== null;
        $policy = $authenticated ? $host->policyFor($user) : null;

        $tools = [];
        foreach ($host->resolveTools($user) as $tool) {
            $tools[] = self::toolToMcpArray($tool);
        }

        return [
            'schema' => Constants::HOST_MANIFEST_SCHEMA,
            'sdk' => [
                'language' => 'php',
                'version' => self::sdkVersion(),
            ],
            'subject' => [
                'authenticated' => $authenticated,
                'policy' => $policy,
            ],
            'hooks' => $host->registeredHooks(),
            'tools' => $tools,
        ];
    }

    private static function sdkVersion(): string
    {
        if (class_exists(\Composer\InstalledVersions::class)
            && \Composer\InstalledVersions::isInstalled('sveda-ai/php-sdk')) {
            return \Composer\InstalledVersions::getPrettyVersion('sveda-ai/php-sdk') ?? 'unknown';
        }

        return 'dev';
    }

    private static function toolAttribute(object $tool, string $name, string $default = ''): string
    {
        if (! method_exists($tool, $name)) {
            return $default;
        }

        $value = $tool->{$name}();

        return is_string($value) ? $value : (string) $value;
    }

    private static function confirmation(object $tool): ?string
    {
        if (! method_exists($tool, 'confirmation')) {
            return null;
        }

        return $tool->confirmation() === 'required' ? 'required' : null;
    }
}
