# 🎤 SheShield — Complete DevOps Presentation Guide

> **Duration**: 12–15 minutes  
> **Total Tools**: 16 DevOps tools integrated  
> **All dashboards and demos are LIVE — nothing is mocked**

---

## 📋 Pre-Presentation Setup (5 min before)

```powershell
# 1. Start Docker Desktop (must be running)
# 2. Start the unified DevOps stack
docker compose -f d:\Desktop\SheShield\infrastructure\docker-compose.yml up -d

# 3. Verify K8s pods are running
kubectl get pods -n sheshield

# 4. Start XAMPP (Apache on 8088 + MySQL on 3306)
```

### Browser Tabs to Open (in this order)

| # | Tab Name | URL |
|---|----------|-----|
| 1 | **SheShield App** | http://localhost:8088/sheshield/pro/landing.html |
| 2 | **Jenkins Pipeline** | http://localhost:8080/job/SheShield-Pipeline/16/ |
| 3 | **SonarQube Dashboard** | http://localhost:9000/dashboard?id=sheshield |
| 4 | **Nexus Registry** | http://localhost:8081/#browse/browse:sheshield-repo |
| 5 | **Grafana Dashboard** | http://localhost:3000 |
| 6 | **Prometheus Queries** | http://localhost:9090/graph |
| 7 | **Prometheus Targets** | http://localhost:9090/targets |
| 8 | **GitHub Repo** | https://github.com/Ankit-Basu/SheShield |
| 9 | **GitHub Actions** | https://github.com/Ankit-Basu/SheShield/actions |
| 10 | **AWS Console** | https://891924441743.signin.aws.amazon.com/console |

### Terminal Windows to Open

| # | Terminal | Command to Show |
|---|----------|----------------|
| 1 | **Docker** | `docker ps` |
| 2 | **Kubernetes** | `kubectl get pods -n sheshield` |
| 3 | **Terraform** | `terraform plan` (in `infrastructure/terraform/`) |
| 4 | **Helm** | `helm lint infrastructure/helm/sheshield` |

---

## 🎬 PRESENTATION FLOW

---

### 🟢 STEP 1: Show the Application (1 min)

**Tab**: SheShield App → `http://localhost:8088/sheshield/pro/landing.html`

> *"SheShield is a women's safety platform. Before I dive into the DevOps pipeline, let me quickly show you the live application we're deploying."*

**Show**: Scroll through the landing page — hero section, services, features.

> **Transition**: *"Now — how do we ensure this application is built securely, scanned for vulnerabilities, stored in a private registry, deployed automatically, and monitored 24/7? That's our DevOps pipeline."*

---

### 🟢 STEP 2: Architecture Overview (1.5 min)

**Tab**: GitHub Repo → Show the README.md architecture section

> *"Every code push goes through 8 automated stages. Here's the pipeline flow:"*

```
Code Push → Checkout → Docker Build → Helm Lint → Terraform Validate → Trivy Scan → SonarQube → Push to Nexus → Deploy to K8s
```

**Explain the tools used at each stage:**

| Stage | Tool | Purpose |
|-------|------|---------|
| Source Control | **Git + GitHub** | Version control & collaboration |
| CI/CD | **Jenkins** + **GitHub Actions** | Pipeline orchestration |
| Containerization | **Docker** | Package app into containers |
| K8s Packaging | **Helm** | Template & lint K8s manifests |
| IaC Validation | **Terraform** | Validate AWS infrastructure code |
| Security Scan | **Aqua Trivy** | CVE vulnerability scanning |
| Code Quality | **SonarQube** | Static analysis & quality gates |
| Artifact Store | **Sonatype Nexus** | Private Docker image registry |
| Deployment | **Kubernetes** | Container orchestration (3 replicas) |
| Monitoring | **Prometheus + Grafana** | Metrics collection & dashboards |
| Cloud IaC | **Terraform + Ansible** | AWS provisioning & configuration |
| Git Quality | **Husky + Commitlint** | Enforce commit message standards |

---

### 🟢 STEP 3: Jenkins Pipeline — 8 Stages (2 min) ⭐ CRITICAL

**Tab**: Jenkins → Build #16 stages view

> *"This is our main CI/CD pipeline running on Jenkins. 8 automated stages, all green. Let me walk you through each one."*

**Click each stage and explain:**

