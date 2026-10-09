<?php
namespace App\Component\Map;

use App\Repository\WmsRepository;
use App\Service\Browser;
use Doctrine\ORM\EntityManagerInterface;

interface MapInterface
{
    public function __construct(EntityManagerInterface $em, Browser $browse, WmsRepository $wmsRepository);
    public function __destruct();
}
