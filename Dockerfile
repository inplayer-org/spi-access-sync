FROM inplayer/php:7.3

COPY . /var/www/service

RUN chown -R www-data:staff /var/www/service && \
    sed -i 's/root\s.*/root \/var\/www\/service\/public\;/' /etc/nginx/nginx.conf

WORKDIR /var/www/service
VOLUME /var/www/service

ENTRYPOINT ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
