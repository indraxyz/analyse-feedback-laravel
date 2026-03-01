# Feedback Laravel

Laravel application with a **customer feedback analysis API** powered by Anthropic Claude. Accepts feedback text in any language and returns an English summary, sentiment, and detected language. The web UI is a React + Inertia + Vite frontend.

---

## Table of Contents

- [Requirements](#requirements)
- [Local Setup](#local-setup)
- [Project Structure & Technical Details](#project-structure--technical-details)
- [API Reference](#api-reference)
- [Configuration](#configuration)
- [Testing](#testing)
- [Deployment on AWS EC2](#deployment-on-aws-ec2)

---

## Requirements

- **PHP** 8.2+
- **Composer** 2.x
- **Node.js** 18+ and **npm**
- **Anthropic API key** (for the feedback analysis API)

---

## Local Setup

### 1. Clone and install dependencies

```bash
git clone <repository-url> feedback-laravel
cd feedback-laravel
composer install
```

### 2. Environment file

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set at least:

- `APP_NAME`, `APP_URL` (e.g. `http://localhost:8080`)
- `ANTHROPIC_API_KEY` – required for `POST /api/analyse-feedback`
- Optionally `ANTHROPIC_MODEL` (default: `claude-sonnet-4-6`), `RATE_LIMIT_MAX`, `LOG_LEVEL`

On Windows, if you see **cURL error 60 (SSL certificate)** when calling the API, set:

```env
HTTP_VERIFY_SSL=false
```

Use this only for local development; in production fix PHP’s CA bundle (`curl.cainfo` / `openssl.cafile` in `php.ini`) and keep `HTTP_VERIFY_SSL` true or unset.

### 3. Frontend build

```bash
npm install
npm run build
```

### 4. Run the application

**One-time setup (install + env + key + build):**

```bash
composer run setup
```

**Development (PHP server + queue + Vite):**

```bash
composer run dev
```

This starts:

- PHP server (default `http://localhost:8000`)
- Queue worker
- Vite dev server

**Or run PHP only (after building assets):**

```bash
php artisan serve --port=8080
```

- Web: `http://localhost:8080/`
- Health: `http://localhost:8080/api/health`
- Analyse feedback: `POST http://localhost:8080/api/analyse-feedback`

---

## Project Structure & Technical Details

### Stack

| Layer    | Technology                               |
| -------- | ---------------------------------------- |
| Backend  | Laravel 12, PHP 8.2+                     |
| Frontend | React 19, Inertia.js 2, Vite 7           |
| Styling  | Tailwind CSS 4                           |
| API AI   | Anthropic Claude (Messages API)          |
| Tests    | Pest (PHP), ESLint, Prettier, TypeScript |

### Directory layout (relevant parts)

```
app/
├── Exceptions/
│   ├── AnthropicApiKeyMissingException.php   # 503 when API key missing
│   └── AnthropicServiceException.php          # 502 on AI/network errors
├── Http/
│   ├── Controllers/
│   │   └── AnalyseFeedbackController.php     # POST /api/analyse-feedback
│   ├── Middleware/
│   │   └── HandleInertiaRequests.php          # Inertia shared props
│   └── Requests/
│       └── AnalyseFeedbackRequest.php        # Validates feedback_text
├── Providers/
│   └── AppServiceProvider.php                # Rate limiting for analyse-feedback
└── Services/
    └── AnalyseFeedbackService.php             # Calls Anthropic API, parses response

config/
├── services.php    # anthropic.*, rate_limit.*
└── session.php     # SESSION_DRIVER (default: file)

routes/
├── api.php         # GET /api/health, POST /api/analyse-feedback (throttled)
└── web.php         # GET / -> Inertia welcome page

bootstrap/app.php   # Registers web, api, console routes; middleware
```

### Request flow (analyse-feedback)

1. Request hits `POST /api/analyse-feedback` (prefix `api` applied by Laravel).
2. **Middleware:** `throttle:analyse-feedback` (per-minute limit from `RATE_LIMIT_MAX`, keyed by IP).
3. **Controller:** `AnalyseFeedbackController@store` uses `AnalyseFeedbackRequest` for validation.
4. **Validation:** `feedback_text` required, string, not empty/whitespace-only → 400 on failure.
5. **Service:** `AnalyseFeedbackService::analyse()`:
    - Ensures `ANTHROPIC_API_KEY` is set → else throws `AnthropicApiKeyMissingException` (503).
    - Calls Anthropic Messages API with a prompt that asks for JSON: `summary`, `sentiment`, `language`.
    - Parses response (handles multiple content blocks, e.g. thinking + text; strips markdown; falls back to first `{` … `}`).
    - On API/network/parse errors throws `AnthropicServiceException` (502).
6. **Controller** maps exceptions to JSON and status codes (503, 502, 500).

### Web routes

- **GET /** – Inertia “welcome” page (React).
- **GET /up** – Laravel health check (used by e.g. load balancers).

---

## API Reference

Base URL for API: `https://your-domain/api` (or `http://localhost:8080/api` locally).

### Endpoints

| Method | Path                    | Description                      |
| ------ | ----------------------- | -------------------------------- |
| GET    | `/api/health`           | Health check `{"status":"ok"}`   |
| POST   | `/api/analyse-feedback` | Analyse feedback (body required) |

### POST /api/analyse-feedback

Analyses customer feedback (any language) and returns an English summary, sentiment, and detected language.

**Request**

- **Content-Type:** `application/json`
- **Body:**

```json
{
    "feedback_text": "Your feedback string here (required, non-empty)"
}
```

**Success (200)**

```json
{
    "summary": "Short English summary of the feedback.",
    "sentiment": "positive",
    "language": "english"
}
```

- `sentiment`: one of `positive`, `neutral`, `negative`.
- `language`: detected language of the input (e.g. `english`, `indonesian`, `japanese`).

**Error responses**

| Status | Meaning                                                                          |
| ------ | -------------------------------------------------------------------------------- |
| 400    | Missing or empty `feedback_text` (body or whitespace-only).                      |
| 429    | Rate limit exceeded (configured by `RATE_LIMIT_MAX` per minute).                 |
| 502    | AI service error or invalid response (e.g. Anthropic API/network/parse failure). |
| 503    | `ANTHROPIC_API_KEY` not set; body includes `"message": "API_KEY_MISSING"`.       |
| 500    | Unexpected server error; body includes `"message": "INTERNAL_ERROR"`.            |

**Example (cURL)**

```bash
curl -X POST http://localhost:8080/api/analyse-feedback \
  -H "Content-Type: application/json" \
  -d '{"feedback_text":"The product is great but delivery was slow."}'
```

---

## Configuration

### Environment variables (main)

| Variable            | Description                                       | Default           |
| ------------------- | ------------------------------------------------- | ----------------- |
| `APP_NAME`          | Application name                                  | Laravel           |
| `APP_ENV`           | Environment (local, production, etc.)             | local             |
| `APP_DEBUG`         | Debug mode (never true in production)             | true              |
| `APP_URL`           | Base URL of the app                               | http://localhost  |
| `LOG_LEVEL`         | Logging level                                     | debug             |
| `ANTHROPIC_API_KEY` | Anthropic API key (required for analyse-feedback) | —                 |
| `ANTHROPIC_MODEL`   | Claude model identifier                           | claude-sonnet-4-6 |
| `HTTP_VERIFY_SSL`   | Enable SSL verification for outbound HTTP         | true              |
| `RATE_LIMIT_MAX`    | Max requests per minute for analyse-feedback      | 60                |

Config is read in `config/services.php` under `anthropic` and `rate_limit`. Session driver is configured in `config/session.php` (default `file`; no database required for sessions if using file).

---

## Testing

**Run all tests (includes lint check):**

```bash
composer test
```

**Run only PHP tests:**

```bash
php artisan test
```

**Run API feature tests:**

```bash
php artisan test tests/Feature/AnalyseFeedbackApiTest.php
```

**Lint (Pint):**

```bash
composer run lint:check
# or auto-fix:
composer run lint
```

**Frontend lint / type check:**

```bash
npm run lint:check
npm run types:check
```

The API tests mock `AnalyseFeedbackService`, so no real API key is needed for tests.

---

## Deployment on AWS EC2

### 1. EC2 instance

- **AMI:** Ubuntu 22.04 LTS (or Amazon Linux 2023).
- **Instance type:** e.g. `t3.small` (or larger for production).
- **Security group:** Allow inbound:
    - **22** (SSH).
    - **80** (HTTP).
    - **443** (HTTPS).
- Attach an **Elastic IP** if you want a fixed public IP.
- Optionally put the instance behind an **Application Load Balancer** and restrict direct access to 80/443 from the ALB only.

### 2. Install PHP, Composer, Node, Nginx

**Ubuntu 22.04:**

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mbstring php8.2-xml php8.2-curl php8.2-sqlite3 php8.2-zip php8.2-intl unzip nginx
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 3. Deploy the application

```bash
sudo mkdir -p /var/www/feedback-laravel
sudo chown -R $USER:$USER /var/www/feedback-laravel
cd /var/www/feedback-laravel
git clone <your-repo-url> .
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Edit `.env` for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
LOG_LEVEL=info
ANTHROPIC_API_KEY=your-anthropic-key
ANTHROPIC_MODEL=claude-sonnet-4-6
RATE_LIMIT_MAX=60
# Keep HTTP_VERIFY_SSL=true or omit (use proper CA bundle)
```

Build frontend:

```bash
npm ci
npm run build
```

Laravel optimizations:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set permissions:

```bash
sudo chown -R www-data:www-data /var/www/feedback-laravel
sudo chmod -R 755 /var/www/feedback-laravel
sudo chmod -R 775 /var/www/feedback-laravel/storage /var/www/feedback-laravel/bootstrap/cache
```

### 4. Nginx

Create a vhost, e.g. `/etc/nginx/sites-available/feedback-laravel`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/feedback-laravel/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 60;
    }
}
```

Enable and test:

```bash
sudo ln -s /etc/nginx/sites-available/feedback-laravel /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 5. SSL with Let’s Encrypt (recommended)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

Follow prompts. Certbot will adjust the Nginx config for HTTPS.

### 6. Queue worker (optional)

If you use queues in the future, run a worker via Supervisor. Example `/etc/supervisor/conf.d/feedback-laravel-worker.conf`:

```ini
[program:feedback-laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/feedback-laravel/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/feedback-laravel/storage/logs/worker.log
stopwaitsecs=3600
```

Then:

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start feedback-laravel-worker:*
```

### 7. Deploy script (updates)

Save as `deploy.sh` in the project root (or run the commands manually):

```bash
#!/bin/bash
set -e
cd /var/www/feedback-laravel
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data /var/www/feedback-laravel
echo "Deploy done."
```

Run after pulling: `bash deploy.sh`.

### 8. Checklist

- [ ] `.env` has `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` set to your domain.
- [ ] `ANTHROPIC_API_KEY` is set; do not commit `.env` or keys.
- [ ] `storage` and `bootstrap/cache` are writable by the web server (`www-data`).
- [ ] Nginx root is `.../public`.
- [ ] SSL is in place for production; `HTTP_VERIFY_SSL` is true or unset.
- [ ] Firewall allows only 22, 80, 443 as needed.

---

## License

...
