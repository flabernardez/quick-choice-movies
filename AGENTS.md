# AGENTS.md

This file provides guidance to AI coding agents (Claude Code, OpenCode) when working with code in this repository.

## Build Commands

- `npm run build` — Production build (compiles `src/` → `build/` via wp-scripts + custom webpack config)
- `npm run start` — Development mode with file watching
- `npm run lint:js` — Lint JavaScript files
- `npm run format` — Format code

**IMPORTANT:** The developer (Flavia) runs `npm run build` manually. The agent must only notify when code is ready to build, never run it autonomously.

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

---

## Plugin Metadata

All plugins by this author must include:

```php
/**
 * Plugin Name: Plugin Name
 * Plugin URI:  https://flabernardez.com/plugin-name
 * Description: Plugin description.
 * Version:     0.1
 * Author:      Flavia Bernardez Rodriguez
 * Author URI:  https://flabernardez.com
 * License:     GPL-2.0-or-later
 * Text Domain: plugin-slug
 */
```

## Versioning

- `0.x` — Development, before first successful test
- `1.0.0` — First version confirmed working by developer
- `x.x.+1` — Bugfix (e.g., `1.0.1`, `1.0.2`)
- `x.+1.0` — Enhancement or style improvement to existing functionality (e.g., `1.1.0`)
- `+1.0.0` — New independent functionality (e.g., `2.0.0`)

Every commit with functionality changes must bump the version in the main plugin file and `package.json` if applicable.

---

## Personal Development Rules

1. **Function prefixing**: Plugin acronym + `_function_name`. Example: `qcm_get_choices()`, `qcm_save_items()`
2. **Code language**: All code in English (functions, variables, inline comments, classes). Communication with the developer is in Spanish
3. **Modular and extensible architecture**: Always think in extensible modules. Never duplicate logic or implement the same functionality in two different ways
4. **SCSS modular by component**: One SCSS file per component, everything compiles to `style.css`. No monolithic stylesheets
5. **Native WP/theme variables**: SCSS must use CSS custom properties from standard WP themes (Twenty Twenty-Three, Twenty Twenty-Five) for colors, spacers, fonts. Never invent custom values when native ones exist
6. **Native WP components**: When WP components exist (buttons, modals, notices, panels, etc.), use native components and classes with their bindings from `@wordpress/components`. Do not reinvent existing interfaces
7. **Public GitHub by default**: Always create a public GitHub repository for new projects and connect it from the start. The developer's account is already configured
8. **Meta Fields API**: Always use the WordPress Meta Fields API (`register_post_meta()`, `useEntityProp()`) for data storage. Never use custom database tables when post meta is a viable alternative
9. **Block API**: Follow the official Block API for all Gutenberg block development. Reference: `https://developer.wordpress.org/block-editor/reference-guides/block-api/`
10. **Build process**: The developer runs `npm run build` manually. The agent notifies when code is ready to build

---

## WordPress Coding Standards

Source: https://developer.wordpress.org/coding-standards/

### PHP Coding Standards

Source: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/

#### Naming Conventions

- Functions, variables, action/filter hooks: `snake_case` lowercase. Example: `my_function_name`, `$my_variable`
- Classes: `Upper_Snake_Case` with words separated by underscores. Example: `QCM_Meta_Fields`, `WP_Query`
- Constants: `UPPER_CASE` with underscores. Example: `QCM_PLUGIN_VERSION`
- File names: lowercase with hyphens. Example: `class-qcm-meta-fields.php`
- Class files must be prefixed with `class-`. Example: `class-qcm-game-manager.php`

#### Indentation and Spacing

- Use **tabs** for indentation, not spaces
- Spaces on both sides of opening and closing parentheses of control structures: `if ( $condition )`
- Spaces inside array parentheses: `array( 1, 2, 3 )`
- No space after function name in function calls: `my_function( $arg )`
- Space after opening and before closing parentheses: `if ( true === $var )`
- Space after commas: `array( 1, 2, 3 )`
- No trailing whitespace at end of lines
- Blank line at end of file

#### Formatting

- Braces always required for control structures, even single-line:

```php
// Correct
if ( $condition ) {
    do_something();
}

// Incorrect
if ( $condition )
    do_something();
```

- Opening brace on same line (K&R style):

```php
if ( $condition ) {
    // code
} elseif ( $other ) {
    // code
} else {
    // code
}
```

- Use `elseif` (not `else if`)
- Yoda conditions — constant on the left:

```php
// Correct
if ( true === $var ) {
    // code
}

// Incorrect
if ( $var === true ) {
    // code
}
```

#### Arrays

- Use long array syntax `array()` (not `[]` shorthand) for consistency with WordPress core
- Trailing comma in multi-line arrays:

```php
$array = array(
    'key1' => 'value1',
    'key2' => 'value2',
);
```

