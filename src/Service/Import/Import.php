<?php

namespace App\Service\Import;

use App\Component\Exceptions\MetadataExistsException;
use App\Component\Exceptions\MetadataNotFoundException;
use App\Entity\Log;
use App\Entity\ImportExport;
use App\Service\Database;
use App\Service\Logger;
use App\Service\Metadata;
use DateTime;
use Exception;

class Import
{
    public Database $db;
    private Metadata $metadata;
    private Logger $logger;

    public function __construct(Database $db, Metadata $metadata, Logger $logger)
    {
        $this->db = $db;
        $this->metadata = $metadata;
        $this->logger = $logger;
    }

    public function importFromXml(string $xml, ImportExport $config): ?\App\Entity\Metadata
    {
        $object  = $this
            ->metadata
            ->getProcessor()
            ->xmlToObjectByMapping($xml, $config->getProfileMapping());

        $options = [
            'source'   => $config->getSource(),
            'username' => $config->getUsername(),
            'public'   => true,
            'flush'    => false,
            'log'      => true
        ];

        try {
            if ($this->metadata->findById($object['_uuid']) && $config->getRemoveDataOnDestination()) {
                $this->logOverrideMetadata($config, $object, $options);
                return $this->metadata->updateByObject($object, $options);
            }
            $this->logImportMetadata($config, $object, $options);
            return $this->metadata->insertByObject($object, $options);
        } catch (MetadataNotFoundException $e) {
            $this->logImportMetadata($config, $object, $options);
            return $this->metadata->insertByObject($object, $options);
        } catch (MetadataExistsException $e) {
            if ($config->getRemoveDataOnDestination()) {
                $this->logException($config, $object, $options, $e);
                return null;
            }
            $this->logMetadataExists($config, $object, $options);
        } catch (Exception $e) {
            $this->logException($config, $object, $options, $e);
        }

        return null;
    }

    public function logOverrideMetadata($config, $object, $options): void
    {
        $message = 'Datensatz "' . $object['title'] . '", mit der UUID "' . $object['_uuid']
            . '", Datenquelle "'  . $config->getSource() . '" wird überschrieben.';

        $this->log($config, Log::INFO, $message, $options['flush']);
    }

    public function logImportMetadata($config, $object, $options): void
    {
        $message = 'Datensatz "' . $object['title'] . '", mit der UUID "' . $object['_uuid'];

        if ($this->isImport($config)) {
            $message .= '", wird in die Datenquelle "'  . $config->getSource() . '" importiert.';
        } else {
            $message .= '", wird aus der Datenquelle "'  . $config->getSource() . '" exportiert.';
        }

        $this->log($config, Log::INFO, $message, $options['flush']);
    }

    public function logException($config, $object, $options, $e): void
    {
        $this->log($config, Log::ERROR, "UUID: " . $object['_uuid'] . " " . $e->getMessage(), $options['flush']);
    }

    public function logMetadataExists(ImportExport $config, array $object, array $options): void
    {
        $message = 'Datensatz "' . $object['title'] . '", mit der UUID "' . $object['_uuid']
            . '", Datenquelle "'  . $config->getSource() . '" existiert und wird nicht überschrieben.';

        $this->log($config, Log::INFO, $message, $options['flush']);
    }

    public function log(ImportExport $config, $type = Log::SUCCESS, $message = '', $flush = true): void
    {
        $log = $this->logger->newLog();
        $log
            ->setType($type)
            ->setCategory('system')
            ->setSubcategory('Import/Export')
            ->setOperation($this->isImport($config) ? 'import' : 'export')
            ->setMessage($message)
            ->setUsername($config->getUsername())
            ->setIdentifier($config->getId())
            ->setPath('metador_admin_import_edit', ['id' => $config->getId()])
            ->setSource($config->getSource())
        ;

        try {
            $log->setDateTime(new DateTime());
        } catch (Exception $e) {
        }

        $this->logger->set($log, $flush);
    }

    public function isImport($config): bool
    {
        return !empty($config->getImportFolder());
    }
}
