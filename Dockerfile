FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

# تفعيل mod_rewrite والسماح بالتحويلات
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# إنشاء ملف .htaccess تلقائياً داخل الحاوية
RUN echo "<IfModule mod_rewrite.c>\n\
    RewriteEngine On\n\
    RewriteCond %{REQUEST_FILENAME} !-f\n\
    RewriteCond %{REQUEST_FILENAME} !-d\n\
    RewriteRule ^ index.php [L]\n\
</IfModule>" > /var/www/html/.htaccess

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80