#### Strings

- Single quotes by default
- Double quotes only when interpolating variables or using special characters (`\n`, `\t`, etc.)
- Use `sprintf()` or `printf()` for complex string formatting

#### Type Declarations

- Use `bool` and `int` (not `boolean` and `integer`) for type casting and documentation
- Always declare visibility on class methods and properties (`public`, `protected`, `private`)
- Magic methods must be `public`

#### Operators

- Use `pre-increment` (`++$i`) over `post-increment` (`$i++`) when the returned value is not used
- Ternary operators should test for true, not false. Exception: `! empty()` is acceptable

```php
// Correct
$value = $condition ? 'yes' : 'no';

// Incorrect
$value = ! $condition ? 'no' : 'yes';
```

#### Prohibited Practices

- **Never** use `extract()` — makes code unpredictable
- **Never** use `eval()` — security risk
- **Never** use `goto` — makes code flow unreadable
- **Never** use the error suppression operator `@` — handle errors properly
- **Never** use `create_function()` — deprecated, use closures instead

#### Database Queries

- Always use `$wpdb->prepare()` for queries with user input:

```php
$wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
        'quick_choice',
        'publish'
    )
);
```

- Never concatenate variables directly into SQL strings
- Use the WordPress Query API (`WP_Query`, `get_posts()`) whenever possible instead of raw SQL

#### Security

- **Sanitize all input**:
  - `sanitize_text_field()` for text
  - `absint()` for positive integers
  - `sanitize_email()` for emails
  - `sanitize_url()` / `esc_url_raw()` for URLs
  - `sanitize_file_name()` for file names
  - `wp_kses()` / `wp_kses_post()` for HTML content

- **Escape all output**:
  - `esc_html()` for HTML content
  - `esc_attr()` for HTML attributes
  - `esc_url()` for URLs
  - `esc_js()` for inline JavaScript
  - `wp_kses()` for allowing specific HTML tags

- **Nonces for forms and AJAX**:
  - `wp_nonce_field()` in forms
  - `wp_verify_nonce()` on submission
  - `check_ajax_referer()` for AJAX requests
  - `wp_create_nonce()` for generating nonces

- **Capability checks**: Always verify user capabilities with `current_user_can()` before performing actions

#### Internationalization (i18n)

- All user-facing strings must be translatable
- Use the plugin's text domain consistently
- Functions: `__()`, `_e()`, `esc_html__()`, `esc_html_e()`, `esc_attr__()`, `esc_attr_e()`
- Placeholders with `sprintf()`: `sprintf( __( 'Hello, %s!', 'text-domain' ), $name )`

---

### JavaScript Coding Standards

Source: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/

#### Naming Conventions

- Variables and functions: `camelCase` (different from PHP!)
- Classes and components: `UpperCamelCase` (PascalCase)
- Constants (never reassigned): `SCREAMING_SNAKE_CASE`
- Acronyms fully capitalized: `currentDOMDocument`, `getHTMLElement`
- Abbreviations in camelCase: `userId`, `getId`
- All `@wordpress/element` components use PascalCase, including stateless function components

#### Indentation and Spacing

- Use **tabs** for indentation
- Spaces inside parentheses: `if ( condition )`
- Spaces inside brackets: `[ a, b ]`, `array[ 0 ]`
- Spaces after `!` negation: `if ( ! condition )`
- No space in empty constructs: `{}`, `[]`, `fn()`
- Space on both sides of ternary `?` and `:`
- Lines should not exceed 100 characters (soft limit at 80)
- New line at end of file

#### Variables

- Use `const` and `let` (ES2015+), never `var` in new code
- `const` by default, `let` only when reassignment is needed
- Declare at point of first use (not hoisted to top)

#### Strings

- Single quotes for string literals: `'my string'`
- Escape single quotes with backslash: `'it\'s a string'`

#### Semicolons

- Always use semicolons. Never rely on ASI (Automatic Semicolon Insertion)

#### Equality

- Always use strict equality `===` and `!==`. Never use `==` or `!=`

#### Control Structures

- Always use braces, even for single-line blocks
- Opening brace on same line as statement
- Multi-line statements: line breaks after operators (not before)

#### jQuery

- Access jQuery through `$` by wrapping in IIFE:

```javascript
( function ( $ ) {
    // Expressions
} )( jQuery );
```

#### Best Practices

- Use `[]` constructor for arrays (not `new Array()`)
- Use `{}` for objects (not `new Object()`)
- Access object properties via dot notation unless key is variable or invalid identifier
- Store loop max value in variable for large iterations

---

### CSS Coding Standards

Source: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/

#### Structure

- Use **tabs** for indentation
- Two blank lines between sections, one blank line between blocks
- Each selector on its own line
- Opening brace on same line as last selector
- One property per line, indented one tab
- Closing brace flush left

