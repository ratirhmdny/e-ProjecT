# E-SPP System Installation Guide

## Requirements

### Server Requirements
- **Web Server**: Apache/Nginx
- **PHP**: Version 8.0 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.2+
- **Operating System**: Windows/Linux/MacOS

### PHP Extensions Required
- PDO
- PDO_MySQL
- mbstring
- openssl
- gd
- fileinfo
- intl

### Browser Support
- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## Installation Steps

### 1. Download and Extract
```bash
# Clone from repository or download zip file
git clone https://github.com/yourusername/e-spp-system.git
cd e-spp-system
```

### 2. Database Setup

#### Create Database
```sql
CREATE DATABASE e_spp_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### Import Database Structure
```bash
# Using MySQL command line
mysql -u root -p e_spp_system < sql/database_structure.sql

# Using phpMyAdmin
# Import the database_structure.sql file through the import tab
```

### 3. Configuration

#### Database Configuration
Edit `config/database.php` and update the database connection settings:

```php
private $host = 'localhost';
private $db_name = 'e_spp_system';
private $username = 'root';        // Your MySQL username
private $password = '';            // Your MySQL password
```

#### Application Configuration
Edit `config/config.php` for additional settings:

```php
// Environment
define('ENVIRONMENT', 'development'); // Change to 'production' for live site

// Base URL will be automatically detected
// You can manually set it if needed:
// define('BASE_URL', 'https://yourdomain.com/');
```

### 4. Web Server Configuration

#### Apache
- Ensure `mod_rewrite` is enabled
- Set document root to the project directory
- The included `.htaccess` file will handle URL rewriting

#### Nginx
Add the following location block to your server configuration:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock; # Adjust PHP version
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 5. File Permissions

Set proper permissions for the following directories:

```bash
# Linux/MacOS
chmod 755 uploads/
chmod 755 uploads/proofs/
chmod 755 uploads/exports/
chmod 644 .htaccess
chmod 644 config/*.php

# Make sure PHP can write to these directories
chown -R www-data:www-data uploads/ # For Ubuntu/Debian
# or
chown -R apache:apache uploads/    # For CentOS/RHEL
```

### 6. Testing the Installation

#### Access the Application
Open your web browser and navigate to:
- `http://localhost/e-spp-system/` (if in subdirectory)
- `http://localhost/` (if in root directory)

#### Default Login Credentials
**Admin Account:**
- Email: `admin@university.ac.id`
- Password: `admin123`

**Staff Account:**
- Email: `staff01@university.ac.id`
- Password: `admin123`

**Note:** Change these passwords immediately after first login!

### 7. Post-Installation Setup

#### Change Default Passwords
1. Login with admin credentials
2. Go to Profile → Change Password
3. Update to a strong password

#### Configure Programs
1. Login as admin
2. Go to Manajemen → Program Studi
3. Add/edit programs as needed

#### Add Students
1. Go to Manajemen → Mahasiswa
2. Click "Tambah Mahasiswa"
3. Fill in the required information

## Security Considerations

### 1. Production Environment
Change `ENVIRONMENT` to `'production'` in `config/config.php`:

```php
define('ENVIRONMENT', 'production');
```

### 2. Database Security
- Use a dedicated database user with limited privileges
- Regularly backup your database
- Keep database credentials secure

### 3. File Upload Security
- Uploaded files are stored in `uploads/proofs/`
- Only specific file types are allowed (JPG, PNG, PDF)
- Maximum file size is 5MB

### 4. Session Security
- Sessions are configured with secure parameters
- Session timeout is set to 24 hours
- CSRF protection is implemented

### 5. Rate Limiting
- Login attempts are rate-limited
- Failed attempts are logged
- IP-based blocking after multiple failures

## Troubleshooting

### Common Issues

#### 1. "Connection error" on login
- Check database credentials in `config/database.php`
- Ensure MySQL is running
- Verify database exists and user has privileges

#### 2. "Page not found" errors
- Ensure `.htaccess` file exists
- Check if `mod_rewrite` is enabled (Apache)
- Verify URL rewriting configuration (Nginx)

#### 3. "Permission denied" errors
- Check file permissions (see step 5)
- Ensure PHP can write to upload directories
- Check web server user permissions

#### 4. Session issues
- Clear browser cookies
- Check session save path permissions
- Verify session configuration in php.ini

#### 5. CSS/JS not loading
- Check file paths in HTML
- Ensure files exist in `assets/` directory
- Verify web server can serve static files

### Debug Mode
For development, enable error display in `config/config.php`:

```php
define('ENVIRONMENT', 'development');
```

This will show detailed error messages.

## Backup and Maintenance

### Database Backup
```bash
# Create backup
mysqldump -u root -p e_spp_system > backup_$(date +%Y%m%d).sql

# Restore backup
mysql -u root -p e_spp_system < backup_20240101.sql
```

### File Backup
```bash
# Backup uploaded files
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/

# Backup entire application
tar -czf e-spp-backup_$(date +%Y%m%d).tar.gz \
  --exclude='uploads/proofs/*' \
  --exclude='uploads/exports/*' \
  .
```

## Support

For issues and feature requests:
1. Check this installation guide
2. Review the troubleshooting section
3. Check system logs
4. Contact system administrator

## License

This E-SPP System is provided as-is for educational purposes. Please ensure you comply with your institution's data privacy and security policies.