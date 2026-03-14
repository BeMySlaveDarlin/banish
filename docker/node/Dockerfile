FROM node:20-alpine AS node_base

WORKDIR /app

ARG VITE_API_URL=http://localhost

COPY frontend/package*.json ./

RUN npm install

COPY frontend .

ENV VITE_API_URL=$VITE_API_URL

FROM node_base AS node_dev

CMD ["npm", "run", "dev", "--", "--host"]

FROM node_base AS node_prod

RUN npm run build

# NOTE: Sleep loop is required because docker-compose uses a shared volume
# (frontend_dist) to pass built assets to the nginx container.
# The container must stay alive to keep the volume mounted.
# A better approach would be to COPY --from the build stage directly into
# the nginx image via a multi-stage build, but that requires changes to
# the nginx Dockerfile and docker-compose architecture.
CMD ["sh", "-c", "while true; do sleep 3600; done"]
