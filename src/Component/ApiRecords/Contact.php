<?php

namespace App\Component\ApiRecords;

use App\Entity\Records;

class Contact
{
    private $records;

    public function __construct(Records $records)
    {
        $this->records = $records;
    }

    public function generateForService(): array
    {
        $contact = [
            'name' => $this->records->getIndividualName(),
            'organization' => $this->records->getProviderName()
        ];
//        $this->addProperty($contact, 'logo', $this->records->getPositionName());
        $this->addPhonesAndEmails($contact);
        $this->addAddress($contact);
        if ($this->records->getPositionName()) {
            $this->addProperty($contact, 'roles', [$this->records->getPositionName()]);
        }
        return $contact;
    }

    private function addProperty(array &$contact, string $key, $value): void
    {
        if ($value) {
            $contact[$key] = $value;
        }
    }

    private function addPhonesAndEmails(array &$contact): void
    {
        $phones = [];
        if ($this->records->getPhoneVoice()) {
            $phones[] = [
                'value' => $this->records->getPhoneVoice(),
//                'roles' => ['work']
            ];
        }
        if ($this->records->getPhoneFacsimile()) {
            $phones[] = [
                'value' => $this->records->getPhoneFacsimile(),
                'roles' => ['fax']
            ];
        }

        if (count($phones) > 0) {
            $this->addProperty($contact, 'phones', $phones);
        }

        if ($this->records->getElectronicMailAddress()) {
            $this->addProperty($contact, 'emails', [['value' => $this->records->getElectronicMailAddress()]]);
        }
    }

    private function addAddress(array &$contact)
    {
        $address = [];
        $this->addProperty($address, 'deliveryPoint', $this->records->getDeliveryPoint());
        $this->addProperty($address, 'city', $this->records->getCity());
        $this->addProperty($address, 'administrativeArea', $this->records->getAdministrativeArea());
        $this->addProperty($address, 'postalCode', $this->records->getPostalCode());
        $this->addProperty($address, 'country', $this->records->getCountry());
//        $this->addProperty($address, 'roles', [$this->records->getPositionName()]);
        if (count($address) > 0) {
            $this->addProperty($address, 'addresses', [$address]);
        }
    }
}
