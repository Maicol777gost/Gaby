FROM php:8.2-apache

# Habilitar extensión mysqli necesaria para la base de datos MySQL
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copiar todos los archivos del proyecto al directorio de Apache
COPY . /var/www/html/

# Exponer el puerto 80
EXPOSE 80
