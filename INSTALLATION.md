# Quick Installation Guide

Follow these simple steps to get the Vtiger CRM REST API up and running.

## Prerequisites Check

Before starting, ensure you have:

```bash
# Check PHP version (need 8.0+)
php -v

# Check required extensions
php -m | grep -E "pdo|mysql|json|mbstring|openssl"

# Check MySQL
mysql --version

# Check Apache mod_rewrite
apache2ctl -M | grep rewrite
```

---

## Quick Start (5 Minutes)

### Step 1: Download/Clone Project

```bash
cd /var/www/html
# Clone or extract project files here
```

### Step 2: Configure Database

```bash
# Copy example file
cp config/database.example.php config/database.php

# Edit with your database details
nano config/database.php
```

Update these values:
```php
'host' => 'localhost',
'database' => 'vconn',          // Your Vtiger database name
'username' => 'root',             // Your database username
'password' => 'your_password',    // Your database password
```

### Step 3: Configure JWT

```bash
# Copy example file
cp config/jwt.example.php config/jwt.php

# Edit and set a strong secret key
nano config/jwt.php
```

Generate a random secret key:
```bash
# Linux/Mac
openssl rand -base64 32

# Or use online generator
# https://randomkeygen.com/
```

Update:
```php
'secret_key' => 'your_generated_secret_key_here',
```

### Step 4: Set Permissions (Linux/Mac)

```bash
# Set proper permissions
chmod -R 755 .
chown -R www-data:www-data .
```

### Step 5: Enable Apache mod_rewrite

```bash
# Ubuntu/Debian
sudo a2enmod rewrite
sudo systemctl restart apache2

# CentOS/RHEL
# Usually enabled by default
```

### Step 6: Test the API

Open browser or use curl:

```bash
# Health check
curl http://localhost/vtiger-api/public/

# Expected response:
# {
#   "success": true,
#   "message": "Vtiger CRM REST API",
#   "version": "1.0.0"
# }
```

### Step 7: Test Login

```bash
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"your_vtiger_password"}'
```

---

## Windows (XAMPP) Installation

### Step 1: Install XAMPP
- Download from https://www.apachefriends.org/
- Install with Apache and MySQL

### Step 2: Copy Files
```
Copy project folder to: C:\xampp\htdocs\vtiger-api\
```

### Step 3: Configure
- Copy `config\database.example.php` to `config\database.php`
- Copy `config\jwt.example.php` to `config\jwt.php`
- Edit both files with your settings

### Step 4: Start XAMPP
- Open XAMPP Control Panel
- Start Apache and MySQL

### Step 5: Test
```
http://localhost/vtiger-api/public/
```

---

## Docker Installation

### Step 1: Create docker-compose.yml

```yaml
version: '3.8'

services:
  web:
    image: php:8.1-apache
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
    depends_on:
      - db
    command: >
      bash -c "docker-php-ext-install pdo pdo_mysql
      && a2enmod rewrite
      && apache2-foreground"

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: vconn
    ports:
      - "3306:3306"
```

### Step 2: Run

```bash
docker-compose up -d
```

### Step 3: Access

```
http://localhost:8080/public/
```

---

## Verification Checklist

After installation, verify:

- [ ] Health check endpoint returns success
- [ ] Login with Vtiger credentials works
- [ ] JWT token is generated
- [ ] Dashboard endpoint returns data
- [ ] Tickets endpoint returns data
- [ ] No PHP errors in logs

### Check Logs

```bash
# Apache error log
tail -f /var/log/apache2/error.log

# Or XAMPP
# C:\xampp\apache\logs\error.log
```

---

## Common Issues & Solutions

### Issue 1: "Database connection failed"

**Solution:**
```bash
# Test MySQL connection
mysql -u root -p

# If connection works, check:
# - Database name in config/database.php
# - Username and password
# - Host (usually 'localhost')
```

### Issue 2: "404 Not Found" on all routes

**Solution:**
```bash
# Enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2

# Check .htaccess files exist:
ls -la .htaccess
ls -la public/.htaccess
```

### Issue 3: "Permission denied"

**Solution:**
```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/html/vtiger-api
sudo chmod -R 755 /var/www/html/vtiger-api
```

### Issue 4: "500 Internal Server Error"

**Solution:**
```bash
# Check PHP error log
tail -50 /var/log/apache2/error.log

# Enable display_errors temporarily
# Edit public/index.php
ini_set('display_errors', 1);
```

### Issue 5: CORS Errors

**Solution:**
Headers are already set in `public/index.php`. If still having issues, check browser console for specific errors.

---

## Import Postman Collection

1. Open Postman
2. Click "Import"
3. Select `POSTMAN_COLLECTION.json`
4. Update `base_url` variable to your actual URL
5. Run "Login" request first to set token
6. Token will auto-populate in other requests

---

## Security Recommendations

### Before Going to Production:

1. **Change JWT Secret**
   - Use strong random key
   - Never commit to version control

2. **Disable Error Display**
   ```php
   // public/index.php
   ini_set('display_errors', 0);
   ```

3. **Use HTTPS**
   ```bash
   # Get free SSL certificate
   sudo certbot --apache -d api.yourdomain.com
   ```

4. **Strong Database Password**
   - Use complex password
   - Restrict to localhost if possible

5. **File Permissions**
   - Directories: 755
   - Files: 644
   - Config files: 600 (if possible)

6. **Keep Updated**
   - Update PHP regularly
   - Update MySQL regularly
   - Monitor security advisories

---

## Next Steps

After successful installation:

1. **Read API Documentation**
   - See `README.md` for endpoint details
   - See `API_EXAMPLES.md` for request samples

2. **Test All Endpoints**
   - Use Postman collection
   - Verify admin vs. user access

3. **Integrate with Your Application**
   - Use JWT authentication
   - Handle token expiration
   - Implement error handling

4. **Monitor Performance**
   - Check logs regularly
   - Monitor response times
   - Optimize queries if needed

---

## Support Resources

- **Documentation:** `README.md`
- **API Examples:** `API_EXAMPLES.md`
- **Deployment Guide:** `DEPLOYMENT.md`
- **Project Structure:** `PROJECT_STRUCTURE.md`

---

## Quick Reference

### Configuration Files
- `config/database.php` - Database settings
- `config/jwt.php` - JWT settings

### Entry Point
- `public/index.php` - Main application file

### Logs
- Apache: `/var/log/apache2/error.log`
- XAMPP: `C:\xampp\apache\logs\error.log`

### Default URL
- Development: `http://localhost/vtiger-api/public/`
- Production: `https://api.yourdomain.com/`

---

## Testing Credentials

Use your existing Vtiger CRM user credentials:
- Username: Your Vtiger username
- Password: Your Vtiger password

The API uses the same authentication as Vtiger CRM.

---

That's it! You're ready to use the Vtiger CRM REST API. 🚀

