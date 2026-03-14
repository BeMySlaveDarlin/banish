FROM nginxinc/nginx-unprivileged:1.29.2-trixie

USER root

RUN mkdir -p /var/www /var/log/nginx \
    && chown nginx:nginx /var/www /var/log/nginx

RUN mkdir -p /etc/nginx/templates
COPY default.conf.template /etc/nginx/templates

RUN chown nginx:nginx -R /etc/nginx

USER nginx
