# 🎤 SheShield — DevOps Pipeline Presentation Guide

> **Purpose**: Step-by-step guide to deliver a confident, impressive hackathon demo of the SheShield CI/CD and DevOps infrastructure.  
> **Duration**: ~8–12 minutes (adjust based on time slot)

---

## 📋 Pre-Presentation Checklist

Before you start presenting, make sure **all services are running**:

```powershell
# 1. Open Docker Desktop (must be running first)

# 2. Start all containers
docker start nexus sonarqube
docker-compose -f d:\Desktop\SheShield\infrastructure\monitoring\docker-compose.yml up -d

# 3. Verify everything is UP
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

**Expected output — all 4 should be running:**
| Container | Port | URL |
|-----------|------|-----|
| `nexus` | 8081, 8082 | http://localhost:8081 |
| `sonarqube` | 9000 | http://localhost:9000 |
| `grafana` | 3000 | http://localhost:3000 |
| `prometheus` | 9090 | http://localhost:9090 |

**Also open these browser tabs beforehand:**
1. **GitHub** → `https://github.com/Ankit-Basu/SheShield/actions` (Actions tab)
2. **Jenkins** → `http://localhost:8080` (SheShield-Pipeline)
3. **SonarQube** → `http://localhost:9000/dashboard?id=sheshield`
4. **Nexus** → `http://localhost:8081/#browse/browse:sheshield-repo`
5. **Grafana** → `http://localhost:3000` (SheShield Infrastructure Monitor dashboard)
6. **SheShield App** → `http://localhost/sheshield/pro/landing.html`

---

## 🎬 Presentation Flow (Step by Step)

---

### STEP 1: The Hook — Show the App First (1 min)

> **What to say:**  
> *"SheShield is a women's safety platform. Before I show the DevOps pipeline, let me quickly show you what we're deploying..."*

**Action:** Show the landing page (`landing.html`) — scroll briefly through the hero, honeycomb services, and the dashboard.

> **Transition:**  
> *"Now, the question is — how do we ensure this application is built securely, tested for code quality, stored safely, deployed automatically, and monitored 24/7? That's where our DevOps pipeline comes in."*

---

### STEP 2: The Big Picture — Architecture Overview (1.5 min)

> **What to say:**  
> *"Our pipeline follows a complete DevOps lifecycle. Every single code push goes through 7 automated stages."*

**Action:** Show the README on GitHub — scroll to the **Pipeline Architecture** ASCII diagram:

```
Checkout → Build Docker → Security Scan (Trivy) → SonarQube → Push to Nexus → Deploy to K8s → Monitor (Grafana)
```

**Explain each box briefly:**
1. **Checkout** — Code pulled from GitHub
2. **Build** — Docker image built from a multi-stage Dockerfile (PHP 8.2 + Apache)
3. **Security Scan** — Aqua Trivy scans for CVEs (HIGH/CRITICAL vulnerabilities)
4. **Code Quality** — SonarQube analyzes 16,000+ lines across 9 languages
5. **Artifact Storage** — Docker image pushed to private Nexus registry
6. **Deployment** — Kubernetes deploys 3 replicas with rolling update
7. **Monitoring** — Prometheus scrapes metrics, Grafana visualizes them

> **Key phrase to impress judges:**  
> *"This is not a theoretical pipeline — every stage you see here is live and running on my machine right now. Let me prove it."*

---

### STEP 3: Live Demo — GitHub Actions (1 min)

> **What to say:**  
> *"We have two parallel CI/CD systems. First, GitHub Actions triggers on every push to the master branch."*

**Action:** Switch to the **GitHub Actions** tab. Show:
- The workflow runs list (multiple green ✅ checkmarks)
- Click on the latest run to show the steps (PHP setup, Composer, Trivy scan, Docker build)

> **Key point:**  
> *"This runs in the cloud on GitHub's infrastructure. It's our first line of defense — if something breaks here, it never reaches production."*

---

### STEP 4: Live Demo — Jenkins Pipeline (2 min) ⭐ MOST IMPORTANT

> **What to say:**  
> *"Our main pipeline runs on Jenkins with 7 stages. Let me show you a successful build."*

**Action:** Switch to **Jenkins** → SheShield-Pipeline → Show Build #11 (all green).

