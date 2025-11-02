FROM nginxinc/nginx-unprivileged:1.29.2-trixie

ARG UID
ARG GID

USER root

RUN <<EOF
  set -xe
  if [ "${UID}" != "0" ] || [ "${GID}" != "0" ]; then
      groupmod --gid=${GID} www-data
      usermod --uid=${UID} --gid=${GID} www-data
  fi
EOF

RUN mkdir -p /var/www
RUN mkdir /etc/nginx/templates

COPY default.conf.template /etc/nginx/templates

RUN chown nginx:nginx -R /var/www
RUN chown nginx:nginx -R /etc/nginx

USER nginx
