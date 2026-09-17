<?php

namespace Sveda\Client\Exceptions;

use Exception;

final class ErrorException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly ?array $response = null,
    ) {
        parent::__construct($message, $statusCode);
    }
}
