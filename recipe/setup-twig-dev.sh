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
git clone https://github.com/OXID-eSales/oxideshop_ce.git --branch=b-8.0.x source

make setup
make addbasicservices
make file=services/adminer.yml addservice

echo "module root is $MODULE_ROOT"
cd "$PROJECT_ROOT" || exit 1

echo "Configure containers..."
perl -pi\
  -e 's#error_reporting = .*#error_reporting = E_ALL ^ E_WARNING ^ E_DEPRECATED#g;'\
  containers/php/custom.ini

perl -pi\
  -e 's#/var/www/#/var/www/source/#g;'\
  containers/httpd/project.conf

echo "Preparing shop config environment..."
mkdir -p "$PROJECT_ROOT"/source/extensions || exit 1
cp -r "$MODULE_ROOT" "$PROJECT_ROOT"/source/extensions/ || exit 1
mkdir -p ./source/var/configuration/shops/1/modules
cp $MODULE_ROOT/recipe/environment/osc_paypal.yaml ./source/var/configuration/shops/1/modules/osc_paypal.yaml
perl -pi\
  -e 'print "SetEnvIf Authorization \"(.*)\" HTTP_AUTHORIZATION=\$1\n\n" if $. == 1'\
  source/source/.htaccess

make up

echo "Adding OXID eShop PE, EE dependencies to composer.json"
docker compose exec \
  php composer config repositories.oxid-esales/oxideshop-pe \
  --json '{"type":"git", "url":"https://github.com/OXID-eSales/oxideshop_pe"}'
docker compose exec php composer require oxid-esales/oxideshop-pe:dev-b-8.0.x --no-update

docker compose exec \
  php composer config repositories.oxid-esales/oxideshop-ee \
  --json '{"type":"git", "url":"https://github.com/OXID-eSales/oxideshop_ee"}'
docker compose exec php composer require oxid-esales/oxideshop-ee:dev-b-8.0.x --no-update

echo "Adding twig APEX theme dependencies to composer.json..."
$PROJECT_ROOT/source/extensions/paypal/recipe/parts/shared/require_twig_components.sh -e"EE" -b"b-8.0.x"

echo "Adding require_theme_dev..."
$PROJECT_ROOT/source/extensions/paypal/recipe/parts/shared/require_theme_dev.sh -t"apex" -b"b-8.0.x"

echo "Adding demodata dependencies..."
$PROJECT_ROOT/source/extensions/paypal/recipe/parts/shared/require_demodata_package.sh -e"${edition}" -b"b-8.0.x"

echo "Adding OXID eShop PayPal module dependencies to composer.json"

docker compose exec -T \
  php composer config repositories.oxid-solution-catalysts/paypal \
  --json '{"type":"path", "url":"./extensions/paypal", "options": {"symlink": true}}' || exit 1
docker compose exec -T php composer require oxid-solution-catalysts/paypal-module:* --no-update || exit 1

docker compose exec -T \
  php composer config repositories.oxid-solution-catalysts/paypal-client \
  --json '{"type":"git", "url":"https://github.com/OXID-eSales/paypal-client" }'
docker compose exec -T php composer require oxid-solution-catalysts/paypal-client:* --no-update || exit 1

echo "Run composer update to install all dependencies..."
docker compose exec -T php composer update --no-interaction

echo "Setting up shop..."
docker compose exec -T php bin/oe-console oe:setup:shop

echo "Setting up demodata..."
docker compose exec -T php bin/oe-console oe:setup:demodata

echo "Turning on twig Apex theme..."
docker compose exec -T php bin/oe-console oe:theme:activate apex

echo "Installing OXID eShop PayPal module..."
docker compose exec -T php bin/oe-console oe:module:install extensions/paypal

echo "Activating OXID eShop PayPal module..."
docker compose exec -T php bin/oe-console oe:module:activate osc_paypal

echo "Creating admin and password (noreply@oxid-esales.com admin)..."
docker compose exec -T php bin/oe-console oe:admin:create noreply@oxid-esales.com admin

echo "Setting up OXID eShop PayPal module..."
$PROJECT_ROOT/source/extensions/paypal/recipe/parts/shared/create_admin.sh

echo "IDE tweaking: Register all related project packages git repositories"
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

#rm -rf "$MODULE_ROOT"

exit 0
