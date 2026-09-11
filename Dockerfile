# Web (nginx + SPA) image for Railway — auto-detected at repo root.
# Mirrors backend/docker/web/Dockerfile (same build context: repo root),
# plus a configurable php-fpm upstream so the same image works locally
# (compose default app:9000) and on PAAS (docsy-app.railway.internal:9000).
FROM node:20-alpine AS build
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ .
RUN npm run build

FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
COPY backend/docker/web/default.conf /etc/nginx/conf.d/default.conf
ENV APP_UPSTREAM=app:9000
EXPOSE 80
CMD sh -c "sed -i s/app:9000/$APP_UPSTREAM:9000/ /etc/nginx/conf.d/default.conf && exec nginx -g 'daemon off;'"
