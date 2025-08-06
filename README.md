## Nginx Configuration

Add this to your Nginx site configuration in Forge
This should be added to the server block

```
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_cache_bypass $http_upgrade;
    
    # WebSocket specific
    proxy_read_timeout 86400;
    proxy_send_timeout 86400;
}
```

Optional: Add a health check endpoint

```
location /health {
    proxy_pass http://127.0.0.1:8080/health;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

## Supevisor Configuration

```
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

## Client configuration Example

```
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

// .env configuration for client apps
/*
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-app-id-from-admin
PUSHER_APP_KEY=your-app-key-from-admin
PUSHER_APP_SECRET=your-app-secret-from-admin
PUSHER_HOST=pusher.yourdomain.com
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=
*/

// JavaScript configuration (for frontend)
/*
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
*/
```

## Running the Websockets Server

    php artisan reverb:start --host=0.0.0.0 --port=8080

## Endpoints

### Admin Panel Access

URL: https://pusher.yourdomain.com/admin
Port: 443 (HTTPS)
Served by: Laravel app with Filament

### WebSocket Connections

URL: wss://pusher.yourdomain.com/app/your-app-key
Port: 443 (WSS - WebSocket Secure)
Served by: Nginx proxy → Reverb server (internal port 8080)

### API Endpoints (if needed)

URL: https://pusher.yourdomain.com/broadcasting/auth
Port: 443 (HTTPS)
Served by: Laravel app (for private channel authentication)

### Internal Architecture

```
Internet (Port 443) 
    ↓
Nginx (Port 80/443)
    ├── /admin → Laravel App (Filament admin)
    ├── /app → Reverb Server (Port 8080)
    └── /broadcasting → Laravel App (auth endpoints)
```

All Pusher-compatible WebSocket servers (including Soketi, Laravel Reverb, and the official Pusher service) use this URL structure:

    wss://your-domain.com/app/{app_key}

### Laravel Broadcasting Addition

Authentication endpoint: https://domain.com/broadcasting/auth
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

1. Client connects to WebSocket: wss://pusher.yourdomain.com/app/your-key
2. Client requests private channel: private-orders.
3. Laravel client automatically calls: POST /broadcasting/auth
4. Your Laravel app validates the user can access that channel
5. Returns signed authentication token
6. Client subscribes with the token

## nginx minimal change needed

```
# Add this BEFORE the existing location / block

    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400;
        proxy_send_timeout 86400;
    }
```

## Websockets Server Daemon

Directory:
/home/forge/your.websockets.server.domain/

Command:
php8.4 artisan reverb:start --host=0.0.0.0 --port=8080

## Create a user

    php artisan make:filament-user

    php8.4 artisan make:filament-user
