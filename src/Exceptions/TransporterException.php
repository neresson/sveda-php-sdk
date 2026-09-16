<?php

namespace Veda\Client\Exceptions;

use Exception;
use Throwable;

final class TransporterException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
