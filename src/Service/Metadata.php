<?php

namespace App\Service;

use DateTime;
use Exception;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Component\Exceptions\MetadataException;
use App\Component\Exceptions\MetadataExistsException;
use App\Component\Exceptions\MetadataNotFoundException;
use App\Component\Metadata\PrepareMetadata;
use App\Component\Metadata\Validator;
use App\Entity\Address as AddressEntity;
use App\Entity\Metadata as MetadataEntity;
use App\Event\MetadataChangeEvent;
use App\Entity\User;

/**
 * Class Metadata
 * @package App\Service\Metadata
 */
class Metadata
{
    /** @var Database */
    public $db;

    /** @var App */
    protected $app;

    /** @var Address */
    protected $address;

    /** @var MetadataXmlProcessor */
    protected $processor;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var UserInterface */
    protected $user;

    /** @var Logger */
    protected $logger;

    /** @var Validator */
    public $validator;

    /** @var Configuration */
    public $configuration;

    public function __construct(
        MetadataXmlProcessor     $processor,
        EventDispatcherInterface $eventDispatcher,
        App                      $app,
        UserInterface            $user,
        Logger                   $logger,
        Database                 $db,
        Address                  $address,
        Validator                $validator,
        Configuration            $configuration
    )
    {
        $this->processor = $processor;
        $this->eventDispatcher = $eventDispatcher;
        $this->app = $app;
        $this->user = $user;
        $this->logger = $logger;
        $this->db = $db;
        $this->address = $address;
        $this->validator = $validator;
        $this->configuration = $configuration;
    }

    public function __destruct()
    {
        unset(
            $this->processor,
            $this->eventDispatcher,
            $this->app,
            $this->user,
            $this->logger,
            $this->db,
            $this->address,
            $this->validator,
            $this->configuration
        );
    }

    public function getProcessor(): MetadataXmlProcessor
    {
        return $this->processor;
    }

    public function findById($id, $dispatchEvent = true)
    {
        if (empty($id)) {
            return null;
        }

        $entity = $this->db->getRepository()->findOneById($id);

        if ($entity === null) {
            throw new MetadataNotFoundException();
        }

        self::addAddressDetails($this->address, $entity);

        if ($entity instanceof MetadataEntity && $dispatchEvent) {
            $this->eventDispatcher->dispatch(new MetadataChangeEvent($entity, []), 'metadata.on_load');
        }

        return $entity;
    }

    public function prepareObjectForInsert(array $object, array $options = []): MetadataEntity
    {
        $this->setDefaultOptionValues($options);

        $metadata = new MetadataEntity();

        if (empty($object['_uuid'])) {
            $object['_uuid'] = $object['fileIdentifier'] = $this->app->generateUuid();
        }
        try {
            if ($this->findById($object['_uuid'])) {
                throw new MetadataExistsException("Datensatz " . $object['_uuid'] . " existiert bereits.");
            }
        } catch (MetadataNotFoundException $e) {
            //
        }

        $metadata->setObject($object);

        self::setAddressDetails($this->address, $metadata);

        return PrepareMetadata::prepareInsert(
            $metadata,
            $this->findUser($object, $options),
            $options,
            $this->user
        );
    }

    /**
     * @throws MetadataNotFoundException
     */
    public function prepareObjectForUpdate(array $object, array $options = []): MetadataEntity
    {
        $this->setDefaultOptionValues($options);

        $metadata = $this->findById($object['_uuid']);

        if (is_null($metadata)) {
            throw new MetadataNotFoundException("Datensatz mit der UUID " . $object['_uuid'] . " nicht gefunden.");
        }

        $oldObject = $metadata->getObject();
        // Todo: why oldObject?
        $object['_insert_user'] = $oldObject['_username'];
        $object['_insert_time'] = $oldObject['dateStamp'];

        $date = new DateTime($object['_insert_time']);

        $metadata
            ->setInsertUser($this->app->getUser($object['_insert_user']))
            ->setInsertTime($date->getTimestamp());

        $metadata->setObject($object);

        self::setAddressDetails($this->address, $metadata);

        return PrepareMetadata::prepareUpdate(
            $metadata,
            $this->findUser($object, $options),
            $options,
            $this->user
        );
    }

    public function insertByObject(array $object, array $options = []): MetadataEntity
    {
        return $this->save($this->prepareObjectForInsert($object, $options), $options);
    }

    public function updateByObject(array $object, array $options = []): MetadataEntity
    {
        return $this->save($this->prepareObjectForUpdate($object, $options), $options);
    }

    public function findUser(array $p, array $options): User
    {
        $user = $this->app->getUser($options['username']);

        if (!$user) {
            $user = $this->app->getUser($p['_username']);
        }

        if (!$user) {
            throw new RuntimeException("User not found.");
        }

        return $user;
    }

