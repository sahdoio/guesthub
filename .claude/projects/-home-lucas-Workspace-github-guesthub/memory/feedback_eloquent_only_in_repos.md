---
name: Eloquent only in repositories
description: Eloquent model usage must be confined to repository classes — never in Views, Actions, Handlers, Presenters, or Services
type: feedback
---

Eloquent only and only inside repos. Always!

**Why:** Clean architecture — the domain and application layers must not depend on infrastructure (Eloquent). Views/Actions/Handlers/Presenters should use repository interfaces or dedicated read-model services.

**How to apply:** When writing or modifying code that needs database access, always go through a repository. If a query doesn't fit an existing repo method, add one. Never import `*Model` classes outside of `Persistence/Eloquent/` repositories and seeders.
