# Docsy — File Management System

Full-stack file management system: **Laravel 11 REST API + Vue 3 SPA + PostgreSQL 16 + Redis 7**, fully containerized. One command to run, one port for everything.

## Features

- **Auth & RBAC** — token auth (Sanctum); `administrator` (full CRUD) vs `viewer` (read/download only, 403 on mutations)
- **Hierarchical folders** — unlimited nesting via PostgreSQL recursive CTEs: breadcrumbs in a single query, circular-move rejection (422), soft-delete of whole subtrees
- **File management** — upload (mime allowlist, 25MB cap) to a **private disk** with UUID names; streamed download/preview (flat memory, `Content-Disposition` aware); metadata edit; soft delete
- **Full-text search** — `tsvector` generated column + **GIN inverted index** (stemming, multi-word AND, relevance ranking)
- **Dashboard & audit** — cached stats + recent files (`cache-aside` with TTL and event-driven invalidation); asynchronous activity log + image thumbnails via Redis queue workers
- **SPA** — Vue 3 Composition API + Tailwind: folder explorer with breadcrumbs, drag & drop upload with progress, PDF/image preview modal, instant search, dark mode, responsive

## Architecture

```
                ┌────────────────────────────────────────────┐
                │  nginx (web) :8000                         │
                │  ├─ /            → SPA dist (Vue 3 build)  │
                │  └─ /api, /up    → php-fpm (Laravel 11)    │
                └───────────────┬────────────────────────────┘
                                │
     ┌──────────────────────────┼───────────────────────────┐
     │                     Laravel API                       │
     │  FormRequest → Policy → Service → Resource            │
     │  recursive CTEs · cache-aside · queue jobs            │
     └──────┬──────────────────────┬───────────────────┬────┘
            │                      │                   │
      PostgreSQL 16           Redis 7            queue worker
      (files, folders,        (cache db 1,       (thumbnails,
       activity_logs,          queue db 0)         activity log)
       GIN + partial indexes)
```

## Quick Start

Requirements: Docker (any runtime — Docker Engine / Colima / Docker Desktop).

```bash
git clone git@github.com:athallarizky/docsy.git && cd docsy

# 1. Backend environment
cp backend/.env.example backend/.env

# 2. Application key
docker run --rm -v "$PWD/backend":/app -w /app composer:2 \
  php artisan key:generate --force

# 3. Build & start (SPA is compiled inside the web image — no node needed)
cd backend && docker compose up -d --build

# 4. Database
docker compose exec app php artisan migrate --seed

# 5. Test database (once)
docker compose exec db psql -U docsy -d docsy_db -c 'CREATE DATABASE docsy_test;'
```

Open **http://localhost:8000** — sign in with a demo account:

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@example.com` | `password` |
| Viewer | `viewer@example.com` | `password` |

## API Overview (`/api/v1`, envelope `{success, message, data}`)

| Area | Endpoints |
|---|---|
| Auth | `POST /auth/login` · `GET /auth/me` · `POST /auth/logout` |
| Departments | CRUD (`GET` list cached) |
| Folders | CRUD + `GET /folders/{id}/breadcrumbs` + `GET /folders/tree` (cached) |
| Files | list (+`?search=` FTS, `?folder_id=`, `?department_id=`, pagination) · upload · detail · metadata edit · soft delete · `download` · `preview` |
| Dashboard | `GET /dashboard/stats` (admin, cached) |
| Audit | `GET /activity-logs` (admin, paginated) |

## Testing

```bash
cd backend
docker compose exec app php artisan test     # 60+ tests: auth, RBAC, folders CTE,
                                             # upload/streaming, FTS, cache, jobs
```

## Engineering Decisions (highlights)

| Decision | Why |
|---|---|
| Adjacency list + recursive CTEs for folders | Cheap moves (one UPDATE), single-query breadcrumbs — no denormalization to keep in sync |
| Private disk + UUID filenames + streamed responses | Storage paths never leak; memory stays flat for any file size |
| Generated `tsvector` + GIN | Tokenized once at write; word→rows lookup instead of table scans |
| Cache-aside + TTL + observer invalidation | Fast reads, bounded staleness, precise flush on mutation (never `Cache::flush()`) |
| Async jobs (Redis) for thumbnails & audit | Uploads respond <100ms; failures retry with backoff (5s/30s/120s) into `failed_jobs` |
| Validation at the edge, constraints at the core | FormRequests for UX (422), FK/unique indexes as the last line of truth |

## Project Structure

```
backend/    Laravel 11 API (app/{Http,Models,Services,Jobs,Observers}, docker/, tests/)
frontend/   Vue 3 SPA (src/{api,stores,components,views}, Vite + Tailwind)
```

## Submission Notes

- Conventional commits throughout; no generated attribution trailers
- Demo credentials are intentionally documented for reviewer convenience
- Planning & learning artifacts live outside this repository by design