    public function save(MetadataEntity $metadata, array $options): MetadataEntity
    {
        return $this->saveRaw($metadata, $options);
    }

    protected function saveRaw(MetadataEntity $metadata, array $options): MetadataEntity
    {
        $this->setDefaultOptionValues($options);

        if ($options['dispatchEvent'] === true) {
            $event = new MetadataChangeEvent($metadata, [
                'log' => $options['log'],
                'flush' => $options['flush']
            ]);
            $this->db->dispatchPreSave($event);
        }

        $this->db->persist($metadata);

        if (isset($options['flush']) && $options['flush'] === true) {
            $this->db->dispatchFlush();
        }

        if ($options['dispatchEvent'] === true) {
            $this->db->dispatchPostSave($event);
            if (isset($options['flush']) && $options['flush'] === true) {
                $this->db->dispatchFlush();
            }
        }

        return $metadata;
    }

    protected function setDefaultOptionValues(&$options): void
    {
        $options['source'] = $options['source'] ?? null;
        $options['profile'] = $options['profile'] ?? null;
        $options['username'] = $options['username'] ?? null;
        $options['public'] = $options['public'] ?? null;
        $options['dispatchEvent'] = $options['dispatchEvent'] ?? true;
        $options['log'] = $options['log'] ?? true;
        $options['flush'] = $options['flush'] ?? true;
        $options['raw'] = $options['raw'] ?? false;
    }

    public function lock(MetadataEntity $entity): static
    {
        $p = $entity->getObject();
        $p['_locked'] = true;
        $p['_lock_user'] = $this->user->getUsernameFromSession();
        $p['_lock_time'] = (new DateTime())->getTimestamp();

        $entity
            ->setLocked(true)
            ->setLockUser($this->user->getUserFromSession())
            ->setLockTime((new DateTime())->getTimestamp())
            ->setObject($p);

        $this->saveRaw($entity, ['raw' => true]);

        $this->log('success', 'lock', $entity, '%title% gesperrt.', [
            '%title%' => $entity->getTitle() !== '' ? $entity->getTitle() : 'Datensatz'
        ]);

        return $this;
    }

    public function unlock(MetadataEntity $entity): void
    {
        $p = $entity->getObject();
        $p['_locked'] = false;

        $entity
            ->setLocked(false)
            ->setObject($p);
        self::setAddressDetails($this->address, $entity);
        $this->saveRaw($entity, ['raw' => true]);

        $this->log('success', 'unlock', $entity, '%title% freigegeben.', [
            '%title%' => $entity->getTitle() !== '' ? $entity->getTitle() : 'Datensatz'
        ]);
    }

    public function delete(MetadataEntity $entity, $dispatchEvent = true, $flush = true, $log = true): void
    {
        if ($dispatchEvent) {
            $this->db->dispatchPreDelete(new MetadataChangeEvent($entity, []));
        }

        foreach ($entity->getGroups() as $group) {
            $entity->removeGroup($group);
        }

        foreach ($entity->getAddress() as $address) {
            $entity->removeAddress($address);
        }

        if ($log) {
            $this->log('success', 'delete', $entity, '%title% gelöscht.', [
                '%title%' => $entity->getTitle() !== '' ? $entity->getTitle() : 'Datensatz'
            ], $flush);
        }

        $this->db->delete($entity);

        if ($flush) {
            $this->db->dispatchFlush();
        }
    }

    public function mergeSystemInformations(array $source, array &$target): void
    {
        foreach ($source as $key => $value) {
            if (str_starts_with($key, '_')) {
                $target[$key] = $value;
            }
        }
    }

    public function log($type, $operation, $entity, $message, $params = [], $flush = true): void
    {
        $log = $this->logger->newLog();
        $log
            ->setType($type)
            ->setCategory('metadata')
            ->setSubcategory('')
            ->setOperation($operation)
            ->setSource(!is_null($entity) ? $entity->getSource() : '')
            ->setIdentifier(!is_null($entity) ? $entity->getId() : '')
            ->setMessage($message, $params);

        $this->logger->set($log, $flush);
    }

    public function generateUuid(): string
    {
        try {
            $uuid4 = Uuid::uuid4();
            return $uuid4->toString();
        } catch (Exception $e) {
            $message = 'Beim erzeugen der UUID ist ein Fehler unterlaufen';
            $log = $this->logger->newLog();
            $log
                ->setType('error')
                ->setCategory('metadata')
                ->setSubcategory('uuid')
                ->setOperation('generate')
                ->setMessage($message);

            $this->logger->set($log);

            throw new MetadataException($message);
        }
    }

