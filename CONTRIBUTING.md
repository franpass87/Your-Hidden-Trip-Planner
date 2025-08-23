# Contributing Guidelines

## Conventional Commits

This repository uses [Conventional Commits](https://www.conventionalcommits.org/) for automatic changelog generation and version bumping.

### Commit Message Format

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

### Types

- **feat**: A new feature (triggers MINOR version bump)
- **fix**: A bug fix (triggers PATCH version bump)
- **docs**: Documentation only changes
- **style**: Changes that do not affect the meaning of the code
- **refactor**: A code change that neither fixes a bug nor adds a feature
- **perf**: A code change that improves performance (triggers PATCH version bump)
- **test**: Adding missing tests or correcting existing tests
- **chore**: Changes to the build process or auxiliary tools
- **feat!** or **fix!**: Breaking changes (triggers MAJOR version bump)

### Examples

```
feat: add AI-powered tour recommendations
fix: resolve PDF generation error with special characters
docs: update installation instructions
chore: upgrade composer dependencies
feat!: redesign trip builder API (breaking change)
```

### Release Process

1. **Commits**: Use conventional commit messages for all changes
2. **Release PR**: release-please automatically creates a Release PR when changes are pushed to `main`
3. **Version Updates**: The Release PR updates versions in:
   - `your-hidden-trip-planner.php` (header and constant)
   - `includes/class-yht-plugin.php` (class version)
   - `readme.txt` (stable tag)
   - `README.md` (current version)
   - `CHANGELOG.md` (generated automatically)
4. **Release**: When the Release PR is merged, it creates a Git tag and GitHub Release
5. **Distribution**: The existing `build-release.yml` workflow builds the distribution package

### Notes for Developers

- Always use meaningful commit messages
- Group related changes in single commits when possible
- Use the correct commit type to ensure proper version bumping
- Breaking changes must include `!` after the type or `BREAKING CHANGE:` in the footer
- The changelog is generated automatically from commit messages