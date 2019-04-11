FROM silintl/php7:7.2
MAINTAINER Phillip Shipley <phillip_shipley@sil.org>

ENV REFRESHED_AT 2017-02-03

RUN apt-get update -y && \
    apt-get install -y make

COPY dockerbuild/vhost.conf /etc/apache2/sites-enabled/
COPY dockerbuild/broker-cron /etc/cron.d/
RUN chmod 0644 /etc/cron.d/broker-cron

RUN mkdir -p /data

# Copy in syslog config
RUN rm -f /etc/rsyslog.d/*
COPY dockerbuild/rsyslog.conf /etc/rsyslog.conf

# get s3-expand
RUN curl https://raw.githubusercontent.com/silinternational/s3-expand/1.5/s3-expand -o /usr/local/bin/s3-expand
RUN chmod a+x /usr/local/bin/s3-expand

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

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/s3-expand"]
CMD ["/data/run.sh"]