```css
#selector-1,
#selector-2 {
    background: #fff;
    color: #000;
}
```

#### Selectors

- Lowercase with hyphens: `.my-selector` (no camelCase, no underscores)
- Human readable names that describe what they style
- Double quotes in attribute selectors: `input[type="text"]`
- No over-qualified selectors: `.container` not `div.container`

#### Properties

- Colon followed by a space: `property: value;`
- All properties and values lowercase
- Hex colors lowercase, shortened when possible: `#fff` not `#FFFFFF`
- Use shorthand for `background`, `border`, `font`, `list-style`, `margin`, `padding`
- Property ordering (logical grouping):
  1. Display
  2. Positioning
  3. Box model
  4. Colors and Typography
  5. Other

#### Values

- Space before value, after colon
- No padding inside parentheses: `rgba(0, 0, 0, 0.5)`
- Always end with semicolon
- Double quotes for font names with spaces: `"Helvetica Neue"`
- Font weights as numbers: `700` not `bold`, `400` not `normal`
- Zero values without units: `0` not `0px` (exception: `transition-duration`)
- `line-height` without units (unitless)
- Leading zero for decimals: `0.5` not `.5`
- Comma-separated values on same line or each on new line (indented) for long values

#### Media Queries

- Group at bottom of stylesheet
- Rule sets indented one level inside media query

#### Comments

- Section headers with `/** */` format:

```css
/**
 * #.# Section title
 *
 * Description of section.
 */
```

- Inline comments with `/* */`

#### Best Practices

- Remove code before adding more when fixing issues
- No magic numbers
- Target elements directly, not through parents
- Use `line-height` instead of `height` when possible
- Do not restate default property values

---

### HTML Coding Standards

Source: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/html/

- All tags and attributes in lowercase
- Always quote attribute values (double quotes preferred)
- Self-closing elements with space before slash: `<br />`
- Use tabs for indentation, reflecting logical structure
- Validate against W3C validator
- Boolean attributes without value: `<input type="text" disabled />`

When mixing PHP and HTML:

```php
<?php if ( ! have_posts() ) : ?>
<div id="post-1" class="post">
    <h1 class="entry-title">Not Found</h1>
    <div class="entry-content">
        <p>Apologies, but no results were found.</p>
        <?php get_search_form(); ?>
    </div>
</div>
<?php endif; ?>
```

---

### Accessibility Coding Standards

Source: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/

WordPress code must conform to **WCAG 2.2 at level AA**.

#### Four Principles (POUR)

1. **Perceivable** — Text alternatives for non-text content, captions for media, adaptable layouts, distinguishable foreground/background
2. **Operable** — All functionality keyboard accessible, enough time to read content, no seizure-inducing content, navigable with clear focus
3. **Understandable** — Readable text, predictable behavior, input assistance for forms
4. **Robust** — Compatible with assistive technologies, valid markup, proper ARIA usage

#### Key Requirements

- Alt text on all images
- Labels on all form inputs (visible labels, not just placeholders)
- Keyboard navigation for all interactive elements
- Sufficient color contrast (4.5:1 for normal text, 3:1 for large text)
- ARIA roles and attributes only when native HTML semantics are insufficient
- Focus management for dynamic content (modals, dropdowns)
- Skip navigation links where appropriate
- Semantic HTML structure (headings hierarchy, landmarks)

#### Normative References

