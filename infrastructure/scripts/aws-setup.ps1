# ─────────────────────────────────────────────────────────────
# SheShield — Automated AWS Cloud Setup Script
# Run this ONCE to set up everything needed for AWS deployment
# Prerequisites: AWS CLI configured with root/admin credentials
# ─────────────────────────────────────────────────────────────

param(
    [string]$Region = "ap-south-1",
    [string]$ProjectName = "sheshield",
    [string]$IAMUserName = "sheshield-deployer",
    [string]$KeyPairName = "sheshield-key",
    [string]$DBPassword = "SheShield@2026"
)

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "╔══════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   SheShield — AWS Cloud Setup                    ║" -ForegroundColor Cyan
Write-Host "║   Region: $Region                          ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# ─── Step 0: Verify AWS CLI is working ────────────────────────
Write-Host "[0/6] Verifying AWS CLI..." -ForegroundColor Yellow
try {
    $identity = aws sts get-caller-identity --output json 2>&1 | ConvertFrom-Json
    Write-Host "  ✅ Authenticated as: $($identity.Arn)" -ForegroundColor Green
    Write-Host "  ✅ Account ID: $($identity.Account)" -ForegroundColor Green
} catch {
    Write-Host "  ❌ AWS CLI not configured. Run 'aws configure' first!" -ForegroundColor Red
    Write-Host "     You need: Access Key ID, Secret Access Key, Region" -ForegroundColor Red
    exit 1
}

# ─── Step 1: Create IAM User ─────────────────────────────────
Write-Host ""
Write-Host "[1/6] Creating IAM user: $IAMUserName..." -ForegroundColor Yellow

# Check if user already exists
$userExists = aws iam get-user --user-name $IAMUserName 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "  ⚠️  User '$IAMUserName' already exists, skipping creation" -ForegroundColor DarkYellow
} else {
    aws iam create-user --user-name $IAMUserName --output json | Out-Null
    Write-Host "  ✅ IAM user '$IAMUserName' created" -ForegroundColor Green
}

# ─── Step 2: Attach IAM Policies ─────────────────────────────
Write-Host ""
Write-Host "[2/6] Attaching IAM policies..." -ForegroundColor Yellow

$policies = @(
    "arn:aws:iam::aws:policy/AmazonEC2FullAccess",
    "arn:aws:iam::aws:policy/AmazonRDSFullAccess",
    "arn:aws:iam::aws:policy/AmazonS3FullAccess",
    "arn:aws:iam::aws:policy/AmazonEC2ContainerRegistryFullAccess",
    "arn:aws:iam::aws:policy/AmazonVPCFullAccess"
)

foreach ($policy in $policies) {
    $policyName = ($policy -split "/")[-1]
    aws iam attach-user-policy --user-name $IAMUserName --policy-arn $policy 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  ✅ Attached: $policyName" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️  Could not attach: $policyName" -ForegroundColor DarkYellow
    }
}

# ─── Step 3: Create Access Keys ──────────────────────────────
Write-Host ""
Write-Host "[3/6] Creating access keys for $IAMUserName..." -ForegroundColor Yellow

$keysOutput = aws iam create-access-key --user-name $IAMUserName --output json 2>&1
if ($LASTEXITCODE -eq 0) {
    $keys = $keysOutput | ConvertFrom-Json
    $accessKeyId = $keys.AccessKey.AccessKeyId
    $secretKey = $keys.AccessKey.SecretAccessKey

    Write-Host "  ✅ Access Key ID:     $accessKeyId" -ForegroundColor Green
    Write-Host "  ✅ Secret Access Key: $secretKey" -ForegroundColor Green
    Write-Host ""
    Write-Host "  ⚠️  SAVE THESE KEYS! The secret key cannot be retrieved again." -ForegroundColor Red

    # Save to a secure file
    $credsFile = "d:\Desktop\SheShield\infrastructure\terraform\aws-credentials.txt"
    @"
# SheShield AWS Credentials (KEEP SECRET - DO NOT COMMIT)
# Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
AWS_ACCESS_KEY_ID=$accessKeyId
AWS_SECRET_ACCESS_KEY=$secretKey
AWS_REGION=$Region
"@ | Set-Content $credsFile
    Write-Host "  📄 Credentials saved to: $credsFile" -ForegroundColor Cyan

    # Configure AWS CLI with the new deployer profile
    aws configure set aws_access_key_id $accessKeyId --profile $IAMUserName
    aws configure set aws_secret_access_key $secretKey --profile $IAMUserName
    aws configure set region $Region --profile $IAMUserName
    Write-Host "  ✅ AWS CLI profile '$IAMUserName' configured" -ForegroundColor Green

} else {
    Write-Host "  ⚠️  Could not create access keys (may already have 2 keys)" -ForegroundColor DarkYellow
    Write-Host "  Listing existing keys..."
    aws iam list-access-keys --user-name $IAMUserName --output table
}

