<?php

namespace App\Subscriber;

use App\Service\Configuration;
use App\Entity\Log;
use App\Event\HealthCheckEvent;
use Doctrine\DBAL\DriverManager;

/**
 * Class CoreHealthCheckSubscriber
 * @package App\Subscriber
 */
class CoreHealthCheckSubscriber extends BasicHealthcheckSubscriber
{
    private $driver;
    private $host;
    private $port;
    private $name;
    private $user;
    private $password;
    private $path;
    private $configuration;

    public function __construct(
        Configuration $configuration,
        $driver = null,
        $host = null,
        $port = null,
        $name = null,
        $user = null,
        $password = null,
        $path = null
    ) {
        $this->configuration = $configuration;
        $this->driver   = $driver;
        $this->host     = $host;
        $this->port     = $port;
        $this->name     = $name;
        $this->user     = $user;
        $this->password = $password;
        $this->path     = $path;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'application.health-check' => ['onCheck', 0],
        ];
    }

    public function onCheck(HealthCheckEvent $healthCheck): void
    {
        $this->healthCheck = $healthCheck;
        $this->add($this->databaseConnection(), 'healthcheck_database_connection_error');


        $hierarchyLevel = $this->configuration->get('hierarchy_levels', 'plugin', 'metador_core');

        if (!empty($hierarchyLevel)) {
            $this->add(Log::SUCCESS, 'healthcheck_hierarchy_level_support_success', [
                '%hierarchyLevel%' => implode(', ', $hierarchyLevel)
            ]);
        } else {
            $this->add(Log::INFO, 'healthcheck_hierarchy_level_support_error');
        }
    }

    private function databaseConnection() : string
    {
        try {
            $connection = DriverManager::getConnection([
                'driver'   => $this->driver,
                'user'     => $this->user,
                'host'     => $this->host,
                'password' => $this->password,
                'dbname'   => $this->name,
                'path'     => $this->path,
                'port'     => $this->port,
            ]);

            $connection->getNativeConnection();
            return Log::SUCCESS;
        } catch (\Exception $e) {
            return Log::ERROR;
        }
    }
}

