<?php

namespace App\Subscriber\Manual;

use App\Event\ManualEvent;
use App\Service\Plugin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ManualSubscriber
 * @package App\Subscriber\Manual
 */
final class ManualSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Plugin $plugin)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'manual.loading' => ['onManualLoading', 0],
        ];
    }

    public function onManualLoading(ManualEvent $event): void
    {
        if (!$this->plugin->isActive('metador_documentation')) {
            return;
        }

        $event
            // @codingStandardsIgnoreStart
            ->add('Anwenderhandbuch', 'Allgemein', 'MetadorManual', 'general', null)
            ->add('Anwenderhandbuch', 'Startseite', 'MetadorManual', 'home', null)
            ->add('Anwenderhandbuch', 'Arbeiten mit der Karte', 'MetadorManual', 'map', null)
            ->add('Anwenderhandbuch', 'Nach der Anmeldung', 'MetadorManual', 'after-login', null)
            ->add('Anwenderhandbuch', 'Metadaten', 'MetadorManual', 'metadata', 'ROLE_SYSTEM_USER')
            ->add('Anwenderhandbuch', 'Metadaten - Formulare', 'MetadorManual', 'metadata-form', 'ROLE_SYSTEM_USER')
            ->add('Anwenderhandbuch', 'Metadaten - Hilfetexte', 'MetadorManual', 'metadata-help', 'ROLE_SYSTEM_USER')
            ->add('Anwenderhandbuch', 'Metadaten - Auswahllisten', 'MetadorManual', 'metadata-list', 'ROLE_SYSTEM_USER')
            ->add('Anwenderhandbuch', 'Metadaten - Suche', 'MetadorManual', 'search', null)
            ->add('Administration', 'Allgemein', 'MetadorManual', 'admin', 'ROLE_SYSTEM_GEO_OFFICE')
            ->add('Administration', 'Adressen', 'MetadorManual', 'address', 'ROLE_SYSTEM_GEO_OFFICE')
            ->add('Administration', 'Benutzer/Gruppen', 'MetadorManual', 'user', 'ROLE_SYSTEM_SUPERUSER')
            ->add('Administration', 'Plugins', 'MetadorManual', 'plugin', 'ROLE_SYSTEM_SUPERUSER')
            ->add('Entwicklung', 'Handbucheintrag', 'MetadorManual', 'dev-manual', null, 'dev')
            ->add('Entwicklung', 'Pluginentwicklung', 'MetadorManual', 'dev-plugin', null, 'dev')
            ->add('Entwicklung', 'Profilentwicklung', 'MetadorManual', 'dev-profile', null, 'dev')
            // @codingStandardsIgnoreEnd
        ;
    }
}

