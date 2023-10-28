FROM php:8.2-cli

RUN apt-get -y update && \
	apt-get -y install supervisor

COPY supervisord.conf /etc/supervisor/supervisord.conf
RUN mkdir /var/supervisor

CMD /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
