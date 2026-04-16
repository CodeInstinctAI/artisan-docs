<?php

namespace CodeInstinct\ArtisanDocs\Tests\Unit\Generators;

use CodeInstinct\ArtisanDocs\Generators\MarkdownGenerator;
use CodeInstinct\ArtisanDocs\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MarkdownGeneratorTest extends TestCase
{
    private MarkdownGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new MarkdownGenerator;
    }

    private function makeGroups(): array
    {
        return [
            'Code Generators' => [
                [
                    'name' => 'make:model',
                    'description' => 'Create a new Eloquent model class',
                    'help' => 'Use the --migration flag to also create a migration.',
                    'hidden' => false,
                    'aliases' => [],
                    'namespace' => 'make',
                    'group' => 'Code Generators',
                    'arguments' => [
                        [
                            'name' => 'name',
                            'description' => 'The name of the model',
                            'required' => true,
                            'is_array' => false,
                            'default' => null,
                        ],
                    ],
                    'options' => [
                        [
                            'name' => '--migration',
                            'shortcut' => '-m',
                            'description' => 'Create a new migration file',
                            'required' => false,
                            'is_array' => false,
                            'accepts_value' => false,
                            'default' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    public function it_generates_a_markdown_title(): void
    {
        $output = $this->generator->generate($this->makeGroups(), 'Test Docs');

        $this->assertStringContainsString('# Test Docs', $output);
    }

    #[Test]
    public function it_includes_a_table_of_contents_with_groups(): void
    {
        $output = $this->generator->generate($this->makeGroups(), 'Test Docs');

        $this->assertStringContainsString('## Table of Contents', $output);
        $this->assertStringContainsString('- [Code Generators]', $output);
    }

    #[Test]
    public function it_includes_command_names_as_h3(): void
    {
        $output = $this->generator->generate($this->makeGroups(), 'Test Docs');

        $this->assertStringContainsString('### `make:model`', $output);
    }

    #[Test]
    public function it_includes_the_description_and_help_text(): void
    {
        $output = $this->generator->generate($this->makeGroups(), 'Test Docs');

        $this->assertStringContainsString('Create a new Eloquent model class', $output);
        $this->assertStringContainsString('Use the --migration flag', $output);
    }

    #[Test]
    public function it_renders_argument_tables(): void
    {
        $output = $this->generator->generate($this->makeGroups(), 'Test Docs');

        $this->assertStringContainsString('#### Arguments', $output);
        $this->assertStringContainsString('| Argument |', $output);
        $this->assertStringContainsString('`name`', $output);
        $this->assertStringContainsString('✅ Yes', $output);
    }

    #[Test]
    public function it_renders_options_tables(): void
    {
        $output = $this->generator->generate($this->makeGroups(), 'Test Docs');

        $this->assertStringContainsString('#### Options', $output);
        $this->assertStringContainsString('`--migration`', $output);
        $this->assertStringContainsString('-m', $output);
    }

    #[Test]
    public function it_renders_multiple_groups(): void
    {
        $groups = $this->makeGroups();
        $groups['Database'] = [
            [
                'name' => 'db:seed', 'description' => 'Seed the database', 'help' => '',
                'hidden' => false, 'aliases' => [], 'namespace' => 'db', 'group' => 'Database',
                'arguments' => [], 'options' => [],
            ],
        ];

        $output = $this->generator->generate($groups, 'Docs');

        $this->assertStringContainsString('## Code Generators', $output);
        $this->assertStringContainsString('## Database', $output);
    }
}
