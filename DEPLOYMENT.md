# Deployment Guide

This guide will walk you through deploying the Vtiger CRM REST API on various environments.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Local Development Setup](#local-development-setup)
3. [Production Deployment](#production-deployment)
4. [Testing](#testing)
5. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Minimum Requirements

- **PHP:** 8.0 or higher
- **MySQL:** 5.7+ or MariaDB 10.3+
- **Web Server:** Apache 2.4+ with mod_rewrite
- **Memory:** 256 MB RAM minimum
- **Disk Space:** 50 MB

### PHP Extensions Required

- `pdo`
- `pdo_mysql`
- `json`
- `mbstring`
- `openssl`

### Check PHP Version and Extensions

```bash
php -v
php -m | grep -E "pdo|mysql|json|mbstring|openssl"
```

---

## Local Development Setup

### Windows (XAMPP)

1. **Install XAMPP**
   - Download from https://www.apachefriends.org/
   - Install with Apache and MySQL

2. **Copy Project Files**
   ```
   Copy project to: C:\xampp\htdocs\vtiger-api\
   ```

3. **Configure Database**
   - Open `config/database.php`
   - Update connection details

4. **Start Services**
   - Open XAMPP Control Panel
   - Start Apache and MySQL

5. **Access API**
   ```
   http://localhost/vtiger-api/public/
   ```

### Linux (Ubuntu/Debian)

1. **Install Apache, PHP, and MySQL**
   ```bash
   sudo apt update
   sudo apt install apache2 php8.1 php8.1-mysql php8.1-mbstring php8.1-xml mysql-server
   ```

2. **Enable mod_rewrite**
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

3. **Copy Project Files**
   ```bash
   sudo cp -r vtiger-api /var/www/html/
   ```

4. **Set Permissions**
   ```bash
   sudo chown -R www-data:www-data /var/www/html/vtiger-api
   sudo chmod -R 755 /var/www/html/vtiger-api
   ```

5. **Configure Apache**
   
   Edit `/etc/apache2/sites-available/000-default.conf`:
   ```apache
   <Directory /var/www/html/vtiger-api>
       Options Indexes FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>
   ```

6. **Restart Apache**
   ```bash
   sudo systemctl restart apache2
   ```

7. **Access API**
   ```
   http://localhost/vtiger-api/public/
   ```

### macOS (MAMP)

1. **Install MAMP**
   - Download from https://www.mamp.info/

2. **Copy Project**
   ```
   Copy to: /Applications/MAMP/htdocs/vtiger-api/
   ```

3. **Configure and Start MAMP**

4. **Access API**
   ```
   http://localhost:8888/vtiger-api/public/
   ```

---

## Production Deployment

### Shared Hosting (cPanel)

1. **Upload Files**
   - Use FTP/SFTP or File Manager
   - Upload to `public_html/api/` or similar

2. **Configure Database**
   - Update `config/database.php` with hosting database details

3. **Set Permissions**
   - Directories: 755
   - Files: 644

4. **Verify .htaccess**
   - Ensure .htaccess files are present and not hidden

5. **Test API**
   ```
   https://yourdomain.com/api/public/
   ```

### VPS/Dedicated Server (Ubuntu)

1. **Update System**
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

2. **Install LAMP Stack**
   ```bash
   sudo apt install apache2 mysql-server php8.1 php8.1-mysql php8.1-mbstring php8.1-xml libapache2-mod-php8.1
   ```

3. **Enable Firewall**
   ```bash
   sudo ufw allow 'Apache Full'
   sudo ufw enable
   ```

4. **Upload Application**
   ```bash
   cd /var/www/html
   sudo git clone <repository> vtiger-api
   # Or use SCP/SFTP to upload files
   ```

5. **Configure Database**
   ```bash
   sudo nano /var/www/html/vtiger-api/config/database.php
   ```

6. **Set Permissions**
   ```bash
   sudo chown -R www-data:www-data /var/www/html/vtiger-api
   sudo chmod -R 755 /var/www/html/vtiger-api
   ```

7. **Configure Apache Virtual Host**
   
   Create `/etc/apache2/sites-available/vtiger-api.conf`:
   ```apache
   <VirtualHost *:80>
       ServerName api.yourdomain.com
       DocumentRoot /var/www/html/vtiger-api/public

       <Directory /var/www/html/vtiger-api/public>
           Options -Indexes +FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/vtiger-api-error.log
       CustomLog ${APACHE_LOG_DIR}/vtiger-api-access.log combined
   </VirtualHost>
   ```

8. **Enable Site and mod_rewrite**
   ```bash
   sudo a2ensite vtiger-api.conf
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

9. **SSL Certificate (Let's Encrypt)**
   ```bash
   sudo apt install certbot python3-certbot-apache
   sudo certbot --apache -d api.yourdomain.com
   ```

### Docker Deployment

**Dockerfile:**
```dockerfile
FROM php:8.1-apache

# Install MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable mod_rewrite
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80
```

**docker-compose.yml:**
```yaml
version: '3.8'

services:
  web:
    build: .
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: vconn
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

**Run:**
```bash
docker-compose up -d
```

---

## Testing

### Health Check

```bash
curl http://localhost/vtiger-api/public/
```

Expected response:
```json
{
  "success": true,
  "message": "Vtiger CRM REST API",
  "version": "1.0.0"
}
```

### Test Login

```bash
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}'
```

### Test with Token

```bash
TOKEN="your_token_here"

curl -X GET http://localhost/vtiger-api/public/dashboard \
  -H "Authorization: Bearer $TOKEN"
```

---

## Troubleshooting

### Issue: 404 Not Found on All Routes

**Solution:**
1. Check if mod_rewrite is enabled:
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

2. Verify .htaccess files exist in root and public folder

3. Check Apache configuration allows .htaccess:
   ```apache
   AllowOverride All
   ```

### Issue: 500 Internal Server Error

**Solution:**
1. Check PHP error logs:
   ```bash
   sudo tail -f /var/log/apache2/error.log
   ```

2. Enable error display in development:
   ```php
   // In public/index.php
   ini_set('display_errors', 1);
   ```

3. Check file permissions

### Issue: Database Connection Failed

**Solution:**
1. Verify database credentials in `config/database.php`

2. Test MySQL connection:
   ```bash
   mysql -u root -p
   ```

3. Grant proper privileges:
   ```sql
   GRANT ALL PRIVILEGES ON vconn.* TO 'user'@'localhost';
   FLUSH PRIVILEGES;
   ```

### Issue: JWT Token Invalid

**Solution:**
1. Verify JWT secret key is set in `config/jwt.php`

2. Check token expiration time

3. Ensure Authorization header format:
   ```
   Authorization: Bearer <token>
   ```

### Issue: CORS Errors

**Solution:**
Add CORS headers in `public/index.php`:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

---

## Security Checklist for Production

- [ ] Change JWT secret key to a strong random value
- [ ] Disable error display (`display_errors = 0`)
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Enable HTTPS with SSL certificate
- [ ] Use strong database password
- [ ] Restrict database access to localhost only
- [ ] Enable firewall rules
- [ ] Regular backups
- [ ] Keep PHP and MySQL updated
- [ ] Monitor error logs regularly

---

## Performance Optimization

### Enable OPcache

Edit `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

### Enable Gzip Compression

Add to Apache config:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE application/json
</IfModule>
```

### Database Indexing

Ensure proper indexes on:
- `vtiger_crmentity.crmid`
- `vtiger_crmentity.smownerid`
- `vtiger_troubletickets.ticketid`
- `vtiger_ticketcomments.ticketid`

---

## Monitoring

### Apache Access Logs
```bash
tail -f /var/log/apache2/access.log
```

### Apache Error Logs
```bash
tail -f /var/log/apache2/error.log
```

### MySQL Slow Query Log
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

---

## Support

For deployment issues, please refer to:
- Apache documentation: https://httpd.apache.org/docs/
- PHP documentation: https://www.php.net/docs.php
- MySQL documentation: https://dev.mysql.com/doc/



