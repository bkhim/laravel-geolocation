# Contributing

Thank you for considering contributing to Laravel Geolocation!

## Ways to Contribute

- **Bug reports** - Open an issue with details
- **Feature requests** - Suggest new features
- **Pull requests** - Submit bug fixes or new features
- **Documentation** - Improve docs or translations
- **Testing** - Add tests for new providers/features

## Development Setup

```bash
# Clone the repository
git clone https://github.com/bkhim/laravel-geolocation.git
cd laravel-geolocation

# Install dependencies
composer install

# Run tests
composer test
```

## Coding Standards

This package follows PSR-12 coding standards:

```bash
# Check code style
vendor/bin/phpcs

# Auto-fix code style
vendor/bin/phpcbf
```

## Pull Request Process

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Add tests for your changes
4. Ensure all tests pass
5. Commit your changes with clear messages
6. Push to your fork
7. Submit a Pull Request

## Test Requirements

- All tests must pass
- New features require tests
- Bug fixes require tests that reproduce the bug

## Commit Messages

Use clear, descriptive commit messages:

```
feat: Add proxy detection for IP2Location provider
fix: Correct MaxMind country mapping
docs: Update provider comparison matrix
```

## Issue Templates

Use the GitHub issue templates:
- Bug Report
- Feature Request
- Question

## Code of Conduct

This project follows the Laravel code of conduct. Please be respectful and professional.
