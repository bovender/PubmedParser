# This Dockerfile can be used to create a Docker image/container
# that runs the unit tests on the PubmedParser extension.
FROM mediawiki:1.46
LABEL maintainer="Daniel Kraus (https://www.bovender.de)"
RUN apt-get update -yqq && \
	apt-get install -yqq \
	sqlite3 \
	unzip \
	zip
RUN curl https://raw.githubusercontent.com/composer/getcomposer.org/f3108f64b4e1c1ce6eb462b159956461592b3e3e/web/installer -s | php -- --quiet && \
	mv composer.phar /usr/local/bin/composer

# The published mediawiki Docker image omits phpunit.xml.template (it is only
# needed for running tests, not for serving a wiki), so composer's phpunit
# scripts cannot generate their config without it. Restore it from the
# matching release branch.
RUN curl -fsSL -o phpunit.xml.template \
	https://raw.githubusercontent.com/wikimedia/mediawiki/REL1_46/phpunit.xml.template

RUN composer install

COPY . /var/www/html/extensions/PubmedParser/
RUN mkdir /data && chown www-data /data

WORKDIR /var/www/html/maintenance
RUN php install.php --pass pubmedparsertest --dbtype sqlite --extensions PubmedParser Tests admin

WORKDIR /var/www/html
CMD [ "composer", "phpunit:entrypoint", "--", "extensions/PubmedParser/tests/phpunit" ]
