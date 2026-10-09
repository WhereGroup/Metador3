<?php

namespace App\Component;

use Symfony\Component\HttpFoundation\Response;

class CsvResponse extends Response
{
    public function __construct(array $data, $status = 200, $headers = [])
    {
        parent::__construct('', $status, $headers);

        if (null === $data) {
            $data = new \ArrayObject();
        }

        ob_start();
        $output = fopen('php://output', 'w');

        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);


        $this->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $this->headers->set('Content-Disposition', 'filename=result.csv');

        return $this->setContent(ob_get_clean());
    }
}
