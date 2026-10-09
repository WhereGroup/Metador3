<?php

namespace App\Twig;

use App\Service\Plugin;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ValidationExtension extends AbstractExtension
{
    public function __construct(
        private readonly Plugin $plugin,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'read_validation_rules', $this->readValidationRules(...), ['is_safe' => ['html']]
            ),
        ];
    }

    public function readValidationRules(string $profile): string
    {
        $file = $this->plugin->getPath($profile, '/Resources/validation.json');

        if (!file_exists($file)) {
            return json_encode([], JSON_FORCE_OBJECT);
        }

        $validationRules = json_decode(file_get_contents($file), true);

        if (!is_array($validationRules)) {
            throw new \Exception('Can not parse validation.json file.');
        }

        foreach ($validationRules as $key => $rules) {
            foreach ($rules as $i => $rule) {
                if (isset($rule['message'])) {
                    $validationRules[$key][$i]['message'] = $this->translator->trans($rule['message']);
                }
            }
        }

        return json_encode($validationRules, JSON_FORCE_OBJECT);
    }

    public function getName(): string
    {
        return 'metador_basic_profile_validation';
    }
}
