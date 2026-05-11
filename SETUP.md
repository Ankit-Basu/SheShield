# 🚀 SheShield — Quick Setup Guide

Follow these steps to start the entire SheShield DevOps and Application infrastructure on your local machine.

---

## 📋 Prerequisites

Ensure the following tools are running before starting:
1. **Docker Desktop**: Must be open and running.
2. **Kubernetes**: Enabled inside Docker Desktop settings.
3. **Jenkins**: Running via Windows Service or `.war` file.

---

## 🏃‍♂️ Step-by-Step Startup

### 1. Start the Unified DevOps Stack
This single command starts Prometheus, Grafana, SonarQube, and Sonatype Nexus.

Open a terminal in the project root (`d:\Desktop\SheShield`) and run:
```bash
docker compose -f infrastructure/docker-compose.yml up -d
```
*Wait ~2 minutes for all services (especially SonarQube and Nexus) to fully initialize.*

### 2. Verify Services are Running
Check the status of the containers:
```bash
docker compose -f infrastructure/docker-compose.yml ps
```
You should see all 4 services listed as `Up`.

### 3. Access the Tools (Localhost Links)

| Tool | URL | Default Credentials (User/Pass) | Purpose |
|------|-----|---------------------------------|---------|
| **SheShield App** | [http://localhost/sheshield/pro/landing.html](http://localhost/sheshield/pro/landing.html) | N/A | The main application interface. *(Requires Apache/XAMPP running on port 80)* |
| **Jenkins** | [http://localhost:8080](http://localhost:8080) | `admin` / `admin` | CI/CD Pipeline Orchestrator. |
| **SonarQube** | [http://localhost:9000](http://localhost:9000) | `admin` / `admin` | Static Code Analysis & Quality Gates. |
| **Nexus** | [http://localhost:8081](http://localhost:8081) | `admin` / `admin123` | Private Docker Artifact Registry. |
| **Nexus Docker Repo** | `127.0.0.1:8082` (Via Docker CLI) | `admin` / `admin123` | Hosted Docker repository endpoint. |
| **Grafana** | [http://localhost:3000](http://localhost:3000) | `admin` / `admin` | Monitoring Dashboards. |
| **Prometheus** | [http://localhost:9090](http://localhost:9090) | N/A | Metrics Scraper & Time-Series DB. |

---

## 🛑 Stopping the Infrastructure

To shut down the DevOps stack cleanly:
```bash
docker compose -f infrastructure/docker-compose.yml down
```

---

## 💡 Troubleshooting

*   **Port Conflicts:** Ensure no other applications (like another Apache instance, Skype, etc.) are using ports `80`, `8080`, `8081`, `8082`, `9000`, `9090`, or `3000`. If you use XAMPP, ensure its Apache is configured correctly (e.g., if you changed it to 8088 to avoid conflicts, access the app at `http://localhost:8088/sheshield/pro/landing.html`).
*   **Docker Login Fails:** Remember the Nexus Docker registry password was updated to `admin123` to meet the 8-character minimum requirement.
*   **Jenkins Pipeline Fails at SonarQube:** Ensure SonarQube is fully booted up before triggering the Jenkins build. It can take a couple of minutes to start.
