FROM php:8.1-fpm

# ユーザー作成
RUN groupadd -g 1000 app \
 && useradd -u 1000 -g app -m app

# php.ini コピー
COPY php.ini /usr/local/etc/php/

# 必要パッケージ + GD追加
RUN apt update \
  && apt install -y \
     default-mysql-client \
     zlib1g-dev \
     libzip-dev \
     unzip \
     libpng-dev \
     libjpeg-dev \
     libfreetype6-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install pdo_mysql zip gd

# Composerインストール
RUN curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/local/bin/composer \
  && composer self-update

WORKDIR /var/www

USER app