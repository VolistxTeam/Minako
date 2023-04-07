# Minako API
Minako is an PHP & REST API for anime. Based on Anime Notifier and MAL.

### Requirements
- PHP 8.1 or Above
- PHP Imagick, APCu and Required Extension for Laravel/Lumen
- MySQL 8.0 or Above / MariaDB 10.5 or Above

### Installation
Run commands:
```
composer install
cp .env.example .env
php artisan key:generate
php artisan cloudflare:reload
php artisan stackpath:reload
php artisan migrate
```

Do not forget to set a cronjob for production:
```
* * * * * php /path/to/artisan schedule:run
```

### Build Database
Run commands:
```
php artisan minako:notify:anime
php artisan minako:notify:characters
php artisan minako:notify:company
php artisan minako:notify:relation
php artisan minako:notify:character-relation
php artisan minako:notify:thumbnail
php artisan minako:notify:character-images
php artisan minako:mal:episodes
php artisan minako:ohys:download

php artisan scout:import "App\Models\MALAnime"
php artisan scout:import "App\Models\NotifyAnime"
php artisan scout:import "App\Models\NotifyCharacter"
php artisan scout:import "App\Models\NotifyCharacterRelation"
php artisan scout:import "App\Models\NotifyCompany"
php artisan scout:import "App\Models\NotifyRelation"
php artisan scout:import "App\Models\OhysRelation"
php artisan scout:import "App\Models\OhysTorrent"
```

### Swoole Setup
Run Laravel/Lumen Swoole using this package:
```
php bin/laravels start -i
```

If you want the Swoole server to run after reboot, add the following line to your crontab:
```
@reboot php /path/to/bin/laravels start -i
```

For supervisor, check following configuration:
```
[program:minako-api-swoole-worker]
directory=/var/www/vhosts/minako.moe/api.minako.moe
command=php81 bin/laravels start -i
numprocs=1
autostart=true
autorestart=true
startretries=3
user=minako.moe
redirect_stderr=true
stdout_logfile=/var/log/supervisor/%(program_name)s.log
```

Check more information about it at [hhxsv5/laravel-s](https://github.com/hhxsv5/laravel-s)