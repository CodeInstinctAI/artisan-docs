<?php

namespace CodeInstinct\ArtisanDocs\Tests\Feature;

use CodeInstinct\ArtisanDocs\Tests\TestCase;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class GenerateDocsCommandTest extends TestCase
{
    /**
     * Relative output path (relative to base_path()) used by non-mock tests.
     * Stored as relative so it can be passed directly to --output.
     */
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();
        // Relative path inside the project root – keeps us within base_path()
        // and exercises the resolveSafePath() happy-path in every test.
        $this->outputPath = 'docs/artisan-docs-test-'.uniqid().'.md';
    }

    protected function tearDown(): void
    {
        if (File::exists(base_path($this->outputPath))) {
            File::delete(base_path($this->outputPath));
        }
        parent::tearDown();
    }

    #[Test]
    public function it_runs_successfully_with_default_options(): void
    {
        $this->artisan('docs:commands', [
            '--output' => $this->outputPath,
        ])->assertExitCode(0);
    }

    #[Test]
    public function it_creates_the_output_file(): void
    {
        $this->artisan('docs:commands', [
            '--output' => $this->outputPath,
        ]);

        $this->assertFileExists(base_path($this->outputPath));
    }

    #[Test]
    public function it_generates_markdown_format_by_default(): void
    {
        $this->artisan('docs:commands', [
            '--output' => $this->outputPath,
        ]);

        $content = File::get(base_path($this->outputPath));
        $this->assertStringContainsString('# Artisan Command Reference', $content);
        $this->assertStringContainsString('## Table of Contents', $content);
    }

    #[Test]
    public function it_generates_json_output_format(): void
    {
        $jsonPath = str_replace('.md', '.json', $this->outputPath);

        $this->artisan('docs:commands', [
            '--format' => 'json',
            '--output' => $jsonPath,
        ])->assertExitCode(0);

        $this->assertFileExists(base_path($jsonPath));
        $decoded = json_decode(File::get(base_path($jsonPath)), true);
        $this->assertNotNull($decoded);
        $this->assertArrayHasKey('title', $decoded);
        $this->assertArrayHasKey('groups', $decoded);

        File::delete(base_path($jsonPath));
    }

    #[Test]
    public function it_filters_by_namespace(): void
    {
        $this->artisan('docs:commands', [
            '--namespace' => 'make',
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        $content = File::get(base_path($this->outputPath));
        // All listed commands should be make:* commands
        preg_match_all('/### `([^`]+)`/', $content, $matches);
        foreach ($matches[1] as $commandName) {
            $this->assertStringStartsWith('make:', $commandName, "Expected only make: commands, got: {$commandName}");
        }
    }

    #[Test]
    public function it_check_passes_when_file_is_up_to_date(): void
    {
        // Generate the file first
        $this->artisan('docs:commands', ['--output' => $this->outputPath]);

        // Now run with --check; it should pass
        $this->artisan('docs:commands', [
            '--output' => $this->outputPath,
            '--check' => true,
        ])->assertExitCode(0);
    }

    #[Test]
    public function it_check_fails_when_file_is_missing(): void
    {
        // A relative path that does not exist on disk yet.
        $missingPath = 'docs/artisan-docs-nonexistent-'.uniqid().'.md';

        $this->artisan('docs:commands', [
            '--output' => $missingPath,
            '--check' => true,
        ])->assertExitCode(1);
    }

    #[Test]
    public function it_check_fails_when_file_is_out_of_sync(): void
    {
        // Write stale content to the file
        File::ensureDirectoryExists(base_path(dirname($this->outputPath)));
        File::put(base_path($this->outputPath), '# Stale documentation that is out of date');

        $this->artisan('docs:commands', [
            '--output' => $this->outputPath,
            '--check' => true,
        ])->assertExitCode(1);
    }

    #[Test]
    public function it_includes_hidden_commands_when_flag_is_set(): void
    {
        // Register a hidden command for this test
        $hiddenCommand = new class extends Command
        {
            protected $signature = 'test:hidden-artisan-docs-cmd';

            protected $description = 'A hidden test command for artisan-docs';

            protected $hidden = true;

            public function handle(): int
            {
                return 0;
            }
        };

        $this->app->make(Kernel::class)->registerCommand($hiddenCommand);

        $this->artisan('docs:commands', [
            '--include-hidden' => true,
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        $content = File::get(base_path($this->outputPath));
        $this->assertStringContainsString('test:hidden-artisan-docs-cmd', $content);
    }

    #[Test]
    public function it_excludes_hidden_commands_by_default(): void
    {
        $hiddenCommand = new class extends Command
        {
            protected $signature = 'test:hidden-artisan-docs-cmd-2';

            protected $description = 'Another hidden test command';

            protected $hidden = true;

            public function handle(): int
            {
                return 0;
            }
        };

        $this->app->make(Kernel::class)->registerCommand($hiddenCommand);

        $this->artisan('docs:commands', [
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        $content = File::get(base_path($this->outputPath));
        $this->assertStringNotContainsString('test:hidden-artisan-docs-cmd-2', $content);
    }

    #[Test]
    public function it_warns_when_no_commands_match_filters(): void
    {
        $this->artisan('docs:commands', [
            '--namespace' => 'this-namespace-does-not-exist-xyz',
            '--output' => $this->outputPath,
        ])->assertExitCode(0);
    }

    // -------------------------------------------------------------------------
    // Dynamic output path resolution tests
    // -------------------------------------------------------------------------

    #[Test]
    public function it_writes_to_the_explicit_output_path_when_output_option_is_provided(): void
    {
        $expectedPath = base_path('custom/path.md');

        File::shouldReceive('ensureDirectoryExists')->once()->with(dirname($expectedPath));
        File::shouldReceive('put')->once()->with($expectedPath, Mockery::type('string'));
        // tearDown calls File::exists(base_path($this->outputPath)) on the mocked facade
        File::shouldReceive('exists')->andReturn(false);

        $this->artisan('docs:commands', ['--output' => 'custom/path.md'])
            ->assertExitCode(0);
    }

    #[Test]
    public function it_defaults_output_path_to_docs_commands_dot_format_when_only_format_is_provided(): void
    {
        // Uses json to avoid Blade rendering; the path-interpolation logic is identical for every format.
        $expectedPath = base_path('docs/commands.json');

        File::shouldReceive('ensureDirectoryExists')->once()->with(dirname($expectedPath));
        File::shouldReceive('put')->once()->with($expectedPath, Mockery::type('string'));
        // tearDown calls File::exists(base_path($this->outputPath)) on the mocked facade
        File::shouldReceive('exists')->andReturn(false);

        $this->artisan('docs:commands', ['--format' => 'json'])
            ->assertExitCode(0);
    }

    #[Test]
    public function it_falls_back_to_config_default_output_when_neither_output_nor_format_is_provided(): void
    {
        $expectedPath = base_path(config('artisan-docs.default_output'));

        File::shouldReceive('ensureDirectoryExists')->once()->with(dirname($expectedPath));
        File::shouldReceive('put')->once()->with($expectedPath, Mockery::type('string'));
        // tearDown calls File::exists(base_path($this->outputPath)) on the mocked facade
        File::shouldReceive('exists')->andReturn(false);

        $this->artisan('docs:commands')
            ->assertExitCode(0);
    }

    // -------------------------------------------------------------------------
    // Security: path validation tests
    // -------------------------------------------------------------------------

    #[Test]
    public function it_rejects_absolute_unix_output_paths(): void
    {
        $this->artisan('docs:commands', [
            '--output' => '/tmp/artisan-docs-evil.md',
        ])->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_absolute_paths_in_check_mode(): void
    {
        $this->artisan('docs:commands', [
            '--output' => '/tmp/artisan-docs-evil.md',
            '--check' => true,
        ])->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_stream_wrapper_output_paths(): void
    {
        $this->artisan('docs:commands', [
            '--output' => 'php://output',
        ])->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_phar_stream_wrapper_paths(): void
    {
        $this->artisan('docs:commands', [
            '--output' => 'phar://some-archive.phar/evil.md',
        ])->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_directory_traversal_in_output_path(): void
    {
        $this->artisan('docs:commands', [
            '--output' => '../../etc/cron.d/artisan-docs',
        ])->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_traversal_sequences_embedded_in_the_path(): void
    {
        $this->artisan('docs:commands', [
            '--output' => 'docs/../../../etc/passwd',
        ])->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_directory_traversal_in_check_mode(): void
    {
        $this->artisan('docs:commands', [
            '--output' => '../../etc/passwd',
            '--check' => true,
        ])->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_a_relative_path_within_the_project_root(): void
    {
        $this->artisan('docs:commands', [
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        $this->assertFileExists(base_path($this->outputPath));
    }

    #[Test]
    public function it_accepts_a_nested_relative_path_without_traversal(): void
    {
        $nested = 'docs/sub/artisan-docs-test-'.uniqid().'.md';

        $this->artisan('docs:commands', [
            '--output' => $nested,
        ])->assertExitCode(0);

        $this->assertFileExists(base_path($nested));

        File::delete(base_path($nested));
    }
}