    public static function addAddressDetails(Address $address, MetadataEntity &$entity): void
    {
        $p = $entity->getObject();
        $addresses = [];
        /* @var \App\Entity\Address $addrEntity */
        foreach ($entity->getAddress() as $addrEntity) {
            foreach (['distributionContact', 'responsibleParty', 'contact'] as $addressKey) {
                if (isset($p[$addressKey]) && is_array($p[$addressKey])) {
                    foreach ($p[$addressKey] as $key => $val) {
                        if (isset($p[$addressKey][$key]['id']) && (int)$p[$addressKey][$key]['id'] === $addrEntity->getId()) {
                            if (!isset($addresses[$addressKey])) {
                                $addresses[$addressKey] = [];
                            }
                            $addresses[$addressKey][$key] = $address->refreshArray($addrEntity, $p[$addressKey][$key]);
                        }
                    }
                }
            }
        }
        foreach (['distributionContact', 'responsibleParty', 'contact'] as $addressKey) {
            if (isset($p[$addressKey]) && isset($addresses[$addressKey])) {
                $p[$addressKey] = $addresses[$addressKey];
//            } elseif (isset($p[$addressKey])) {
//                $p[$addressKey] = [];
            }
        }
        $entity->setObject($p);
    }

    public static function setAddressDetails(Address $address, MetadataEntity &$metadataEntity): void
    {
        $addressEntities = [];
        $p = $metadataEntity->getObject();
        foreach (['distributionContact', 'responsibleParty', 'contact'] as $addressKey) {
            if (isset($p[$addressKey]) && is_array($p[$addressKey])) {
                for ($key = count($p[$addressKey]) - 1; $key >= 0; $key--) {
                    if (!empty($p[$addressKey][$key]['id'])) {
                        if (($addr = $address->get((int)$p[$addressKey][$key]['id']))) {
                            Metadata::addAddressToMetadata($metadataEntity,$addr, $p[$addressKey][$key], $addressEntities);
                            continue;
                        } else {
                            unset($p[$addressKey][$key]['id']);
                        }
                    }
                    if (count($p[$addressKey][$key]) === 0) {
                        unset($p[$addressKey][$key]);
                        continue;
                    }
                    $new_address = $address->prepareArray($p[$addressKey][$key]);
                    if (($addr = $address->getByUuid($address->generateUuidFromArray($new_address)))) {
                        Metadata::addAddressToMetadata($metadataEntity, $addr, $p[$addressKey][$key], $addressEntities);
                        continue;
                    }
                    // add new address for expression
                    if ($new_address['organisationName'] !== ''
                        && $new_address['country'] !== ''
                        && $new_address['administrativeArea'] !== ''
                        && $new_address['email'] !== ''
                    ) {
                        $addrList = $address->findByAttrs($p[$addressKey][$key]);
                        if (count($addrList) > 0) {
                            Metadata::addAddressToMetadata($metadataEntity, $addrList[0], $p[$addressKey][$key], $addressEntities);
                            continue;
                        }
                        $addr = $address->saveArray($new_address);
                        Metadata::addAddressToMetadata($metadataEntity, $addr, $p[$addressKey][$key], $addressEntities);
                        continue;
                    }
                    // serialise address
                    $p[$addressKey][$key] = $new_address;
//                    // add new address
//                    $addr = $address->saveArray($new_address);
//                    Metadata::addAddressToMetadata($metadataEntity, $addr, $p[$addressKey][$key], $addressEntities);
                }
            }
        }
        foreach ($metadataEntity->getAddress() as $addr) {
            if (!in_array($addr, $addressEntities)) {
                $metadataEntity->removeAddress($addr);
            }
        }
        $metadataEntity->setObject($p);
    }

    private static function addAddressToMetadata(MetadataEntity &$metadataEntity, AddressEntity $addressEntity, array &$p, array &$addressEntities): array
    {
        $metadataEntity->addAddress($addressEntity);

        $res = ['id' => $addressEntity->getId()];
        if (isset($p['role'])) {
            $res['role'] = $p['role'];
        }
        if (!in_array($addressEntity, $addressEntities)) {
            $addressEntities[] = $addressEntity;
        }
        $p = $res;
        return $res;
    }

    public function handleInheritance(array &$childObject, array $parentObject)
    {
        if (!$this->hasDataToBeInherited($parentObject)) {
            return $this;
        }

        $childObject['_inheritance'] = $parentObject['_inheritance'];

        foreach ($parentObject['_inheritance'] as $key => $value) {
            if (substr($key, 0, 8) === 'keywords') {
                $key = substr($key, 8);

                if (!isset($parentObject['keywords']) || !isset($parentObject['keywords'][$key])) {
                    continue;
                }

                $childObject['keywords'][$key] = $parentObject['keywords'][$key];
                continue;
            }

            if (!isset($parentObject[$key])) {
                continue;
            }

            $childObject[$key] = $parentObject[$key];
        }

        return $this;
    }

    public function hasDataToBeInherited(array $p): bool
    {
        return (isset($p['_inheritance']) && is_array($p['_inheritance']) && !empty($p['_inheritance']));
    }
}
