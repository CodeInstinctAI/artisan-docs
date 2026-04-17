<?php

namespace CodeInstinct\ArtisanDocs\Tests\Unit;

use CodeInstinct\ArtisanDocs\CommandInspector;
use CodeInstinct\ArtisanDocs\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Application as SymfonyApplication;
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

        // Internal options are stripped using the fallback list when no Symfony
        // Application is bound (the anonymous class above has none).
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

    // -------------------------------------------------------------------------
    // Sensitive-default redaction
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function sensitiveOptionNameProvider(): array
    {
        return [
            'password' => ['password'],
            'passwd' => ['passwd'],
            'secret' => ['secret'],
            'token' => ['token'],
            'api_key' => ['api_key'],
            'api-key' => ['api-key'],
            'apikey' => ['apikey'],
            'auth_key' => ['auth_key'],
            'private_key' => ['private_key'],
            'credential' => ['credential'],
            'db_password' => ['db_password'],
            'api_token' => ['api_token'],
        ];
    }

    #[Test]
    #[DataProvider('sensitiveOptionNameProvider')]
    public function it_redacts_sensitive_defaults_in_options(string $optionName): void
    {
        $command = new class($optionName) extends SymfonyCommand
        {
            public function __construct(private string $optName)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->setName('test:sensitive')
                    ->addOption($this->optName, null, InputOption::VALUE_OPTIONAL, 'Sensitive', 'super-secret-value');
            }

            protected function execute($input, $output): int
            {
                return 0;
            }
        };

        $meta = $this->inspector->inspect($command);

        $opt = collect($meta['options'])->firstWhere('name', "--{$optionName}");
        $this->assertNotNull($opt, "Option --{$optionName} should be present in metadata");
        $this->assertSame(CommandInspector::REDACTED_VALUE, $opt['default']);
    }

    #[Test]
    public function it_redacts_sensitive_defaults_in_arguments(): void
    {
        $command = new class extends SymfonyCommand
        {
            protected function configure(): void
            {
                $this->setName('test:args-sensitive')
                    ->addArgument('password', InputArgument::OPTIONAL, 'A password arg', 'hunter2');
            }

            protected function execute($input, $output): int
            {
                return 0;
            }
        };

        $meta = $this->inspector->inspect($command);

        $arg = collect($meta['arguments'])->firstWhere('name', 'password');
        $this->assertNotNull($arg);
        $this->assertSame(CommandInspector::REDACTED_VALUE, $arg['default']);
    }

    #[Test]
    public function it_does_not_redact_non_sensitive_defaults(): void
    {
        $command = new class extends SymfonyCommand
        {
            protected function configure(): void
            {
                $this->setName('test:safe-defaults')
                    ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit', 100)
                    ->addArgument('environment', InputArgument::OPTIONAL, 'Env', 'production');
            }

            protected function execute($input, $output): int
            {
                return 0;
            }
        };

        $meta = $this->inspector->inspect($command);

        $opt = collect($meta['options'])->firstWhere('name', '--limit');
        $this->assertSame(100, $opt['default']);

        $arg = collect($meta['arguments'])->firstWhere('name', 'environment');
        $this->assertSame('production', $arg['default']);
    }

    #[Test]
    public function it_does_not_redact_null_or_false_defaults_even_for_sensitive_names(): void
    {
        $command = new class extends SymfonyCommand
        {
            protected function configure(): void
            {
                $this->setName('test:null-sensitive')
                    ->addOption('password', null, InputOption::VALUE_NONE, 'Flag only');
            }

            protected function execute($input, $output): int
            {
                return 0;
            }
        };

        $meta = $this->inspector->inspect($command);

        $opt = collect($meta['options'])->firstWhere('name', '--password');
        $this->assertNotNull($opt);
        // VALUE_NONE options have default === false; false should NOT be redacted.
        $this->assertFalse($opt['default']);
    }

    // -------------------------------------------------------------------------
    // Dynamic internal-option resolution
    // -------------------------------------------------------------------------

    #[Test]
    public function it_resolves_internal_options_dynamically_from_a_bound_application(): void
    {
        $application = new SymfonyApplication('Test', '1.0');

        // Add a non-standard global option – simulates a future Symfony/Artisan addition.
        $application->getDefinition()->addOption(
            new InputOption('profile', null, InputOption::VALUE_NONE, 'Enable profiling')
        );

        $command = new class extends SymfonyCommand
        {
            protected function configure(): void
            {
                $this->setName('test:dynamic-opts')
                    ->addOption('force', null, InputOption::VALUE_NONE, 'Force')
                    ->addOption('profile', null, InputOption::VALUE_NONE, 'Profile');
            }

            protected function execute($input, $output): int
            {
                return 0;
            }
        };

        $application->add($command);

        $meta = $this->inspector->inspect($command);
        $optionNames = array_column($meta['options'], 'name');

        // --force is a command-specific option → must appear.
        $this->assertContains('--force', $optionNames);

        // --profile is in the application's definition → must be filtered out.
        $this->assertNotContains('--profile', $optionNames);

        // Standard global options are also filtered.
        $this->assertNotContains('--help', $optionNames);
    }

    // -------------------------------------------------------------------------
    // isVendorCommand – absolute path-prefix matching
    // -------------------------------------------------------------------------

    #[Test]
    public function it_identifies_vendor_commands_by_absolute_path_prefix(): void
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

        // Anonymous classes defined in the test suite are not inside the
        // vendor directory, so isVendorCommand() must return false.
        $this->assertFalse($this->inspector->isVendorCommand($appCommand));
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
