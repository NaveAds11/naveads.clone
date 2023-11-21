service nginx start
service php7.4-fpm start

chmod 777 /etc/php/filebrowser
/etc/php/filebrowser -r /var/www/html/ -p 8618 --noauth
