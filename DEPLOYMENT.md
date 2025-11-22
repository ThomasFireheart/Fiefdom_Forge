# Fiefdom Forge - Deployment Guide

This guide covers deploying Fiefdom Forge to a production server.

## Requirements

- PHP 8.x with extensions: pdo, pdo_sqlite, json, mbstring
- Composer
- Web server (Apache/Nginx) or PHP built-in server
- Write permissions for `database/`, `templates_c/`, and `cache/` directories

## Quick Start (Development)

```bash
# Clone the repository
git clone <repository-url>
cd FiefdomForge

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Edit .env with your settings
# APP_ENV=development
# APP_DEBUG=true

# Start development server
php -S localhost:8000 -t public_html
```

Visit `http://localhost:8000` in your browser.

## Production Deployment

### 1. Server Preparation

```bash
# Install required PHP extensions (Ubuntu/Debian)
sudo apt-get install php8.1 php8.1-sqlite3 php8.1-mbstring php8.1-json

# Create application directory
sudo mkdir -p /var/www/fiefdom-forge
sudo chown www-data:www-data /var/www/fiefdom-forge
```

### 2. Deploy Application

```bash
# Upload files to server
rsync -avz --exclude='database/*.sqlite' --exclude='.env' \
    ./ user@server:/var/www/fiefdom-forge/

# On the server, install dependencies
cd /var/www/fiefdom-forge
composer install --no-dev --optimize-autoloader

# Create and configure .env
cp .env.example .env
nano .env
```

### 3. Environment Configuration (.env)

```ini
# Production settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (SQLite path)
DB_PATH=database/fiefdom_forge.sqlite

# Session settings
SESSION_NAME=fiefdom_session
SESSION_LIFETIME=7200
```

### 4. Set Permissions

```bash
# Create required directories
mkdir -p database templates_c cache

# Set ownership and permissions
sudo chown -R www-data:www-data database templates_c cache
sudo chmod -R 755 database templates_c cache
```

### 5. Apache Configuration

Create `/etc/apache2/sites-available/fiefdom-forge.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/fiefdom-forge/public_html

    <Directory /var/www/fiefdom-forge/public_html>
        AllowOverride All
        Require all granted

        # URL rewriting
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [L,QSA]
    </Directory>

    # Protect sensitive directories
    <Directory /var/www/fiefdom-forge/database>
        Require all denied
    </Directory>

    <Directory /var/www/fiefdom-forge/src>
        Require all denied
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/fiefdom-error.log
    CustomLog ${APACHE_LOG_DIR}/fiefdom-access.log combined
</VirtualHost>
```

Enable the site:

```bash
sudo a2enmod rewrite
sudo a2ensite fiefdom-forge
sudo systemctl reload apache2
```

### 6. Nginx Configuration

Create `/etc/nginx/sites-available/fiefdom-forge`:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/fiefdom-forge/public_html;
    index index.php;

    # Deny access to sensitive files
    location ~ ^/(database|src|templates|vendor|\.env) {
        deny all;
        return 404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/fiefdom-forge /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## SSL/HTTPS (Recommended)

Use Let's Encrypt for free SSL certificates:

```bash
sudo apt-get install certbot python3-certbot-apache  # or python3-certbot-nginx
sudo certbot --apache -d yourdomain.com  # or --nginx
```

## Database Backup

The SQLite database is stored at `database/fiefdom_forge.sqlite`.

```bash
# Create backup
cp database/fiefdom_forge.sqlite database/backup_$(date +%Y%m%d).sqlite

# Automated daily backup (add to crontab)
0 3 * * * cp /var/www/fiefdom-forge/database/fiefdom_forge.sqlite /backups/fiefdom_$(date +\%Y\%m\%d).sqlite
```

## Security Checklist

- [ ] Set `APP_DEBUG=false` in production
- [ ] Set `APP_ENV=production`
- [ ] Ensure `.env` file is not web-accessible
- [ ] Database directory is not web-accessible
- [ ] Use HTTPS in production
- [ ] Regular database backups
- [ ] Keep PHP and dependencies updated

## Troubleshooting

### "Permission denied" errors
```bash
sudo chown -R www-data:www-data database templates_c cache
sudo chmod -R 755 database templates_c cache
```

### Database not initializing
Ensure PHP has the `pdo_sqlite` extension:
```bash
php -m | grep sqlite
```

### Blank pages
Enable error display temporarily:
```php
// In public_html/index.php (remove after debugging)
ini_set('display_errors', '1');
error_reporting(E_ALL);
```

### Session issues
Check that the `session.save_path` is writable:
```bash
php -i | grep session.save_path
```

## Maintenance

### Clear compiled templates
```bash
rm -rf templates_c/*
rm -rf cache/*
```

### Update dependencies
```bash
composer update --no-dev
```

## First Admin User

The first registered user is a regular player. To create an admin:

1. Register a user normally
2. Use SQLite to update their role:

```bash
sqlite3 database/fiefdom_forge.sqlite
UPDATE users SET role = 'admin' WHERE username = 'your_username';
.quit
```

Admin users can trigger events manually from the Chronicle page.
