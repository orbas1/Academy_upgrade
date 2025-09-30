terraform {
  required_version = ">= 1.5.0"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = ">= 5.0"
    }
  }
}

provider "aws" {
  region = var.region
}

variable "region" {
  description = "AWS region to deploy storage resources into"
  type        = string
  default     = "us-east-1"
}

variable "project" {
  description = "Project prefix for resource names"
  type        = string
  default     = "academy"
}

variable "kms_keys" {
  description = "Optional KMS keys for each bucket"
  type = object({
    community_media = optional(string)
    avatars         = optional(string)
    banners         = optional(string)
    audit_logs      = optional(string)
  })
  default = {}
}

variable "object_lock_default_mode" {
  description = "Default S3 Object Lock mode for audit logs"
  type        = string
  default     = "COMPLIANCE"
}

variable "object_lock_default_days" {
  description = "Retention period (in days) for audit log objects"
  type        = number
  default     = 3650
}

locals {
  community_media_bucket = "${var.project}-community-media"
  avatars_bucket         = "${var.project}-avatars"
  banners_bucket         = "${var.project}-banners"
  audit_logs_bucket      = "${var.project}-audit-logs"
}

resource "aws_s3_bucket" "community_media" {
  bucket = local.community_media_bucket
  tags = {
    Project = var.project
    Tier    = "media"
  }
}

resource "aws_s3_bucket_public_access_block" "community_media" {
  bucket                  = aws_s3_bucket.community_media.id
  block_public_acls       = true
  block_public_policy     = false
  ignore_public_acls      = true
  restrict_public_buckets = false
}

resource "aws_s3_bucket_versioning" "community_media" {
  bucket = aws_s3_bucket.community_media.id
  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "community_media" {
  bucket = aws_s3_bucket.community_media.id
  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm     = var.kms_keys.community_media != null ? "aws:kms" : "AES256"
      kms_master_key_id = var.kms_keys.community_media
    }
  }
}

resource "aws_s3_bucket_lifecycle_configuration" "community_media" {
  bucket = aws_s3_bucket.community_media.id

  rule {
    id     = "transition-originals"
    status = "Enabled"

    filter {
      prefix = "originals/"
    }

    transition {
      storage_class = "STANDARD_IA"
      days          = 30
    }

    transition {
      storage_class = "GLACIER"
      days          = 180
    }

    expiration {
      days = 730
    }

    noncurrent_version_transition {
      storage_class = "GLACIER"
      noncurrent_days = 30
    }

    noncurrent_version_expiration {
      noncurrent_days = 365
    }
  }

  rule {
    id     = "purge-transcodes"
    status = "Enabled"

    filter {
      prefix = "transcodes/"
    }

    expiration {
      days = 7
    }
  }
}

resource "aws_s3_bucket" "avatars" {
  bucket = local.avatars_bucket
  tags = {
    Project = var.project
    Tier    = "avatars"
  }
}

resource "aws_s3_bucket_public_access_block" "avatars" {
  bucket                  = aws_s3_bucket.avatars.id
  block_public_acls       = true
  block_public_policy     = false
  ignore_public_acls      = true
  restrict_public_buckets = false
}

resource "aws_s3_bucket_versioning" "avatars" {
  bucket = aws_s3_bucket.avatars.id
  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "avatars" {
  bucket = aws_s3_bucket.avatars.id
  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm     = var.kms_keys.avatars != null ? "aws:kms" : "AES256"
      kms_master_key_id = var.kms_keys.avatars
    }
  }
}

resource "aws_s3_bucket_lifecycle_configuration" "avatars" {
  bucket = aws_s3_bucket.avatars.id

  rule {
    id     = "transition-avatars"
    status = "Enabled"

    transition {
      storage_class = "STANDARD_IA"
      days          = 60
    }

    expiration {
      days = 365
    }

    abort_incomplete_multipart_upload {
      days_after_initiation = 3
    }
  }
}

resource "aws_s3_bucket" "banners" {
  bucket = local.banners_bucket
  tags = {
    Project = var.project
    Tier    = "banners"
  }
}

resource "aws_s3_bucket_public_access_block" "banners" {
  bucket                  = aws_s3_bucket.banners.id
  block_public_acls       = true
  block_public_policy     = false
  ignore_public_acls      = true
  restrict_public_buckets = false
}

resource "aws_s3_bucket_versioning" "banners" {
  bucket = aws_s3_bucket.banners.id
  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "banners" {
  bucket = aws_s3_bucket.banners.id
  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm     = var.kms_keys.banners != null ? "aws:kms" : "AES256"
      kms_master_key_id = var.kms_keys.banners
    }
  }
}

resource "aws_s3_bucket_lifecycle_configuration" "banners" {
  bucket = aws_s3_bucket.banners.id

  rule {
    id     = "transition-banners"
    status = "Enabled"

    transition {
      storage_class = "STANDARD_IA"
      days          = 30
    }

    transition {
      storage_class = "GLACIER"
      days          = 365
    }

    expiration {
      days = 1095
    }

    abort_incomplete_multipart_upload {
      days_after_initiation = 7
    }
  }
}

resource "aws_s3_bucket" "audit_logs" {
  bucket = local.audit_logs_bucket
  object_lock_enabled = true
  tags = {
    Project = var.project
    Tier    = "audit"
  }
}

resource "aws_s3_bucket_public_access_block" "audit_logs" {
  bucket                  = aws_s3_bucket.audit_logs.id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_versioning" "audit_logs" {
  bucket = aws_s3_bucket.audit_logs.id
  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_object_lock_configuration" "audit_logs" {
  bucket = aws_s3_bucket.audit_logs.id

  rule {
    default_retention {
      mode = var.object_lock_default_mode
      days = var.object_lock_default_days
    }
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "audit_logs" {
  bucket = aws_s3_bucket.audit_logs.id
  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm     = var.kms_keys.audit_logs != null ? "aws:kms" : "AES256"
      kms_master_key_id = var.kms_keys.audit_logs
    }
  }
}

resource "aws_s3_bucket_lifecycle_configuration" "audit_logs" {
  bucket = aws_s3_bucket.audit_logs.id

  rule {
    id     = "retain-audit-logs"
    status = "Enabled"

    transition {
      storage_class = "GLACIER"
      days          = 90
    }

    expiration {
      days = 3650
    }

    abort_incomplete_multipart_upload {
      days_after_initiation = 1
    }
  }
}

output "community_media_bucket" {
  description = "Name of the community media bucket"
  value       = aws_s3_bucket.community_media.bucket
}

output "avatars_bucket" {
  description = "Name of the avatars bucket"
  value       = aws_s3_bucket.avatars.bucket
}

output "banners_bucket" {
  description = "Name of the banners bucket"
  value       = aws_s3_bucket.banners.bucket
}

output "audit_logs_bucket" {
  description = "Name of the audit logs bucket"
  value       = aws_s3_bucket.audit_logs.bucket
}
