#!/usr/bin/env bash
# Exit on error
set -o errexit

# Install PHP and required extensions
sudo apt-get update
sudo apt-get install -y php php-common php-curl php-mbstring php-mysql php-xml

# Install Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
chmod -R 755 storage bootstrap/cache

# Generate application key
php artisan key:generate
