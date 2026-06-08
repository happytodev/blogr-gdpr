---
name: release-manager
description: Automate Blogr GDPR package releases: version bumping, CHANGELOG updates, tagging, and publishing to GitHub.
---

## When to use

Trigger phrases: "release", "tag a new version", "publish vX.Y.Z", "cut a release", "bump version".

## Workflow

### 1. Determine the new version

- Read current version from `composer.json` (`"version": "0.x.y"`)
- Also verify it matches `BlogrGdprPlugin::getVersion()` — if they differ, sync them first
- Ask user for bump type: `patch`, `minor`, `major`, or an explicit version like `0.19.0`
- **Compute the new version using semver rules correctly**:

  | Current | patch (Z+1) | minor (Y+1, Z=0) | major (X+1, Y=Z=0) |
  |---------|-------------|------------------|-------------------|
  | `0.22.0` | `0.22.1` | `0.23.0` | `1.0.0` |
  | `0.22.5` | `0.22.6` | `0.23.0` | `1.0.0` |
  | `1.0.0` | `1.0.1` | `1.1.0` | `2.0.0` |

  ⚠️ **Common mistake**: patch is NOT `0.22.0 → 0.23.0` — that is a minor bump. Patch only increments the last digit.

- Present the computed version to the user for confirmation

### 2. Organize uncommitted changes into feature-grouped commits

- Run `git status --short` to list changed/new files
- **If there are no uncommitted changes**, skip this step
- **If there are uncommitted changes**, group files by feature area using path heuristics:

  | Pattern | Suggested commit message |
  |---|---|
  | `src/Services/ConsentService*`, `src/Models/ConsentLog*` | `feat: consent logging and management` |
  | `src/Http/Controllers/GdprController*`, `routes/*` | `feat: GDPR data request endpoints` |
  | `src/Filament/Pages/GdprSettings*` | `feat: GDPR settings UI in Filament` |
  | `resources/views/cookie*`, `resources/views/analytics*` | `feat: cookie consent banner and analytics gate` |
  | `resources/views/contact*`, `resources/views/footer*` | `feat: contact form consent and footer privacy link` |
  | `resources/views/dpo*`, `data/privacy-policy/*` | `feat: DPO injection and privacy policy pages` |
  | `src/BlogrGdprPlugin*` | `feat: plugin registration and extension interface` |
  | `src/BlogrGdprServiceProvider*`, `config/blogr-gdpr.php` | `feat: service provider configuration` |
  | `src/Console/Commands/*` | `feat: GDPR console commands` |
  | `src/Notifications/*` | `feat: GDPR notification channels` |
  | `tests/*` | `test: add tests for new features` (attach to relevant feature commit if possible, otherwise a single test commit) |
  | `.github/workflows/*` | `ci: add GitHub Actions workflows` |
  | `CHANGELOG.md` | `docs(changelog): update` (attach to feature commits if possible) |

- **Heuristics**:
  - Files matching multiple patterns go with the *first* matching feature group
  - Config-only changes to keys unrelated to above → `chore: update config`
  - Dependabot / lockfile changes → `chore(deps): update dependencies`
- For each group, stage and commit:
  ```bash
  git add <file1> <file2> ...
  git commit -m "<type>(<scope>): <description>"
  ```

### 3. Generate and present release notes

- Run: `git log $(git describe --tags --abbrev=0)..HEAD --oneline --no-decorate`
- If no tags exist yet, use: `git log --oneline --no-decorate` (all commits since beginning)
- Format as markdown with conventional commit categories (Features, Bug Fixes, Dependencies, etc.)
- **Show the formatted markdown to the user using the `question` tool** with a "Looks good, proceed" option and an "Edit notes" option.
- **Do NOT just ask "proceed?"** — display the full formatted release notes in the question so the user can review every line before approving.
- Include a third option "Cancel" in case the user wants to abort.
- Only proceed when the user explicitly approves.

### 4. Run tests (ZERO TOLERANCE)

- Run: `vendor/bin/pest`
- **Do NOT pipe through grep/tail/head — capture the raw output.** The last lines show the result:
  ```
  Tests:    41 passed (64 assertions)
  ```
- **If ANY test fails (even 1), abort immediately.** Do not proceed, do not commit, do not push.
- Zero tolerance: "skipped" and "passed" are OK; "failed" or "ERROR" means STOP.
- Report the failure count to the user and tell them what tests failed.

### 5. Update version files (atomic commit)

- **`composer.json`**: Edit the `"version"` field
- **`src/BlogrGdprPlugin.php`**: Edit the string returned by `getVersion()` (line with `return 'x.y.z';`)
- **Commit** these two changes atomically:
  ```bash
  git add composer.json src/BlogrGdprPlugin.php
  git commit -m "chore: bump version to v{version}"
  ```

### 6. Update CHANGELOG.md (atomic commit)

- Prepend a new entry at the top following the existing format:

  ```markdown
  ## v{version} - {date}

  ### ✨ Features (or 🐛 Bug Fixes | ⬆️ Dependencies)

  - **{title}**: {description}
  ```

- Use the user-approved release notes content from step 3
- Keep existing entries intact
- **Commit** only CHANGELOG.md:
  ```bash
  git add CHANGELOG.md
  git commit -m "docs(changelog): v{version}"
  ```

### 7. Tag

```bash
git tag v{version}
```

### 8. Push commits and tag

```bash
git push origin main v{version}
```

### 9. Create GitHub Release

- Set `RELEASE_NOTES` to the *exact markdown* the user approved in step 3 (the body of the CHANGELOG entry, without the heading/date line)
- Run: `gh release create v{version} --title "v{version}" --notes "$RELEASE_NOTES"`

### 10. Confirm

- Inform the user the release was published with the URL and commit hash
