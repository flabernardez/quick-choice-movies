# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Build Commands

- `npm run build` — Production build (compiles `src/` → `build/` via wp-scripts + custom webpack config)
- `npm run start` — Development mode with file watching
- `npm run lint:js` — Lint JavaScript files
- `npm run format` — Format code

No test framework is configured.

## Architecture

This is a WordPress plugin ("Quick Choice Movies") that implements a bracket-style elimination game. Users pick between two items repeatedly until one winner remains. Despite the name, it supports movies, video games, and books.

### Two Runtime Contexts

**Admin/Editor (React + Gutenberg):** Three webpack entry points in `src/`, built to `build/`:
- `game-block` — Gutenberg block for embedding a game on any page/post. Editor side only shows a list selector; the actual game runs via vanilla JS on the frontend.
- `list-block` — Gutenberg block for displaying items as a grid. Uses server-side rendering (`includes/list-block-render.php`).
- `meta-fields` — React app rendered into a classic meta box (`#qcm-meta-fields-root`) on the `quick_choice` CPT editor. Manages the item list with drag-and-drop (@dnd-kit) and API search modal. Saves via custom AJAX endpoint (`qcm_save_items`), NOT through the REST API/block editor save flow.

**Frontend (vanilla JS + jQuery):** `public/js/game.js` contains the `QuickChoiceGame` class that powers the elimination game. Loads items via AJAX (`qcm_get_choices`), saves progress to cookies.

### PHP Classes (all singletons via `get_instance()`)

- `QCM_CPT_Quick_Choices` — Registers the `quick_choice` CPT and `quick_choice_category` taxonomy
- `QCM_Meta_Fields` — Registers post meta (`qcm_choice_items` as JSON string, `qcm_api_source`), handles AJAX save and API search. This is the primary data management class.
- `QCM_Admin_Settings` — Settings page under Quick Choices menu for API keys (TMDB, RAWG) and Open Library toggle
- `QCM_Game_Manager` — Enqueues frontend assets and handles public-facing AJAX (game data + API search)

### Data Flow

Items are stored as a JSON string in `qcm_choice_items` post meta. Each item has `{id, title, image}`. The meta-fields editor auto-saves via AJAX 2 seconds after changes. The frontend game fetches items via `qcm_get_choices` AJAX action and manages game state (remaining items, current pair) in memory with cookie persistence.

### External APIs

Configured in Quick Choices → Settings. TMDB and RAWG require API keys stored as WP options (`qcm_tmdb_api_key`, `qcm_rawg_api_key`). Open Library needs no key. TMDB search supports smart person lookup (prefix query with "actor"/"director").

### Key Conventions

- PHP prefix: `qcm_` for functions, `QCM_` for classes
- Text domain: `quick-choice-movies`
- Some UI strings are in Spanish (e.g., "Guardando...", "¡Ganadora!")
- Webpack extends `@wordpress/scripts/config/webpack.config` with custom multi-entry setup
