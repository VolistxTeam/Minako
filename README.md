# Minako API
Minako is an PHP & REST API for anime. Based on Anime Notifier and MAL.

Confirmed to work with LiteSpeed + Laravel Swoole + APCu + MySQL.

### Requirements
- PHP 8.0 or Above
- PHP Swoole, Imagick, APCu and Required Extension for Laravel/Lumen
- Meilisearch
- MySQL 8.0 or Above / MariaDB 10.5 or Above

### Installation
You have to install Meilisearch in your system, and set MEILISEARCH_KEY if required. After that, run follow commands:
```
composer install
php artisan key:generate
php artisan migrate
```

Do not forget to set a cronjob for production:
```
* * * * * php /path/to/artisan schedule:run
```

Run Laravel/Lumen Swoole using this package:
```
php artisan swoole:http start
```

If you want the Swoole server to run after reboot, add the following line to your crontab:
```
@reboot php /path/to/artisan swoole:http start
```