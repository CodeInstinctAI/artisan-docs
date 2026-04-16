<?php

namespace CodeInstinct\ArtisanDocs\Generators;

class JsonGenerator implements GeneratorContract
{
    /**
     * {@inheritdoc}
     */
    public function generate(array $groups, string $title): string
    {
        $payload = [
            'title' => $title,
            'generated_at' => now()->toIso8601String(),
            'total' => array_sum(array_map('count', $groups)),
            'groups' => [],
        ];

        foreach ($groups as $group => $commands) {
            $payload['groups'][] = [
                'name' => $group,
                'commands' => array_values($commands),
            ];
        }

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
