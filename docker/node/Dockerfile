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

CMD ["sh", "-c", "while true; do sleep 3600; done"]
