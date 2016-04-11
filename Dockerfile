# This is a comment
FROM mageinferno/magento2-php:7.0.2-fpm-1
MAINTAINER Petter Kjelkenes <kjelkenes@gmail.com>



RUN apt-get update && apt-get install -y gcc g++ unzip
RUN curl -o clusterclient-aws-php7.zip https://s3.amazonaws.com/elasticache-downloads/ClusterClient/PHP-7.0/latest-64bit && \
     unzip clusterclient-aws-php7.zip && \
     cp artifact/amazon-elasticache-cluster-client.so "$(php -r 'echo ini_get("extension_dir");')" && \ 
     docker-php-ext-enable amazon-elasticache-cluster-client


ENV PHP_SENDMAIL_PATH /usr/sbin/ssmtp -t
RUN sed -i \
    -e "s!^;sendmail_path =.*\$!sendmail_path = $PHP_SENDMAIL_PATH!g" \
    /usr/local/etc/php/php.ini

## sSMTP
ENV SSMTP_ROOT example.address@gmail.com
ENV SSMTP_MAILHUB smtp.gmail.com:587
ENV SSMTP_HOSTNAME smtp.gmail.com:587
ENV SSMTP_USE_STARTTLS YES
ENV SSMTP_AUTH_USER example.address@gmail.com
ENV SSMTP_AUTH_PASS emailpassword
ENV SSMTP_FROMLINE_OVERRIDE YES
ENV SSMTP_AUTH_METHOD LOGIN

ADD ./assets/update_ssmtp.sh /usr/bin/update_ssmtp.sh
RUN rm -f /etc/ssmtp/ssmtp.conf
ADD ./assets/ssmtp.conf /etc/ssmtp/ssmtp.conf
RUN bash /usr/bin/update_ssmtp.sh