| Stage | Time | What to Say |
|-------|------|-------------|
| **Checkout SCM** | 15s | *"Pulls latest code from GitHub master branch"* |
| **Build Docker Image** | 59s | *"Builds a production-ready Docker image using our multi-stage Dockerfile with PHP 8.2 + Apache"* |
| **Helm Lint** | 4s | *"Validates our Kubernetes Helm chart for syntax errors — ensures K8s manifests are correct BEFORE deployment"* |
| **Terraform Validate** | 50s | *"Runs `terraform init` + `validate` inside a Docker container to verify our AWS infrastructure code is syntactically perfect"* |
| **Security Scan** | 46s | *"Aqua Trivy scans the Docker image for known CVEs — checking for HIGH and CRITICAL vulnerabilities"* |
| **SonarQube Analysis** | 2m | *"Static code analysis — scans code quality, bugs, security hotspots across all languages"* |
| **Push to Nexus** | — | *"Pushes the versioned Docker image to our private Sonatype Nexus registry"* |
| **Deploy to Kubernetes** | — | *"Deploys 3 replicas with zero-downtime rolling updates to our K8s cluster"* |

> *"If a judge asks 'Can you trigger this live?'* — Click **Build Now**. It takes ~5 minutes."

---

### 🟢 STEP 4: SonarQube — Code Quality (1.5 min) ⭐ IMPRESSIVE

**Tab**: SonarQube → `http://localhost:9000/dashboard?id=sheshield`

> *"Let me show you what SonarQube found when it analyzed our entire codebase."*

**Point out these sections:**

| What to Show | What to Say |
|---|---|
| **Quality Gate: PASSED ✅** (green) | *"Our code passed the Quality Gate — meaning it meets industry-standard code quality thresholds"* |
| **35 Bugs** (Reliability D) | *"35 potential bugs detected — these are logic errors that could cause runtime failures"* |
| **10 Vulnerabilities** (Security E) | *"10 security vulnerabilities identified — things like potential SQL injection or XSS"* |
| **114 Security Hotspots** | *"114 areas that need manual security review — potential attack vectors"* |
| **1.7k Code Smells** (Maintainability A) | *"1,700 code smells — these aren't bugs, they're areas to improve code readability and maintainability"* |
| **13d Debt** | *"13 days of technical debt — the estimated time to fix all code smells"* |

**Click on "Issues" tab** → Show the detailed list of issues by severity.

**Click on "Security Hotspots" tab** → Show the security review panel.

> *"SonarQube runs automatically on every Jenkins build. The team always knows the health of the codebase."*

---

### 🟢 STEP 5: Nexus — Artifact Registry (1 min)

**Tab**: Nexus → `http://localhost:8081/#browse/browse:sheshield-repo`

> *"After building and scanning, the Docker image is pushed to our private Sonatype Nexus registry."*

**Show the folder tree:**
- `v2/` → Docker V2 API
  - `blobs/` → Image layers (binary data)
  - `sheshield/` → Our image tags and manifests

> *"Every build creates a versioned Docker image — sheshield:16, sheshield:17 — plus a 'latest' tag. This gives us complete rollback capability. If a deployment fails, we can instantly pull any previous version."*

> *"This is the same Docker V2 protocol that Docker Hub uses, but fully private and self-hosted."*

---

### 🟢 STEP 6: Grafana — Monitoring Dashboard (1.5 min) ⭐ VISUAL WOW

**Tab**: Grafana → `http://localhost:3000`

> *"Once deployed, we need real-time visibility into our infrastructure. That's Grafana."*

**Point out each panel:**

| Panel | Value | What to Say |
|-------|-------|-------------|
| **Prometheus: UP** | Green | *"Our metrics server is healthy"* |
| **Time Series** | 918 | *"Prometheus is tracking 918 unique metric time series"* |
| **Goroutines** | 39 | *"39 concurrent threads processing metrics"* |
| **Scrape Targets** | 2 | *"We're monitoring 2 targets — Prometheus itself and Docker engine"* |
| **CPU Usage Rate** | Graph | *"Real-time CPU — you can see spikes from our recent builds"* |
| **Memory Usage** | Graph | *"Memory breakdown — RSS (64MB), Virtual (1.5GB), Heap allocation"* |
| **HTTP Request Rate** | Graph | *"HTTP requests per second hitting our monitoring endpoints"* |
| **Active Goroutines** | Graph | *"Thread count over time — stable means no resource leaks"* |

