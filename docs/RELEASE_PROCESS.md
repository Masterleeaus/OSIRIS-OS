# Release Process

## Versioning
- Semantic Versioning: `MAJOR.MINOR.PATCH`.
- Breaking changes -> MAJOR bump.

## Steps to release
1. Finish all PRs targeting `main`.
2. Update `CHANGELOG.md` (keep PR descriptions).
3. Run full test suite + CI.
4. Bump `version.txt` and tag: `vX.Y.Z`.
5. Build and publish Docker images.
6. Create GitHub Release and attach changelog notes.

## Hotfixes
- Create `hotfix/*` branch from `main`, apply fix, tag and publish.

## Release checklist
- [ ] Security scan passed
- [ ] Tests green
- [ ] Docs updated (README, QUICK_START)
- [ ] Migration notes included (if DB/schema changes)
