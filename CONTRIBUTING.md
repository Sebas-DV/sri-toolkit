# Contributing to SRI Toolkit

Thank you for considering contributing to **SRI Toolkit**! Open source thrives because of developers like you. Whether you are reporting a bug, proposing a new feature, or submitting code improvements, your help is greatly appreciated.

---

## Code of Conduct

To ensure a welcoming and inclusive environment for everyone, all contributors and maintainers are expected to uphold standard open-source community guidelines:
- Use welcoming and inclusive language.
- Be respectful of differing viewpoints and experiences.
- Gracefully accept constructive criticism.
- Focus on what is best for the community and users of the package.

---

## How Can I Contribute?

### 1. Reporting Bugs
Before creating a bug report, please check the [existing issues](https://github.com/Sebas-DV/sri-toolkit/issues) to ensure the problem has not already been reported.

When filing a bug report, please include:
- A clear and descriptive title.
- A minimal, reproducible code sample.
- Expected behavior vs. actual behavior.
- PHP version (`php -v`), OS, and package version.
- Any relevant SRI error response or exception trace (**remember to redact sensitive RUCs, private keys, or certificate passwords**).

### 2. Suggesting Enhancements
If you have ideas for new features (e.g., support for newly updated SRI schemas, additional storage drivers, or framework adapters):
- Open an issue describing the proposed feature.
- Explain the use case and why it benefits the broader community.
- Provide examples of the expected API design where possible.

### 3. Submitting Pull Requests (PRs)
We actively welcome pull requests. To make the review process as smooth as possible:

1. **Fork the repository** and clone it locally:
   ```bash
   git clone https://github.com/Sebas-DV/sri-toolkit.git
   cd sri-toolkit
   ```
2. **Create a topic branch** from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/issue-description
   ```
3. **Install dependencies**:
   ```bash
   composer install
   ```
4. **Make your changes**:
   - Write clean, readable, and strictly typed PHP (>= 8.2).
   - Adhere to PSR-12 coding standards.
   - Avoid introducing heavy third-party runtime dependencies unless strictly necessary.
5. **Add tests**:
   - Every bug fix should have a regression test.
   - New features must include comprehensive unit and integration tests.
6. **Run code quality checks locally**:
   ```bash
   # Run automated test suite
   composer test

   # Run static analysis (PHPStan / Rector)
   composer stan
   composer rector

   # Fix/check code formatting
   composer cs
   ```
7. **Commit and Push**:
   - Write clear, conventional commit messages (e.g., `feat: add support for reimbursement vouchers in delivery guides`, `fix: correct check digit calculation for public entities`).
   ```bash
   git push origin feature/your-feature-name
   ```
8. **Open a Pull Request**:
   - Describe what changed and link any relevant issue numbers (e.g., `Fixes #12`).

---

## Development Standards & Guidelines

- **Strict Types**: Always declare `declare(strict_types=1);` at the top of every PHP file.
- **Exact Arithmetic**: Never use native float arithmetic for monetary values, taxes, or invoice totals. Use `brick/money` or dedicated decimal handling.
- **Mocking External Services**: Do not make real network calls to SRI production/testing Web Services inside automated unit tests. Use injectable mock SOAP clients and fixture XMLs.
- **Compatibility**: Ensure your code remains compatible across supported PHP versions (PHP 8.2, 8.3, 8.4, and 8.5).

---

## Security Vulnerabilities

If you discover a security vulnerability (especially regarding cryptographic signing, PKCS#12 certificate handling, or sensitive data leaks), please **do not open a public issue**. 

Instead, refer to [SECURITY.md](SECURITY.md) to report it privately.

---

## Questions and Support

If you have questions about implementing the toolkit or navigating Ecuadorian SRI regulations:
- Check the [Official Documentation](https://sri-toolkit.matizstudiocreative.com).
- Start a discussion in the GitHub Discussions / Issues tab.
