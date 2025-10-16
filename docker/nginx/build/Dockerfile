FROM nginx:latest

WORKDIR /var/www/

ENV USER=syncio
ENV USER_PASSWORD=syncio
ENV UID=100

RUN apt-get update && apt-get -y upgrade

RUN apt-get update && apt-get install -y \
	bash \
	vim

RUN mkdir -p /etc/nginx/sites-available
RUN mkdir -p /etc/nginx/sites-enabled

RUN mv /etc/nginx/nginx.conf /etc/nginx/nginx.confORG
COPY ../configs/nginx.conf /etc/nginx/nginx.conf

RUN useradd -G www-data,root -u $UID -d /home/$USER $USER
RUN mkdir -p /home/$USER/.composer && \
chown -R $USER:$USER /home/$USER
RUN echo "${USER}:${USER_PASSWORD}" | chpasswd
RUN chsh -s /usr/local/bin/bash $USER

COPY ../entrypoint/entrypoint.sh /var/run/entrypoint.sh
RUN /usr/bin/chmod +x /var/run/entrypoint.sh
ENTRYPOINT ["/var/run/entrypoint.sh"]
