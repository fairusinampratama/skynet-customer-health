# Skynet Customer Health Monitor

A real-time network monitoring dashboard designed for ISPs to track customer and server connectivity health.

![Dashboard Preview](https://github.com/laravel/framework/workflows/tests/badge.svg) *Note: Replace with actual screenshot*

## 🚀 Overview

Skynet Customer Health provides Network Operations Centers (NOC) and Support teams with instant visibility into network performance. It monitors thousands of customers and critical servers in real-time, offering both a detailed Admin Panel and a hands-free TV Dashboard for wall-mounted displays.

## ✨ Key Features

- **Real-Time Monitoring**: Checks customer connection status, latency, and packet loss every minute.
- **TV Dashboard**: Dedicated, auto-scrolling views (`/tv/areas`, `/tv/servers`) for clear visibility in the NOC.
- **Smart Isolation**: Toggle "Isolation Mode" for customers (maintenance/non-payment) to suppress alerts and exclude them from downtime stats.
- **Incident Prevention**: High-frequency 30s dashboard refresh rate to spot outages instantly.
- **Scalable Architecture**:
    - Automated **Data Pruning**: Retains detailed health logs for 7 days to keep the database fast.
    - Optimized Indexes: efficient querying for large datasets (~1,600+ customers).
- **Reporting**: Automated WhatsApp integration for daily status reports.

## 🛠 Tech Stack

- **Framework**: [Laravel](https://laravel.com)
- **Admin Panel**: [FilamentPHP](https://filamentphp.com)
- **Database**: MySQL / MariaDB
- **Frontend**: Blade & TailwindCSS (Alpine.js for interactive widgets)
- **Tools**: `ripgrep` (for internal ops tools), Artisan Scheduler

## ⚙️ Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-org/skynet-customer-health.git
   cd skynet-customer-health
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Configure your database credentials in `.env`*

4. **Database Migration**
   ```bash
   php artisan migrate --seed
   ```

5. **Start the System**
   ```bash
   php artisan serve
   ```
   *Visit `http://localhost:8000/admin`*

## ⏰ Scheduler & Workers

For the monitoring Checks and Auto-Pruning to work, the Laravel Scheduler must be running:

```bash
# Run locally
php artisan schedule:work

# Production (Cron)
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

**Key Scheduled Tasks:**
- `health:check` (Every Minute): Pings all customers/servers.
- `model:prune` (Daily): Deletes health logs older than 7 days.
- `app:send-daily-error-report` (Daily 08:00): WhatsApp report.

## 📺 TV Dashboard Routes

- **Area Health**: `/tv/areas`
- **Server Health**: `/tv/servers`
- **Downtime Feed**: `/tv/downtime`
