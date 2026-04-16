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
}
