FROM php:8.4-cli

WORKDIR /var/www/html

# نسخ جميع الملفات
COPY . .

# تشغيل البوت
CMD ["php", "index.php"]
