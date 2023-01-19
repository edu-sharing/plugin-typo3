ARG dev=0

FROM martinhelmich/typo3:11 as base
# We need to initialize `typo3conf/ext` with the correct permissions, so it won't be created as a
# root-owned folder by Docker when binding the volume for our plugin at runtime in dev mode.
COPY --chown=www-data:www-data docker/typo3conf /var/www/html/typo3conf

FROM base AS dev-0
# The plugin is added to the Docker image only in non-dev mode since it will be bound as volume in
# dev mode.
COPY --chown=www-data:www-data src /var/www/html/typo3conf/ext/edusharing

FROM base AS dev-1
# xdebug 3.1.6 is the latest version still compatible with php 7.
RUN pecl install xdebug-3.1.6
RUN docker-php-ext-enable xdebug
COPY docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# We want to provide a different Typo3 configuration in dev mode. We cannot directly copy files to
# `typo3conf` since the folder is bound to a volume in the upstream Docker image and any updates to
# the folder in our image (when switching between dev and non-dev) wouldn't be reflected in an
# existing container until recreated. Therefore, we symlink to files in a separate `static` folder,
# that we can swap freely when switching modes.
COPY --chown=www-data:www-data docker/static-dev /var/www/html/static

FROM dev-${dev} AS final
