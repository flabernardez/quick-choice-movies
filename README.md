# Quick Choice Movies

A WordPress plugin that implements a quick choice game interface where users can choose between two options (movies, video games, books, etc.) until only one winner remains.

## Features

- Custom Post Type "Quick Choices" for managing game lists
- Visual editor with drag & drop for organizing items
- Integration with external APIs:
    - TMDB (Movies)
    - RAWG (Video Games)
    - Google Books (Books)
- Gutenberg block for displaying games
- Cookie-based progress saving
- Reset and save game functionality
- Responsive design

## Installation

1. Clone or download this repository to your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/flabernardez/quick-choice-movies.git
   ```

2. Install dependencies:
   ```bash
   cd quick-choice-movies
   npm install
   ```

3. Build the assets:
   ```bash
   npm run build
   ```

   For development with watch mode:
   ```bash
   npm run start
   ```

4. Activate the plugin in WordPress admin

## Configuration

1. Go to **Quick Choices → Settings**
2. Add your API keys:
    - **TMDB API Key**: Get from [TMDB](https://www.themoviedb.org/settings/api)
    - **RAWG API Key**: Get from [RAWG](https://rawg.io/apidocs)
    - **Google Books API Key**: Get from [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
3. Configure cookie expiration (default: 30 days)

## Usage

### Creating a Game List

1. Go to **Quick Choices → Add New**
2. Give your list a title
3. Add items using either:
    - **Manual upload**: Click "Add Manual Item" and upload images
    - **API Search**: Click "Search API", select a source, and search
4. Drag and drop to reorder items
5. Publish your list

### Adding a Game to a Page

1. Edit a page or post
2. Add the "Quick Choice Game" block
3. Select your game list from the block settings
4. Publish

## Development

### File Structure

```
quick-choice-movies/
├── includes/              # PHP classes
│   ├── class-cpt-quick-choices.php
│   ├── class-meta-fields.php
│   ├── class-admin-settings.php
│   └── class-game-manager.php
├── src/                   # Source files (React/JS/SCSS)
│   ├── game-block/
│   └── meta-fields/
├── build/                 # Compiled assets
├── public/               # Public-facing assets
│   ├── js/
│   └── css/
└── quick-choice-movies.php
```

### NPM Scripts

- `npm run build` - Build for production
- `npm run start` - Development mode with watch
- `npm run lint:js` - Lint JavaScript files
- `npm run format` - Format code

## Requirements

- WordPress 6.6+
- PHP 7.0+
- Node.js (for development)

## License

GPL-2.0-or-later

## Author

Flavia Bernárdez Rodríguez
