# Contributing

Thank you for considering contributing to **artisan-docs**! Whether you're reporting a bug, suggesting a feature, or submitting a pull request, your help is greatly appreciated.

---

## Reporting Bugs

If you've found a bug, please [open an issue](https://github.com/codeinstinctai/artisan-docs/issues/new) and include the following:

- A **clear and descriptive title**.
- The **Laravel and PHP versions** you are running.
- **Reproducible steps** — the smallest set of actions that consistently trigger the bug.
- The **expected behaviour** and what you observed instead.
- Any relevant **error messages or stack traces**.

The more detail you provide, the faster the issue can be triaged and resolved.

---

## Feature Requests

Have an idea that would make artisan-docs more useful? [Open an issue](https://github.com/codeinstinctai/artisan-docs/issues/new) and:

- Use a **clear and descriptive title** prefixed with `[Feature Request]`.
- Explain the **problem you are trying to solve** and why existing behaviour does not address it.
- Describe the **proposed solution** or API you have in mind.
- If applicable, include **examples** of what the new functionality would look like.

Feature requests are reviewed and discussed openly on the issue tracker before any implementation begins.

---

## Development Setup

To set up a local development environment, clone the repository and install dependencies via Composer:

```bash
git clone https://github.com/codeinstinctai/artisan-docs.git
cd artisan-docs
composer install
```

No additional services or environment variables are required — the package uses [Orchestra Testbench](https://github.com/orchestral/testbench) to bootstrap a Laravel application for testing.

---

## Testing and Linting

Before submitting a pull request, please ensure all tests pass and the code style is consistent.

### Running Tests (PHPUnit)

```bash
composer test
```

This executes the full PHPUnit test suite via `vendor/bin/phpunit`, as defined in the project's `composer.json`.

### Running the Linter (Laravel Pint)

```bash
composer lint
```

This runs Laravel Pint via `vendor/bin/pint` to automatically fix code-style issues according to the project's conventions, as defined in the project's `composer.json`.

Both commands must complete without errors before a pull request will be reviewed.

---

## Pull Request Process

1. **Fork** the repository and create your branch from `main`:
   ```bash
   git checkout -b feature/my-new-feature
   ```
   Use a descriptive branch name that reflects the change (e.g. `fix/html-output-encoding`, `feature/csv-export-format`).

2. **Make your changes**, keeping commits focused and atomic. Write **descriptive commit messages** that explain *why* a change was made, not just *what* changed:
   ```
   Add --namespace flag to scope HTML output to a single group
   
   Previously the --namespace filter only applied to Markdown output.
   This change propagates the filter through to the HTML generator so
   all three formats behave consistently.
   ```

3. **Add or update tests** to cover your changes. Pull requests that reduce test coverage are unlikely to be merged.

4. **Run tests and linting** (see above) and confirm everything passes locally.

5. **Open a pull request** against the `main` branch. In the PR description:
   - Reference any related issue (e.g. `Closes #42`).
   - Summarise *what* changed and *why*.
   - Include any notes on backwards compatibility or migration steps.

6. A maintainer will review your pull request. Please be responsive to feedback — PRs that go stale may be closed.

---

Thank you for helping make artisan-docs better! 🎉
