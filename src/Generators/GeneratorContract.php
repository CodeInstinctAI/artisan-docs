<?php

namespace CodeInstinct\ArtisanDocs\Generators;

interface GeneratorContract
{
    /**
     * Generate documentation content from grouped command data.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $groups  Keyed by group label.
     * @param  string  $title  Document title.
     * @return string The rendered documentation content.
     */
    public function generate(array $groups, string $title): string;
}
