# artisan-docs

[![Tests](https://github.com/codeinstinctai/artisan-docs/actions/workflows/tests.yml/badge.svg)](https://github.com/codeinstinctai/artisan-docs/actions)
[![Latest Version](https://img.shields.io/packagist/v/code-instinct/artisan-docs.svg)](https://packagist.org/packages/code-instinct/artisan-docs)
[![License](https://img.shields.io/packagist/l/code-instinct/artisan-docs.svg)](LICENSE)

Automatically generate structured reference documentation for **every Artisan command** registered in your Laravel application — including custom commands, third-party packages, and Laravel's own built-in commands.

---

## Installation

```bash
composer require code-instinct/artisan-docs
```

Laravel's auto-discovery will register the service provider automatically.

Optionally publish the config and/or the HTML template:

```bash
# Publish configuration
php artisan vendor:publish --tag=artisan-docs-config

# Publish HTML Blade template (for customisation)
php artisan vendor:publish --tag=artisan-docs-views
```

---

## Usage

### Zero-config (Markdown, outputs to `docs/commands.md`)
```bash
php artisan docs:commands
```

### Choose output format
```bash
php artisan docs:commands --format=html   --output=docs/commands.html
php artisan docs:commands --format=json   --output=docs/commands.json
php artisan docs:commands --format=markdown --output=README-commands.md
```

### Filtering
```bash
# Only application-defined commands (in App\Console\Commands)
php artisan docs:commands --only-custom

# Exclude anything that lives in vendor/
php artisan docs:commands --exclude-vendor

# Scope to a single namespace
php artisan docs:commands --namespace=make

# Also surface hidden commands
php artisan docs:commands --include-hidden
```

### CI check — fail if docs are out of sync
```bash
php artisan docs:commands --check
```
Exits with code `1` when the committed documentation file does not match the current command structure. Ideal for a CI gate.

Before comparing, `--check` performs **structural normalisation**: it strips format-specific timestamps embedded by each generator — the `> Auto-generated on …` blockquote line in Markdown, the `generated_at` field in JSON, and the generated-time suffix inside the `<p class="meta">` element in HTML — so that a run whose only difference from the committed file is the generation timestamp still passes. Only genuine structural changes (commands added or removed, arguments or options modified) will cause the check to fail.

---

## Configuration

After publishing, edit `config/artisan-docs.php`:

| Key | Default | Description |
|-----|---------|-------------|
| `default_format` | `markdown` | `markdown`, `html`, or `json` |
| `default_output` | `docs/commands.md` | Output file path (relative to base path) |
| `include_hidden` | `false` | Include hidden commands |
| `include_vendor` | `true` | Include vendor package commands |
| `excluded_namespaces` | `['_', 'completion', 'horizon', 'telescope', 'nova']` | Namespaces to always skip. `horizon`, `telescope`, and `nova` are excluded by default to prevent exposing internal queue/worker configuration, recording settings, and admin tooling internals. |
| `excluded_commands` | `[]` | Specific command names to skip |
| `groups` | *(see config)* | Map namespace → group display name |
| `app_command_paths` | `['App\\Console\\Commands\\']` | Paths used to detect custom commands |
| `html_template` | `artisan-docs::commands` | Blade view for HTML output |
| `title` | `Artisan Command Reference` | Document title |

---

## Security

### Sensitive Data Redaction

`CommandInspector` automatically redacts the default values of any command argument or option whose name matches a sensitive-credential pattern. This prevents secrets that were accidentally set as defaults from leaking into generated documentation.

The following keyword patterns trigger redaction (matched case-insensitively, including compound names such as `db_password` or `api_token`):

| Matched keywords |
|-----------------|
| `password`, `passwd` |
| `secret` |
| `token` |
| `api_key`, `api-key`, `apikey` |
| `auth_key`, `auth-key` |
| `private_key`, `private-key` |
| `credential` |

When a match is found, the default value is replaced with the `[ REDACTED ]` placeholder (exposed as `CommandInspector::REDACTED_VALUE`) in all output formats. Arguments and options that have no meaningful default (`null`, `false`, or an empty string) are left untouched.

---

## Output Formats

### Markdown
GitHub-friendly tables for arguments and options, TOC with anchor links, group sections.

### HTML
Self-contained, dark-themed single-page reference with a sticky sidebar navigation. Zero external dependencies — ship it to GitHub Pages, an S3 bucket, or your internal wiki.

### JSON
Machine-readable structure for integration with Confluence, Notion, or custom tooling.

---

## Customising the HTML Template

```bash
php artisan vendor:publish --tag=artisan-docs-views
```

Edit `resources/views/vendor/artisan-docs/commands.blade.php`.  
Update `html_template` in your config to `artisan-docs::commands` (default already points to it after publishing).

---

## Testing

```bash
composer test
```

## Linting

```bash
composer lint
```

---

## Contributing

Contributions are welcome! If you've found a bug, have a feature request, or want to submit a pull request, please read the [Contributing Guidelines](CONTRIBUTING.md) to get started.

---

## License

Open-sourced software licensed under the [MIT LICENSE](LICENSE).
