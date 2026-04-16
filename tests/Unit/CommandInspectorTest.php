<?php

namespace CodeInstinct\ArtisanDocs\Tests\Unit;

use CodeInstinct\ArtisanDocs\CommandInspector;
use CodeInstinct\ArtisanDocs\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CommandInspectorTest extends TestCase
{
    private CommandInspector $inspector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inspector = new CommandInspector;
    }

    #[Test]
    public function it_extracts_basic_metadata_from_a_command(): void
    {
        $command = new class extends SymfonyCommand
        {
            protected function configure(): void
            {
                $this->setName('test:command')
                    ->setDescription('A test command')
                    ->setHelp('Detailed help text for the test command.')
                    ->setAliases(['test:cmd']);
            }

            protected function execute($input, $output): int
            {
                return 0;
            }
        };

        $meta = $this->inspector->inspect($command);

        $this->assertSame('test:command', $meta['name']);
        $this->assertSame('A test command', $meta['description']);
        $this->assertSame('Detailed help text for the test command.', $meta['help']);
        $this->assertFalse($meta['hidden']);
        $this->assertSame(['test:cmd'], $meta['aliases']);
        $this->assertSame('test', $meta['namespace']);
    }

    #[Test]
    public function it_extracts_arguments_correctly(): void
    {
        $command = new class extends SymfonyCommand
        {
            protected function configure(): void
            {
                $this->setName('test:args')
                    ->addArgument('name', InputArgument::REQUIRED, 'The name argument')
                    ->addArgument('type', InputArgument::OPTIONAL, 'An optional type', 'default-type');
            }

            protected function execute($input, $output): int
            {
                return 0;
            }
        };

        $meta = $this->inspector->inspect($command);

        $this->assertCount(2, $meta['arguments']);

        $this->assertSame('name', $meta['arguments'][0]['name']);
        $this->assertTrue($meta['arguments'][0]['required']);
        $this->assertSame('The name argument', $meta['arguments'][0]['description']);
        $this->assertNull($meta['arguments'][0]['default']);

        $this->assertSame('type', $meta['arguments'][1]['name']);
        $this->assertFalse($meta['arguments'][1]['required']);
        $this->assertSame('default-type', $meta['arguments'][1]['default']);
    }

    #[Test]
    public function it_extracts_options_and_filters_internal_options(): void
    {
        $command = new class extends SymfonyCommand
        {
            protected function configure(): void
            {
                $this->setName('test:options')
                    ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the operation')
                    ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Result limit', 10);
            }

            protected function execute($input, $output): int
            {
                return 0;
            }
        };

        $meta = $this->inspector->inspect($command);

        // Internal options (help, quiet, verbose, version, ansi, no-ansi, no-interaction, env) should be stripped.
        $optionNames = array_column($meta['options'], 'name');
        $this->assertContains('--force', $optionNames);
        $this->assertContains('--limit', $optionNames);
        $this->assertNotContains('--help', $optionNames);
        $this->assertNotContains('--verbose', $optionNames);

        $limitOpt = collect($meta['options'])->firstWhere('name', '--limit');
        $this->assertSame('-l', $limitOpt['shortcut']);
        $this->assertSame(10, $limitOpt['default']);
        $this->assertTrue($limitOpt['required']);
    }

    #[Test]
    public function it_returns_null_namespace_for_top_level_commands(): void
    {
        $this->assertNull($this->inspector->extractNamespace('list'));
        $this->assertNull($this->inspector->extractNamespace(''));
        $this->assertNull($this->inspector->extractNamespace(null));
        $this->assertSame('make', $this->inspector->extractNamespace('make:model'));
        $this->assertSame('queue', $this->inspector->extractNamespace('queue:work'));
    }

    #[Test]
    public function it_identifies_vendor_commands_by_file_path(): void
    {
        $appCommand = new class extends SymfonyCommand
        {
            protected function configure(): void
            {
                $this->setName('app:test');
            }

            protected function execute($input, $output): int
            {
                return 0;
            }
        };

        // Anonymous classes defined in tests won't reside in /vendor/, so this should be false.
        $this->assertFalse($this->inspector->isVendorCommand($appCommand));
    }

    #[Test]
    public function it_identifies_application_commands_by_namespace(): void
    {
        $appCommand = new class extends SymfonyCommand
        {
            protected function configure(): void
            {
                $this->setName('app:hello');
            }

            protected function execute($input, $output): int
            {
                return 0;
            }
        };

        // The anonymous class won't match App\Console\Commands\, so this is false.
        $this->assertFalse($this->inspector->isApplicationCommand($appCommand, ['App\\Console\\Commands\\']));
    }

    #[Test]
    public function it_does_not_duplicate_help_when_equal_to_description(): void
    {
        $command = new class extends SymfonyCommand
        {
            protected function configure(): void
            {
                $this->setName('test:nodupe')
                    ->setDescription('Same text')
                    ->setHelp('Same text');
            }

            protected function execute($input, $output): int
            {
                return 0;
            }
        };

        $meta = $this->inspector->inspect($command);
        $this->assertSame('', $meta['help']);
    }
}
