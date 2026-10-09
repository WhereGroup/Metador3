<?php

namespace App\Service;

class FrontendCommand
{
    /** @var  Core */
    private $core;

    public function __construct(Core $core)
    {
        $this->core = $core;
    }

    public function redirect(&$response, $url): static
    {
        $this->runMethod('metador', 'redirect', $url, $response);

        return $this;
    }

    public function changeLocation(&$response, $url): static
    {
        $this->runMethod('metador', 'changeLocation', $url, $response);

        return $this;
    }

    public function displayInfo(&$response, $message, $parameters = []): static
    {
        $this->runMethod(
            'metador',
            'displayInfo',
            $this->core->translate($message, $parameters),
            $response
        );

        return $this;
    }

    public function displaySuccess(&$response, $message, $parameters = []): static
    {
        $this->runMethod(
            'metador',
            'displaySuccess',
            $this->core->translate($message, $parameters),
            $response
        );

        return $this;
    }

    public function displayWarning(&$response, $message, $parameters = []): static
    {
        $this->runMethod(
            'metador',
            'displayWarning',
            $this->core->translate($message, $parameters),
            $response
        );

        return $this;
    }

    public function displayError(&$response, $message, $parameters = []): static
    {
        $this->runMethod(
            'metador',
            'displayError',
            $this->core->translate($message, $parameters),
            $response
        );

        return $this;
    }
    public function runMethod($class, $method, $argument, &$response): static
    {
        if (!isset($response['METADOR']['runMethod'])) {
            $response['METADOR']['runMethod'] = [];
        }

        $response['METADOR']['runMethod'][] = [
            'class'    => $class,
            'method'   => $method,
            'argument' => $argument
        ];

        return $this;
    }

    public function runFunction($function, $argument, &$response): static
    {
        if (!isset($response['METADOR']['runFunction'])) {
            $response['METADOR']['runFunction'] = [];
        }

        $response['METADOR']['runFunction'][] = [
            'function' => $function,
            'argument' => $argument
        ];

        return $this;
    }
}
