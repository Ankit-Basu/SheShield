# ─────────────────────────────────────────────────
# SheShield — Terraform Variables
# ─────────────────────────────────────────────────

variable "aws_region" {
  description = "AWS region to deploy resources"
  type        = string
  default     = "ap-south-1" # Mumbai — lowest latency for India
}

variable "project_name" {
  description = "Project name used for tagging and naming"
  type        = string
  default     = "sheshield"
}

variable "environment" {
  description = "Deployment environment"
  type        = string
  default     = "production"
}

variable "db_password" {
  description = "RDS MySQL root password"
  type        = string
  sensitive   = true
}

variable "key_pair_name" {
  description = "EC2 SSH Key Pair name (must exist in AWS)"
  type        = string
}

variable "instance_type" {
  description = "EC2 instance type"
  type        = string
  default     = "t2.micro" # Free Tier eligible
}

variable "db_instance_class" {
  description = "RDS instance class"
  type        = string
  default     = "db.t3.micro" # Free Tier eligible
}
