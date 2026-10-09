<?php

namespace App\Twig;

use App\Service\Configuration;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ListsExtension extends AbstractExtension
{
    public function __construct(private readonly Configuration $configuration)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_list_options', $this->getListOptions(...), ['is_safe' => ['html']]),
        ];
    }

    public function getListOptions(
        string $profile,
        string $key,
        array $default = [],
        ?string $sort = null,
        ?string $direction = null,
    ): array {
        $key = preg_replace('/(_[\d]+_?)/s', '', $key);

        $options = $this->configuration->get($key, 'list-option', $profile);

        if ($options === null) {
            // Save Template
            if (!empty($default) && is_array($default[key($default)] ?? null)) {
                $this->configuration->set(
                    $key,
                    array_keys($default[key($default)]),
                    'list-option-template',
                    $profile
                );
            }

            $options = $default;
            $this->configuration->set($key, $options, 'list-option', $profile);
        }

        if ($sort) {
            array_multisort(
                array_map('strtolower', $options), // ignore case
                ($direction === 'desc') ? SORT_DESC : SORT_ASC,
                ($sort === 'numeric') ? SORT_NUMERIC : SORT_STRING,
                $options
            );
        }

        return $options;
    }

    public function getName(): string
    {
        return 'metador_lists';
    }
}
