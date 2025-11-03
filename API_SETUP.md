# API Setup Guide

This guide will help you set up the PHP backend API for the Wikimedia Commons Dashboard.

## Quick Start (Development)

### 1. Start PHP Development Server

```bash
# Navigate to the project root
cd /path/to/mist-commons

# Start PHP built-in server on port 8000
php -S localhost:8000
```

### 2. Start Vue.js Development Server

Open a new terminal:

```bash
# Install dependencies (if not already done)
npm install
# or
pnpm install

# Start development server
npm run dev
# or
pnpm dev
```

### 3. Test the APIs

**Categories API:**
```bash
curl http://localhost:8000/api/categories.php
```

**Dashboard API (with mock data):**
```bash
curl "http://localhost:8000/api/dashboard.php?category=Images_from_Wiki_Loves_Birds_India_2024_(maintenance-earth)&mock=1"
```

## Production Setup

### 1. Web Server Configuration

#### Apache Configuration

Add to your `.htaccess` file:

```apache
# Enable CORS
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Accept"

# Handle preflight requests
RewriteEngine On
RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^(.*)$ $1 [R=200,L]

# Redirect API calls
RewriteRule ^api/(.*)$ api/$1 [L]
```

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your/project;
    index index.html;

    # Handle CORS
    add_header Access-Control-Allow-Origin *;
    add_header Access-Control-Allow-Methods "GET, POST, OPTIONS";
    add_header Access-Control-Allow-Headers "Content-Type, Accept";

    # Handle preflight requests
    if ($request_method = 'OPTIONS') {
        return 200;
    }

    # API endpoints
    location ~ ^/api/(.+\.php)$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root/api/$1;
        include fastcgi_params;
    }

    # Serve static files
    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

### 2. Database Configuration

#### For Wikimedia Toolforge

Update `api/config.php`:

```php
<?php
$dbConfig = [
    'host' => 'commonswiki.analytics.db.svc.wikimedia.cloud',
    'dbname' => 'commonswiki_p',
    'username' => 'your_toolforge_username',
    'password' => 'your_toolforge_password',
    'charset' => 'utf8mb4'
];
?>
```

#### For Local Development with MySQL

```php
<?php
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'your_local_db',
    'username' => 'your_username', 
    'password' => 'your_password',
    'charset' => 'utf8mb4'
];
?>
```

### 3. Environment Variables

Create a `.env` file in the project root:

```bash
# For production
VITE_API_BASE_URL=/api
VITE_API_TIMEOUT=60000
VITE_APP_ENV=production

# For development with custom PHP server
VITE_PHP_SERVER_URL=http://localhost:8000
```

## API Endpoints

### Categories API

**Endpoint:** `/api/categories.php`  
**Method:** GET  
**Description:** Returns list of available contest categories

**Response:**
```json
{
  "success": true,
  "categories": [
    {
      "id": "wlb-india-2024",
      "name": "Wiki Loves Birds India 2024",
      "slug": "wiki-loves-birds-india-2024",
      "description": "Photography contest celebrating Indian bird diversity",
      "categoryName": "Images_from_Wiki_Loves_Birds_India_2024_(maintenance-earth)",
      "icon": "🦅",
      "year": "2024",
      "color1": "#3B82F6",
      "color2": "#1D4ED8"
    }
  ],
  "count": 6,
  "timestamp": "2024-11-03 12:00:00"
}
```

### Dashboard API

**Endpoint:** `/api/dashboard.php`  
**Method:** GET  
**Parameters:**
- `category` (required): The exact category name from Wikimedia Commons
- `refresh` (optional): Set to "1" to bypass cache
- `mock` (optional): Set to "1" to use mock data for testing

**Example:**
```
/api/dashboard.php?category=Images_from_Wiki_Loves_Birds_India_2024_(maintenance-earth)
```

**Response:**
```json
{
  "success": true,
  "rows": [
    [1, "category_name", "filename.jpg", "20241103", "20241103120000", 2048000, "{metadata}", "username"]
  ],
  "count": 1250,
  "timestamp": "2024-11-03 12:00:00",
  "category": "Images_from_Wiki_Loves_Birds_India_2024_(maintenance-earth)",
  "cached": false
}
```

**Data Structure:**
Each row in the `rows` array contains:
- `[0]` - Page ID
- `[1]` - Category name
- `[2]` - Filename
- `[3]` - Upload date (YYYYMMDD)
- `[4]` - Upload timestamp (YYYYMMDDHHMMSS)
- `[5]` - File size in bytes
- `[6]` - Metadata JSON string
- `[7]` - Username

## Troubleshooting

### Common Issues

1. **CORS Errors**
   - Ensure proper headers are set in PHP files
   - Check web server CORS configuration
   - Use browser developer tools to check preflight requests

2. **API Not Loading**
   - Check if PHP server is running on correct port
   - Verify Vite proxy configuration
   - Check browser console for error messages

3. **Database Connection Issues**
   - Verify database credentials in `config.php`
   - Check network connectivity to database server
   - Use `?mock=1` parameter to test with mock data

4. **Empty Categories**
   - Check if `api/categories.php` has proper categories defined
   - Verify JSON format in `api/categories.json` if using external file
   - Check browser network tab for API response

### Development Testing

```bash
# Test categories API
curl -i http://localhost:8000/api/categories.php

# Test dashboard API with mock data
curl -i "http://localhost:8000/api/dashboard.php?category=test&mock=1"

# Test with real category (requires database)
curl -i "http://localhost:8000/api/dashboard.php?category=Images_from_Wiki_Loves_Birds_India_2024_(maintenance-earth)"
```

### Logging

Enable PHP error logging to debug issues:

```php
// Add to the top of your PHP files for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

## Security Considerations

1. **Input Validation**: All user inputs are validated and sanitized
2. **SQL Injection Prevention**: Using prepared statements
3. **CORS Configuration**: Properly configured for your domain
4. **Rate Limiting**: Consider implementing rate limiting for production
5. **Error Handling**: Sensitive information is not exposed in error messages

## Performance Optimization

1. **Caching**: APIs use file-based caching (1-hour default)
2. **Database Indexing**: Ensure proper indexes on categorylinks and image tables
3. **Query Limits**: Dashboard API limits results to 10,000 rows
4. **Compression**: Enable gzip compression on your web server

## Support

If you encounter issues:

1. Check the browser console for JavaScript errors
2. Check the network tab for API request/response details
3. Review PHP error logs
4. Test APIs directly using curl or Postman
5. Use mock data mode for initial testing