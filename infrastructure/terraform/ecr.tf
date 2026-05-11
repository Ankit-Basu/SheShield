# ─────────────────────────────────────────────────
# SheShield — AWS ECR (Elastic Container Registry)
# Private Docker registry — replaces Nexus in cloud
# Free Tier: 500MB private storage
# ─────────────────────────────────────────────────

resource "aws_ecr_repository" "sheshield" {
  name                 = var.project_name
  image_tag_mutability = "MUTABLE"
  force_delete         = true

  image_scanning_configuration {
    scan_on_push = true # Auto-scan for CVEs on every push
  }

  tags = {
    Name        = "${var.project_name}-ecr"
    Project     = "SheShield"
    Environment = var.environment
  }
}

# ─── Lifecycle Policy (keep last 10 images) ──────
resource "aws_ecr_lifecycle_policy" "cleanup" {
  repository = aws_ecr_repository.sheshield.name

  policy = jsonencode({
    rules = [
      {
        rulePriority = 1
        description  = "Keep only last 10 images"
        selection = {
          tagStatus   = "any"
          countType   = "imageCountMoreThan"
          countNumber = 10
        }
        action = {
          type = "expire"
        }
      }
    ]
  })
}

# ─── Output push commands for convenience ────────
output "ecr_push_commands" {
  description = "Commands to push Docker image to ECR"
  value       = <<-EOT
    # 1. Authenticate Docker with ECR
    aws ecr get-login-password --region ${var.aws_region} | docker login --username AWS --password-stdin ${aws_ecr_repository.sheshield.repository_url}
    
    # 2. Tag your image
    docker tag sheshield:latest ${aws_ecr_repository.sheshield.repository_url}:latest
    
    # 3. Push to ECR
    docker push ${aws_ecr_repository.sheshield.repository_url}:latest
  EOT
}
