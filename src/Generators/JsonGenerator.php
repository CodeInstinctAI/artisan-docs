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

        // JSON_HEX_TAG / JSON_HEX_AMP / JSON_HEX_APOS / JSON_HEX_QUOT ensure that
        // HTML-sensitive characters are unicode-escaped so the output is safe when
        // embedded in an HTML page or served with an incorrect Content-Type header.
        return json_encode(
            $payload,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );
    }
}