**Walk through each stage visually** (they're shown as green circles in the stage view):

1. ✅ **Checkout SCM** — *"Pulls the latest code from GitHub"*
2. ✅ **Build Docker Image** — *"Builds a production-ready Docker image using our multi-stage Dockerfile"*
3. ✅ **Security Scan** — *"Aqua Trivy runs inside Docker to scan for vulnerabilities — no local install needed"*
4. ✅ **SonarQube Analysis** — *"Performs static code analysis — I'll show you the results in a moment"*
5. ✅ **Push to Nexus** — *"The image gets versioned and pushed to our private Docker registry"*
6. ✅ **Deploy to Kubernetes** — *"Kubernetes performs a rolling deployment with 3 replicas"*
7. ✅ **Post Actions** — *"Workspace cleanup and build notifications"*

> **If a judge asks "Can you trigger a build live?":**
> Click **Build Now** — it will start running through all stages in real time (~3-4 min). You can show it progressing while continuing to present.

---

### STEP 5: Live Demo — SonarQube Results (1.5 min)

> **What to say:**  
> *"Let me show you what SonarQube found when it analyzed our entire codebase."*

**Action:** Switch to **SonarQube** dashboard. Point out:

| What to show | What to say |
|---|---|
| **16k Lines of Code** | *"It analyzed over 16,000 lines across PHP, JavaScript, CSS, HTML, YAML, Docker, and more"* |
| **Quality Gate: Passed ✅** | *"Our Quality Gate passed, meaning the code meets industry standards"* |
| **23 Security issues** | *"It found 23 security hotspots — things like potential injection points that we can prioritize fixing"* |
| **229 Bugs, 814 Code Smells** | *"These are reliability and maintainability issues — code smells aren't errors, they're areas for improvement"* |
| **11% Duplication** | *"11% duplication is within acceptable thresholds"* |

> **Key phrase:**  
> *"SonarQube gives us continuous code quality feedback. Every build automatically pushes new analysis results, so the team always knows the health of the codebase."*

---

### STEP 6: Live Demo — Nexus Artifact Registry (1 min)

> **What to say:**  
> *"After building and scanning, the Docker image gets pushed to our private Sonatype Nexus registry."*

**Action:** Switch to **Nexus** → Browse → sheshield-repo. Show:
- The `v2/` folder structure → `sheshield/` → `manifests/` → SHA256 hashes
- The `tags/` → `latest` tag

> **Explain:**  
> *"Every build creates a versioned Docker image — sheshield:11, sheshield:12 — plus a 'latest' tag. This gives us full rollback capability. If a deployment goes wrong, we can pull any previous version instantly from this registry."*

> **Key phrase:**  
> *"This is a real Docker V2 registry running on port 8082 — same protocol Docker Hub uses, but fully private and self-hosted."*

---

### STEP 7: Live Demo — Grafana Monitoring (1.5 min) ⭐ VISUAL WOW FACTOR

> **What to say:**  
> *"Once deployed, we need to know if the infrastructure is healthy. That's where our monitoring stack comes in."*

**Action:** Switch to **Grafana** dashboard. This is the most visual part — let it impress.

**Point out the panels:**

| Panel | What to say |
|---|---|
| **Prometheus: UP** (green) | *"Our metrics server is running and healthy"* |
| **Time Series: 871** | *"Prometheus is tracking 871 unique time series metrics"* |
| **Goroutines: 40** | *"40 concurrent goroutines processing requests"* |
| **CPU Usage graph** | *"Real-time CPU utilization — you can see the spikes from our recent builds"* |
| **Memory Usage graph** | *"Memory breakdown — RSS, virtual, and heap allocation"* |
| **HTTP Request Rate** | *"HTTP requests per second hitting our monitoring endpoints"* |

> **Key phrase:**  
> *"This entire dashboard is provisioned as code — it's a JSON file in our repo. When we spin up the monitoring stack with docker-compose, Grafana auto-loads this dashboard. No manual configuration needed. That's Infrastructure-as-Code."*

---

### STEP 7.5: Live Demo — Prometheus Queries (1.5 min) ⭐ LIVE CODING WOW

> **What to say:**  
> *"Grafana is the visualization layer, but the real power is in Prometheus — our metrics engine. Let me show you live queries."*

**Action:** Switch to **Prometheus** tab (`http://localhost:9090`). Click the **Graph** tab. Type each query in the expression box and click **Execute**. Switch between **Table** and **Graph** views for effect.

#### 🔥 Demo Query 1: "Is everything alive?"

```promql
up
```
> *"This is the simplest but most powerful query. It tells us which services Prometheus is monitoring. A value of 1 means UP, 0 means DOWN. Right now all our targets are healthy."*

**View:** Table — shows `up{instance="...", job="prometheus"} → 1`

---

#### 🔥 Demo Query 2: "How much memory is our server using?"

```promql
process_resident_memory_bytes / 1024 / 1024
```
> *"This shows resident memory in megabytes. You can see Prometheus itself is using about 65 MB — very lightweight for a metrics server monitoring 319 metrics."*

**View:** Table — shows value in MB

---

#### 🔥 Demo Query 3: "CPU usage over time" ⭐ Best for Graph view

```promql
rate(process_cpu_seconds_total[5m])
```
> *"This uses the `rate()` function — one of the most important PromQL concepts. It calculates the per-second CPU usage averaged over the last 5 minutes. Click Graph to see the trend."*

**View:** Switch to **Graph** — shows a live CPU usage line chart

---

#### 🔥 Demo Query 4: "How many HTTP requests has our monitoring received?"

```promql
prometheus_http_requests_total
```
> *"This shows every HTTP request Prometheus has served, broken down by handler and status code. You can see /metrics, /api/v1/query, /graph — these are all the endpoints being hit."*

**View:** Table — shows multiple rows with different handlers

---

#### 🔥 Demo Query 5: "Request rate per second" ⭐ Impressive

```promql
rate(prometheus_http_requests_total[5m])
```
> *"By wrapping the counter with `rate()`, we convert it to requests-per-second. This is exactly what production monitoring looks like — you watch for spikes that indicate unusual traffic."*

**View:** Switch to **Graph** — shows request rate over time

---

#### 🔥 Demo Query 6: "How many concurrent threads are running?"

```promql
go_goroutines
```
> *"Goroutines are Go's lightweight threads. Prometheus is running about 40 concurrent goroutines — each handling scraping, storage, API requests, etc. If this number spikes, it could indicate a resource leak."*

**View:** Graph — shows goroutine count over time

---

#### 🎁 Bonus Queries (if judges ask for more)

| Query | What it shows | What to say |
|-------|---------------|-------------|
| `prometheus_tsdb_head_series` | Total active time series | *"We're tracking 871+ unique time series"* |
| `process_open_fds` | Open file descriptors | *"Shows how many files/sockets the server has open"* |
| `go_gc_duration_seconds` | Garbage collection pauses | *"GC pause durations — tells us if memory management is healthy"* |
| `prometheus_tsdb_head_samples_appended_total` | Total samples ingested | *"Total data points stored — proves the system is actively collecting"* |
| `rate(prometheus_tsdb_head_samples_appended_total[5m])` | Ingestion rate | *"Samples per second being ingested — our data pipeline throughput"* |
| `process_virtual_memory_bytes / 1024 / 1024 / 1024` | Virtual memory in GB | *"Total virtual memory allocation in gigabytes"* |

> **Key phrase for judges:**  
> *"Prometheus uses its own query language called PromQL. Every metric you see on our Grafana dashboard is powered by a PromQL query behind the scenes. Grafana is just the visualization — Prometheus is the brain."*

> **Pro tip:** After showing Prometheus queries, switch back to Grafana and say:  
> *"Now you understand — every panel in this Grafana dashboard is running a PromQL query like the ones I just showed you, but visualized beautifully with auto-refresh every 5 seconds."*

---

### STEP 8: Wrap-Up — The DevOps Philosophy (1 min)

> **What to say:**  
> *"To summarize our DevOps approach:"*

Show these points (you can have a slide or just speak):

1. **Automation** — Zero manual steps from code push to production deployment
2. **Security-first** — Trivy + SonarQube catch vulnerabilities before deployment
3. **Observability** — Prometheus + Grafana provide real-time infrastructure health
4. **Reproducibility** — Everything is containerized with Docker and orchestrated with Kubernetes
5. **Infrastructure-as-Code** — Dashboards, pipelines, configs — everything is version-controlled in Git

> **Closing line:**  
> *"SheShield isn't just a web application — it's a production-grade platform with enterprise-level DevOps practices. Thank you."*

---

## 🔥 Power Phrases for Judges

Use these when judges ask questions:

| Question | Answer |
|---|---|
| *"Why not just deploy manually?"* | *"Manual deployments are error-prone and not reproducible. Our pipeline ensures every deployment goes through security scanning, code quality checks, and automated testing before reaching production."* |
| *"Why Docker?"* | *"Docker gives us consistent environments. What runs on my machine runs identically in production. No 'works on my machine' problems."* |
| *"Why Kubernetes?"* | *"Kubernetes gives us self-healing, auto-scaling, and rolling deployments. If a container crashes, K8s automatically restarts it. We run 3 replicas for high availability."* |
| *"Why SonarQube?"* | *"SonarQube catches bugs, security vulnerabilities, and code smells that manual code review might miss. It analyzed 16,000+ lines of code in 9 languages automatically."* |
| *"Why Nexus?"* | *"Nexus is our private Docker registry. It gives us version control for Docker images, rollback capability, and ensures we're not dependent on external registries."* |
| *"Why Grafana?"* | *"Grafana gives us real-time visibility into our infrastructure. If CPU spikes or memory leaks occur, we see it immediately — not after users complain."* |
| *"Why Prometheus?"* | *"Prometheus is the industry standard for metrics collection. It uses a pull-based model — scraping targets every 15 seconds — and its query language PromQL lets us slice and dice data in ways simple logging can't. It's what powers our Grafana dashboards."* |
| *"What is PromQL?"* | *"PromQL is Prometheus Query Language — a functional language for selecting and aggregating time series data. Functions like `rate()` convert raw counters into meaningful per-second rates. It's the same language used at Google, Netflix, and Uber for production monitoring."* |
| *"What about Ansible?"* | *"We chose Docker Compose and Kubernetes for our infrastructure provisioning because our stack is fully containerized. Ansible is better suited for configuring bare-metal servers, which isn't our use case."* |
| *"Is this all running locally?"* | *"Yes — the entire pipeline runs on a single machine using Docker Desktop with Kubernetes. In production, each service would be on separate nodes, but the architecture is identical."* |

---

## 🛠️ Troubleshooting During Demo

| Problem | Quick Fix |
|---------|-----------|
| Grafana not loading | `docker restart grafana` |
| SonarQube down | `docker start sonarqube` — takes ~30s to boot |
| Nexus shows empty | `docker start nexus` — takes ~2 min to boot |
| Jenkins build fails | Click **Build Now** again — first run after restart may timeout |
| Docker Desktop not running | Open Docker Desktop app — wait for "Engine running" |
| GitHub Actions not showing | Make a small code change, commit, and push to trigger |

---

## 📁 Files to Reference During Presentation

If judges want to see the actual code/configuration:

| File | What it shows |
|------|--------------|
| `infrastructure/jenkins/Jenkinsfile` | The 7-stage pipeline definition |
| `.github/workflows/main.yml` | GitHub Actions workflow |
| `infrastructure/docker/Dockerfile` | Multi-stage Docker build |
| `infrastructure/kubernetes/deployment.yaml` | K8s deployment (3 replicas + MySQL) |
| `infrastructure/monitoring/docker-compose.yml` | Prometheus + Grafana stack |
| `infrastructure/monitoring/grafana/dashboards/sheshield-overview.json` | Grafana dashboard (IaC) |
| `infrastructure/monitoring/prometheus/prometheus.yml` | Prometheus scrape config |

---

## ⏱️ Timing Breakdown

| Section | Duration | Priority |
|---------|----------|----------|
| 1. Show the App | 1 min | Medium |
| 2. Architecture Overview | 1.5 min | High |
| 3. GitHub Actions | 1 min | Medium |
| 4. Jenkins Pipeline | 2 min | **Critical** |
| 5. SonarQube Results | 1.5 min | High |
| 6. Nexus Registry | 1 min | Medium |
| 7. Grafana Monitoring | 1.5 min | **Critical** |
| 7.5. Prometheus Live Queries | 1.5 min | **Critical** |
| 8. Wrap-Up | 1 min | High |
| **Total** | **~12 min** | |

> **If you only have 5 minutes:** Skip sections 1, 3, and 6. Focus on Jenkins (4), SonarQube (5), Prometheus queries (7.5), and Grafana (7).  
> **If you only have 8 minutes:** Skip sections 1 and 3. Do everything else.

---

## 🎯 Tools Used in SheShield DevOps Stack

| Tool | Purpose | Port |
|------|---------|------|
| **Docker** | Containerization | — |
| **Docker Compose** | Multi-container orchestration | — |
| **Jenkins** | CI/CD pipeline orchestrator | 8080 |
| **GitHub Actions** | Cloud CI/CD (parallel) | — |
| **SonarQube** | Static code analysis & quality gates | 9000 |
| **Aqua Trivy** | Container vulnerability scanning | — |
| **Sonatype Nexus** | Private Docker image registry | 8081/8082 |
| **Kubernetes** | Container orchestration & deployment | — |
| **Prometheus** | Metrics collection & alerting | 9090 |
| **Grafana** | Monitoring dashboards & visualization | 3000 |

> **Note:** Ansible is **NOT** used in this project. The infrastructure is fully containerized using Docker and orchestrated with Kubernetes. Ansible is typically used for bare-metal server provisioning, which is not needed in a containerized architecture.

---

<div align="center">
  <strong>🛡️ Good luck with the presentation!</strong>
  <br>
  <sub>Remember: Confidence + Live Demo = Winning Formula</sub>
</div>
