FROM nginxinc/nginx-unprivileged:1.29.2-trixie

ARG UID
ARG GID

USER root

RUN <<EOF
  set -xe
  groupmod --gid=${GID} nginx
  usermod --uid=${UID} --gid=${GID} nginx
EOF

RUN mkdir -p /var/www && chown nginx:nginx /var/www

RUN mkdir /etc/nginx/templates
COPY default.conf.template /etc/nginx/templates

USER nginx