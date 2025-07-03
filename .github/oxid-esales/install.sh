#!/bin/bash
# shellcheck disable=SC2154
# Lower case environment variables are passed from the workflow and used here
# We use a validation loop in init to ensure, they're set
# shellcheck disable=SC2086
# We want install_container_options to count as multiple arguments
set -e

function error() {
    echo -e "\033[0;31m${1}\033[0m"
    exit 1
}

function init() {
    make file=services/node.yml addservice
    docker compose up -d --build node
    for VAR in install_container_method install_container_options install_container_name \
        install_config_idebug install_is_enterprise; do
        echo -n "Checking, if $VAR is set ..."
        if [ -z ${VAR+x} ]; then
            error "Variable '${VAR}' not set"
        fi
        echo "OK, ${VAR}='${!VAR}'"
    done
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
    echo "OK, using '${OE_CONSOLE}'"
    if [ -z "${OXID_BUILD_DIRECTORY}" ]; then
      echo "OXID_BUILD_DIRECTORY is not set, setting it to /var/www/var/cache/"
      export OXID_BUILD_DIRECTORY="/var/www/var/cache/"
    else
      echo "OXID_BUILD_DIRECTORY is set to '${OXID_BUILD_DIRECTORY}'"
    fi

    if [ ! -d "${OXID_BUILD_DIRECTORY/\/var\/www/source}" ]; then
      echo "Creating '${OXID_BUILD_DIRECTORY}'"
      docker compose "${install_container_method}" -T \
        ${install_container_options} \
        "${install_container_name}" \
        mkdir -p "${OXID_BUILD_DIRECTORY}"
    fi
}

init
cp vendor/oxid-esales/oxideshop-ce/.env.dist ./.env

echo "CURRENT DIR"
echo "current directory is: $(pwd)"
echo "ls -la"
ls -la

echo "./source"
ls -la source

echo ".."
ls -la ..

cat composer.json
cat composer.lock

# Run Install Shop
docker compose "${install_container_method}" -T \
    ${install_container_options} \
    "${install_container_name}" \
    ${OE_CONSOLE} oe:database:reset --force

# Activate iDebug
if [ "${install_config_idebug}" == 'true' ]; then
    export OXID_DEBUG_MODE="true"
fi

# Activate theme
docker compose "${install_container_method}" -T \
    ${install_container_options} \
    "${install_container_name}" \
    ${OE_CONSOLE} oe:theme:activate apex

# Output PHP error log
if [ -s data/php/logs/error_log.txt ]; then
    echo -e "\033[0;35mPHP error log\033[0m"
    cat data/php/logs/error_log.txt
fi

docker compose "${install_container_method}" -T \
    ${install_container_options} \
    "${install_container_name}" \
    cp tests/.env.dist tests/.env


docker compose "${install_container_method}" -T \
    ${install_container_options} \
    "${install_container_name}" \
    mkdir -p /var/www/var/configuration/environment/shops/1/modules


docker compose "${install_container_method}" -T \
    ${install_container_options} \
    "${install_container_name}" \
    cp ./recipe/environment/1.yaml /var/www/var/configuration/environment/shops/1/modules/osc_paypal.yaml


docker compose "${install_container_method}" -T \
    ${install_container_options} \
    "${install_container_name}" \
    vendor/bin/oe-console oe:module:install ./

docker compose "${install_container_method}" -T \
    ${install_container_options} \
    "${install_container_name}" \
    vendor/bin/oe-console oe:module:activate osc_paypal

ls -la
pwd
cd ..
pwd
docker compose up -d --build node
docker ps
for container in $(docker ps --filter "name=node" -q); do
      container_name=$(docker inspect --format '{{.Name}}' "$container" | sed 's/^\/\?//')
      echo "Logs for container $container_name:"
      docker logs "$container"
done



if docker ps | grep -q "paypal-module-node"; then
       # Install Playwright dependencies
       docker compose "${install_container_method}" -T \
           ${install_container_options} \
           node \
           npm install playwright --save-dev

       # Install Playwright browsers
       docker compose "${install_container_method}" -T \
           ${install_container_options} \
           node \
           npx playwright install

       # Run Playwright tests
       docker compose "${install_container_method}" -T \
           ${install_container_options} \
           node \
           npx playwright test tests/e2e

       # Optional: Generate Playwright HTML report
       docker compose "${install_container_method}" -T \
           ${install_container_options} \
           node \
           npx playwright show-report
   else
       echo "NODE ERROR:: Node container is not running. Exiting..."
       exit 1
   fi


exit 0