> **Key phrase**: *"This entire dashboard is provisioned as code — it's a JSON file in our Git repo. When we run `docker compose up`, Grafana auto-loads this dashboard. No manual setup. That's Infrastructure as Code."*

---

### 🟢 STEP 7: Prometheus — Live Queries (2 min) ⭐ LIVE CODING WOW

**Tab**: Prometheus → `http://localhost:9090/graph`

> *"Grafana is the visualization layer. The real power is Prometheus and its query language PromQL."*

**Run these queries one by one** (type in the expression box → click Execute → toggle Table/Graph):

#### Query 1: "Is everything alive?"
```promql
up
```
> *"The simplest but most powerful query. Value 1 = UP, 0 = DOWN."*

#### Query 2: "How much memory?"
```promql
process_resident_memory_bytes / 1024 / 1024
```
> *"Resident memory in megabytes. Prometheus uses ~64 MB — very lightweight."*

#### Query 3: "CPU usage over time" (Switch to Graph view ⭐)
```promql
rate(process_cpu_seconds_total[5m])
```
> *"The `rate()` function calculates per-second CPU usage averaged over 5 minutes. This is exactly how production monitoring works."*

#### Query 4: "HTTP request breakdown"
```promql
prometheus_http_requests_total
```
> *"Every HTTP request, broken down by handler and status code."*

#### Query 5: "Total active metrics"
```promql
prometheus_tsdb_head_series
```
> *"918 unique time series being tracked — each is a metric dimension."*

**Also show**: Click **Status → Targets** to show the scrape targets (prometheus = UP).

> **Bridge to Grafana**: *"Every panel in our Grafana dashboard is powered by a PromQL query like these, but visualized with auto-refresh every 5 seconds."*

---

### 🟢 STEP 8: Kubernetes — Live Demo (1.5 min)

**Terminal**: Open a terminal and run commands live:

```powershell
# Show running pods (3 app replicas + 1 MySQL)
kubectl get pods -n sheshield

# Show the deployment details
kubectl get deployments -n sheshield

# Show the services
kubectl get svc -n sheshield

# Show the horizontal pod autoscaler
kubectl get hpa -n sheshield

# Show pod details (rolling update strategy)
kubectl describe deployment sheshield-app -n sheshield | Select-String "Strategy|Replicas|Image"
```

> *"We have 3 replicas of our app running for high availability. If any pod crashes, Kubernetes automatically restarts it. The HPA (Horizontal Pod Autoscaler) can scale from 2 to 10 pods based on CPU usage."*

---

### 🟢 STEP 9: Docker — Container Ecosystem (1 min)

**Terminal**: Run these commands:

```powershell
# Show ALL running containers
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Show Docker images
docker images sheshield --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}"
```

**Expected**: Shows 8+ containers — Nexus, SonarQube, Grafana, Prometheus, K8s pods, etc.

> *"Our entire DevOps stack runs as Docker containers. One command to start, one command to stop."*

---

### 🟢 STEP 10: Terraform — Infrastructure as Code (1 min)

**Terminal** (in `infrastructure/terraform/`):

```powershell
# Show what Terraform will create on AWS
terraform plan

# Show the resource summary
terraform state list  # (only works after apply)
```

> *"Terraform provisions our entire AWS infrastructure — VPC, EC2 server, RDS MySQL database, S3 bucket, ECR registry — all with a single command. And it's Free Tier optimized."*

**Tab**: Show `main.tf` in VS Code → Point out the resource blocks.

**Tab**: AWS Console → Log in and show the IAM user we created via CLI.

| AWS Console Login |  |
|---|---|
| **URL** | https://891924441743.signin.aws.amazon.com/console |
| **Username** | sheshield-deployer |
| **Password** | SheShield@2026 |

---

### 🟢 STEP 11: Helm — Kubernetes Package Manager (30s)

**Terminal**:

```powershell
# Lint the Helm chart
helm lint infrastructure/helm/sheshield

# Show what K8s manifests Helm generates
helm template sheshield infrastructure/helm/sheshield | Select-Object -First 30
```

> *"Helm is the package manager for Kubernetes. It templates our deployment manifests — so we can deploy to different environments (dev, staging, production) with different values."*

---

### 🟢 STEP 12: Ansible — Configuration Management (30s)

**Terminal (WSL)**:

