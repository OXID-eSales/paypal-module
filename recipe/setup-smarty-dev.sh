#!/bin/bash
# Flags possible:
# -e for shop edition. Possible values: CE/EE
##
##  Use: cd oxideshop_root
##  git clone <git> extensions/paypal
##  bash:: ./extensions/paypal/recipe/setup-dev.sh -eEE
##

# set -x  # Enables debugging
set -e  # Stops script on any error


edition='EE'
while getopts e: flag; do
  case "${flag}" in
  e) edition=${OPTARG} ;;
  *) ;;
  esac
done

source "${BASH_SOURCE[0]%/*}/include.sh"

# Find the module root and set the working directory
MODULE_ROOT=$(find_module_root)
echo "Module root is: $MODULE_ROOT"
PROJECT_ROOT=$(find_project_root)
echo "Project root is: $PROJECT_ROOT"

# Check if docker-compose.yml.dist exists at the project root
if [ -f "$PROJECT_ROOT/docker-compose.yml.dist" ]; then
  echo "docker-compose.yml.dist found at $PROJECT_ROOT"
  # Optionally, copy it to docker-compose.yml if needed
  cp -n "$PROJECT_ROOT/docker-compose.yml.dist" "$PROJECT_ROOT/docker-compose.yml"
  echo "Created docker-compose.yml from docker-compose.yml.dist"
fi

cd "$PROJECT_ROOT" || exit 1

# Prepare services configuration
make setup
make addbasicservices
make file=services/adminer.yml addservice

echo "module root is $MODULE_ROOT"
cd "$PROJECT_ROOT" || exit 1

$MODULE_ROOT/recipe/parts/b-7.0.x/start_shop.sh -e"${edition}" -u"false" || exit 1

mkdir -p "$PROJECT_ROOT"/source/extensions || exit 1
cp -r "$MODULE_ROOT" "$PROJECT_ROOT"/source/extensions/ || exit 1
git clone git@github.com:OXID-eSales/paypal-client.git --branch=v3.0.16 "$PROJECT_ROOT"/source/extensions/paypal-client || exit 1
mkdir -p ./source/var/configuration/environment/shops/1/modules
cp $MODULE_ROOT/recipe/environment/1.yaml ./source/var/configuration/environment/shops/1/modules/osc_paypal.yaml


$PROJECT_ROOT/source/extensions/paypal/recipe/parts/b-7.0.x/require_smarty_components.sh -e"${edition}" || exit 1

# Require demodata package
docker compose exec -T \
  php composer config repositories.oxid-esales/oxideshop-demodata-ee \
  --json '{"type":"git", "url":"https://github.com/OXID-eSales/oxideshop_demodata_ee"}'
docker compose exec -T php composer require oxid-esales/oxideshop-demodata-ee:dev-b-7.0.x-SMARTY --no-update

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

docker compose exec -T php bin/oe-console oe:theme:activate flow
docker compose exec -T php bin/oe-console oe:module:install extensions/paypal
docker compose exec -T php bin/oe-console oe:module:activate osc_paypal

$PROJECT_ROOT/source/extensions/paypal/recipe/parts/shared/create_admin.sh
# Register all related project packages git repositories
mkdir -p .idea; mkdir -p source/.idea; cp "$PROJECT_ROOT/source/extensions/paypal/recipe/parts/bases/vcs.xml.base" .idea/vcs.xml
perl -pi\
  -e 's#</component>#<mapping directory="\$PROJECT_DIR\$/source" vcs="Git" />\n  </component>#g;'\
  -e 's#</component>#<mapping directory="\$PROJECT_DIR\$/source/vendor/oxid-esales/oxideshop-ce" vcs="Git" />\n  </component>#g;'\
  -e 's#</component>#<mapping directory="\$PROJECT_DIR\$/source/vendor/oxid-esales/oxideshop-pe" vcs="Git" />\n  </component>#g;'\
  -e 's#</component>#<mapping directory="\$PROJECT_DIR\$/source/vendor/oxid-esales/oxideshop-ee" vcs="Git" />\n  </component>#g;'\
  .idea/vcs.xml

echo -e "\033[1;37m\033[1;42mInstallation is finished!\033[0m\n"
echo -e "\033[1;37m\033[1;42mYou can now access your shop at http://localhost.local/\033[0m\n"
echo -e "\033[1;37m\033[1;42mShop admin at http://localhost.local/admin\033[0m\n"
echo -e "\033[1;37m\033[1;42mYou can access the Adminer at http://localhost.local:8080/\033[0m\n"

rm -rf "$MODULE_ROOT"

exit 0
