#!/bin/sh
set -e

# Создаём директорию runtime для логов
mkdir -p /var/www/html/runtime/logs
chown -R www-data:www-data /var/www/html/runtime
chmod -R 755 /var/www/html/runtime

# Устанавливаем зависимости Composer при первом запуске
if [ ! -d /var/www/html/vendor ]; then
    COMPOSER_FLAGS="--no-interaction --no-progress --optimize-autoloader --working-dir=/var/www/html"
    if [ "$YII_ENV" != "dev" ]; then
        COMPOSER_FLAGS="--no-dev $COMPOSER_FLAGS"
    fi

    echo "Установка зависимостей Composer (YII_ENV=${YII_ENV:-prod})..."
    if ! composer install $COMPOSER_FLAGS; then
        echo "ОШИБКА: composer install завершился с ошибкой" >&2
        exit 1
    fi
fi

echo "Запуск PHP-FPM..."
exec "$@"
