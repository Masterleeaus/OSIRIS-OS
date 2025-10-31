# Contributing to AIPlatform

Thanks for considering contributing! This document explains how to get the code, run it, and submit patches.

## Code of conduct
Please read `CODE_OF_CONDUCT.md` before contributing.

## How to contribute
1. Fork the repo.
2. Create a feature branch:
   ```
   git checkout -b feat/short-description
   ```
3. Keep commits atomic and well-documented.
4. Push and open a Pull Request to `main`.

## Branching & workflow
- `main` — production-ready stable releases only.
- `develop` — active development integration (if used).
- feature branches: `feat/*`, `fix/*`, `chore/*`, `docs/*`.

## Commit message style
Use Conventional Commits:
```
feat(auth): add SSO
fix(api): correct token expiry
docs: update installation
```

## Tests & CI
- All PRs must pass CI (lint, unit tests).
- Run unit tests locally before pushing.

## Formatting & linting
- JavaScript/TypeScript: Prettier + ESLint.
- PHP: PHP-CS-Fixer / Pint.
- Run linters before committing.

## Review process
- 1-2 approvals required (maintainers).
- Small PRs merged by maintainers after CI passes.
- Large changes may require design doc and maintained issue/epic.

## How to propose large changes
Create a design document in `docs/designs/` and open an issue linking to it. Tag maintainers.
