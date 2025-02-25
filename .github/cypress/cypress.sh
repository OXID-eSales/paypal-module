#!/bin/bash

# Define colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color
SCRIPT_DIR=$(dirname "$(readlink -f "$0")")

set -e  # Exit on error
echo "Current directory: $(pwd)"

check_docker_compose_file() {
    if [ ! -f docker-compose.yml ]; then
        echo "Error: docker-compose.yml not found in current directory"
        exit 1
    fi
}

check_docker_compose() {
    if ! command -v docker compose &> /dev/null; then
        echo "Error: docker compose is not installed"
        exit 1
    fi
}


"$SCRIPT_DIR/set_shop_url.sh" https://oxideshop.local
#echo "Setting module configuration..."
#mkdir -p source/var/configuration/shops/
#cp "$SCRIPT_DIR/config/1.yaml" source/var/configuration/shops/1.yaml


echo -n "Locating oe-console ... "
cd source || exit 1
if [ -f 'bin/oe-console' ]; then
    OE_CONSOLE='bin/oe-console'
else
    if [ -f 'vendor/bin/oe-console' ]; then
    OE_CONSOLE='vendor/bin/oe-console'
    else
        error "Can't find oe-console in bin or vendor/bin!"
    fi
fi
cd ..
echo "OK, using '${OE_CONSOLE}'"

#docker compose exec -T php php ${OE_CONSOLE} oe:module:apply-configuration

echo "Stopping running containers..."
docker compose down --remove-orphans || {
    echo "Error stopping containers"
    exit 1
}

echo "Cleaning up unused networks..."
docker network prune -f || echo "No unused networks to prune"

check_docker_compose_file
check_docker_compose

echo "Ensuring default network exists..."
NETWORK_NAME="paypal_network"
if ! docker network inspect "$NETWORK_NAME" &>/dev/null; then
    echo "Network $NETWORK_NAME not found. Creating it..."
    docker network create "$NETWORK_NAME"
else
    echo "Network $NETWORK_NAME already exists."
fi


echo "Creating backup of docker-compose.yml..."
cp docker-compose.yml docker-compose.yml.backup

echo "Cleanup docker-compose.yml..."
sed -i '/^  cypress:/,/^  [a-z]/d' docker-compose.yml
sed -i '/^  selenium:/,/^  [a-z]/d' docker-compose.yml
sed -i '/^networks:/,/^  [a-z]/d' docker-compose.yml

echo "Checking if port 5900 is available..."
# Loop a few times to try to free the port
attempt=0
max_attempts=5
while lsof -i :5900 >/dev/null && [ $attempt -lt $max_attempts ]; do
    PID=$(lsof -ti :5900)
    echo "Port 5900 is in use by process: $PID. Attempting to close the application..."
    kill "$PID" || true  # Try graceful termination
    sleep 2  # Allow some time for the process to terminate
    if lsof -i :5900 >/dev/null; then
        echo "Process still running. Forcing termination..."
        kill -9 "$PID" || true  # Force kill if necessary
    fi
    attempt=$((attempt + 1))
done

if lsof -i :5900 >/dev/null; then
    echo "Port 5900 is still in use after $max_attempts attempts. Exiting..."
    exit 1
else
    echo "Port 5900 is now free. Continuing..."
fi

echo "Cleaning up existing network..."
docker network rm $NETWORK_NAME || echo "Network not found, continuing..."


echo "Starting MySQL service..."
docker compose up -d mysql || {
    echo "Failed to start MySQL. Exiting..."
    exit 1
}


echo "Waiting for MySQL to be healthy..."
docker compose exec mysql sh -c 'until mysqladmin ping --silent; do sleep 2; done'


echo "Running database scripts..."
chmod +x "$SCRIPT_DIR/db_backup.sh"
"$SCRIPT_DIR/db_backup.sh" backup
"$SCRIPT_DIR/db_backup.sh" import_test_db

echo "Validating docker-compose.yml..."
docker compose config || {
    echo "Invalid docker-compose.yml. Restoring backup..."
    mv docker-compose.yml.backup docker-compose.yml
    exit 1
}

echo "Adding Cypress service..."
cat <<EOF >> docker-compose.yml

  cypress:
    image: cypress/included:latest
    working_dir: /var/www/vendor/oxid-solution-catalysts/paypal-module/tests/e2e
    volumes:
      - ./source/vendor/oxid-solution-catalysts/paypal-module:/var/www/vendor/oxid-solution-catalysts/paypal-module:cached
      - /tmp/.X11-unix:/tmp/.X11-unix
    environment:
      - CYPRESS_baseUrl=http://apache
    ports:
      - "5900:5900"
    networks:
      - default
    extra_hosts:
      - "oxideshop.local:172.17.0.1"
    depends_on:
      apache:
        condition: service_started
      mysql:
        condition: service_healthy
EOF

echo "Finally starting Cypress..."
docker compose up -d || {
    echo "Error starting containers. Checking logs..."
    docker compose logs
    echo "Restoring backup..."
    mv docker-compose.yml.backup docker-compose.yml
    exit 1
}

echo "Waiting for services to stabilize..."
sleep 60

#"$SCRIPT_DIR/db_backup.sh" restore
"$SCRIPT_DIR/set_shop_url.sh" https://oxideshop.local

echo "Displaying Cypress logs..."
docker compose logs cypress || echo -e "${RED}No logs available for Cypress.${NC}"

NO_MAKE_UP=false

# Parse arguments
for arg in "$@"; do
    case $arg in
        --no_make_up=true)
            NO_MAKE_UP=true
            shift
            ;;
    esac
done

# Decide whether to run 'make up'
if [ "$NO_MAKE_UP" == "false" ]; then
    echo -e "${GREEN}Running 'make up'...${NC}"
    make up >> /dev/null
else
    echo -e "${GREEN}Skipping 'make up' as per user request (--no_make_up=true).${NC}"
fi

docker compose logs cypress > cypress_output.log

# Check for failure indicators in the logs
if grep -iE "AssertionError|Failing:\s+1|failed" cypress_output.log > /dev/null; then
    echo -e "${RED} Tests failed! Found error indicators in Cypress logs${NC}"
    cat cypress_output.log
    rm cypress_output.log
    exit 1
elif grep -i "All specs passed!" cypress_output.log > /dev/null; then
    echo -e "${GREEN} All tests passed successfully!${NC}"
    rm cypress_output.log
    exit 0
else
    echo -e "${RED}  Unable to determine test status - checking logs for details: ${NC}"
    cat cypress_output.log
    rm cypress_output.log
    exit 1
fi