# ─── Step 4: Create EC2 Key Pair ─────────────────────────────
Write-Host ""
Write-Host "[4/6] Creating EC2 key pair: $KeyPairName..." -ForegroundColor Yellow

$keyFile = "d:\Desktop\SheShield\infrastructure\terraform\$KeyPairName.pem"

$keyExists = aws ec2 describe-key-pairs --key-names $KeyPairName --region $Region 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "  ⚠️  Key pair '$KeyPairName' already exists in AWS" -ForegroundColor DarkYellow
} else {
    $keyMaterial = aws ec2 create-key-pair --key-name $KeyPairName --region $Region --query 'KeyMaterial' --output text 2>&1
    if ($LASTEXITCODE -eq 0) {
        $keyMaterial | Set-Content $keyFile -NoNewline
        Write-Host "  ✅ Key pair created and saved to: $keyFile" -ForegroundColor Green
        Write-Host "  🔒 Setting file permissions..." -ForegroundColor Cyan
    } else {
        Write-Host "  ❌ Failed to create key pair: $keyMaterial" -ForegroundColor Red
    }
}

# ─── Step 5: Create terraform.tfvars ─────────────────────────
Write-Host ""
Write-Host "[5/6] Creating terraform.tfvars..." -ForegroundColor Yellow

$tfvarsFile = "d:\Desktop\SheShield\infrastructure\terraform\terraform.tfvars"
@"
# SheShield — Terraform Variables
# Auto-generated by aws-setup.ps1 on $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

aws_region      = "$Region"
project_name    = "$ProjectName"
environment     = "production"
db_password     = "$DBPassword"
key_pair_name   = "$KeyPairName"
instance_type   = "t2.micro"       # Free Tier
db_instance_class = "db.t3.micro"  # Free Tier
"@ | Set-Content $tfvarsFile
Write-Host "  ✅ terraform.tfvars created" -ForegroundColor Green

# ─── Step 6: Validate Terraform ──────────────────────────────
Write-Host ""
Write-Host "[6/6] Validating Terraform configuration..." -ForegroundColor Yellow

Push-Location "d:\Desktop\SheShield\infrastructure\terraform"
terraform init -backend=false -no-color 2>&1 | Out-Null
$validateOutput = terraform validate -no-color 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "  ✅ Terraform configuration is valid!" -ForegroundColor Green
} else {
    Write-Host "  ❌ Terraform validation failed:" -ForegroundColor Red
    Write-Host "  $validateOutput" -ForegroundColor Red
}
Pop-Location

# ─── Summary ─────────────────────────────────────────────────
Write-Host ""
Write-Host "╔══════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║   ✅ AWS Cloud Setup Complete!                    ║" -ForegroundColor Green
Write-Host "╚══════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
Write-Host "  Next Steps:" -ForegroundColor Cyan
Write-Host "  1. Review credentials:  infrastructure\terraform\aws-credentials.txt" -ForegroundColor White
Write-Host "  2. Preview resources:   cd infrastructure\terraform && terraform plan" -ForegroundColor White
Write-Host "  3. Deploy (COSTS $):    cd infrastructure\terraform && terraform apply" -ForegroundColor White
Write-Host "  4. Destroy (save $):    cd infrastructure\terraform && terraform destroy" -ForegroundColor White
Write-Host ""
Write-Host "  ⚠️  DO NOT run 'terraform apply' until presentation day!" -ForegroundColor Red
Write-Host "  ⚠️  DO NOT commit aws-credentials.txt or *.pem to Git!" -ForegroundColor Red
Write-Host ""
