#!/bin/bash

# oxid_setup.sh - One-stop setup script for OXID eShop development
# Usage: ./oxid_setup.sh [-e edition] [-b branch] [-c cleanup]
# Example: ./oxid_setup.sh -e EE -b b-7.0.x -c true

# Default values
edition="CE"
branch="b-7.0.x"
cleanup="false"
update="true"
theme="apex"
module_path="extensions/paypal"

# Parse command-line arguments
while getopts e:b:c:u:t:m: flag; do
  case "${flag}" in
    e) edition=${OPTARG} ;;
    b) branch=${OPTARG} ;;
    c) cleanup=${OPTARG} ;;
    u) update=${OPTARG} ;;
    t) theme=${OPTARG} ;;
    m) module_path=${OPTARG} ;;
    *) ;;
  esac
done

# Set up script directory for accessing parts scripts
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PARTS_DIR="${SCRIPT_DIR}/parts"

echo "Parts dir found: ${PARTS_DIR}"

# Helper function to find project root
find_project_root() {
  local current_dir="$(pwd)"
  while [[ "$current_dir" != "/" ]]; do
    if [[ -f "$current_dir/docker-compose.yml.dist" ]]; then
      echo "$current_dir"
      return 0
    fi
    current_dir="$(dirname "$current_dir")"
  done
  echo "$(pwd)"
  return 1
}

# Set up project root
PROJECT_ROOT=$(find_project_root)
echo "Project root is: $PROJECT_ROOT"
cd "$PROJECT_ROOT" || exit 1

echo -e "\033[1;37m\033[1;42mOXID eShop Setup: Edition: ${edition}, Branch: ${branch}\033[0m\n"

