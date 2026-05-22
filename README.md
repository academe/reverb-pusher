# Reverb Pusher

A self-hosted, Pusher-compatible WebSocket server built on Laravel 12 and [Laravel Reverb](https://reverb.laravel.com). Designed for teams and organisations that want full control over their real-time infrastructure without relying on a third-party SaaS. Manage multiple Reverb apps through a Filament admin panel, connect your existing Laravel applications using the standard Pusher protocol, and optionally manage apps programmatically via a REST API.

This project is under active development. Documentation and features will continue to be refined.

## Table of Contents

- [Nginx Configuration](#nginx-configuration)
- [Supervisor Configuration](#supervisor-configuration)
- [Client Configuration Example](#client-configuration-example)
- [Running the WebSocket Server](#running-the-websocket-server)
- [Endpoints](#endpoints)
  - [Admin Panel Access](#admin-panel-access)
  - [WebSocket Connections](#websocket-connections)
  - [API](#api)
  - [Broadcasting Auth Endpoint](#broadcasting-auth-endpoint)
  - [Internal Architecture](#internal-architecture)
  - [Laravel Broadcasting Addition](#laravel-broadcasting-addition)
- [WebSocket Server Daemon](#websocket-server-daemon)
- [Create a User](#create-a-user)

## Nginx Configuration

Browsers on an HTTPS page cannot speak plain `ws://`. Nginx (or whichever
reverse proxy fronts your site) has to terminate TLS and forward the WebSocket
upgrade through to Reverb on its local port. Substitute `REVERB_PORT` below
with whatever port `php artisan reverb:start` listens on (default `8080`).

Add the following inside the `server { listen 443 ssl; ... }` block, **before**
any catch-all `location /`:

```nginx
# Reverb WebSocket — required for any browser client (including the diagnostic page)
location ^~ /app/ {
    proxy_pass http://127.0.0.1:REVERB_PORT;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_read_timeout 86400;
    proxy_send_timeout 86400;
}

# Reverb HTTP API — only needed if PHP reaches Reverb via this proxy
# (skip this block if your PHP broadcaster connects directly to 127.0.0.1:REVERB_PORT)
location ^~ /apps/ {
    proxy_pass http://127.0.0.1:REVERB_PORT;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

Notes:

- `^~` makes these prefix matches win over any regex `location` blocks in the
  same server (Herd's generated config contains `location ~ \.php` and
  `location ~ /\.ht`, which would otherwise compete).
- The trailing slash on `/app/` and `/apps/` matches paths *under* those
  prefixes (e.g. `/app/{appKey}`, `/apps/{appId}/events`) without
  accidentally matching unrelated routes that just happen to start with
  `app`.
- `proxy_read_timeout 86400` is a day — without it nginx kills idle
  WebSocket connections at 60s.

Optional health-check passthrough:

```nginx
location = /health {
    proxy_pass http://127.0.0.1:REVERB_PORT/health;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

### Verifying the proxy

The **Reverb Diagnostics** page in the admin panel includes a server-side
transport probe that issues an HTTP request with `Upgrade: websocket`
headers against `/app/{loopbackKey}` and reads only the status line:

| Result | Meaning |
| --- | --- |
| `101 Switching Protocols` | Proxy forwards upgrades to Reverb correctly |
| `404` | Proxy is not forwarding `/app/*` — the snippet above is missing |
| `5xx` | Proxy is configured but Reverb on the upstream port is not running |
| Connection refused / timeout | Nothing listening at the host/port the browser would use |

### Herd-specific note

Herd generates its per-site nginx config at
`~/.config/herd/config/valet/Nginx/{site}.test.conf` and may regenerate that
file (e.g. when toggling TLS), discarding manual edits. Keep this snippet
somewhere safe so you can re-paste it after a regeneration. Herd does not
currently document a per-site custom-config drop-in mechanism.

## Supervisor Configuration

```ini
[program:reverb-server]
process_name=%(program_name)s_%(process_num)02d
command=php /home/forge/pusher.yourdomain.com/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=forge
numprocs=1
redirect_stderr=true
stdout_logfile=/home/forge/pusher.yourdomain.com/storage/logs/reverb.log
stopwaitsecs=3600
```

## Client Configuration Example

```php
<?php

// For your existing Laravel 9 applications
// config/broadcasting.php

'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'), // Your app key from Filament admin
    'secret' => env('PUSHER_APP_SECRET'), // Your app secret from Filament admin
    'app_id' => env('PUSHER_APP_ID'), // Your app ID from Filament admin
    'options' => [
        'host' => 'pusher.yourdomain.com',
        'port' => 443,
        'scheme' => 'https',
        'encrypted' => true,
        'useTLS' => true,
    ],
    'client_options' => [
        // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
    ],
],
```

`.env` configuration for client apps:

```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-app-id-from-admin
PUSHER_APP_KEY=your-app-key-from-admin
PUSHER_APP_SECRET=your-app-secret-from-admin
PUSHER_HOST=pusher.yourdomain.com
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=
```

JavaScript configuration (for frontend):

```javascript
// resources/js/bootstrap.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'your-app-key-from-admin',
    cluster: '', // Leave empty for custom host
    wsHost: 'pusher.yourdomain.com',
    wsPort: 443,
    wssPort: 443,
    forceTLS: true,
    encrypted: true,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
});
```

## Running the WebSocket Server

For development this is the easiest to run:

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

On production you may want to bind to localhost or a private NIC only:

```bash
php artisan reverb:start --host=127.0.0.1 --port=8080
php artisan reverb:start --host=10.0.0.5 --port=8080
```

By binding to 0.0.0.0 there is a risk that port 8080 will be exposed to the Internet if not blocked by firewall.
However, if your nginx server is not local, then you may have to use 0.0.0.0 and be very careful with firewall settings.

## Endpoints

### Admin Panel Access

URL: `https://pusher.yourdomain.com/admin`
Port: 443 (HTTPS)
Served by: Laravel app with Filament

### WebSocket Connections

URL: `wss://pusher.yourdomain.com/app/your-app-key`
Port: 443 (WSS - WebSocket Secure)
Served by: Nginx proxy → Reverb server (internal port 8080)

### API

The application provides a REST API for programmatic management of Reverb apps. By default, the API is **disabled** — all endpoints return `401 Unauthorized` until a token is configured.

#### Enabling API Access

Set a bearer token in your `.env` file:

```env
API_TOKEN=your-secret-token-here
```

This can be any string you choose. For production, generate a strong random token:

```bash
php artisan tinker --execute="echo Str::random(64);"
```

#### Authentication

All API requests must include the token as a Bearer token in the `Authorization` header:

```bash
curl -H "Authorization: Bearer your-secret-token-here" \
     https://pusher.yourdomain.com/api/reverb-apps
```

| Scenario                               | Response                                  |
| -------------------------------------- | ----------------------------------------- |
| No `API_TOKEN` set in `.env`           | `401` — "API access is not configured."   |
| Missing or incorrect token in request  | `401` — "Invalid API token."              |
| Correct token                          | Request proceeds                          |

#### Available Endpoints

| Method | Endpoint                  | Description                                       |
| ------ | ------------------------- | ------------------------------------------------- |
| `GET`  | `/api/reverb-apps`        | List all apps (optionally filter by `?active=1`)  |
| `POST` | `/api/reverb-apps`        | Create a new app                                  |
| `GET`  | `/api/reverb-apps/{id}`   | Show a specific app                               |
| `POST` | `/api/reverb-apps/restart`| Queue a Reverb server restart                     |

#### Alternative Authentication

The built-in token authentication is intentionally simple — a single shared secret suitable for server-to-server communication. If you need more advanced authentication (per-user tokens, scoped permissions, token revocation), you can replace the `AuthenticateApiToken` middleware with [Laravel Sanctum](https://laravel.com/docs/sanctum) or any other authentication guard. The API routes are defined in `routes/api.php`.

### Broadcasting Auth Endpoint

URL: `https://pusher.yourdomain.com/broadcasting/auth`
Port: 443 (HTTPS)
Served by: Laravel app (for private channel authentication)

### Internal Architecture

```text
Internet (Port 443)
    ↓
Nginx (Port 80/443)
    ├── /admin → Laravel App (Filament admin)
    ├── /app → Reverb Server (Port 8080)
    └── /broadcasting → Laravel App (auth endpoints)
```

All Pusher-compatible WebSocket servers (including Soketi, Laravel Reverb, and the official Pusher service) use this URL structure:

```text
wss://your-domain.com/app/{app_key}
```

### Laravel Broadcasting Addition

Authentication endpoint: `https://domain.com/broadcasting/auth`
Purpose: Authenticate private/presence channels from client-side

When Laravel Uses /broadcasting/auth:

For Private Channels:

```javascript
// Client tries to subscribe to private channel
Echo.private('orders.1').listen('OrderShipped', (e) => {
    console.log(e.order);
});
```

What happens:

1. Client connects to WebSocket: `wss://pusher.yourdomain.com/app/your-key`
2. Client requests private channel: `private-orders.1`
3. Laravel client automatically calls: `POST /broadcasting/auth`
4. Your Laravel app validates the user can access that channel
5. Returns signed authentication token
6. Client subscribes with the token

## WebSocket Server Daemon

Directory: `/home/forge/your.websockets.server.domain/`

Command:

```bash
php8.4 artisan reverb:start --host=0.0.0.0 --port=8080
```

## Create a User

```bash
php artisan make:filament-user

php8.4 artisan make:filament-user
```
