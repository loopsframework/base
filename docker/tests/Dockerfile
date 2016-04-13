# This file is part of the Loops framework.
#
# @author Lukas <lukas@loopsframework.com>
# @license https://raw.githubusercontent.com/loopsframework/base/master/LICENSE
# @link https://github.com/loopsframework/base
# @link https://loopsframework.com/
# @version 0.1
#
# Dockerfile that creates an environment for unit testing
#

FROM php:5

RUN apt-get update
RUN apt-get -y upgrade
RUN apt-get install -y --no-install-recommends git unzip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
RUN composer require loopsframework/base:dev-master
RUN composer require phpunit/phpunit:^5

ENTRYPOINT [ "vendor/bin/phpunit", "vendor/loopsframework/base/tests/" ]