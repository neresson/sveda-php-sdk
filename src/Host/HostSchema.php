<?php

namespace Sveda\Client\Host;

use Sveda\Client\Host\Contracts\HostTool;

final class HostSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function buildInputSchema(object $tool): array
    {
        if (method_exists($tool, 'inputSchema')) {
            $schema = $tool->inputSchema();
            if (is_array($schema)) {
                return $schema;
            }
        }

        $raw = $tool->schema();
        if (! is_array($raw)) {
            $raw = [];
        }

        if (($raw['type'] ?? null) === 'object' && is_array($raw['properties'] ?? null)) {
            return $raw;
        }

        $properties = [];
        $required = [];

        foreach ($raw as $key => $definition) {
            if (! is_string($key)) {
                continue;
            }
            $properties[$key] = self::normalizeProperty($definition);
            if (is_array($definition) && ($definition['required'] ?? false) === true) {
                $required[] = $key;
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];
        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * @return array<string, bool>
     */
    public static function toolAnnotations(string $mode): array
    {
        if ($mode === HostTool::MODE_READ) {
            return ['readOnlyHint' => true];
        }
        if ($mode === HostTool::MODE_DELETE) {
            return ['readOnlyHint' => false, 'destructiveHint' => true];
        }

        return ['readOnlyHint' => false, 'destructiveHint' => false];
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeProperty(mixed $definition): array
    {
        if (! is_array($definition)) {
            return ['type' => 'string'];
        }

        $rest = $definition;
        unset($rest['required']);

        return $rest !== [] ? $rest : ['type' => 'string'];
    }
}
