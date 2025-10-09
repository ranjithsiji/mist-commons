# Wikimedia Commons Analytics Dashboard

A modular Vue.js dashboard for analyzing Wikimedia Commons photo contests.

## Quick Start

```bash
# Install dependencies
npm install

# Run development server
npm run dev

# Build for production
npm run build
```

## Configuration

1. Edit `api/categories.php` to add your categories
2. Update `api/config.php` with database credentials
3. Create cache directory: `mkdir cache && chmod 755 cache`

## Documentation

- See QUICKSTART.md for quick setup guide
- See PROJECT_STRUCTURE.md for architecture details
- See README.md for full documentation

## Features

- 📊 Multiple photo contests support
- 🗺️ Interactive maps with OpenStreetMap
- 📈 Beautiful charts and statistics
- 💾 Intelligent caching system
- 📱 Fully responsive design

## Tech Stack

- Vue.js 3 + Vite
- Tailwind CSS
- Chart.js
- Leaflet.js
- PHP 8.1+
- MySQL/MariaDB
