<?php

namespace App\Entity;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class Plugin
{
    #[Assert\File(
        maxSize: '3072k',
        mimeTypes: [
            'application/zip',
            'application/x-zip',
            'application/octet-stream',
            'application/x-zip-compressed',
        ],
        mimeTypesMessage: 'Bitte Metador2 Plugin hochladen.'
    )]
    protected ?UploadedFile $attachment = null;

    public function getAttachment(): ?UploadedFile
    {
        return $this->attachment;
    }

    public function setAttachment(?UploadedFile $attachment): static
    {
        $this->attachment = $attachment;

        return $this;
    }
}
