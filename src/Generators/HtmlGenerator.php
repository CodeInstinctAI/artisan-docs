<?php

namespace CodeInstinct\ArtisanDocs\Generators;

use Illuminate\Support\Facades\View;

class HtmlGenerator implements GeneratorContract
{
    /**
     * The Blade view name to use for rendering.
     */
    private string $template;

    public function __construct(string $template = 'artisan-docs::commands')
    {
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $groups, string $title): string
    {
        return View::make($this->template, [
            'title' => $title,
            'groups' => $groups,
            'generatedAt' => now()->toDateTimeString(),
            'total' => array_sum(array_map('count', $groups)),
        ])->render();
    }
}