- [WCAG 2.2](https://www.w3.org/TR/WCAG22)
- [WAI-ARIA 1.1](https://www.w3.org/TR/wai-aria/)
- [ATAG 2.0](https://www.w3.org/TR/ATAG20/)
- [WAI-ARIA Authoring Practices Guide](https://www.w3.org/WAI/ARIA/apg/)

---

## Inline Documentation Standards

### PHP Documentation Standards (PHPDoc)

Source: https://developer.wordpress.org/coding-standards/inline-documentation-standards/php/

#### What Must Be Documented

- Functions and class methods
- Classes
- Class members (properties and constants)
- Requires and includes
- Hooks (actions and filters)
- Inline comments
- File headers
- Constants

#### Language Rules

- Summaries use third-person singular verbs: "Does something." (not "Do something.")
- Functions: describe **what** the function does
- Filters: describe **what** is being filtered
- Actions: describe **when** the action fires
- Use serial (Oxford) comma
- No HTML in summaries. Write "link tag" not "`<link>`"
- Markdown allowed in descriptions
- Wrap at 80 characters

#### Function/Method DocBlock

```php
/**
 * Summary. (use period)
 *
 * Description. (use period)
 *
 * @since x.x.x
 *
 * @see Function/method/class relied on
 * @link URL
 * @global type $varname Description.
 *
 * @param type $var Description.
 * @param type $var Optional. Description. Default.
 * @return type Description.
 */
```

#### Array Parameters

```php
/**
 * Summary.
 *
 * @since x.x.x
 *
 * @param array $args {
 *     Optional. An array of arguments.
 *
 *     @type type $key Description. Default 'value'. Accepts 'value', 'value'.
 *     @type type $key Description.
 * }
 * @return type Description.
 */
```

#### Class DocBlock

```php
/**
 * Summary.
 *
 * Description.
 *
 * @since x.x.x
 */
```

#### Hook DocBlock (Actions and Filters)

```php
/**
 * Summary. (use period)
 *
 * Description.
 *
 * @since x.x.x
 *
 * @param type  $var Description.
 * @param array $args {
 *     Short description about this hash.
 *
 *     @type type $var Description.
 * }
 */
```

#### File Headers

```php
/**
 * Summary (no period for file headers)
 *
 * Description. (use period)
 *
 * @link URL
 *
 * @package WordPress
 * @subpackage Component
 * @since x.x.x
 */
```

#### Inline Comments

```php
// Single line comment.

/*
 * Multi-line comment spanning multiple lines.
 * Use /* (single asterisk), never /** for inline.
 */
```

#### @since Tags

- Always 3-digit format: `@since 3.9.0`
- Add changelog entries for significant changes:

```php
 * @since 3.0.0
 * @since 3.8.0 Added the `post__in` argument.
 * @since 4.1.0 The `$force` parameter is now optional.
```

#### Key PHPDoc Tags

| Tag | Usage |
|-----|-------|
| `@since` | Version added (3-digit: x.x.x) |
| `@param` | Function parameter: type, name, description |
| `@return` | Return type and description |
| `@var` | Class property type |
| `@type` | Array argument value type (in hash notation) |
| `@see` | Reference to related function/class |
| `@link` | URL for more information |
| `@deprecated` | Version deprecated + replacement |
| `@access` | Only for private core APIs |
| `@global` | Global variables used |
| `@todo` | Planned changes |
| `@package` | For plugins: plugin name. For themes: theme name |

#### Rules for Plugins

- `@package` must be the plugin name (never `WordPress`)
- Do not use `@author` tag (WordPress policy)
- Do not use `@copyright` or `@license` (unless external library)

---

### JavaScript Documentation Standards (JSDoc)

Source: https://developer.wordpress.org/coding-standards/inline-documentation-standards/javascript/

WordPress follows JSDoc 3 standard.

#### What Must Be Documented

- Functions and class methods
- Objects
- Closures
- Object properties
- Requires
- Events
- File headers

#### Function DocBlock

```javascript
/**
 * Summary. (use period)
 *
 * Description. (use period)
 *
 * @since      x.x.x
 * @deprecated x.x.x Use new_function_name() instead.
 * @access     private
 *
 * @class
 * @augments parent
 * @mixes    mixin
 *
 * @see  Function/class relied on
 * @link URL
 *
 * @fires   eventName
 * @listens event:eventName
 *
 * @param {type}   var           Description.
 * @param {type}   [var]         Description of optional variable.
 * @param {type}   [var=default] Description of optional variable with default.
 * @param {Object} objectVar     Description.
 * @param {type}   objectVar.key Description of a key in the objectVar parameter.
 *
 * @return {type} Return value description.
 */
```

#### Class Member DocBlock

```javascript
/**
 * Short description. (use period)
 *
 * @since  x.x.x
 * @access (private, protected, or public)
 *
 * @type     {type}
 * @property {type} key Description.
 */
```

#### File Header

```javascript
/**
 * Summary. (use period)
 *
 * Description. (use period)
 *
 * @link   URL
 * @file   This files defines the MyClass class.
 * @author AuthorName.
 * @since  x.x.x
 */
```

#### Inline Comments

```javascript
// Single line comment.

/*
 * Multi-line comment. Use /* (single asterisk),
 * never /** for inline comments.
 */
```

#### Alignment

Align related comments for readability:

```javascript
/**
 * @param {very_long_type} name           Description.
 * @param {type}           very_long_name Description.
 */
```

---

## Trusted References

| Resource | Purpose |
|----------|---------|
| [Block Editor Reference Guides](https://developer.wordpress.org/block-editor/reference-guides/) | Complete reference: Block API, Components, Packages, Data Module, Hooks, SlotFills, theme.json |
| [WordPress Code Reference](https://developer.wordpress.org/reference/) | Functions, classes, hooks, methods reference |
| [WordPress Developer Blog](https://developer.wordpress.org/news/) | Official development news, patterns, best practices |
| [Full Site Editing](https://fullsiteediting.com/) | FSE, block themes, theme.json, site editor |
| [Brian Coords](https://www.briancoords.com/) | Theme and block development reference |
| [Rich Tabor](https://richtabor.com/) | Gutenberg, blocks, and design reference |
