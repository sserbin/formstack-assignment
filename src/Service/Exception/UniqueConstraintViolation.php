<?php

namespace App\Service\Exception;

use Throwable;

class UniqueConstraintViolation extends \RuntimeException
{
    public function __construct(string $msg, ?Throwable $prev = null)
    {
        parent::__construct($msg, 0, $prev);
    }
}