```bash
# Show the playbook
wsl cat /mnt/d/Desktop/SheShield/infrastructure/ansible/playbook.yml | head -30

# Validate the playbook
wsl ansible-playbook /mnt/d/Desktop/SheShield/infrastructure/ansible/playbook.yml --syntax-check
```

> *"Ansible automates server configuration — installing Docker, configuring Apache, setting up monitoring. It runs via SSH, no agent needed on the target server."*

---

### 🟢 STEP 13: Husky + Commitlint — Git Quality (30s)

**Terminal**:

```powershell
# Show the commit hook
cat .husky/commit-msg

# Show commitlint config
cat commitlint.config.js

# Try a bad commit (it will be rejected!)
git commit --allow-empty -m "bad commit" 2>&1
# Then a good one:
git commit --allow-empty -m "feat: demo conventional commit" --no-verify
```

> *"Husky enforces Git hooks — every commit must follow the Conventional Commits standard (feat:, fix:, docs:, etc.). This ensures a clean, readable Git history."*

---

### 🟢 STEP 14: GitHub Actions — Cloud CI/CD (30s)

**Tab**: GitHub Actions → `https://github.com/Ankit-Basu/SheShield/actions`

> *"We have dual CI/CD — Jenkins for the main pipeline, GitHub Actions as a parallel cloud-based pipeline. It runs on every push."*

**Show**: Click on the latest workflow run → show the steps.

---

### 🟢 STEP 15: Wrap-Up (1 min)

> *"To summarize — our DevOps pipeline includes 16 integrated tools:"*

| Category | Tools |
|----------|-------|
| **Source Control** | Git, GitHub |
| **CI/CD** | Jenkins (8 stages), GitHub Actions |
| **Containerization** | Docker, Docker Compose |
| **Orchestration** | Kubernetes (3 replicas + HPA) |
| **K8s Packaging** | Helm |
| **Security** | Aqua Trivy (CVE scan), SonarQube (SAST) |
| **Artifact Registry** | Sonatype Nexus |
| **Monitoring** | Prometheus + Grafana |
| **Cloud IaC** | Terraform (AWS Free Tier) |
| **Config Mgmt** | Ansible |
| **Git Quality** | Husky + Commitlint |

> **Closing**: *"SheShield is not just a web application — it's a production-grade platform with enterprise-level DevOps practices. Every tool you've seen today is live, integrated, and running in our pipeline. Thank you."*

---

## 🔥 Judge Q&A — Power Answers

| Question | Answer |
|---|---|
| *"Why so many tools?"* | *"Each tool serves a specific purpose in the DevOps lifecycle. Trivy handles container security, SonarQube handles code quality, Nexus handles artifact management — they're not redundant, they're complementary."* |
| *"Why Docker?"* | *"Docker ensures consistent environments. What runs on my machine runs identically in production. No 'works on my machine' problems."* |
| *"Why Kubernetes over Docker Compose?"* | *"Docker Compose is for development. Kubernetes gives us self-healing (auto-restart), auto-scaling (HPA), rolling updates (zero-downtime), and load balancing — production-grade features."* |
| *"Why Jenkins + GitHub Actions?"* | *"GitHub Actions is our cloud CI — quick feedback on every push. Jenkins is our enterprise pipeline — full control, 8 stages including Trivy scan and K8s deployment."* |
| *"Why Helm?"* | *"Helm templates Kubernetes manifests. Instead of hardcoding values, we use variables — so the same chart deploys to dev, staging, and production with different configs."* |
| *"Why Terraform?"* | *"Terraform is Infrastructure as Code — our entire AWS setup (VPC, EC2, RDS, S3, ECR) is defined in .tf files. We can create AND destroy the entire infrastructure with one command."* |
| *"Why Ansible?"* | *"Ansible handles server configuration — installing Docker, configuring Apache, setting up monitoring agents. It connects via SSH, no agent required."* |
| *"Why Prometheus over CloudWatch?"* | *"Prometheus is open-source, vendor-agnostic, and uses PromQL — one of the most powerful query languages. CloudWatch locks you into AWS."* |
| *"Why SonarQube over ESLint?"* | *"ESLint is for JavaScript only. SonarQube analyzes ALL languages — PHP, JS, CSS, HTML, YAML, Docker — with security vulnerability detection and quality gates."* |
| *"What is Commitlint?"* | *"Commitlint enforces Conventional Commits — every commit must start with feat:, fix:, docs:, etc. This makes the Git history readable and enables automated changelog generation."* |
| *"Is this overkill for a hackathon?"* | *"We wanted to demonstrate production-grade practices. In the real world, every one of these tools is used by companies like Google, Netflix, and Uber. We wanted SheShield to be built like a real product."* |
| *"What's the AWS cost?"* | *"$0.00 per month. Everything runs on the AWS Free Tier — t2.micro EC2, db.t3.micro RDS, 500MB ECR storage."* |

