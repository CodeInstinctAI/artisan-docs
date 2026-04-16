<?php

namespace CodeInstinct\ArtisanDocs\Generators;

class MarkdownGenerator implements GeneratorContract
{
    /**
     * {@inheritdoc}
     */
    public function generate(array $groups, string $title): string
    {
        $lines = [];

        $lines[] = "# {$title}";
        $lines[] = '';
        $lines[] = '> Auto-generated on '.now()->toDateTimeString();
        $lines[] = '';

        // Table of contents
        $lines[] = '## Table of Contents';
        $lines[] = '';
        foreach (array_keys($groups) as $group) {
            $anchor = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $group));
            $lines[] = "- [{$group}](#{$anchor})";
        }
        $lines[] = '';

        foreach ($groups as $group => $commands) {
            $lines[] = '---';
            $lines[] = '';
            $lines[] = "## {$group}";
            $lines[] = '';

            foreach ($commands as $command) {
                $lines[] = "### `{$command['name']}`";
                $lines[] = '';

                if ($command['description']) {
                    $lines[] = $command['description'];
                    $lines[] = '';
                }

                if ($command['help']) {
                    $lines[] = $command['help'];
                    $lines[] = '';
                }

                if (! empty($command['aliases'])) {
                    $lines[] = '**Aliases:** '.implode(', ', array_map(fn ($a) => "`{$a}`", $command['aliases']));
                    $lines[] = '';
                }

                if (! empty($command['arguments'])) {
                    $lines[] = '#### Arguments';
                    $lines[] = '';
                    $lines[] = '| Argument | Required | Description | Default |';
                    $lines[] = '|----------|----------|-------------|---------|';
                    foreach ($command['arguments'] as $arg) {
                        $required = $arg['required'] ? '✅ Yes' : 'No';
                        $default = $arg['default'] !== null ? '`'.json_encode($arg['default']).'`' : '—';
                        $desc = $arg['description'] ?: '—';
                        $lines[] = "| `{$arg['name']}` | {$required} | {$desc} | {$default} |";
                    }
                    $lines[] = '';
                }

                if (! empty($command['options'])) {
                    $lines[] = '#### Options';
                    $lines[] = '';
                    $lines[] = '| Option | Shortcut | Required | Description | Default |';
                    $lines[] = '|--------|----------|----------|-------------|---------|';
                    foreach ($command['options'] as $opt) {
                        $shortcut = $opt['shortcut'] ?? '—';
                        $required = $opt['required'] ? '✅ Yes' : 'No';
                        $default = $opt['default'] !== null && $opt['default'] !== false
                            ? '`'.json_encode($opt['default']).'`'
                            : '—';
                        $desc = $opt['description'] ?: '—';
                        $lines[] = "| `{$opt['name']}` | {$shortcut} | {$required} | {$desc} | {$default} |";
                    }
                    $lines[] = '';
                }
            }
        }

        return implode("\n", $lines);
    }
}
