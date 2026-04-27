FROM php:8.4-cli

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libicu-dev \
        libzip-dev \
    && docker-php-ext-install \
        intl \
        pdo \
        pdo_pgsql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupadd -g ${GID} app \
    && useradd -m -u ${UID} -g app app

WORKDIR /app

USER app

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
