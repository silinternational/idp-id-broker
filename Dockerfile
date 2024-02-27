FROM silintl/php8:8.1

ENV REFRESHED_AT 2024-02-27

RUN apt-get update -y && \
    apt-get install -y make

WORKDIR /data

# Install/cleanup composer dependencies
COPY application/composer.json /data/
COPY application/composer.lock /data/
RUN composer install --prefer-dist --no-interaction --no-dev --optimize-autoloader --no-progress

# It is expected that /data is = application/ in project folder
COPY application/ /data/

# Fix folder permissions
RUN chown -R www-data:www-data \
    console/runtime/

COPY dockerbuild/vhost.conf /etc/apache2/sites-enabled/

# ErrorLog inside a VirtualHost block is ineffective for unknown reasons
RUN sed -i -E 's@ErrorLog .*@ErrorLog /proc/self/fd/2@i' /etc/apache2/apache2.conf

ADD https://github.com/silinternational/config-shim/releases/latest/download/config-shim_0.0.2-pre.3_linux_amd64.tar.gz config-shim.tar.gz
RUN tar xzf config-shim.tar.gz --directory /usr/local/bin --wildcards config-shim && rm config-shim*tar.gz

EXPOSE 80
CMD ["/data/run.sh"]
