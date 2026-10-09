<?php

namespace App\Service\Import;

use App\Service\Plugin;
use Symfony\Component\HttpKernel\KernelInterface;

interface MetadataImportInterface
{
    public function __construct(KernelInterface $kernel, Plugin $plugin);
    public function load($xml, $pluginClassName);
    public function loadWMS($xml);
    public function loadWFS($xml);
}
