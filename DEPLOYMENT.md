# Deployment Guide: Sky-Cacti Server

This guide explains how to safely deploy the **Skynet Customer Health** monitor to the existing `Sky-Cacti` server using Docker.

> [!IMPORTANT]
> **Why Docker?** The Sky-Cacti server likely runs an older PHP version. We use Docker to run our app in an isolated container (PHP 8.2) without breaking the existing Cacti installation.

## Prerequisites

SSH into the `Sky-Cacti` server (`103.156...`) and ensure Docker is installed.

```bash
# Check if docker is installed
docker -v

# If not installed, install it:
curl -fsSL https://get.docker.com | sh
```

## Step-by-Step Deployment

### 1. Download the Code
Clone the repository to a new folder (e.g., `/opt/skynet`).

```bash
cd /opt
git clone https://github.com/your-org/skynet-customer-health.git skynet
cd skynet
```

### 2. Configure Environment
Create the `.env` file. You can use the existing MySQL database (if reachable) or the one inside Cacti's server, but simpler to use the `host.docker.internal` mapping.

```bash
cp .env.example .env
nano .env
```

**Required Updates in `.env`:**
```ini
APP_URL=http://your-server-ip:8080

# Database Configuration
# If checking Cacti's DB, use host.docker.internal to reach the host's MySQL
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=skynet_health
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### 3. Build & Run
We use `docker compose` to build the image and start it on **Port 8080**.

```bash
# Build and start in background
docker compose up -d --build
```

### 4. Verify Installation
Check if the container is running:
```bash
docker ps
```
You should see:
`0.0.0.0:8080->80/tcp   skynet_app`

Now access the dashboard at: **http://103.156.128.98:8080/admin**

---

## Troubleshooting

**Port 8080 already in use?**
Edit `compose.yml` and change the mapping:
```yaml
ports:
  - "8081:80" # Change 8080 to 8081
```

**Cannot connect to Database?**
Ensure the Host MySQL allows connections from Docker. Only for testing, you can modify `/etc/mysql/mariadb.conf.d/50-server.cnf` on the host:
`bind-address = 0.0.0.0`
(And grant permissions to the user).
