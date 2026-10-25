#!/bin/sh
set -e

attempt=1
until php artisan migrate --force; do
    if [ "$attempt" -ge 30 ]; then
        echo "Migrasi gagal setelah 30 percobaan." >&2
        exit 1
    fi

    echo "Database belum siap; mencoba migrasi lagi ($attempt/30)..." >&2
    attempt=$((attempt + 1))
    sleep 2
done

php artisan db:seed --class=AdminSeeder --force

exec php artisan serve --host=0.0.0.0 --port=8000
