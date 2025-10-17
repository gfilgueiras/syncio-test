# Syncio Payload Comparison Tool

A full-stack Laravel + Vue.js application that receives two JSON payloads, compares them, and displays the differences in a beautiful side-by-side diff viewer.

## Tech Stack

- **Backend**: Laravel 11 (PHP 8.3+)
- **Frontend**: Vue 3 + Vite + Tailwind CSS
- **Storage**: Cache (in-memory)
- **Infrastructure**: Docker (nginx, php-fpm, node, mysql)

## Features

- 🎯 Receive two payloads via API endpoints
- 🔍 Deep comparison of nested objects (images, variants)
- 🎨 Beautiful side-by-side diff visualization
- 🧪 Unit tests included

## Setup & Installation

### Prerequisites

- Docker & Docker Compose
- Git

### Quick Start

1. **Clone the repository**

```bash
git clone git@github.com:gfilgueiras/syncio-test.git
cd syncio-test
```

2. **Permission and run script**

```bash
chmod 755 startUp.sh
./startUp.sh
```

3. **Access the application**

- Open: http://localhost

## API Endpoints

All endpoints are prefixed with `/api`:

### Store First Payload

```
POST /api/payloads/first
Content-Type: application/json

{
  "id": 432232523,
  "title": "Product Title",
  "images": [...],
  "variants": [...]
}
```

### Store Second Payload

```
POST /api/payloads/second
Content-Type: application/json

{
  "id": 432232523,
  "title": "Updated Product Title",
  "images": [...],
  "variants": [...]
}
```

### Get Comparison Result

```
POST /api/payloads/diff

Response (pending):
{
  "status": "pending",
  "message": "Waiting for both payloads",
  "received": { "p1": true, "p2": false }
}

Response (done):
{
  "status": "done",
  "diff": {
    "rootChanges": { ... },
    "images": { "added": [...], "removed": [...], "changed": [...] },
    "variants": { "added": [...], "removed": [...], "changed": [...] }
  }
}
```

## Running Tests

### Backend Tests

```bash
docker exec -w /var/www/syncio/backend sync_php php artisan test
```

## License

MIT
