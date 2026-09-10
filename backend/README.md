# Docsy Backend API Service

[![PHP Version](https://img.shields.io/badge/PHP-8.3_FPM-777BB4?logo=php&logoColor=white)](https://php.net)
[![Framework](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Database](https://img.shields.io/badge/PostgreSQL-16--alpine-4169E1?logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Cache & Queue](https://img.shields.io/badge/Redis-7--alpine-DC382D?logo=redis&logoColor=white)](https://redis.io)
[![Web Server](https://img.shields.io/badge/Nginx-Alpine-009639?logo=nginx&logoColor=white)](https://nginx.org)

Docsy Backend is an enterprise-grade RESTful API service powering the **Docsy File Management System (FMS)**. Built with Laravel 11 on a containerized LEMP stack, the system implements robust backend system design patterns including hierarchical relational data traversal via PostgreSQL Recursive CTEs, token-based stateful authentication, decoupled API contracts, and strict domain-driven layering.

---

## 1. Architectural Blueprint & Design Patterns

The service is architected around strict separation of concerns, ensuring high maintainability, testability, and deterministic performance:

* **Layered Clean Architecture:** Complete decoupling of HTTP transport (`Controllers`), boundary validation (`FormRequests`), domain serialization (`JsonResources`), access control (`Policies`), and business algorithms (`Services`).
* **Hierarchical Tree Traversal Engine:** Folders are modeled using the **Adjacency List** pattern (`parent_id` foreign key) paired with **PostgreSQL Recursive Common Table Expressions (`WITH RECURSIVE`)** for $O(1)$ breadth-first breadcrumb computation and cycle detection.
* **Stateless API & Stateful Tokens:** Secured via **Laravel Sanctum Personal Access Tokens (PAT)** using SHA-256 hashed secret persistence in PostgreSQL, supporting multi-device sessions and instantaneous server-side revocation.
* **Strict API Contract & Envelope:** Standardized immutable JSON payload envelopes across all endpoints (`success`, `message`, `data`, `errors`).
* **Database Isolation:** Production/development database (`docsy_db`) and automated testing database (`docsy_test`) are strictly separated to prevent test side-effects and data pollution.

---

## 2. Containerized Infrastructure Topology

The application runs on a multi-container Docker Compose network (`docsy` bridge driver):

```
                       [ Host / Client ]
                               │
                ┌──────────────┴──────────────┐
                │ HTTP :8000                  │ TCP :5432 / :6379 (Dev GUI)
                ▼                             ▼
       ┌─────────────────┐           ┌──────────────────┐
       │   docsy-web     │           │ Host Tools       │
       │  (Nginx Alpine) │           │ (DBeaver / Redis)│
       └────────┬────────┘           └──────────────────┘
                │ FastCGI (TCP :9000)
                ▼
       ┌─────────────────┐
       │   docsy-app     │
       │ (PHP 8.3-FPM)   │
       └────────┬────────┘
                │
         ┌──────┴──────────────────────────┐
         │                                 │
         ▼                                 ▼
┌──────────────────┐              ┌──────────────────┐
│    docsy-db      │              │   docsy-redis    │
│ (PostgreSQL 16)  │              │    (Redis 7)     │
└──────────────────┘              └────────┬─────────┘
         ▲                                 │
         │                                 │
         └─────────┐                       │
                   │                       │
          ┌────────┴────────┐              │
          │   docsy-queue   │◀─────────────┘
          │ (Queue Worker)  │
          └─────────────────┘
```

### Exposed Service Ports:

| Service Name | Container Name | Image / Base | Internal Port | Host Port | Purpose |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **`web`** | `docsy-web` | `nginx:alpine` | `80` | `8000` | HTTP reverse proxy, TLS termination, static asset streaming |
| **`app`** | `docsy-app` | `php:8.3-fpm-alpine` | `9000` | — | Core application runtime & PHP-FPM worker pool |
| **`db`** | `docsy-db` | `postgres:16-alpine` | `5432` | `5432` | Relational storage (`pgcrypto` enabled for UUIDs) |
| **`redis`** | `docsy-redis` | `redis:7-alpine` | `6379` | `6379` | Cache store, distributed lock manager & queue broker |
| **`queue`** | `docsy-queue` | `php:8.3-fpm-alpine` | — | — | Async queue worker (`php artisan queue:work redis`) |

---

## 3. Directory Structure & Domain Layering

```text
backend/
├── app/
│   ├── Enums/                 # Strongly-typed string-backed enums (e.g. RoleEnum)
│   ├── Http/
│   │   ├── Controllers/Api/V1/# Thin transport controllers (no business logic)
│   │   ├── Requests/          # FormRequest validation rules & boundary authorization
│   │   └── Resources/V1/      # API transformation layer (strict JSON schema contracts)
│   ├── Models/                # Eloquent ORM entity models with relationships & scopes
│   ├── Policies/              # Declarative Role-Based Access Control (RBAC) rules
│   ├── Providers/             # Service bootstrap and container bindings
│   └── Services/              # Pure domain logic (e.g., FolderBreadcrumbService, CycleDetector)
├── bootstrap/
│   └── app.php                # Application configuration: API prefix (/api/v1) & error rendering
├── config/                    # Framework configuration definitions
├── database/
│   ├── factories/             # Model factories for deterministic test state generation
│   ├── migrations/            # Version-controlled schema migrations
│   └── seeders/               # Baseline RBAC & initial data population
├── docker/                    # Docker infrastructure definitions (Nginx, PHP, Postgres init)
├── routes/
│   ├── api.php                # Protected & public API route definitions
│   └── console.php            # Artisan CLI task commands
├── tests/
│   ├── Feature/               # End-to-end HTTP integration and contract test suites
│   └── Unit/                  # Isolated algorithm and domain unit tests
├── docker-compose.yml         # Container orchestration manifest
└── phpunit.xml                # Automated test environment configuration (docsy_test DB)
```

---

## 4. Getting Started & Local Development

### Prerequisites
* Docker Engine (`>= 24.x`) and Docker Compose (`>= v2.x`)
* macOS: Colima runtime (`colima start --vm-type=vz --mount-type=virtiofs`) or Docker Desktop

### 1. Environment Initialization
Clone the repository and prepare the local environment file:

```bash
cd backend
cp .env.example .env
```

Ensure the database and cache configurations align with the Docker Compose specifications:

```ini
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=docsy_db
DB_USERNAME=docsy
DB_PASSWORD=secret

QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

### 2. Bootstrapping Container Stack

Start all 5 services in detached mode:

```bash
docker compose up -d --build
```

Verify that all healthchecks report healthy:

```bash
docker compose ps
```

### 3. Database Migration & Baseline Seeding

Generate the application encryption key and execute the initial migrations and seeders:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
```

#### Pre-seeded Default Accounts:
* **Administrator:** `admin@example.com` / `password` (Full Read/Write access)
* **Viewer:** `viewer@example.com` / `password` (Read-only access)

---

## 5. API Conventions & Standard Response Envelope

All API endpoints reside under the `/api/v1` namespace. Every JSON response strictly complies with the following unified envelopes:

### Success Response (`200 OK`, `201 Created`):
```json
{
  "success": true,
  "message": "Operation description",
  "data": { ... }
}
```

### Validation Error Response (`422 Unprocessable Entity`):
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "name": [
      "The name field is required."
    ]
  }
}
```

### Authentication / Authorization Errors:
* **`401 Unauthorized`:** Missing or invalid Bearer Token.
* **`403 Forbidden`:** Authenticated user lacks required RBAC role permissions (enforced via Policies).
* **`404 Not Found`:** Implicit route model binding failure or resource non-existence.

---

## 6. Testing & Quality Assurance

Automated tests execute against a dedicated, isolated PostgreSQL test database (`docsy_test`) to ensure zero pollution of development data.

### Running Test Suite:
```bash
docker compose exec app php artisan test
```

### Code Formatting & Static Analysis:
The codebase adheres to PSR-12 and Laravel opinionated styling via **Laravel Pint**:

```bash
# Check code style violations
docker compose exec app ./vendor/bin/pint --test

# Auto-fix formatting
docker compose exec app ./vendor/bin/pint
```

---

## 7. Security Architecture

* **Least Privilege Principle:** Database user permissions and container runtimes follow least-privilege standards.
* **Anti-Enumeration Auth:** Authentication failures return generic error messages to prevent email enumeration attacks.
* **Mass-Assignment Protection:** Strict attribute filtering across all Eloquent models via FormRequest validation.
* **Path Traversal & Tree Integrity:** Folder movements and deletions are verified via cycle detection algorithms before mutation.
