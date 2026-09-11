# --- Stage 1: build the SPA (reviewers don't need node on their machine) ---
FROM node:20-alpine AS build
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ .
RUN npm run build

# --- Stage 2: nginx serves the SPA and proxies /api to php-fpm ---
FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
COPY backend/docker/web/default.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
