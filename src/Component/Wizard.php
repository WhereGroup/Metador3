<?php

namespace App\Component;

class Wizard
{
    public function getExamples($path): array
    {
        $examples = [];

        foreach (scandir($path) as $file) {
            if ($file !== '.' && $file !== '..' && is_dir($path . $file)) {
                $array = [];

                foreach (scandir($path . $file) as $subFile) {
                    if (!str_ends_with($subFile, ".json")) {
                        continue;
                    }

                    $array = array_merge($array, json_decode(file_get_contents($path . $file . '/' . $subFile), true));
                }

                $examples[$file] = $array;

                continue;
            } elseif (!str_ends_with($file, ".json")) {
                continue;
            }

            $examples[substr($file, 0, -5)]
                = json_decode(file_get_contents($path . $file), true);
        }

        return $examples;
    }
}
