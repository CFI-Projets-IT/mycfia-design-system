# ===== STAGE COMMUN =====
FROM dunglas/frankenphp:1.9-php8.3-bookworm AS base

# Configuration du timezone système
ENV TZ=Europe/Paris

# Mise à jour des paquets et installation des dépendances système communes
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    curl \
    procps \
    net-tools \
    lsof \
    tzdata \
    gosu \
    supervisor \
    && rm -rf /var/lib/apt/lists/* \
    && ln -sf /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone

# Installer les extensions PHP nécessaires
RUN install-php-extensions \
    pdo_mysql \
    gd \
    intl \
    zip \
    opcache \
    bcmath \
    dom \
    xml \
    curl \
    redis

# Installer Composer (gestionnaire de dépendances PHP)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installer Symfony CLI (outil en ligne de commande pour Symfony)
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# ===== STAGE DÉVELOPPEMENT =====
FROM base AS development

# Installer Node.js et npm pour le développement
RUN curl -fsSL https://deb.nodesource.com/setup_current.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest \
    && rm -rf /var/lib/apt/lists/*

# Créer le script d'entrée pour la gestion des droits
COPY ./docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Configuration PHP développement
COPY ./docker/php.ini.dev /usr/local/etc/php/php.ini

# Préparer les répertoires avec permissions par défaut
RUN mkdir -p /var/www/html \
    && chown -R www-data:www-data /data/caddy /config/caddy /var/www

# Définir le répertoire de travail
WORKDIR /var/www/html

# Exposer les ports
EXPOSE 80 443

# Point d'entrée intelligent
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# ===== STAGE PRODUCTION =====
FROM base AS production

# Configuration PHP production (pas de Node.js/npm)
COPY ./docker/php.ini.prod /usr/local/etc/php/php.ini

# Utilisateur sécurisé fixe pour la production (UID 82 = www-data Alpine)
RUN usermod -u 82 www-data && groupmod -g 82 www-data 2>/dev/null || true

# Permissions production restrictives
RUN mkdir -p /var/www/html \
    && chown -R www-data:www-data /data/caddy /config/caddy /var/www \
    && chmod -R 755 /var/www

# Définir le répertoire de travail
WORKDIR /var/www/html

# Passer à l'utilisateur non privilégié
USER www-data

# Exposer les ports
EXPOSE 80 443

# Point d'entrée direct pour la production
ENTRYPOINT ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]