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

    // -------------------------------------------------------------------------
    // Security: table-cell escaping
    // -------------------------------------------------------------------------

    #[Test]
    public function it_escapes_pipe_characters_in_argument_descriptions(): void
    {
        $groups = [
            'General' => [
                [
                    'name' => 'test:cmd', 'description' => 'A command', 'help' => '',
                    'hidden' => false, 'aliases' => [], 'namespace' => 'test', 'group' => 'General',
                    'arguments' => [
                        [
                            'name' => 'value',
                            'description' => 'One | Two | Three',
                            'required' => false,
                            'is_array' => false,
                            'default' => null,
                        ],
                    ],
                    'options' => [],
                ],
            ],
        ];

        $output = $this->generator->generate($groups, 'Docs');

        // Pipe characters in descriptions must be escaped to protect GFM table structure.
        $this->assertStringContainsString('One \| Two \| Three', $output);
        $this->assertStringNotContainsString('One | Two | Three', $output);
    }

    #[Test]
    public function it_escapes_pipe_characters_in_option_descriptions(): void
    {
        $groups = [
            'General' => [
                [
                    'name' => 'test:cmd', 'description' => 'A command', 'help' => '',
                    'hidden' => false, 'aliases' => [], 'namespace' => 'test', 'group' => 'General',
                    'arguments' => [],
                    'options' => [
                        [
                            'name' => '--mode',
                            'shortcut' => null,
                            'description' => 'fast | slow | medium',
                            'required' => false,
                            'is_array' => false,
                            'accepts_value' => true,
                            'default' => null,
                        ],
                    ],
                ],
            ],
        ];

        $output = $this->generator->generate($groups, 'Docs');

        $this->assertStringContainsString('fast \| slow \| medium', $output);
    }

    #[Test]
    public function it_collapses_newlines_in_table_cell_descriptions(): void
    {
        $groups = [
            'General' => [
                [
                    'name' => 'test:cmd', 'description' => 'A command', 'help' => '',
                    'hidden' => false, 'aliases' => [], 'namespace' => 'test', 'group' => 'General',
                    'arguments' => [
                        [
                            'name' => 'arg',
                            'description' => "Line one\nLine two",
                            'required' => false,
                            'is_array' => false,
                            'default' => null,
                        ],
                    ],
                    'options' => [],
                ],
            ],
        ];

        $output = $this->generator->generate($groups, 'Docs');

        // Newlines inside table cells must be collapsed to spaces.
        $this->assertStringContainsString('Line one Line two', $output);
        $this->assertStringNotContainsString("Line one\nLine two", $output);
    }

    // -------------------------------------------------------------------------
    // Security: HTML sanitisation in block text
    // -------------------------------------------------------------------------

    #[Test]
    public function it_strips_script_tags_from_command_description(): void
    {
        $groups = [
            'General' => [
                [
                    'name' => 'test:xss',
                    'description' => 'Safe text <script>alert("xss")</script> more text',
                    'help' => '',
                    'hidden' => false, 'aliases' => [], 'namespace' => 'test', 'group' => 'General',
                    'arguments' => [], 'options' => [],
                ],
            ],
        ];

        $output = $this->generator->generate($groups, 'Docs');

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('alert("xss")', $output);
        $this->assertStringContainsString('Safe text', $output);
        $this->assertStringContainsString('more text', $output);
    }

    #[Test]
    public function it_strips_script_tags_from_help_text(): void
    {
        $groups = [
            'General' => [
                [
                    'name' => 'test:xss-help',
                    'description' => 'A command',
                    'help' => 'Run this command. <script>document.cookie</script> See docs.',
                    'hidden' => false, 'aliases' => [], 'namespace' => 'test', 'group' => 'General',
                    'arguments' => [], 'options' => [],
                ],
            ],
        ];

        $output = $this->generator->generate($groups, 'Docs');

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('Run this command.', $output);
        $this->assertStringContainsString('See docs.', $output);
    }

    #[Test]
    public function it_strips_iframe_tags_and_their_contents_from_description(): void
    {
        $groups = [
            'General' => [
                [
                    'name' => 'test:iframe',
                    'description' => 'Before <iframe src="https://evil.test"></iframe> After',
                    'help' => '',
                    'hidden' => false, 'aliases' => [], 'namespace' => 'test', 'group' => 'General',
                    'arguments' => [], 'options' => [],
                ],
            ],
        ];

        $output = $this->generator->generate($groups, 'Docs');

        $this->assertStringNotContainsString('<iframe', $output);
        $this->assertStringNotContainsString('evil.test', $output);
        $this->assertStringContainsString('Before', $output);
        $this->assertStringContainsString('After', $output);
    }

    #[Test]
    public function it_strips_html_tags_from_table_cell_descriptions(): void
    {
        $groups = [
            'General' => [
                [
                    'name' => 'test:html-cell', 'description' => 'cmd', 'help' => '',
                    'hidden' => false, 'aliases' => [], 'namespace' => 'test', 'group' => 'General',
                    'arguments' => [
                        [
                            'name' => 'arg',
                            'description' => '<b>Bold</b> description',
                            'required' => false,
                            'is_array' => false,
                            'default' => null,
                        ],
                    ],
                    'options' => [],
                ],
            ],
        ];

        $output = $this->generator->generate($groups, 'Docs');

        $this->assertStringNotContainsString('<b>', $output);
        $this->assertStringContainsString('Bold description', $output);
    }
}
