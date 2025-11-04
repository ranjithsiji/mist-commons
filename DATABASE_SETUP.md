# Database Setup for Mist Commons

This document explains how to set up the database connection for the Mist Commons project to work with Wikimedia Commons replica database.

## Quick Fix for Statistics Issue

If your statistics are not working, the most likely cause is missing database credentials. Follow these steps:

### 1. Check Current Data Source

Your API currently has different modes:
- **Real Database** (default): Connects to `commonswiki.analytics.db.svc.wikimedia.cloud`
- **Mock Data**: Use `?mock=1` parameter for testing
- **Sample Data**: Use `?sample=1` parameter for sample JSON data

### 2. Set Up Database Credentials

#### For Toolforge Deployment:

1. **Get your Toolforge credentials:**
   - Go to [Toolsadmin](https://toolsadmin.wikimedia.org/tools/id/YOUR_TOOL_NAME)
   - Note your tool username (usually starts with 's' followed by numbers)
   - Get your database password

2. **Create the credentials file:**
   ```bash
   # On Toolforge, create the file at:
   /data/project/YOUR_TOOL_NAME/replica.my.cnf
   
   # Content should be:
   [client]
   user = s12345  # Your actual tool username
   password = YOUR_ACTUAL_PASSWORD
   ```

3. **Set proper permissions:**
   ```bash
   chmod 600 /data/project/YOUR_TOOL_NAME/replica.my.cnf
   ```

#### For Local Development:

1. **Copy the example file:**
   ```bash
   cp api/replica.my.cnf.example api/replica.my.cnf
   ```

2. **Edit with your credentials:**
   ```bash
   nano api/replica.my.cnf
   ```

3. **Or use environment variables:**
   ```bash
   export TOOL_REPLICA_USER="s12345"
   export TOOL_REPLICA_PASSWORD="your_password"
   ```

### 3. Test the Connection

Test your API endpoint:

```bash
# Test with a real category
curl "https://your-domain.com/api/dashboard.php?category=Birds_of_India"

# Test with mock data (should always work)
curl "https://your-domain.com/api/dashboard.php?category=Test&mock=1"

# Test with sample data
curl "https://your-domain.com/api/dashboard.php?category=Test&sample=1"
```

### 4. Check Error Logs

If connection fails, check the PHP error logs:

```bash
# On Toolforge
tail -f /data/project/YOUR_TOOL_NAME/logs/error.log

# Local development
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/nginx/error.log
```

## Database Schema Information

The API queries these main tables from `commonswiki_p` database:

- **`categorylinks`**: Links between categories and pages
- **`page`**: Page information (namespace 6 = File namespace)
- **`image`**: Image metadata and file information
- **`actor`**: User information for uploaders

## Troubleshooting

### Common Issues:

1. **"Database connection failed"**
   - Check if `replica.my.cnf` exists and has correct credentials
   - Verify file permissions (should be 600)
   - Ensure you're using the correct Toolforge username/password

2. **"Category not found" with valid categories**
   - The category name should match exactly as it appears on Commons
   - Try without the "Category:" prefix
   - Check for special characters or encoding issues

3. **"Mock data" always returned**
   - This was the main issue - now fixed in the code
   - The system now properly checks for real database connection first

4. **Empty results for valid categories**
   - Some categories might not have files directly (only subcategories)
   - Try a category you know has images, like "Birds_of_India"

### Testing Different Categories:

```bash
# Large category with many images
curl "your-api/dashboard.php?category=Photographs_by_User:YourUsername"

# Specific subject category
curl "your-api/dashboard.php?category=Birds_of_Kerala"

# Check if category exists first
curl "your-api/categories.php?validate=CategoryName"
```

### Performance Notes:

- The API caches results for 1 hour by default
- Use `?refresh=1` to bypass cache during testing
- Large categories (>10,000 files) are limited to prevent timeouts

## Security Notes

- Never commit `replica.my.cnf` to Git (it's in .gitignore)
- Keep database credentials secure
- The replica databases are read-only, so no data modification is possible
- All queries are parameterized to prevent SQL injection

## Next Steps

After setting up the database:

1. Test with a small category first
2. Monitor the logs for any connection issues
3. Set up proper caching in production
4. Consider adding error monitoring/alerting

For more information about Wikimedia replica databases, see:
- [Toolforge Database Documentation](https://wikitech.wikimedia.org/wiki/Help:Toolforge/Database)
- [Commons Database Schema](https://www.mediawiki.org/wiki/Manual:Database_layout)