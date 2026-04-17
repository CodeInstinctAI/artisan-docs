<?php

namespace CodeInstinct\ArtisanDocs\Tests\Unit\Generators;

use CodeInstinct\ArtisanDocs\Generators\JsonGenerator;
use CodeInstinct\ArtisanDocs\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class JsonGeneratorTest extends TestCase
{
    private JsonGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new JsonGenerator;
    }

    private function makeGroups(): array
    {
        return [
            'Code Generators' => [
                [
                    'name' => 'make:model',
                    'description' => 'Create a new Eloquent model class',
                    'help' => '',
                    'hidden' => false,
                    'aliases' => [],
                    'namespace' => 'make',
                    'group' => 'Code Generators',
                    'arguments' => [],
                    'options' => [],
                ],
            ],
            'Database' => [
                [
                    'name' => 'db:seed',
                    'description' => 'Seed the database',
                    'help' => '',
                    'hidden' => false,
                    'aliases' => [],
                    'namespace' => 'db',
                    'group' => 'Database',
                    'arguments' => [],
                    'options' => [],
                ],
            ],
        ];
    }

    #[Test]
    public function it_generates_valid_json(): void
    {
        $output = $this->generator->generate($this->makeGroups(), 'My Docs');
        $decoded = json_decode($output, true);

        $this->assertNotNull($decoded, 'Output is not valid JSON');
        $this->assertArrayHasKey('title', $decoded);
        $this->assertArrayHasKey('generated_at', $decoded);
        $this->assertArrayHasKey('total', $decoded);
        $this->assertArrayHasKey('groups', $decoded);
    }

    #[Test]
    public function it_includes_the_document_title(): void
    {
        $output = $this->generator->generate($this->makeGroups(), 'My Custom Title');
        $decoded = json_decode($output, true);

        $this->assertSame('My Custom Title', $decoded['title']);
    }

    #[Test]
    public function it_counts_total_commands(): void
    {
        $output = $this->generator->generate($this->makeGroups(), 'Docs');
        $decoded = json_decode($output, true);

        $this->assertSame(2, $decoded['total']);
    }

    #[Test]
    public function it_contains_both_groups(): void
    {
        $output = $this->generator->generate($this->makeGroups(), 'Docs');
        $decoded = json_decode($output, true);

        $groupNames = array_column($decoded['groups'], 'name');
        $this->assertContains('Code Generators', $groupNames);
        $this->assertContains('Database', $groupNames);
    }

    #[Test]
    public function it_includes_command_data_within_groups(): void
    {
        $output = $this->generator->generate($this->makeGroups(), 'Docs');
        $decoded = json_decode($output, true);

        $generatorsGroup = collect($decoded['groups'])->firstWhere('name', 'Code Generators');
        $this->assertNotNull($generatorsGroup);
        $this->assertCount(1, $generatorsGroup['commands']);
        $this->assertSame('make:model', $generatorsGroup['commands'][0]['name']);
    }

    #[Test]
    public function it_includes_an_iso8601_generated_at_timestamp(): void
    {
        $output = $this->generator->generate($this->makeGroups(), 'Docs');
        $decoded = json_decode($output, true);

        // ISO 8601 basic check: contains T and timezone indicator
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $decoded['generated_at']);
    }

    // -------------------------------------------------------------------------
    // Security: HTML-safe encoding flags
    // -------------------------------------------------------------------------

    #[Test]
    public function it_hex_encodes_html_angle_brackets_in_output(): void
    {
        $groups = [
            'General' => [
                [
                    'name' => 'test:xss',
                    'description' => '<script>alert(1)</script>',
                    'help' => '',
                    'hidden' => false,
                    'aliases' => [],
                    'namespace' => 'test',
                    'group' => 'General',
                    'arguments' => [],
                    'options' => [],
                ],
            ],
        ];

        $output = $this->generator->generate($groups, 'Docs');

        // Raw angle brackets must NOT appear – they must be unicode-escaped.
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('</script>', $output);

        // The unicode escape sequences produced by JSON_HEX_TAG must be present.
        $this->assertStringContainsString('\u003C', $output);
        $this->assertStringContainsString('\u003E', $output);

        // The JSON must still decode successfully and preserve the original value.
        $decoded = json_decode($output, true);
        $this->assertNotNull($decoded);
        $commands = $decoded['groups'][0]['commands'];
        $this->assertSame('<script>alert(1)</script>', $commands[0]['description']);
    }

    #[Test]
    public function it_hex_encodes_ampersands_in_output(): void
    {
        $groups = [
            'General' => [
                [
                    'name' => 'test:amp',
                    'description' => 'Foo & Bar',
                    'help' => '',
                    'hidden' => false,
                    'aliases' => [],
                    'namespace' => 'test',
                    'group' => 'General',
                    'arguments' => [],
                    'options' => [],
                ],
            ],
        ];

        $output = $this->generator->generate($groups, 'Docs');

        $this->assertStringNotContainsString(' & ', $output);
        $this->assertStringContainsString('\u0026', $output);

        $decoded = json_decode($output, true);
        $this->assertSame('Foo & Bar', $decoded['groups'][0]['commands'][0]['description']);
    }

    #[Test]
    public function it_produces_valid_json_after_applying_html_safe_flags(): void
    {
        $groups = [
            'General' => [
                [
                    'name' => 'test:special',
                    'description' => '<b>Bold</b> "quoted" & \'apostrophe\'',
                    'help' => '',
                    'hidden' => false,
                    'aliases' => [],
                    'namespace' => 'test',
                    'group' => 'General',
                    'arguments' => [],
                    'options' => [],
                ],
            ],
        ];

        $output = $this->generator->generate($groups, 'Docs');

        $decoded = json_decode($output, true);
        $this->assertNotNull($decoded, 'Output must be valid JSON even with HTML-safe flags applied');
        $this->assertSame(
            '<b>Bold</b> "quoted" & \'apostrophe\'',
            $decoded['groups'][0]['commands'][0]['description']
        );
    }
}
