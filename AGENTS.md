# AGENTS.md

## Communication

- Communicate with the project owner in Russian.
- Keep all user-facing interface copy in Slovak unless a task explicitly says otherwise.
- Do not mix chat language and product language: implementation discussion is in Russian, visible UI text is in Slovak.

## General Coding Rules

- Prefer small, explicit, reversible changes over broad rewrites.
- Follow existing project conventions before introducing new abstractions.
- Keep changes scoped to the part of the codebase required by the task.
- Use the `docs/` directory as the primary local source of project context when you need to understand the current structure, state, or workflow.
- Avoid coupling unrelated layers or modules unless the task explicitly requires it.
- Do not hardcode secrets, tokens, or API keys in source files.
- Use configuration or environment variables for deployment-specific values.
- Preserve stable public contracts when changing shared code.
- Add fallbacks for incomplete or invalid data where reasonable.
- Do not add speculative features that are not required for the task.

## Backend Guidelines

- Keep data access, business rules, and transport concerns separated.
- Prefer indexed queries and bounded result sets over loading full datasets into memory.
- Use caching conservatively and make cache invalidation explicit.
- Keep API responses stable and predictable.
- Validate and sanitize external input.
- Fail safely: one bad record should not break the full response when graceful degradation is possible.

## Frontend Guidelines

- Keep payloads as small as practical.
- Avoid unnecessary rerenders, full teardown/rebuild cycles, and large DOM bursts.
- Prefer incremental updates when the UI changes frequently.
- Load detailed data lazily when it is not required for the initial view.
- Treat network calls as unreliable: show sane loading, empty, and error states.
- Do not assume frontend and backend are always on the same origin.

## Editing Guidelines

- Update documentation when setup, build, deployment, or public behavior changes.
- Before creating a commit, make sure the relevant files in `docs/` are updated to match the changes that were made.
- Do not delete generated folders or dependency directories unless the task requires it.
- Do not overwrite user data or unrelated local changes.
- Before introducing a new dependency, prefer existing platform or project tooling if it already solves the problem well.

## Verification

- Run the smallest useful verification for the scope of the change.
- For backend code, prefer syntax and targeted behavior checks.
- For frontend code, run type/check/build commands when the touched area requires it.
- If something could not be verified locally, state that clearly.
