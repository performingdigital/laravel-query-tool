<?php

namespace PerformingDigital\QueryTool\Exceptions;

use InvalidArgumentException;

final class InvalidQueryToolPayload extends InvalidArgumentException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
