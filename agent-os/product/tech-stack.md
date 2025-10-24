# Tech Stack

This document outlines the complete technical stack for Reverb Pusher, a self-hosted WebSocket server for managing real-time communication across multiple applications.

## Framework & Runtime

- **Application Framework:** Laravel 12
- **Language/Runtime:** PHP 8.3
- **Package Manager:** Composer
- **WebSocket Server:** Laravel Reverb (official Laravel WebSocket server)

**Rationale:** Laravel 12 provides a mature, well-documented framework with excellent ecosystem support. Laravel Reverb offers native WebSocket capabilities with Pusher protocol compatibility, eliminating the need for external WebSocket servers while maintaining developer familiarity.

## Frontend

- **JavaScript Framework:** Alpine.js
- **CSS Framework:** Tailwind CSS 3/4
- **Build Tool:** Vite 6
- **Admin Panel:** Filament 3.3

**Rationale:** Alpine.js provides lightweight reactivity without heavy JavaScript framework overhead. Tailwind CSS enables rapid UI development with utility-first approach. Filament 3.3 delivers a production-ready admin panel with minimal custom code, accelerating development of management interfaces.

## Database & Storage

- **Database:** MySQL
- **ORM/Query Builder:** Eloquent (Laravel's built-in ORM)
- **Queue Driver:** Database

**Rationale:** MySQL is widely supported by hosting providers and offers reliability for production deployments. Database queue driver simplifies deployment by eliminating need for Redis/Beanstalkd while providing adequate performance for configuration change processing.

## WebSocket & Real-Time

- **WebSocket Protocol:** Pusher Protocol (via Laravel Reverb)
- **Broadcasting:** Laravel Broadcasting subsystem
- **Connection Management:** Laravel Reverb server

**Rationale:** Pusher protocol compatibility enables seamless migration from Pusher.com and provides well-documented client libraries across multiple platforms. Laravel Reverb handles WebSocket connections natively within the Laravel ecosystem.

## Testing & Quality

- **Test Framework:** Pest PHP
- **Testing Approach:** Feature tests, unit tests for critical business logic
- **Code Style:** Laravel conventions with PSR-12 standard

**Rationale:** Pest PHP offers expressive, modern testing syntax while maintaining PHPUnit compatibility. Focus on feature tests ensures end-to-end functionality works correctly, which is critical for WebSocket reliability.

## Deployment & Infrastructure

- **Web Server:** Nginx (reverse proxy for Laravel and WebSocket connections)
- **Process Management:** Supervisor (manages Laravel Reverb and queue workers)
- **Hosting Compatibility:** Laravel Forge, DigitalOcean, AWS, any VPS
- **SSL/TLS:** Required for secure WebSocket connections (wss://)

**Rationale:** Nginx efficiently handles both HTTP and WebSocket traffic with proven reverse proxy capabilities. Supervisor ensures Laravel Reverb and queue workers restart automatically on failure. Laravel Forge compatibility simplifies deployment for Laravel developers.

## Development Tools

- **Version Control:** Git
- **Local Development:** Laravel Herd, Valet, or Docker (via Laravel Sail)
- **Environment Management:** .env files with environment variables
- **Asset Compilation:** Vite for hot module replacement during development

**Rationale:** Standard Laravel development workflow with environment-specific configuration through .env files prevents accidental credential commits and supports multiple deployment environments.

## Security

- **Authentication:** Laravel Breeze/Jetstream (for admin panel users when implemented)
- **Credential Storage:** Encrypted app secrets in database
- **CORS:** Per-application allowed origins configuration
- **SSL/TLS:** Required for production WebSocket connections

**Rationale:** Laravel's built-in authentication provides secure user management. Per-app CORS configuration ensures only authorized origins can establish WebSocket connections.

## Third-Party Services

- **Authentication:** Native Laravel authentication (no external service)
- **Email:** Laravel Mail (configurable SMTP, Mailgun, etc.)
- **Monitoring:** To be determined (future consideration: Laravel Pulse, Sentry)

**Rationale:** Minimize external dependencies to reduce costs and complexity. Self-hosted authentication aligns with the product's philosophy of infrastructure ownership.

## Architecture Decisions

### Separation of Concerns
The WebSocket server runs as an independent application, separate from consuming apps. Client applications connect via Pusher protocol using app credentials, maintaining clean architectural boundaries.

### Database-Driven Configuration
All app configurations stored in MySQL allow dynamic updates without server restarts or file changes. Queue-based restart mechanism ensures configuration changes propagate automatically.

### Open Source Philosophy
Technology choices prioritize standard, well-documented tools over proprietary solutions, enabling community contributions and customization.

### Hosting Flexibility
Stack compatible with standard LAMP/LEMP hosting, avoiding specialized WebSocket hosting requirements. Works on shared hosting, VPS, or dedicated servers with standard PHP support.