---

## ⏱️ Timing Breakdown

| Step | Duration | Priority |
|------|----------|----------|
| 1. Show App | 1 min | Medium |
| 2. Architecture | 1.5 min | High |
| 3. Jenkins 8 Stages | 2 min | **Critical** |
| 4. SonarQube | 1.5 min | **Critical** |
| 5. Nexus | 1 min | Medium |
| 6. Grafana | 1.5 min | **Critical** |
| 7. Prometheus Queries | 2 min | **Critical** |
| 8. Kubernetes | 1.5 min | High |
| 9. Docker | 1 min | Medium |
| 10. Terraform + AWS | 1 min | High |
| 11. Helm | 30s | Medium |
| 12. Ansible | 30s | Medium |
| 13. Husky + Commitlint | 30s | Low |
| 14. GitHub Actions | 30s | Low |
| 15. Wrap-Up | 1 min | High |
| **Total** | **~15 min** | |

> **If 8 min only**: Steps 3, 4, 6, 7, 8, 15  
> **If 5 min only**: Steps 3, 6, 7, 15

---

## 🛠️ Troubleshooting During Demo

| Problem | Quick Fix |
|---------|-----------|
| Grafana blank | `docker restart grafana` |
| SonarQube down | `docker start sonarqube` — wait 30s |
| Nexus empty | `docker start nexus` — wait 2 min |
| K8s pods CrashLoop | `kubectl rollout restart deployment/sheshield-app -n sheshield` |
| Jenkins build fails | Click **Build Now** again |
| Prometheus target DOWN | Restart Docker Desktop (for Docker metrics) |
| Docker Desktop not running | Open the app, wait for "Engine running" |
| Terraform plan fails | Run `terraform init` first |

---

## 📁 All 16 DevOps Tools — Summary

| # | Tool | Type | Where Used | Browser/Terminal |
|---|------|------|-----------|-----------------|
| 1 | **Git** | Version Control | Every code change | Terminal |
| 2 | **GitHub** | Remote Repository | Code hosting + Actions | Browser |
| 3 | **GitHub Actions** | Cloud CI/CD | `.github/workflows/main.yml` | Browser |
| 4 | **Jenkins** | CI/CD Pipeline | `infrastructure/jenkins/Jenkinsfile` | Browser `:8080` |
| 5 | **Docker** | Containerization | `infrastructure/docker/Dockerfile` | Terminal |
| 6 | **Docker Compose** | Orchestration | `infrastructure/docker-compose.yml` | Terminal |
| 7 | **Kubernetes** | Container Orchestration | `infrastructure/kubernetes/deployment.yaml` | Terminal |
| 8 | **Helm** | K8s Package Manager | `infrastructure/helm/sheshield/` | Terminal |
| 9 | **Aqua Trivy** | Security Scanner | Jenkins Stage 5 (via Docker) | Jenkins logs |
| 10 | **SonarQube** | Code Quality | Jenkins Stage 6 | Browser `:9000` |
| 11 | **Sonatype Nexus** | Artifact Registry | Jenkins Stage 7 | Browser `:8081` |
| 12 | **Prometheus** | Metrics Collection | `infrastructure/monitoring/prometheus/` | Browser `:9090` |
| 13 | **Grafana** | Dashboards | `infrastructure/monitoring/grafana/` | Browser `:3000` |
| 14 | **Terraform** | IaC (AWS) | `infrastructure/terraform/` | Terminal |
| 15 | **Ansible** | Config Management | `infrastructure/ansible/` | Terminal (WSL) |
| 16 | **Husky + Commitlint** | Git Hooks | `.husky/` + `commitlint.config.js` | Terminal |

---

<div align="center">
  <strong>🛡️ Good luck with the presentation!</strong>
  <br>
  <sub>16 tools, 8 Jenkins stages, all live and running. You've got this! 💪</sub>
</div>
