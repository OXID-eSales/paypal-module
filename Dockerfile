ARG OXID="6.3"
ARG PHP="7.4"
FROM oxidprojects/oxid-apache-php:oxid${OXID}-php${PHP}
ARG OXID="6.3"
ARG PHP="7.4"
ARG MODULE_NAME="oxid-solution-catalysts/paypal"
ENV HTA_PW="test"
ENV HTA_USER="test"
RUN rm -rfv /var/www/oxideshop
RUN composer create-project oxid-professional-services/test-oxid=dev-oxid6.3 /var/www/oxideshop --no-interaction -s dev --repository="{\"url\":\"https://github.com/keywan-ghadami-oxid/test-oxid.git\", \"type\":\"git\"}" --remove-vcs
RUN mkdir -p /var/www/oxideshop/project-modules/module-under-test
#RUN composer require oxid-esales/oxideshop-demodata-ce:

COPY . /var/www/oxideshop/project-modules/module-under-test

WORKDIR /var/www/oxideshop
RUN composer config repositories.build path /var/www/oxideshop/project-modules/\*
RUN composer require --no-interaction $MODULE_NAME
# move config to source folder
RUN cp config.inc.php-dist source/config.inc.php

COPY ./docker-php-entrypoint /usr/local/bin/
RUN chmod 777 /usr/local/bin/docker-php-entrypoint \
    && ln -s /usr/local/bin/docker-php-entrypoint