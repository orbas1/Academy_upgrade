variable "environment" {
  description = "Deployment environment (e.g., staging, production)."
  type        = string
}

variable "aws_region" {
  description = "AWS region for resources."
  type        = string
  default     = "us-east-1"
}

variable "queue_alarms" {
  description = "Map of SQS queue names to backlog thresholds for alarms."
  type = map(object({
    max_visible = number
    period      = optional(number, 60)
    evaluation  = optional(number, 3)
  }))
  default = {
    "notifications" = {
      max_visible = 200
    }
    "media" = {
      max_visible = 150
    }
    "webhooks" = {
      max_visible = 120
    }
    "search-index" = {
      max_visible = 100
    }
  }
}

variable "secret_parameters" {
  description = "Map of SSM parameter names to secret values for CI/CD hydration."
  type        = map(string)
  default     = {}
  sensitive   = true
}

variable "tags" {
  description = "Default tags applied to all resources."
  type        = map(string)
  default = {
    Project     = "Academy Communities"
    ManagedBy   = "terraform"
    Environment = "unknown"
  }
}
