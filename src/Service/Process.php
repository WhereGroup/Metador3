<?php

namespace App\Service;

use Exception;

class Process
{
    private $tempFolder;
    private $console;

    public function __construct($tempFolder, $rootDir)
    {
        $this->tempFolder = $tempFolder;
        $this->console = realpath($rootDir  . '/bin') . '/console';
        // Todo: add env to command
    }

    public function runInBackground($command, $handle = null)
    {
        if (is_null($handle)) {
            $handle = md5(microtime(true));
        }

        $path = $this->getProcessFilename($handle);
        shell_exec(sprintf("%s > %s 2>&1 & echo $! > %s &", $command, $path . '_output', $path . '_pid'));

        return $handle;
    }

    public function runSymfonyCommandInBackground($command, $handle = null)
    {
        return $this->runInBackground($this->console . ' ' . $command, $handle);
    }

    public function isRunning($handle): bool
    {
        $path = $this->getProcessFilename($handle);
        try {
            $result = shell_exec(sprintf("ps %d", file_get_contents($path . '_pid')));

            if (count(preg_split("/\n/", $result)) > 2) {
                return true;
            }
        } catch (Exception $e) {
        }

        $this->clean($handle);

        return false;
    }

    public function clean($handle): static
    {
        $path = $this->getProcessFilename($handle);

        if (file_exists($path . '_pid')) {
            unlink($path . '_pid');
        }

        if (file_exists($path . '_output')) {
            unlink($path . '_output');
        }

        return $this;
    }

    public function getProcessFilename($handle): string
    {
        return rtrim($this->tempFolder, '/') . '/' . $handle;
    }

    public function sendResponse($response): void
    {
        @ob_end_clean();
        header("Connection: close\r\n");
        header("Content-Encoding: none\r\n");
        ignore_user_abort(true);
        //fastcgi_finish_request();
        ob_start();
        echo $response;
        header("Content-Length: " . ob_get_length());
        ob_end_flush();
        flush();

        if (@ob_get_contents()) {
            @ob_end_clean();
        }
    }

    public function sendJsonResponse($array): void
    {
        @ob_end_clean();
        header("Connection: close\r\n");
        header("Content-Type: application/json\r\n");
        header("Content-Encoding: none\r\n");
        ignore_user_abort(true);
        //fastcgi_finish_request();
        ob_start();
        echo json_encode($array);
        header("Content-Length: " . ob_get_length());
        ob_end_flush();
        flush();

        if (ob_get_contents()) {
            @ob_end_clean();
        }
    }

    public function redirectResponse($url): void
    {
        @ob_end_clean();
        header("Connection: close\r\n");
        header("Content-Encoding: none\r\n");
        header("Location: " . $url . "\r\n");
        ignore_user_abort(true);
        ob_start();
        header("Content-Length: " . ob_get_length());
        ob_end_flush();
        flush();

        if (ob_get_contents()) {
            @ob_end_clean();
        }
    }
}
