#!/bin/bash

# Define colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

# Get the script's directory
SCRIPT_DIR=$(dirname "$(readlink -f "$0")")

echo "getting db config..."

extract_credentials() {
    local config_file="source/source/config.inc.php"

    # Check if file exists
    if [ ! -f "$config_file" ]; then
        echo "Error: Config file not found at $config_file"
        exit 1
    fi

    # Add debug output
    echo "Reading credentials from: $config_file"

    # Modified sed patterns to match the actual file format
    dbUser=$(sed -n "s/.*dbUser = '\([^']*\)'.*/\1/p" "$config_file")
    dbPwd=$(sed -n "s/.*dbPwd  = '\([^']*\)'.*/\1/p" "$config_file")  # Note the double space after dbPwd
    dbName=$(sed -n "s/.*dbName = '\([^']*\)'.*/\1/p" "$config_file")

    variables=("$dbUser" "$dbName" "$dbPwd")
    for var in "${variables[@]}"; do
        if [ -z "$var" ]; then
            echo "Error: Empty variable detected '$var'"
            exit 1
        fi
    done
}

# Call the function
extract_credentials

# Function to wait for MySQL to be ready
wait_for_mysql() {
    echo -e "${GREEN}Waiting for MySQL to be ready...${NC}"
    until docker compose exec mysql mysqladmin ping -u"$dbUser" -p"$dbPwd" --silent; do
        sleep 1
    done
    echo -e "${GREEN}MySQL is ready.${NC}"
}

# Backup the current database
backup_database() {
    extract_credentials
    BACKUPS_DIR="$SCRIPT_DIR/../../tests/e2e/_backups"
    mkdir -p "$BACKUPS_DIR"
    wait_for_mysql
    echo -e "${GREEN}Performing database backup...${NC}"
    docker compose exec mysql mysqldump -u "$dbUser" -p"$dbPwd" "$dbName" > "$BACKUPS_DIR/backup_$(date +%Y%m%d%H%M%S).sql"
    echo -e "${GREEN}Database backup completed.${NC}"
}

# Import test database from fixtures.sql
import_test_db() {
    extract_credentials
    wait_for_mysql
    echo -e "${GREEN}Dropping and recreating database...${NC}"
#    docker compose exec mysql mysql -u "$dbUser" -p"$dbPwd" -e "DROP DATABASE IF EXISTS $dbName; CREATE DATABASE $dbName;"
    FIXTURES_FILE="source/vendor/oxid-solution-catalysts/paypal-module/tests/e2e/cypress/_data/dump.sql"
    if [ ! -f "$FIXTURES_FILE" ]; then
        echo -e "${RED}Fixtures file not found: $FIXTURES_FILE${NC}"
        exit 1
    fi
    echo -e "${GREEN}Importing test data from fixtures.sql...${NC}"

    # Copy the backup file into the container
    container_id=$(docker compose ps -q mysql)
    if [ -z "$container_id" ]; then
        echo -e "${RED}MySQL container not found. Ensure the service is running.${NC}"
        exit 1
    fi

    docker exec -i "$container_id" mysql -u "$dbUser" -p"$dbPwd" "$dbName" < "$FIXTURES_FILE"

    echo -e "${GREEN}Test database imported successfully.${NC}"
}

# Restore database from the latest backup
restore_database() {
    extract_credentials
    wait_for_mysql
    echo -e "${GREEN}Dropping and recreating database...${NC}"
    docker compose exec mysql mysql -u "$dbUser" -p"$dbPwd" -e "DROP DATABASE IF EXISTS $dbName; CREATE DATABASE $dbName;"

    BACKUPS_DIR="$SCRIPT_DIR/../../tests/e2e/_backups"
    if [ ! -d "$BACKUPS_DIR" ]; then
        echo -e "${RED}Backups directory not found: $BACKUPS_DIR${NC}"
        exit 1
    fi

    latest_backup=$(ls -t "$BACKUPS_DIR"/*.sql | head -n 1)
    if [ -z "$latest_backup" ]; then
        echo -e "${RED}No backup files found. Cannot restore database.${NC}"
        exit 1
    fi

    echo -e "${GREEN}Restoring database from $latest_backup...${NC}"

    # Copy the backup file into the container
    container_id=$(docker compose ps -q mysql)
    if [ -z "$container_id" ]; then
        echo -e "${RED}MySQL container not found. Ensure the service is running.${NC}"
        exit 1
    fi

    docker cp "$latest_backup" "$container_id:/tmp/latest_backup.sql"

    # Restore the database from the copied backup
    docker exec -i "$container_id" mysql -u "$dbUser" -p"$dbPwd" "$dbName" < /tmp/latest_backup.sql

    echo -e "${GREEN}Database restored from backup successfully.${NC}"

    # Remove old backups, keeping only the latest 3
    echo -e "${GREEN}Cleaning up old backups...${NC}"
    backups_to_remove=$(ls -t "$BACKUPS_DIR"/*.sql 2>/dev/null | tail -n +4)

    if [ -n "$backups_to_remove" ]; then
        echo "$backups_to_remove" | xargs rm -f
        echo -e "${GREEN}Old backups removed.${NC}"
    else
        echo -e "${GREEN}No old backups to remove.${NC}"
    fi
}

# Main script logic
if [ "$1" == "backup" ]; then
    backup_database
elif [ "$1" == "import_test_db" ]; then
    import_test_db
elif [ "$1" == "restore" ]; then
    restore_database
else
    echo -e "${RED}Invalid argument. Usage: $0 {backup|import_test_db|restore}${NC}"
    exit 1
fi
