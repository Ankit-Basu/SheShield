# 🚀 SheShield — AWS Deployment Guide

> **Purpose**: Step-by-step guide to deploy SheShield on AWS using Terraform + Ansible.  
> **Cost**: $0.00/month (AWS Free Tier, first 12 months)  
> **Time**: ~15 minutes to deploy

---

## 📋 Prerequisites Checklist

Before deploying, ensure the following are installed:

| Tool | Check Command | Install |
|------|--------------|---------|
| **AWS CLI** | `aws --version` | [Install Guide](https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html) |
| **Terraform** | `terraform --version` | [Install Guide](https://developer.hashicorp.com/terraform/install) |
| **Ansible** | `wsl ansible --version` | Already installed ✅ |
| **Docker** | `docker --version` | Already installed ✅ |

---

## 🔐 Step 1: Configure AWS Credentials

### 1.1 Create an AWS Account (if needed)
- Go to [aws.amazon.com](https://aws.amazon.com)
- Sign up with email + credit card (won't be charged for Free Tier)

### 1.2 Create IAM User
1. Go to **IAM Console** → **Users** → **Create User**
2. User name: `sheshield-deployer`
3. Attach policies:
   - `AmazonEC2FullAccess`
   - `AmazonRDSFullAccess`
   - `AmazonS3FullAccess`
   - `AmazonEC2ContainerRegistryFullAccess`
   - `AmazonVPCFullAccess`
4. Create **Access Key** → Download the CSV

### 1.3 Configure AWS CLI
```powershell
aws configure
# AWS Access Key ID:     YOUR_ACCESS_KEY
# AWS Secret Access Key: YOUR_SECRET_KEY
# Default region name:   ap-south-1
# Default output format: json
```

### 1.4 Verify
```powershell
aws sts get-caller-identity
# Should show your account ID and IAM user
```

---

## 🔑 Step 2: Create EC2 Key Pair

```powershell
# Create key pair for SSH access
aws ec2 create-key-pair --key-name sheshield-key --query 'KeyMaterial' --output text > sheshield-key.pem

# Set permissions (WSL)
wsl chmod 400 /mnt/d/Desktop/SheShield/sheshield-key.pem
```

---

## 🏗️ Step 3: Deploy with Terraform

```powershell
cd d:\Desktop\SheShield\infrastructure\terraform

# Copy and fill in variables
copy terraform.tfvars.example terraform.tfvars
# Edit terraform.tfvars — set your db_password and key_pair_name

# Initialize Terraform (downloads AWS provider)
terraform init

# Preview what will be created (FREE — no resources created)
terraform plan

# Deploy to AWS (creates real resources)
terraform apply -auto-approve
```

### Expected Output
```
Apply complete! Resources: 12 added, 0 changed, 0 destroyed.

Outputs:
  app_server_public_ip = "13.233.xx.xx"
  app_url              = "http://13.233.xx.xx"
  ecr_repository_url   = "123456789.dkr.ecr.ap-south-1.amazonaws.com/sheshield"
  rds_endpoint         = "sheshield-db.abc123.ap-south-1.rds.amazonaws.com:3306"
  s3_bucket_name       = "sheshield-uploads-a1b2c3d4"
  ssh_command           = "ssh -i sheshield-key.pem ec2-user@13.233.xx.xx"
```

---

## 🔧 Step 4: Configure Server with Ansible

```powershell
# Update the inventory with EC2 IP (from terraform output)
# Edit infrastructure/ansible/inventory.ini — replace YOUR_EC2_IP

# Run the playbook via WSL
wsl ansible-playbook -i /mnt/d/Desktop/SheShield/infrastructure/ansible/inventory.ini /mnt/d/Desktop/SheShield/infrastructure/ansible/playbook.yml
```

---

## 🐳 Step 5: Push Docker Image to ECR

```powershell
# Get ECR URL from Terraform output
$ECR_URL = terraform -chdir=infrastructure/terraform output -raw ecr_repository_url

# Login to ECR
aws ecr get-login-password --region ap-south-1 | docker login --username AWS --password-stdin $ECR_URL

# Tag and push
docker tag sheshield:latest ${ECR_URL}:latest
docker push ${ECR_URL}:latest
```

---

## ✅ Step 6: Verify Deployment

```powershell
# Get the public IP
$IP = terraform -chdir=infrastructure/terraform output -raw app_server_public_ip

# Test the application
curl http://$IP

# SSH into the server
ssh -i sheshield-key.pem ec2-user@$IP
```

---

## 💰 Cost Breakdown (Free Tier)

| Resource | Type | Free Tier Limit | Your Usage | Cost |
|----------|------|-----------------|------------|------|
| EC2 | t2.micro | 750 hrs/mo | ~730 hrs | **$0.00** |
| RDS MySQL | db.t3.micro | 750 hrs/mo | ~730 hrs | **$0.00** |
| S3 | Standard | 5 GB + 20K GET | < 1 GB | **$0.00** |
| ECR | Private | 500 MB storage | ~200 MB | **$0.00** |
| Data Transfer | Outbound | 100 GB/mo | < 1 GB | **$0.00** |
| **Total** | | | | **$0.00/mo** |

> ⚠️ **After 12 months**, Free Tier expires. Same setup costs ~$15-18/month.

---

## 🧹 Step 7: Destroy Everything (Avoid Charges)

```powershell
# IMPORTANT: Run this after your presentation to avoid any charges
cd d:\Desktop\SheShield\infrastructure\terraform
terraform destroy -auto-approve
```

This will delete ALL AWS resources (EC2, RDS, S3, ECR, VPC) — no lingering charges.

---

## 🎤 Presentation Day Quick Commands

```powershell
# ─── Deploy (run 15 min before presentation) ─────
cd d:\Desktop\SheShield\infrastructure\terraform
terraform apply -auto-approve

# ─── Show to judges ──────────────────────────────
terraform output                    # Show all outputs
terraform state list               # Show managed resources
aws ec2 describe-instances --filters "Name=tag:Project,Values=SheShield" --query "Reservations[].Instances[].{ID:InstanceId,IP:PublicIpAddress,State:State.Name}" --output table

# ─── After presentation ──────────────────────────
terraform destroy -auto-approve     # Delete everything
```

---

## ❓ Troubleshooting

| Problem | Fix |
|---------|-----|
| `terraform init` fails | Check internet connection, run `terraform init -upgrade` |
| `terraform apply` — insufficient permissions | Verify IAM user has the 5 policies listed above |
| SSH connection refused | Wait 2-3 min after `terraform apply` for EC2 user_data to finish |
| RDS connection timeout | RDS is private, only accessible from EC2 (by design for security) |
| ECR push fails | Re-run `aws ecr get-login-password` (token expires every 12 hours) |
| Free Tier warning | Check [AWS Billing Dashboard](https://console.aws.amazon.com/billing/) |
