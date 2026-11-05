# 🚀 SheShield — Quick Setup Guide

Follow the steps below to get the complete SheShield DevOps stack and application running on your local machine.

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
| **SheShield App** | [http://localhost:8088/sheshield/pro/landing.html](http://localhost:8088/sheshield/pro/landing.html) | N/A | The main application interface. *(Requires Apache/XAMPP running on port 8088)* |
| **Jenkins** | [http://localhost:8080](http://localhost:8080) | `admin` / `admin` | CI/CD Pipeline Orchestrator. |
| **SonarQube** | [http://localhost:9000](http://localhost:9000) | `admin` / `admin` | Static Code Analysis & Quality Gates. |
| **Nexus** | [http://localhost:8081](http://localhost:8081) | `admin` / `admin123` | Private Docker Artifact Registry. |
| **Nexus Docker Repo** | `127.0.0.1:8082` (Via Docker CLI) | `admin` / `admin123` | Hosted Docker repository endpoint. |
| **Grafana** | [http://localhost:3000](http://localhost:3000) | `admin` / `admin` | Monitoring Dashboards (Prometheus + Loki). |
| **Prometheus** | [http://localhost:9090](http://localhost:9090) | N/A | Metrics Scraper & Time-Series DB. |
| **Loki** | [http://localhost:3100](http://localhost:3100) | N/A | Log Aggregation Engine (query via Grafana). |
| **ArgoCD** | [https://localhost:30443](https://localhost:30443) | `admin` / *(see below)* | GitOps Continuous Deployment Dashboard. |

### 4. ArgoCD Setup (Kubernetes GitOps)

ArgoCD is installed in the `argocd` Kubernetes namespace and watches the SheShield GitHub repo for changes.

```bash
# Install ArgoCD (one-time)
kubectl create namespace argocd
kubectl apply -n argocd -f https://raw.githubusercontent.com/argoproj/argo-cd/stable/manifests/install.yaml --server-side --force-conflicts

# Apply SheShield ArgoCD Application
kubectl apply -f infrastructure/kubernetes/argocd.yaml

# Get ArgoCD admin password
kubectl -n argocd get secret argocd-initial-admin-secret -o jsonpath="{.data.password}" | ForEach-Object { [System.Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($_)) }

# Port-forward ArgoCD UI (alternative to NodePort)
kubectl port-forward svc/argocd-server -n argocd 8443:443
# Then open: https://localhost:8443
```

### 5. Viewing Logs in Grafana (Loki)

1. Open Grafana → [http://localhost:3000](http://localhost:3000)
2. Go to **Explore** (compass icon in sidebar)
3. Select **Loki** from the datasource dropdown (top-left)
4. Use LogQL queries:
   - `{container="prometheus"}` — Prometheus logs
   - `{container="grafana"}` — Grafana logs
   - `{container="sonarqube"}` — SonarQube logs
   - `{container="nexus"}` — Nexus logs
   - `{service="loki"}` — Loki's own logs
   - `{container=~".+"}` — ALL container logs

---

## 🎤 Presentation Demo Commands

> Open these in separate terminal tabs before the presentation and run one by one for screenshots.

---

### 🔹 Terraform — Infrastructure as Code (IaC)

```powershell
# Navigate to Terraform directory
cd d:\Desktop\SheShield\infrastructure\terraform

# Validate configuration files
terraform validate

# List all deployed AWS resources (18 resources)
terraform state list

# Show deployment outputs (EC2 IP, RDS endpoint, S3 bucket, ECR URL)
terraform output

# Show detailed resource info
terraform show -no-color | Select-Object -First 80

# Show plan summary
terraform plan -no-color | Select-String "Plan:|to add|to change|to destroy"

# Deploy everything to AWS (~5 min)
terraform apply -auto-approve

# After presentation — destroy everything ($0 cost)
terraform destroy -auto-approve
```

---

### 🔹 Ansible — Server Configuration Automation

```powershell
# Show playbook structure (23 automated server tasks)
docker run --rm -v "d:\Desktop\SheShield\infrastructure\ansible:/ansible" -w /ansible willhallonline/ansible:latest ansible-playbook playbook.yml --list-tasks -i "localhost,"

# View the playbook contents
cat d:\Desktop\SheShield\infrastructure\ansible\playbook.yml | Select-Object -First 50

# View inventory file
cat d:\Desktop\SheShield\infrastructure\ansible\inventory.ini
```

---

### 🔹 Kubernetes — Container Orchestration

```powershell
# Cluster info
kubectl cluster-info

# All namespaces (sheshield, argocd, dev-environment, kube-system)
kubectl get namespaces

# SheShield app — all resources (3 pods + MySQL + services + HPA)
kubectl get all -n sheshield

# SheShield deployments
kubectl get deployments -n sheshield

# SheShield services
kubectl get services -n sheshield

# Horizontal Pod Autoscaler (auto-scales 2-10 pods)
kubectl get hpa -n sheshield

# Detailed deployment info
kubectl describe deployment sheshield-app -n sheshield

# Live logs from the running app
kubectl logs -n sheshield deployment/sheshield-app --tail=20

# All running pods across all namespaces
kubectl get pods --all-namespaces

# Nodes in the cluster
kubectl get nodes

# ArgoCD — GitOps CD (7 pods)
kubectl get all -n argocd

# Show K8s manifest files
cat d:\Desktop\SheShield\infrastructure\kubernetes\deployment.yaml | Select-Object -First 40
cat d:\Desktop\SheShield\infrastructure\kubernetes\argocd.yaml | Select-Object -First 30
```

---

### 🔹 Docker — Container Management

```powershell
# List all running containers (Prometheus, Grafana, Loki, SonarQube, Nexus, Promtail)
docker compose -f infrastructure/docker-compose.yml ps

# Show Docker images
docker images | Select-String "sheshield|prometheus|grafana|sonar|nexus|loki|promtail"

# Container resource usage (CPU/Memory)
docker stats --no-stream
```

---

### 🔹 Loki — Log Aggregation

```powershell
# Check Loki health
curl http://localhost:3100/ready

# Loki is viewed through Grafana Dashboards:
# 1. Open http://localhost:3000 → Login: admin/admin
# 2. Sidebar → Dashboards → "SheShield Logs (Loki)"
#    Shows: Log Volume, Error/Warning/Info counts, per-container log streams
# 3. Or Sidebar → Explore → Select "Loki" datasource → Run queries:
#    {container=~".+"}              — All container logs
#    {container="sonarqube"}        — SonarQube logs
#    {container=~".+"} |= "error"   — All error logs
```

---

### 🔹 ESLint — JavaScript Code Quality Linter

```powershell
cd d:\Desktop\SheShield

# Run ESLint on all project JavaScript files
npx eslint js/ pro/ public/js/ location/ --format stylish

# Output: ✖ 1298 problems (0 errors, 1298 warnings)
```

---

### 🔹 Commitlint — Conventional Commit Linter

```powershell
cd d:\Desktop\SheShield

# VALID commit message (passes silently ✅)
echo "feat(auth): add password reset flow" | npx commitlint

# INVALID commit message (fails with errors ❌)
echo "fixed some stuff" | npx commitlint
# Output: ✖ subject may not be empty [subject-empty]
#         ✖ type may not be empty [type-empty]
```

---

### 🔹 Husky — Git Hooks Manager

```powershell
cd d:\Desktop\SheShield

# Show commit-msg hook (runs commitlint on every git commit)
cat .husky/commit-msg

# Show pre-commit hook (runs tests before commit)
cat .husky/pre-commit
```

---

### 🔹 AWS Console — Live Infrastructure Screenshots

> ⚠️ **IMPORTANT:** Change AWS region to **Asia Pacific (Mumbai) ap-south-1** in top-right corner!

| Resource | Console URL |
|----------|------------|
| **EC2 Instance** | https://ap-south-1.console.aws.amazon.com/ec2/home?region=ap-south-1#Instances: |
| **RDS Database** | https://ap-south-1.console.aws.amazon.com/rds/home?region=ap-south-1#databases: |
| **S3 Bucket** | https://s3.console.aws.amazon.com/s3/buckets?region=ap-south-1 |
| **ECR Registry** | https://ap-south-1.console.aws.amazon.com/ecr/repositories?region=ap-south-1 |
| **VPC Network** | https://ap-south-1.console.aws.amazon.com/vpcconsole/home?region=ap-south-1#vpcs: |

---

### 🔹 Grafana & Prometheus — Dashboards

| Dashboard | URL |
|-----------|-----|
| **Grafana Home** | http://localhost:3000 (admin/admin) |
| **Infrastructure Monitor** | http://localhost:3000 → Dashboards → "SheShield - Infrastructure Monitor" |
| **Loki Logs Dashboard** | http://localhost:3000 → Dashboards → "SheShield Logs (Loki)" |
| **Prometheus Targets** | http://localhost:9090/targets |
| **Prometheus Queries** | http://localhost:9090/graph |

---

### 🔹 Other Tool Dashboards

| Tool | URL | Credentials |
|------|-----|-------------|
| **Jenkins** | http://localhost:8080 | admin / admin |
| **SonarQube** | http://localhost:9000 | admin / admin |
| **Nexus** | http://localhost:8081 | admin / admin123 |
| **ArgoCD** | https://localhost:30443 | admin / *(run kubectl command from Section 4)* |
| **SheShield App** | http://localhost:8088/sheshield/pro/landing.html | N/A |

---

## 🛑 Stopping the Infrastructure

To shut down the DevOps stack cleanly:
```bash
docker compose -f infrastructure/docker-compose.yml down
```

---

## 💡 Troubleshooting

*   **Port Conflicts:** Ensure no other applications are using ports `8080`, `8081`, `8082`, `3000`, `3100`, `9000`, or `9090`. Apache is on port `8088`.
*   **Docker Login Fails:** Remember the Nexus Docker registry password was updated to `admin123` to meet the 8-character minimum requirement.
*   **Jenkins Pipeline Fails at SonarQube:** Ensure SonarQube is fully booted up before triggering the Jenkins build. It can take a couple of minutes to start.
*   **Loki Not Ready:** Loki takes ~30 seconds to initialize. Check with: `curl http://localhost:3100/ready`
*   **ArgoCD Certificate Warning:** ArgoCD uses a self-signed certificate. Click "Advanced" → "Proceed" in your browser.
*   **ArgoCD Password:** Retrieve with: `kubectl -n argocd get secret argocd-initial-admin-secret -o jsonpath="{.data.password}"` and base64-decode it.
