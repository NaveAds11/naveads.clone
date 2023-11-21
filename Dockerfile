FROM emailnaveads/ubuntu-22.04-nginx-php7.4-ext:latest

ADD nginx/conf.d/default.conf /etc/nginx/conf.d
ADD nginx/sites-enabled/default /etc/nginx/sites-enabled
ADD nginx/nginx.conf /etc/nginx/nginx.conf

ADD etc/php/7.4/cli/php.ini etc/php/7.4/cli/php.ini
ADD etc/php/7.4/fpm/php.ini etc/php/7.4/fpm/php.ini

ADD etc/php/init.sh /etc/php/init.sh
ADD etc/php/filebrowser etc/php/filebrowser

WORKDIR /var/www/html/
COPY web .

# Lembre-se de alterar no nginx\conf.d\default.conf
EXPOSE 8701

#tail -f /var/log/nginx/error.log

CMD ["bash", "/etc/php/init.sh"]

RUN chmod -R 777 .