# Perform cleanup if requested
if [ "$cleanup" = "true" ]; then
  echo "Cleaning up previous installation..."

  # Stop any running containers
  if [ -f "docker-compose.yml" ]; then
    docker compose down --remove-orphans
  fi

  # Remove files and directories
  [ -d "source" ] && rm -rf source
  [ -e ".env" ] && rm .env
  [ -e "docker-compose.yml" ] && rm docker-compose.yml
  [ -e "containers/httpd/project.conf" ] && rm containers/httpd/project.conf
  [ -e "containers/httpd/custom.conf" ] && rm containers/httpd/custom.conf
  [ -e "containers/php/custom.ini" ] && rm containers/php/custom.ini
  [ -d "data/mysql" ] && rm -rf data/mysql/*
  [ -d "data/composer/cache" ] && rm -rf data/composer/cache

  echo "Cleanup completed"
fi

# Create .env file with proper substitutions
echo "Creating .env file..."
cat .env.dist | \
  sed "s/<userId>/$(id -u)/; s/<userName>/$(id -un)/; s/<groupId>/$(id -g)/; s/<groupName>/$(id -gn)/" \
  > .env

# Copy configuration files
echo "Copying configuration files..."
cp -n containers/httpd/project.conf.dist containers/httpd/project.conf
cp -n containers/httpd/custom.conf.dist containers/httpd/custom.conf
cp -n containers/php/custom.ini.dist containers/php/custom.ini
cp -n docker-compose.yml.dist docker-compose.yml

# Create or update custom.conf with ServerName
echo "Creating custom.conf with ServerName..."
cat > containers/httpd/custom.conf << 'EOF'
LoadModule proxy_module modules/mod_proxy.so
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule proxy_fcgi_module modules/mod_proxy_fcgi.so

LoadModule http2_module modules/mod_http2.so

ServerName localhost.local

<IfModule http2_module>
    Protocols h2 http/1.1
</IfModule>

<IfModule dir_module>
    DirectoryIndex index.php index.html
</IfModule>

DocumentRoot "/var/www/"
<Directory "/var/www/">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

<IfModule proxy_module>
    <FilesMatch "\.php$">
        SetHandler  "proxy:fcgi://php:9000"
    </FilesMatch>
</IfModule>
EOF

# Update Dockerfile in containers/httpd
echo "Updating Apache Dockerfile..."
cat > containers/httpd/Dockerfile << 'EOF'
FROM httpd:2.4-alpine

COPY custom.conf /usr/local/apache2/conf/custom.conf
COPY project.conf /usr/local/apache2/conf/project.conf

COPY certs/server.crt /usr/local/apache2/conf/server.crt
COPY certs/server.key /usr/local/apache2/conf/server.key
# Insert the Mutex override at the very top of httpd.conf
RUN sed -i '1i Mutex file:/tmp' /usr/local/apache2/conf/httpd.conf

# Uncomment SSL-related modules in the main configuration file
RUN sed -i \
    -e 's/^#\(Include .*httpd-ssl.conf\)/\1/' \
    -e 's/^#\(LoadModule .*mod_ssl.so\)/\1/' \
    -e 's/^#\(LoadModule .*mod_socache_shmcb.so\)/\1/' \
    /usr/local/apache2/conf/httpd.conf

# Append the correct include directives using the proper relative path
RUN printf "Include conf/custom.conf\n" >> /usr/local/apache2/conf/httpd.conf && \
    printf "Include conf/project.conf\n" >> /usr/local/apache2/conf/httpd.conf
EOF

# Ensure SSL certificates directory exists
echo "Creating SSL certificates directory..."
mkdir -p containers/httpd/certs

# Create self-signed SSL certificates if they don't exist
if [ ! -f "containers/httpd/certs/server.crt" ] || [ ! -f "containers/httpd/certs/server.key" ]; then
  echo "Generating self-signed SSL certificates..."
  openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout containers/httpd/certs/server.key \
    -out containers/httpd/certs/server.crt \
    -subj "/C=DE/ST=Bavaria/L=Fuerth/O=OXID eSales/OU=Development/CN=localhost.local"
fi

# Add services to docker-compose.yml with network configuration
echo "Adding services to docker-compose.yml..."

# Apache service
cat >> docker-compose.yml << 'EOF'
  apache:
    platform: linux/x86_64
    build:
      context: containers/httpd
    working_dir: /var/www/
    volumes:
      - ./source:/var/www:cached
    ports:
      - ${PORT_SHOP}:80
      - ${PORT_SSL_SHOP}:443
    networks:
      - paypal_network
      - shared_network

EOF

# PHP service
cat >> docker-compose.yml << 'EOF'
  php:
    platform: linux/x86_64
    build:
      context: containers/php
      args:
        PHP_VERSION: ${PHP_VERSION}
        HOST_USER_ID: ${HOST_USER_ID}
        HOST_GROUP_ID: ${HOST_GROUP_ID}
        HOST_USER_NAME: ${HOST_USER_NAME}
        HOST_GROUP_NAME: ${HOST_GROUP_NAME}
    links:
      - "apache:localhost.local"
      - "apache:oxideshop.local"
    volumes:
      - ./source:/var/www:cached
      - ./data/php:/var/sync:cached
      - ./data/composer:/home/${HOST_USER_NAME}/.composer/:cached
    user: ${HOST_USER_ID}:${HOST_GROUP_ID}
    depends_on:
      mailpit:
        condition: service_started
      apache:
        condition: service_started
      mysql:
        condition: service_healthy
    extra_hosts:
      - "host.docker.internal:host-gateway"
    networks:
      - paypal_network
      - shared_network

EOF

# Mailpit service
cat >> docker-compose.yml << 'EOF'
  mailpit:
    image: axllent/mailpit
    ports:
      - ${PORT_MAILPIT_SMTP}:1025 # smtp server
      - ${PORT_MAILPIT_WEBUI}:8025 # web ui
    networks:
      - paypal_network
      - shared_network

EOF

# MySQL service
cat >> docker-compose.yml << 'EOF'
  mysql:
    platform: linux/amd64
    image: oxidesales/oxideshop-docker-database:${MYSQL_VERSION}
    cap_add:
      - SYS_NICE  # CAP_SYS_NICE
    environment:
      MYSQL_DATABASE: ${MYSQL_DATABASE:-example}
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD:-root}
      MARIADB_DATABASE: ${MARIADB_DATABASE:-example}
    healthcheck:
      test: out=$$(mysqladmin ping -h localhost -P 3306 -u root --password=$${MYSQL_ROOT_PASSWORD} 2>&1); echo $$out | grep 'mysqld is alive' || { echo $$out; exit 1; }
      start_period: 30s
      interval: 2s
      retries: 180
    restart: always
    user: ${HOST_USER_ID}:${HOST_GROUP_ID}
    volumes:
      - ./data/mysql:/var/lib/mysql:delegated
    ports:
      - ${PORT_MYSQL}:3306 # to access mysql with local client
    networks:
      - paypal_network
      - shared_network

EOF

# Add Adminer service
cat >> docker-compose.yml << 'EOF'
  adminer:
    image: adminer:latest
    restart: always
    ports:
      - ${PORT_ADMINER:-8080}:8080
    environment:
      ADMINER_DEFAULT_SERVER: mysql
      ADMINER_DESIGN: pepa-linha
    networks:
      - paypal_network
      - shared_network

EOF

# Add network definitions
cat >> docker-compose.yml << 'EOF'
networks:
  paypal_network:
    driver: bridge
  shared_network:
    external: true
EOF

# Ensure the shared network exists
if ! docker network ls | grep -wq "shared_network"; then
  echo "Creating shared_network..."
  docker network create shared_network
else
  echo "shared_network already exists."
fi


source "${BASH_SOURCE[0]%/*}/include.sh"

# Find the module root and set the working directory
MODULE_ROOT=$(find_module_root)
echo "Module root is: $MODULE_ROOT"
PROJECT_ROOT=$(find_project_root)
echo "Project root is: $PROJECT_ROOT"

cd "$PROJECT_ROOT" || exit 1

$MODULE_ROOT/recipe/parts/b-7.0.x/start_shop.sh -e"${edition}" -u"false" || exit 1

mkdir -p "$PROJECT_ROOT"/source/extensions || exit 1
cp -r "$MODULE_ROOT" "$PROJECT_ROOT"/source/extensions/ || exit 1
git clone git@github.com:OXID-eSales/paypal-client.git --branch=v3.0.17 "$PROJECT_ROOT"/source/extensions/paypal-client || exit 1
mkdir -p ./source/var/configuration/environment/shops/1/modules
cp $MODULE_ROOT/recipe/environment/1.yaml ./source/var/configuration/environment/shops/1/modules/osc_paypal.yaml


$PROJECT_ROOT/source/extensions/paypal/recipe/parts/b-7.0.x/require_twig_components.sh -e"${edition}" -t"apex" || exit 1

# Require demodata package
docker compose exec -T \
  php composer config repositories.oxid-esales/oxideshop-demodata-ce \
  --json '{"type":"git", "url":"https://github.com/OXID-eSales/oxideshop_demodata_ce"}'
docker compose exec -T php composer require oxid-esales/oxideshop-demodata-ce:dev-b-7.0.x --no-update

# Require demodata package
docker compose exec -T \
  php composer config repositories.oxid-esales/oxideshop-demodata-pe \
  --json '{"type":"git", "url":"https://github.com/OXID-eSales/oxideshop_demodata_pe"}'
docker compose exec -T php composer require oxid-esales/oxideshop-demodata-pe:dev-b-7.0.x --no-update

# Require demodata package
docker compose exec -T \
  php composer config repositories.oxid-esales/oxideshop-demodata-ee \
  --json '{"type":"git", "url":"https://github.com/OXID-eSales/oxideshop_demodata_ee"}'
docker compose exec -T php composer require oxid-esales/oxideshop-demodata-ee:dev-b-7.0.x --no-update

# Install all preconfigured dependencies
docker compose exec -T php composer update --no-interaction

$PROJECT_ROOT/source/extensions/paypal/recipe/parts/shared/setup_database.sh

perl -pi\
  -e 'print "SetEnvIf Authorization \"(.*)\" HTTP_AUTHORIZATION=\$1\n\n" if $. == 1'\
  source/source/.htaccess

# Configure module in composer
docker compose exec -T \
  php composer config repositories.oxid-solution-catalysts/paypal \
  --json '{"type":"path", "url":"./extensions/paypal", "options": {"symlink": true}}' || exit 1
docker compose exec -T php composer require oxid-solution-catalysts/paypal-module:* --no-update || exit 1

# Configure module in composer
docker compose exec -T \
  php composer config repositories.oxid-solution-catalysts/paypal-client \
  --json '{"type":"path", "url":"./extensions/paypal-client", "options": {"symlink": true}}'
docker compose exec -T php composer require oxid-solution-catalysts/paypal-client:* --no-update || exit 1

# Install all preconfigured dependencies
docker compose exec -T php composer update --no-interaction
docker compose exec -T php bin/oe-console oe:setup:demodata
docker compose exec -T php bin/oe-console oe:theme:activate apex
docker compose exec -T php bin/oe-console oe:module:install extensions/paypal
docker compose exec -T php bin/oe-console oe:module:activate osc_paypal

$PROJECT_ROOT/source/extensions/paypal/recipe/parts/shared/create_admin.sh
# Register all related project packages git repositories

echo -e "\033[1;37m\033[1;42mInstallation is finished!\033[0m\n"
echo -e "\033[1;37m\033[1;42mYou can now access your shop at http://localhost.local/\033[0m\n"
echo -e "\033[1;37m\033[1;42mShop admin at http://localhost.local/admin\033[0m\n"
echo -e "\033[1;37m\033[1;42mYou can access the Adminer at http://localhost.local:8080/\033[0m\n"

rm -rf "$MODULE_ROOT"

cp $PROJECT_ROOT/source/extensions/paypal/tests/.env.dist $PROJECT_ROOT/source/extensions/paypal/tests/.env


exit 0
