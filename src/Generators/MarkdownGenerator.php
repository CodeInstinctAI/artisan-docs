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
                    $lines[] = $this->sanitiseBlock($command['description']);
                    $lines[] = '';
                }

                if ($command['help']) {
                    $lines[] = $this->sanitiseBlock($command['help']);
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
                        $default = $arg['default'] !== null
                            ? '`'.$this->escapeTableCell((string) json_encode($arg['default'])).'`'
                            : '—';
                        $desc = $arg['description'] ? $this->escapeTableCell($arg['description']) : '—';
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
                            ? '`'.$this->escapeTableCell((string) json_encode($opt['default'])).'`'
                            : '—';
                        $desc = $opt['description'] ? $this->escapeTableCell($opt['description']) : '—';
                        $lines[] = "| `{$opt['name']}` | {$shortcut} | {$required} | {$desc} | {$default} |";
                    }
                    $lines[] = '';
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Sanitise a block-level text value (description, help text).
     *
     * Strips dangerous inline HTML – specifically tags whose inner content is
     * also executable or privacy-invasive (<script>, <style>, <iframe>,
     * <object>, <embed>, <link>, <meta>) – and then removes all remaining
     * HTML markup, keeping only the plain text.
     */
    private function sanitiseBlock(string $value): string
    {
        // Remove dangerous paired tags together with their content.
        $value = preg_replace(
            '#<(script|style|iframe|object|embed|link|meta)\b[^>]*>.*?</\1>#is',
            '',
            $value
        ) ?? $value;

        // Strip any remaining HTML tags, preserving inner text.
        return strip_tags($value);
    }

    /**
     * Sanitise a value destined for a GFM table cell.
     *
     * – Collapses CR/LF sequences to a single space (newlines break table rows).
     * – Escapes literal pipe characters so they don't terminate the cell.
     * – Strips all HTML markup (tags only; content is kept).
     */
    private function escapeTableCell(string $value): string
    {
        // Collapse newlines to a space.
        $value = (string) preg_replace('/\r\n|\r|\n/', ' ', $value);

        // Strip HTML tags (content preserved).
        $value = strip_tags($value);

        // Escape pipe characters that would break the GFM table structure.
        return str_replace('|', '\|', $value);
    }
}
