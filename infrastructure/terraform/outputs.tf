# ─────────────────────────────────────────────────
# SheShield — Terraform Outputs
# ─────────────────────────────────────────────────

output "app_server_public_ip" {
  description = "Public IP of the EC2 app server"
  value       = aws_instance.app_server.public_ip
}

output "app_server_public_dns" {
  description = "Public DNS of the EC2 app server"
  value       = aws_instance.app_server.public_dns
}

output "rds_endpoint" {
  description = "RDS MySQL connection endpoint"
  value       = aws_db_instance.sheshield_db.endpoint
}

output "s3_bucket_name" {
  description = "S3 bucket for evidence/photo uploads"
  value       = aws_s3_bucket.uploads.bucket
}

output "ecr_repository_url" {
  description = "ECR Docker registry URL"
  value       = aws_ecr_repository.sheshield.repository_url
}

output "ssh_command" {
  description = "SSH command to connect to the app server"
  value       = "ssh -i ${var.key_pair_name}.pem ec2-user@${aws_instance.app_server.public_ip}"
}

output "app_url" {
  description = "Application URL"
  value       = "http://${aws_instance.app_server.public_ip}"
}
