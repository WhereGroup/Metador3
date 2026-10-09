<?php

namespace App\Component\CatalogueService\Parameter;

use App\Component\CatalogueService\Operation;


class GetParameter implements Parameter
{

    private array $requestParameters;

    public function __construct(array $requestParameters)
    {
        $this->requestParameters = $requestParameters;
    }

    public function getOperationName(): string
    {
        return $this->getParameter('request');
    }

    public function initOperation(Operation $operation): Operation
    {
        $parameterMap = $operation->getGETParameterMap();

        $parameters = [];
        foreach ($parameterMap as $name) {
            $parameters[$name] = $this->getParameter($name);
        }

        $operation->setParameters($parameters);

        return $operation;
    }

    private function getParameter(?string $name, ?bool $caseSensitive = false, ?string $xpath = null): mixed
    {
        if ($name === null) {
            return null;
        }
        if ($caseSensitive) {
            return isset($requestParameters[$name]) ? $this->requestParameters[$name] : null;
        } else {
            foreach ($this->requestParameters as $key => $value) {
                if (strtoupper($name) === strtoupper($key)) {
                    return $value;
                }
            }

            return null;
        }
    }
}
