<?php

namespace CodeInstinct\ArtisanDocs;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CommandInspector
{
    /**
     * Regex pattern for argument / option names whose default values should be
     * redacted from generated documentation to prevent accidental secret leakage.
     */
    private const SENSITIVE_KEYWORD_PATTERN = '/(password|passwd|secret|token|api[_\-]?key|auth[_\-]?key|private[_\-]?key|credential|apikey)/i';

    /**
     * Sentinel value written in place of a redacted default.
     */
    public const REDACTED_VALUE = '[ REDACTED ]';

    /**
     * Hardcoded fallback list of Symfony / Artisan global option names that should
     * always be stripped from per-command documentation.  Used only when the command
     * has no bound Application (e.g. bare unit-test stubs).
     *
     * @var string[]
     */
    private const FALLBACK_INTERNAL_OPTIONS = [
        'help', 'quiet', 'verbose', 'version',
        'ansi', 'no-ansi', 'no-interaction', 'env',
    ];

    /**
     * Inspect a single command and return structured metadata.
     *
     * @return array<string, mixed>
     */
    public function inspect(SymfonyCommand $command): array
    {
        $definition = $command->getDefinition();

        return [
            'name' => $command->getName(),
            'description' => $command->getDescription(),
            'help' => $this->resolveHelp($command),
            'hidden' => $command->isHidden(),
            'aliases' => $command->getAliases(),
            'namespace' => $this->extractNamespace($command->getName()),
            'group' => null, // resolved later by GenerateDocsCommand
            'arguments' => $this->inspectArguments($definition->getArguments()),
            'options' => $this->inspectOptions($definition->getOptions(), $command),
        ];
    }

    /**
     * Inspect and normalise all arguments from a command definition.
     *
     * @param  InputArgument[]  $arguments
     * @return array<int, array<string, mixed>>
     */
    private function inspectArguments(array $arguments): array
    {
        $results = [];

        foreach ($arguments as $argument) {
            $results[] = [
                'name' => $argument->getName(),
                'description' => $argument->getDescription(),
                'required' => $argument->isRequired(),
                'is_array' => $argument->isArray(),
                'default' => $this->redactSensitiveDefault(
                    $argument->getName(),
                    $argument->getDefault()
                ),
            ];
        }

        return $results;
    }

    /**
     * Inspect and normalise all options from a command definition.
     *
     * Global / internal options (--help, --quiet, etc.) are resolved dynamically
     * from the command's bound Application; a hardcoded fallback is used when no
     * application is available (e.g. in unit-test stubs).
     *
     * @param  InputOption[]  $options
     * @return array<int, array<string, mixed>>
     */
    private function inspectOptions(array $options, SymfonyCommand $command): array
    {
        $internal = $this->resolveInternalOptionNames($command);
        $results = [];

        foreach ($options as $option) {
            if (in_array($option->getName(), $internal, true)) {
                continue;
            }

            $results[] = [
                'name' => '--'.$option->getName(),
                'shortcut' => $option->getShortcut() ? '-'.$option->getShortcut() : null,
                'description' => $option->getDescription(),
                'required' => $option->isValueRequired(),
                'is_array' => $option->isArray(),
                'accepts_value' => ! $option->isValueOptional() && ! $option->isNegatable()
                    ? $option->isValueRequired()
                    : ! ($option->getDefault() === false && ! $option->acceptValue()),
                'default' => $this->redactSensitiveDefault(
                    $option->getName(),
                    $option->getDefault()
                ),
            ];
        }

        return $results;
    }

    /**
     * Resolve the names of global / internal options that should be stripped from
     * per-command documentation.
     *
     * When the command is bound to a Symfony Application the list is derived
     * directly from the application's input definition, which future-proofs the
     * package against new global flags added by Symfony or Laravel.  When no
     * application is available the hardcoded fallback list is used.
     *
     * @return string[]
     */
    private function resolveInternalOptionNames(SymfonyCommand $command): array
    {
        if ($app = $command->getApplication()) {
            return array_keys($app->getDefinition()->getOptions());
        }

        return self::FALLBACK_INTERNAL_OPTIONS;
    }

    /**
     * Redact the default value when the argument / option name matches a
     * pattern associated with sensitive credentials or secrets.
     */
    private function redactSensitiveDefault(string $name, mixed $default): mixed
    {
        // Nothing to redact when there is no meaningful default.
        if ($default === null || $default === false || $default === '') {
            return $default;
        }

        if (preg_match(self::SENSITIVE_KEYWORD_PATTERN, $name)) {
            return self::REDACTED_VALUE;
        }

        return $default;
    }

    /**
     * Extract the namespace portion from a command name.
     * E.g. "make:model" → "make", "list" → null.
     */
    public function extractNamespace(?string $name): ?string
    {
        if (! $name || ! str_contains($name, ':')) {
            return null;
        }

        return explode(':', $name, 2)[0];
    }

    /**
     * Get the help text, falling back to the description when empty.
     */
    private function resolveHelp(SymfonyCommand $command): string
    {
        $help = $command->getHelp();

        if ($help && $help !== $command->getDescription()) {
            return $help;
        }

        return '';
    }

    /**
     * Determine whether a command belongs to the application (not a vendor package).
     *
     * @param  array<string>  $appCommandPaths
     */
    public function isApplicationCommand(SymfonyCommand $command, array $appCommandPaths): bool
    {
        $class = get_class($command);

        foreach ($appCommandPaths as $path) {
            if (str_starts_with($class, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether a command is from a vendor package.
     *
     * Uses absolute path-prefix matching against the project's real vendor
     * directory instead of a substring search, which would produce false
     * positives for projects whose root path happens to contain "/vendor/".
     */
    public function isVendorCommand(SymfonyCommand $command): bool
    {
        try {
            $file = (new \ReflectionClass($command))->getFileName();
        } catch (\ReflectionException) {
            return false;
        }

        if (! $file) {
            return false;
        }

        // Normalise both paths to forward slashes for cross-platform safety.
        $vendorPrefix = rtrim(str_replace('\\', '/', base_path('vendor')), '/').'/';
        $normalizedFile = str_replace('\\', '/', $file);

        return str_starts_with($normalizedFile, $vendorPrefix);
    }
}
