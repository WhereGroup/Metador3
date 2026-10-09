<?php

namespace App\Service\Search;

use Throwable;

class PropertyNameNotFoundException extends \Exception
{
    private string $propertyName;

    public function __construct($propertyName, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            $message ? $message : 'The property name "'.$propertyName.'" is not found.',
            $code,
            $previous
        );
        $this->propertyName = $propertyName;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }
}
