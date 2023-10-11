<?php

namespace TransformStudios\Events\Exceptions;

use Exception;

class FieldNotFoundException extends Exception
{
    public function __construct(private string $field)
    {
        parent::__construct("Field [{$field}] not found, please make sure you have a `timezone` field in your blueprint.");

        $this->field = $field;
    }
}
