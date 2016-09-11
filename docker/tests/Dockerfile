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

FROM php:7

# Install dependencies
RUN apt-get update && \
    apt-get -y upgrade && \
    apt-get install -y --no-install-recommends git unzip

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# Copy files into here
RUN mkdir -p /opt/loops

# Download and install packages via composer (will be cached by docker)
ADD composer.json /opt/loops
WORKDIR /opt/loops
RUN composer install

# Copy source code
ADD . /opt/loops

# Move into test directory
WORKDIR /opt/loops/tests

# phpunit
ENTRYPOINT [ "/opt/loops/vendor/bin/phpunit" ]
