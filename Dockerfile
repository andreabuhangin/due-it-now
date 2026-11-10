FROM php:8.2-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN printf '<IfModule dir_module>\n    DirectoryIndex index.php index.html\n</IfModule>\n' > /etc/apache2/conf-available/dirindex.conf \
    && a2enconf dirindex
COPY . /var/www/html/
EXPOSE 80