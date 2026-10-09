<?php

namespace App\Component\ApiRecords;

use \Exception;

class OgcApiException extends Exception
{

    const NOAPPLICABLECODE = 500;
    const NOTACCEPTABLE = 406;
    CONST BADREQUEST = 400;
    const NOTFOUND = 404;

    public static $codeTitles = [
        400 => 'Bad Request',
        404 => 'Not Found',
        406 => 'Not Acceptable',
        500 => 'Internal Server Error'
    ];

    public static $codeTexts = [
        400 => 'The server cannot or will not process the request due to an apparent client error. For example, a query parameter had an incorrect value.',
        404 => 'The requested resource does not exist on the server. For example, a path parameter had an incorrect value.',
        406 => 'Content negotiation failed. For example, the Accept header submitted in the request did not support any of the media types supported by the server for the requested resource.',
        500 => 'An internal error occurred in the server.'
    ];
    /**
     * @param $code
     * @param $message
     * @param Exception|null $previous
     */
    public function __construct($code, $message = "", Exception $previous = null)
    {
        parent::__construct($message ?: $this->getCodeText($code), $code, $previous);
    }

    /**
     * @param $code
     * @return string
     */
    public function getCodeTitle(): string
    {
        return isset(self::$codeTitles[$this->code]) ? self::$codeTitles[$this->code] : self::$codeTitles[500];
    }

    /**
     * @return string
     */
    private function getCodeText(int $code): string
    {
        return isset(self::$codeTexts[$code]) ? self::$codeTexts[$code] : self::$codeTexts[500];
    }

    /**
     * @return int
     */
    public function getHttpStatusCode(): int
    {
        return $this->code ?? 500;
    }
}
