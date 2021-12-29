# This Dockerfile can be used to create a Docker image/container
# that runs the unit tests on the PubmedParser extension.
FROM mediawiki:1.37
LABEL maintainer Daniel Kraus (https://www.bovender.de)
RUN apt-get update -yqq && \
	apt-get install -yqq \
	php7.4-sqlite \
	sqlite3 \
	unzip \
	zip
RUN curl https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer -s | php -- --quiet
RUN php composer.phar install

COPY . /var/www/html/extensions/PubmedParser/
RUN mkdir /data && chown www-data /data

WORKDIR /var/www/html/maintenance
RUN php install.php --pass admin --dbtype sqlite --extensions PubmedParser Tests admin

WORKDIR /var/www/html/tests/phpunit
CMD ["php", "phpunit.php", "--group", "bovender"]
