# This Dockerfile can be used to create a Docker image/container
# that runs the unit tests on the PubmedParser extension.
FROM mediawiki:1.42
LABEL maintainer="Daniel Kraus (https://www.bovender.de)"
RUN apt-get update -yqq && \
	apt-get install -yqq \
	#php7.4-sqlite \
	sqlite3 \
	unzip \
	zip
RUN curl https://raw.githubusercontent.com/composer/getcomposer.org/f3108f64b4e1c1ce6eb462b159956461592b3e3e/web/installer -s | php -- --quiet && \
	mv composer.phar /usr/local/bin/composer
RUN composer install

COPY . /var/www/html/extensions/PubmedParser/
RUN mkdir /data && chown www-data /data

WORKDIR /var/www/html/maintenance
RUN php install.php --pass pubmedparsertest --dbtype sqlite --extensions PubmedParser Tests admin

WORKDIR /var/www/html
CMD [ "composer", "phpunit", "--", "extensions/PubmedParser/tests/phpunit/unit" ]
