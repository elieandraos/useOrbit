# Publishing Custom Skills to skills.sh

## Overview

skills.sh distributes Claude Code skills from a GitHub repo. Install command:
```
npx skills add <github-owner>/<repo-name>
```

## Required Repo Structure

```
your-repo/
├── skills.sh.json
└── skills/
    ├── my-laravel-patterns/
    │   ├── SKILL.md
    │   ├── metadata.json
    │   └── rules/
    └── my-phpstorm-conventions/
        ├── SKILL.md
        ├── metadata.json
        └── rules/
```

## Files to Add

**`skills/<skill-name>/metadata.json`** (one per skill):
```json
{
  "version": "1.0.0",
  "organization": "Elie Andraos",
  "date": "June 2026",
  "abstract": "Short description of what this skill does."
}
```

**`skills.sh.json`** at repo root:
```json
{
  "$schema": "https://skills.sh/schemas/skills.sh.schema.json",
  "groupings": [
    {
      "title": "Laravel",
      "skills": ["my-laravel-patterns", "my-phpstorm-conventions"]
    }
  ]
}
```

## Steps

1. Create a new GitHub repo (e.g., `elieandraos/skills`)
2. Copy `.claude/skills/my-laravel-patterns` and `.claude/skills/my-phpstorm-conventions` into a `skills/` directory
3. Add `metadata.json` to each skill folder
4. Add `skills.sh.json` at the repo root
5. Push — skills become installable via `npx skills add elieandraos/skills`

The existing `SKILL.md` and `rules/` files are already in the correct format.