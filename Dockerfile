FROM php:8.2-apache

# 1. تثبيت الإضافات المطلوبة للداتا بيز
RUN docker-php-ext-install pdo pdo_mysql

# 2. تفعيل mod_rewrite في Apache (مهم جداً للـ APIs والـ POST Requests)
RUN a2enmod rewrite

# 3. السماح لـ Apache بـ Override والإرشادات الكاملة لجميع الـ Methods (GET, POST, etc.)
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# 4. نسخ ملفات المشروع
COPY . /var/www/html/

# 5. ضبط الصلاحيات
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80