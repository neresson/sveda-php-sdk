<?php

namespace Sveda\Client\Host;

final class Constants
{
    public const MODE_READ = 'read';

    public const MODE_WRITE = 'write';

    public const MODE_DELETE = 'delete';

    public const MCP_PROTOCOL_VERSION = '2025-11-25';

    public const PAGE_CONTEXT_HEADER = 'x-sveda-page-context';

    public const CHAT_ID_HEADER = 'x-sveda-chat-id';

    public const PAGE_CONTEXT_MAX_BYTES = 24000;

    public const DEFAULT_MCP_ABILITY = 'sveda:mcp';

    public const DEFAULT_MCP_PATH = '/mcp/sveda';

    public const HOST_MANIFEST_SCHEMA = 'sveda.host/v1';
}
