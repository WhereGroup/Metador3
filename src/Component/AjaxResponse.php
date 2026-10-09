<?php

namespace App\Component;

use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxResponse extends JsonResponse
{
    public function __construct($data = null, $status = 200, $headers = [])
    {
        parent::__construct('', $status, $headers);

        if (null === $data) {
            $data = new \ArrayObject();
        }

        $data = array_merge_recursive(
            $data,
            [
                'METADOR' => [
                    'runMethod' => [
                        [
                            'class'    => 'session',
                            'method'   => 'setTimeout',
                            'argument' => ini_get("session.gc_maxlifetime")
                        ]
                    ]
                ]
            ]
        );

        $this->setData($data);
    }
}
