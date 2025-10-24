# Product Roadmap

1. [x] **Core Multi-App WebSocket Server** — Database-driven configuration system with Filament admin panel for creating/managing multiple WebSocket applications, including auto-generation of app credentials (app_id, app_key, app_secret), active/inactive toggling, and allowed origins management with Pusher protocol compatibility. `L`

2. [x] **Automatic Configuration Reload** — Queue-based system that automatically restarts the Laravel Reverb WebSocket server when configuration changes are detected (new apps, credential updates, origin changes), ensuring zero-downtime updates without manual intervention. `M`

3. [x] **User Management System** — ~~Role-based access control with user authentication for the Filament admin panel, supporting multiple team members with different permission levels (admin, operator, viewer) to collaborate on WebSocket server management. `M`~~ User management from within the application. There are no roles at this stage; all users are admins. Any user can add, remove or edit users, but cannot delete themselves.

4. [ ] **Logging Infrastructure** — Comprehensive logging system capturing WebSocket connection events (connect, disconnect, errors), message routing, and configuration changes with structured log format for easy debugging and troubleshooting. `M`

5. [ ] **Monitoring Dashboard** — Real-time monitoring interface showing active connections per app, message throughput, error rates, and server health metrics with historical data visualization to help diagnose issues and plan capacity. `L`

6. [ ] **Connection Statistics** — Per-application analytics tracking connection counts, message volume, peak usage times, and bandwidth consumption with exportable reports for capacity planning and billing transparency. `M`

7. [ ] **Webhook System** — Event notification system that sends HTTP callbacks to configured endpoints when specific events occur (app connected, disconnected, custom channel events), enabling consuming applications to react to WebSocket lifecycle events. `L`

8. [ ] **Rate Limiting** — Configurable rate limits per application for connections, messages, and channel subscriptions to prevent abuse and ensure fair resource allocation across multiple apps, with customizable thresholds and automatic throttling. `M`

9. [ ] **Channel Presence Tracking** — Enhanced presence channel support with member tracking, typing indicators, and online/offline status management, providing building blocks for collaborative features like live cursors and user lists. `L`

10. [ ] **API Access** — RESTful API for programmatic management of WebSocket applications, allowing automated provisioning, credential rotation, and configuration updates from CI/CD pipelines or infrastructure-as-code tools. `M`

11. [ ] **Health Check Endpoints** — Dedicated health check and readiness endpoints for load balancers and monitoring systems, with detailed status information about server capacity, database connectivity, and queue worker health. `S`

12. [ ] **Documentation Site** — Comprehensive documentation covering installation, configuration, API reference, and migration guides from Pusher, with code examples in multiple languages and troubleshooting guides for common setup issues. `L`

> Notes
> - First three items represent completed core functionality
> - Remaining items ordered by technical dependencies and user value
> - Each feature represents end-to-end functionality (backend + admin UI where applicable)
> - User management and logging are highest priority as they enable team collaboration and troubleshooting
> - Later features build on logging/monitoring infrastructure (webhooks, rate limiting, etc.)
