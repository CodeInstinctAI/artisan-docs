<?php

namespace CodeInstinct\ArtisanDocs;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CommandInspector
{
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
            'options' => $this->inspectOptions($definition->getOptions()),
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
                'default' => $argument->getDefault(),
            ];
        }

        return $results;
    }

    /**
     * Inspect and normalise all options from a command definition.
     *
     * @param  InputOption[]  $options
     * @return array<int, array<string, mixed>>
     */
    private function inspectOptions(array $options): array
    {
        $results = [];
        $internal = ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'env'];

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
                'default' => $option->getDefault(),
            ];
        }

        return $results;
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
     */
    public function isVendorCommand(SymfonyCommand $command): bool
    {
        try {
            $file = (new \ReflectionClass($command))->getFileName();
        } catch (\ReflectionException) {
            return false;
        }

        return $file && str_contains(str_replace('\\', '/', $file), '/vendor/');
    }
}
