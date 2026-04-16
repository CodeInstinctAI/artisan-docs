<?php

namespace CodeInstinct\ArtisanDocs\Commands;

use CodeInstinct\ArtisanDocs\CommandInspector;
use CodeInstinct\ArtisanDocs\Generators\GeneratorContract;
use CodeInstinct\ArtisanDocs\Generators\HtmlGenerator;
use CodeInstinct\ArtisanDocs\Generators\JsonGenerator;
use CodeInstinct\ArtisanDocs\Generators\MarkdownGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class GenerateDocsCommand extends Command
{
    protected $signature = 'docs:commands
        {--format=        : Output format: markdown, html, json (default from config)}
        {--output=        : File path to write output (default from config)}
        {--namespace=     : Only document commands in this namespace (e.g. make)}
        {--only-custom    : Only include application-defined commands}
        {--exclude-vendor : Exclude commands from vendor packages}
        {--include-hidden : Include hidden commands}
        {--check          : Compare output against existing file; exit 1 if out of sync}';

    protected $description = 'Generate documentation for all registered Artisan commands.';

    public function __construct(private CommandInspector $inspector)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $format = $this->option('format') ?: config('artisan-docs.default_format', 'markdown');
        $output = $this->option('output')
            ?? ($this->option('format') !== null ? "docs/commands.{$format}" : null)
            ?? config('artisan-docs.default_output', 'docs/commands.md');
        $title = config('artisan-docs.title', 'Artisan Command Reference');

        $commands = $this->gatherCommands();

        if (empty($commands)) {
            $this->warn('No commands matched the given filters.');

            return self::SUCCESS;
        }

        $groups = $this->groupCommands($commands);
        $generator = $this->resolveGenerator($format);
        $content = $generator->generate($groups, $title);

        if ($this->option('check')) {
            return $this->runCheck($content, $output);
        }

        $this->writeOutput($content, $output);

        $total = array_sum(array_map('count', $groups));
        $this->info("✅ Documentation generated: <comment>{$output}</comment> ({$total} commands, ".count($groups).' groups)');

        return self::SUCCESS;
    }

    /**
     * Retrieve all registered Artisan commands and apply filters.
     *
     * @return array<int, array<string, mixed>>
     */
    private function gatherCommands(): array
    {
        $allCommands = Artisan::all();
        $excludedNamespaces = config('artisan-docs.excluded_namespaces', []);
        $excludedCommands = config('artisan-docs.excluded_commands', []);
        $includeHidden = $this->option('include-hidden') || config('artisan-docs.include_hidden', false);
        $includeVendor = ! $this->option('exclude-vendor') && config('artisan-docs.include_vendor', true);
        $onlyCustom = $this->option('only-custom');
        $namespaceFilter = $this->option('namespace');
        $appPaths = config('artisan-docs.app_command_paths', ['App\\Console\\Commands\\']);

        $results = [];

        foreach ($allCommands as $command) {
            $meta = $this->inspector->inspect($command);

            // Hidden filter
            if ($meta['hidden'] && ! $includeHidden) {
                continue;
            }

            // Excluded namespaces
            if ($meta['namespace'] && in_array($meta['namespace'], $excludedNamespaces, true)) {
                continue;
            }

            // Excluded commands
            if (in_array($meta['name'], $excludedCommands, true)) {
                continue;
            }

            // Namespace filter
            if ($namespaceFilter && $meta['namespace'] !== $namespaceFilter) {
                continue;
            }

            // Vendor filter
            if (! $includeVendor && $this->inspector->isVendorCommand($command)) {
                continue;
            }

            // Only-custom filter (overrides vendor filter)
            if ($onlyCustom && ! $this->inspector->isApplicationCommand($command, $appPaths)) {
                continue;
            }

            $results[] = $meta;
        }

        usort($results, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $results;
    }

    /**
     * Group commands by their resolved display group label.
     *
     * @param  array<int, array<string, mixed>>  $commands
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupCommands(array $commands): array
    {
        $configGroups = config('artisan-docs.groups', []);
        $groups = [];

        foreach ($commands as $command) {
            $ns = $command['namespace'];
            $group = $configGroups[$ns] ?? ($ns ? ucfirst($ns) : 'General');

            $command['group'] = $group;
            $groups[$group][] = $command;
        }

        ksort($groups);

        return $groups;
    }

    private function resolveGenerator(string $format): GeneratorContract
    {
        return match (strtolower($format)) {
            'html' => new HtmlGenerator(config('artisan-docs.html_template', 'artisan-docs::commands')),
            'json' => new JsonGenerator,
            default => new MarkdownGenerator,
        };
    }

    private function writeOutput(string $content, string $path): void
    {
        $absolutePath = str_starts_with($path, '/')
            ? $path
            : base_path($path);

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, $content);
    }

    /**
     * Compare generated content against the existing file on disk.
     * Exits with code 1 when the file is missing or differs.
     */
    private function runCheck(string $content, string $path): int
    {
        $absolutePath = str_starts_with($path, '/') ? $path : base_path($path);

        if (! File::exists($absolutePath)) {
            $this->error("❌ Check failed: documentation file does not exist at [{$path}].");
            $this->line('   Run <comment>php artisan docs:commands</comment> to generate it.');

            return self::FAILURE;
        }

        $existing = File::get($absolutePath);

        if (trim($existing) === trim($content)) {
            $this->info('✅ Documentation is up-to-date.');

            return self::SUCCESS;
        }

        $this->error('❌ Check failed: documentation is out of sync with current command structure.');
        $this->line('   Run <comment>php artisan docs:commands</comment> to regenerate it.');

        return self::FAILURE;
    }
}
