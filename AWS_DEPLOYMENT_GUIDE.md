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
| **Terraform** | `terraform --version` | Already installed ✅ |
| **Ansible** | `wsl ansible --version` | Already installed ✅ |
| **Docker** | `docker --version` | Already installed ✅ |

---

## 🔐 Step 1: Configure AWS Root Credentials

### 1.1 Create an AWS Account (if needed)
- Go to [aws.amazon.com](https://aws.amazon.com)
- Sign up with email + credit card (won't be charged for Free Tier)
- After sign-up, go to **IAM Console** → **Security Credentials** → **Create Access Key**

### 1.2 Configure AWS CLI with Root/Admin Credentials
```powershell
aws configure
# AWS Access Key ID:     YOUR_ROOT_ACCESS_KEY
# AWS Secret Access Key: YOUR_ROOT_SECRET_KEY
# Default region name:   ap-south-1
# Default output format: json
```

### 1.3 Verify
```powershell
aws sts get-caller-identity
# Should show your account ID
```

---

## 🤖 Step 2: Automated Setup (Recommended)

We have an automated script that does **everything** via CLI — no manual console work:

```powershell
# Run the automated setup script
powershell -ExecutionPolicy Bypass -File infrastructure\scripts\aws-setup.ps1
```

**This script will automatically:**
1. ✅ Create IAM user `sheshield-deployer` with limited permissions
2. ✅ Attach 5 IAM policies (EC2, RDS, S3, ECR, VPC)
3. ✅ Generate access keys and save to `aws-credentials.txt`
4. ✅ Create EC2 key pair `sheshield-key` and save `.pem` file
5. ✅ Generate `terraform.tfvars` with all variables pre-filled
6. ✅ Validate Terraform configuration

> After running the script, skip to **Step 5** (Deploy with Terraform).

---

## 🔧 Step 3: Manual IAM Setup (Alternative)

If you prefer to do it manually via CLI instead of the script:

### 3.1 Create IAM User
```powershell
aws iam create-user --user-name sheshield-deployer
```

### 3.2 Attach Required Policies
```powershell
aws iam attach-user-policy --user-name sheshield-deployer --policy-arn arn:aws:iam::aws:policy/AmazonEC2FullAccess
aws iam attach-user-policy --user-name sheshield-deployer --policy-arn arn:aws:iam::aws:policy/AmazonRDSFullAccess
aws iam attach-user-policy --user-name sheshield-deployer --policy-arn arn:aws:iam::aws:policy/AmazonS3FullAccess
aws iam attach-user-policy --user-name sheshield-deployer --policy-arn arn:aws:iam::aws:policy/AmazonEC2ContainerRegistryFullAccess
aws iam attach-user-policy --user-name sheshield-deployer --policy-arn arn:aws:iam::aws:policy/AmazonVPCFullAccess
```

### 3.3 Generate Access Keys
```powershell
aws iam create-access-key --user-name sheshield-deployer
# Save the AccessKeyId and SecretAccessKey from the output!
```

### 3.4 Configure a Named Profile
```powershell
aws configure --profile sheshield-deployer
# Enter the access key, secret key, region: ap-south-1, output: json
```

---

## 🔑 Step 4: Create EC2 Key Pair

```powershell
aws ec2 create-key-pair --key-name sheshield-key --region ap-south-1 --query 'KeyMaterial' --output text > infrastructure\terraform\sheshield-key.pem
```

---

## 🏗️ Step 5: Deploy with Terraform

```powershell
cd d:\Desktop\SheShield\infrastructure\terraform

# If you ran the automated script, terraform.tfvars is already created.
# Otherwise, copy the example and edit:
# copy terraform.tfvars.example terraform.tfvars

# Initialize Terraform (downloads AWS provider)
terraform init

# Preview what will be created (FREE — no resources provisioned)
terraform plan

# Deploy to AWS (creates real resources — COSTS START HERE)
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
  ssh_command          = "ssh -i sheshield-key.pem ec2-user@13.233.xx.xx"
```

---

## 🔧 Step 6: Configure Server with Ansible

```powershell
# Get the EC2 public IP from terraform output
terraform output app_server_public_ip

# Update the Ansible inventory with the EC2 IP
# Edit infrastructure/ansible/inventory.ini — replace YOUR_EC2_IP

# Run the playbook via WSL
wsl ansible-playbook -i /mnt/d/Desktop/SheShield/infrastructure/ansible/inventory.ini /mnt/d/Desktop/SheShield/infrastructure/ansible/playbook.yml
```

---

## 🐳 Step 7: Push Docker Image to ECR

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

## ✅ Step 8: Verify Deployment

```powershell
# Get the public IP
$IP = terraform -chdir=infrastructure/terraform output -raw app_server_public_ip

# Test the application
curl http://$IP

# SSH into the server
ssh -i infrastructure\terraform\sheshield-key.pem ec2-user@$IP
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

## 🧹 Step 9: Destroy Everything (Avoid Charges)

```powershell
# IMPORTANT: Run this after your presentation to avoid any charges
cd d:\Desktop\SheShield\infrastructure\terraform
terraform destroy -auto-approve

# Also delete the IAM user (optional cleanup)
aws iam delete-access-key --user-name sheshield-deployer --access-key-id YOUR_ACCESS_KEY_ID
aws iam detach-user-policy --user-name sheshield-deployer --policy-arn arn:aws:iam::aws:policy/AmazonEC2FullAccess
aws iam detach-user-policy --user-name sheshield-deployer --policy-arn arn:aws:iam::aws:policy/AmazonRDSFullAccess
aws iam detach-user-policy --user-name sheshield-deployer --policy-arn arn:aws:iam::aws:policy/AmazonS3FullAccess
aws iam detach-user-policy --user-name sheshield-deployer --policy-arn arn:aws:iam::aws:policy/AmazonEC2ContainerRegistryFullAccess
aws iam detach-user-policy --user-name sheshield-deployer --policy-arn arn:aws:iam::aws:policy/AmazonVPCFullAccess
aws iam delete-user --user-name sheshield-deployer
aws ec2 delete-key-pair --key-name sheshield-key --region ap-south-1
```

This will delete ALL AWS resources — no lingering charges.

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

## 📁 Files Reference

| File | Purpose |
|------|---------|
| `infrastructure/terraform/main.tf` | VPC, EC2, RDS, S3, Security Groups |
| `infrastructure/terraform/ecr.tf` | ECR container registry + lifecycle |
| `infrastructure/terraform/variables.tf` | Variable definitions |
| `infrastructure/terraform/outputs.tf` | Output values (IPs, URLs) |
| `infrastructure/terraform/terraform.tfvars` | Your actual values (gitignored) |
| `infrastructure/scripts/aws-setup.ps1` | Automated setup script |
| `infrastructure/ansible/playbook.yml` | Server configuration playbook |

---

## ❓ Troubleshooting

| Problem | Fix |
|---------|-----|
| `aws: command not found` | Install AWS CLI, restart terminal |
| `terraform init` fails | Check internet, run `terraform init -upgrade` |
| `terraform apply` — insufficient permissions | Verify IAM user has the 5 policies |
| SSH connection refused | Wait 2-3 min for EC2 user_data to finish |
| RDS connection timeout | RDS is private, only accessible from EC2 |
| ECR push fails | Re-run `aws ecr get-login-password` (token expires every 12 hours) |
| Free Tier warning | Check [AWS Billing Dashboard](https://console.aws.amazon.com/billing/) |
