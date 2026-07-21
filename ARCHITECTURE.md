# Architecture Note

## Overview

Ajaia Docs is a small single-repository application with a Vue 3 interface, a Laravel 13 JSON API, and SQLite persistence. The browser loads one Blade shell and Vue handles the document dashboard, editor, user switching, imports, and sharing dialog.

## Data and request flow

- `users` contains the three seeded demo identities.
- `documents` stores a title, rich-text HTML content, timestamps, and an `owner_id`.
- `document_user` is the many-to-many collaborator table.
- Vue sends the selected demo user's ID with each API request.
- `DocumentController` verifies that the selected user owns the document or appears in its collaborator relationship before reading or updating it.
- Only the owner may add a collaborator.

Imported `.txt` and `.md` files are escaped and converted to simple HTML line breaks before storage. Rich-text edits are stored as HTML so formatting survives refreshes.

## Priorities and tradeoffs

The implementation prioritizes a coherent reviewable workflow, persistent formatting, and server-side sharing checks. Mock user switching keeps identity behavior visible without allowing authentication work to dominate the exercise. Browser-native `contenteditable` keeps the dependency footprint small.

This is not production-grade collaborative editing. A production iteration should add real authentication, HTML sanitization for editor submissions, policies instead of controller-local authorization, a structured editor such as TipTap, autosave/version history, and concurrent-edit conflict handling.
