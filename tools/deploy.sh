#!/bin/bash
set -euo pipefail

# Usage: ./deploy.sh {tag or commit}
# Folder structure
# main-folder
#   |-- releases
#     |-- v0.1.1
#   |-- shared
#     |-- storage

MAIN_PATH=$(pwd)

if [ -z "${1:-}" ]
then
    release_name=$(date +"%Y%m%d_%H%M%S")
else
    release_name=$1
fi

echo 'Creating release folder'
mkdir -p "releases/$release_name"

cd "releases/$release_name" || exit

echo 'Cloning project'
git clone https://github.com/tuxonice/time-cube-dashboard.git .
git checkout implementation

echo 'Installing dependencies'
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader


echo 'Removing unneeded files/folders'
rm CLAUDE.md docker-compose.yml .dockerignore .env.example .gitignore Makefile
rm phpcs.xml phpstan.neon phpunit.xml README.md
rm -rf .claude docker .git tools tests


echo 'Creating symlinks'
ln -s "$MAIN_PATH/shared/.env" .env
rm -rf storage
ln -s "$MAIN_PATH/shared/storage" storage

echo 'Running database migrations'
php bin/console migrate --no-interaction

cd "$MAIN_PATH" || exit

echo 'Setup current document root'
rm -f current
ln -s "releases/$release_name/public/" current

# echo 'Cleaning up old releases (keeping last 5)'
# ls -1dt releases/*/ | tail -n +6 | xargs rm -rf

echo 'Done!'
