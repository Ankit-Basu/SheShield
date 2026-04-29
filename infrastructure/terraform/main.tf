# ─────────────────────────────────────────────────
# SheShield - Terraform Infrastructure on AWS
# Provisions: VPC, EC2, RDS MySQL, S3, Security Groups
# ─────────────────────────────────────────────────

terraform {
  required_version = ">= 1.0"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

# ─── Provider ─────────────────────────────────────
provider "aws" {
  region = var.aws_region
}

# ─── Variables ────────────────────────────────────
variable "aws_region" {
  description = "AWS Region"
  default     = "ap-south-1" # Mumbai
}

variable "db_password" {
  description = "RDS MySQL root password"
  type        = string
  sensitive   = true
}

variable "key_pair_name" {
  description = "EC2 SSH Key Pair name"
  type        = string
}

# ─── VPC ──────────────────────────────────────────
resource "aws_vpc" "sheshield_vpc" {
  cidr_block           = "10.0.0.0/16"
  enable_dns_support   = true
  enable_dns_hostnames = true

  tags = {
    Name    = "sheshield-vpc"
    Project = "SheShield"
  }
}

resource "aws_subnet" "public_a" {
  vpc_id                  = aws_vpc.sheshield_vpc.id
  cidr_block              = "10.0.1.0/24"
  availability_zone       = "${var.aws_region}a"
  map_public_ip_on_launch = true

  tags = { Name = "sheshield-public-a" }
}

resource "aws_subnet" "public_b" {
  vpc_id                  = aws_vpc.sheshield_vpc.id
  cidr_block              = "10.0.2.0/24"
  availability_zone       = "${var.aws_region}b"
  map_public_ip_on_launch = true

  tags = { Name = "sheshield-public-b" }
}

resource "aws_internet_gateway" "igw" {
  vpc_id = aws_vpc.sheshield_vpc.id
  tags   = { Name = "sheshield-igw" }
}

resource "aws_route_table" "public" {
  vpc_id = aws_vpc.sheshield_vpc.id

  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.igw.id
  }

  tags = { Name = "sheshield-public-rt" }
}

resource "aws_route_table_association" "public_a" {
  subnet_id      = aws_subnet.public_a.id
  route_table_id = aws_route_table.public.id
}

resource "aws_route_table_association" "public_b" {
  subnet_id      = aws_subnet.public_b.id
  route_table_id = aws_route_table.public.id
}

# ─── Security Groups ─────────────────────────────
resource "aws_security_group" "web_sg" {
  name        = "sheshield-web-sg"
  description = "Allow HTTP/HTTPS and SSH"
  vpc_id      = aws_vpc.sheshield_vpc.id

  ingress {
    description = "HTTP"
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "HTTPS"
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "SSH"
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = { Name = "sheshield-web-sg" }
}

resource "aws_security_group" "db_sg" {
  name        = "sheshield-db-sg"
  description = "Allow MySQL from web servers only"
  vpc_id      = aws_vpc.sheshield_vpc.id

  ingress {
    description     = "MySQL from web"
    from_port       = 3306
    to_port         = 3306
    protocol        = "tcp"
    security_groups = [aws_security_group.web_sg.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = { Name = "sheshield-db-sg" }
}

# ─── EC2 Instance (App Server) ───────────────────
resource "aws_instance" "app_server" {
  ami                    = "ami-0c55b159cbfafe1f0" # Amazon Linux 2
  instance_type          = "t2.micro"
  key_name               = var.key_pair_name
  subnet_id              = aws_subnet.public_a.id
  vpc_security_group_ids = [aws_security_group.web_sg.id]

  user_data = <<-EOF
    #!/bin/bash
    yum update -y
    amazon-linux-extras install docker -y
    service docker start
    usermod -a -G docker ec2-user
    curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
  EOF

  tags = {
    Name    = "sheshield-app-server"
    Project = "SheShield"
  }
}

# ─── RDS MySQL ────────────────────────────────────
resource "aws_db_subnet_group" "sheshield_db_subnet" {
  name       = "sheshield-db-subnet"
  subnet_ids = [aws_subnet.public_a.id, aws_subnet.public_b.id]

  tags = { Name = "sheshield-db-subnet-group" }
}

resource "aws_db_instance" "sheshield_db" {
  identifier             = "sheshield-db"
  allocated_storage      = 20
  engine                 = "mysql"
  engine_version         = "8.0"
  instance_class         = "db.t3.micro"
  db_name                = "sheshield"
  username               = "admin"
  password               = var.db_password
  skip_final_snapshot    = true
  publicly_accessible    = false
  vpc_security_group_ids = [aws_security_group.db_sg.id]
  db_subnet_group_name   = aws_db_subnet_group.sheshield_db_subnet.name

  tags = {
    Name    = "sheshield-rds"
    Project = "SheShield"
  }
}

# ─── S3 Bucket (Uploads) ─────────────────────────
resource "aws_s3_bucket" "uploads" {
  bucket = "sheshield-uploads-${random_string.suffix.result}"

  tags = {
    Name    = "sheshield-uploads"
    Project = "SheShield"
  }
}

resource "random_string" "suffix" {
  length  = 8
  special = false
  upper   = false
}

resource "aws_s3_bucket_public_access_block" "uploads_block" {
  bucket = aws_s3_bucket.uploads.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

# ─── Outputs ──────────────────────────────────────
output "app_server_public_ip" {
  description = "Public IP of the app server"
  value       = aws_instance.app_server.public_ip
}

output "rds_endpoint" {
  description = "RDS MySQL endpoint"
  value       = aws_db_instance.sheshield_db.endpoint
}

output "s3_bucket_name" {
  description = "S3 bucket for uploads"
  value       = aws_s3_bucket.uploads.bucket
}
