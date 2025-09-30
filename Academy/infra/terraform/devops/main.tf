provider "aws" {
  region = var.aws_region
}

locals {
  default_tags = merge(var.tags, {
    Environment = var.environment
  })

  bucket_suffix = var.environment == "production" ? "" : "-${var.environment}"

  bucket_definitions = {
    media = {
      base_name           = "academy-community-media"
      prefix              = "media/"
      transitions = [
        {
          storage_class = "STANDARD_IA"
          days          = 30
        },
        {
          storage_class = "GLACIER"
          days          = 180
        }
      ]
      expiration_days     = 730
      abort_multipart     = 7
      noncurrent_transition = {
        storage_class = "GLACIER"
        days          = 30
      }
      noncurrent_expiration_days = 365
      object_lock        = false
      retain_until_days  = null
    }
    avatars = {
      base_name           = "academy-avatars"
      prefix              = "avatars/"
      transitions = [
        {
          storage_class = "STANDARD_IA"
          days          = 60
        }
      ]
      expiration_days     = 365
      abort_multipart     = 3
      noncurrent_transition = null
      noncurrent_expiration_days = null
      object_lock        = false
      retain_until_days  = null
    }
    banners = {
      base_name           = "academy-banners"
      prefix              = "banners/"
      transitions = [
        {
          storage_class = "STANDARD_IA"
          days          = 30
        },
        {
          storage_class = "GLACIER"
          days          = 365
        }
      ]
      expiration_days     = 1095
      abort_multipart     = 7
      noncurrent_transition = null
      noncurrent_expiration_days = null
      object_lock        = false
      retain_until_days  = null
    }
    audit = {
      base_name           = "academy-audit-logs"
      prefix              = "audit/"
      transitions = [
        {
          storage_class = "GLACIER"
          days          = 90
        }
      ]
      expiration_days     = 3650
      abort_multipart     = 1
      noncurrent_transition = {
        storage_class = "GLACIER"
        days          = 30
      }
      noncurrent_expiration_days = 1825
      object_lock        = true
      retain_until_days  = 3650
    }
  }

  buckets = {
    for name, cfg in local.bucket_definitions : name => merge(cfg, {
      bucket_name = "${cfg.base_name}${local.bucket_suffix}"
    })
  }
}

resource "aws_kms_key" "bucket" {
  for_each                = local.buckets
  description             = "KMS key for ${each.value.bucket_name}"
  deletion_window_in_days = 30
  enable_key_rotation     = true
  tags                    = local.default_tags
}

resource "aws_kms_alias" "bucket" {
  for_each      = local.buckets
  name          = "alias/${each.value.bucket_name}"
  target_key_id = aws_kms_key.bucket[each.key].key_id
}

resource "aws_s3_bucket" "bucket" {
  for_each            = local.buckets
  bucket              = each.value.bucket_name
  force_destroy       = false
  object_lock_enabled = each.value.object_lock
  tags                = local.default_tags
}

resource "aws_s3_bucket_public_access_block" "bucket" {
  for_each                = local.buckets
  bucket                  = aws_s3_bucket.bucket[each.key].id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_versioning" "bucket" {
  for_each = local.buckets
  bucket   = aws_s3_bucket.bucket[each.key].id

  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "bucket" {
  for_each = local.buckets
  bucket   = aws_s3_bucket.bucket[each.key].id

  rule {
    apply_server_side_encryption_by_default {
      kms_master_key_id = aws_kms_key.bucket[each.key].arn
      sse_algorithm     = "aws:kms"
    }
  }
}

resource "aws_s3_bucket_object_lock_configuration" "bucket" {
  for_each = { for name, cfg in local.buckets : name => cfg if cfg.object_lock }
  bucket   = aws_s3_bucket.bucket[each.key].id

  rule {
    default_retention {
      mode = "COMPLIANCE"
      days = each.value.retain_until_days
    }
  }
}

resource "aws_s3_bucket_lifecycle_configuration" "bucket" {
  for_each = local.buckets
  bucket   = aws_s3_bucket.bucket[each.key].id

  dynamic "rule" {
    for_each = [each.value]
    content {
      id     = "${each.value.bucket_name}-transition"
      status = "Enabled"

      filter {
        prefix = rule.value.prefix
      }

      dynamic "transition" {
        for_each = rule.value.transitions
        content {
          days          = transition.value.days
          storage_class = transition.value.storage_class
        }
      }

      expiration {
        days = rule.value.expiration_days
      }

      abort_incomplete_multipart_upload {
        days_after_initiation = rule.value.abort_multipart
      }

      dynamic "noncurrent_version_transition" {
        for_each = rule.value.noncurrent_transition == null ? [] : [rule.value.noncurrent_transition]
        content {
          noncurrent_days = noncurrent_version_transition.value.days
          storage_class   = noncurrent_version_transition.value.storage_class
        }
      }

      dynamic "noncurrent_version_expiration" {
        for_each = rule.value.noncurrent_expiration_days == null ? [] : [rule.value.noncurrent_expiration_days]
        content {
          noncurrent_days = noncurrent_version_expiration.value
        }
      }
    }
  }
}

resource "aws_s3_bucket_policy" "tls_only" {
  for_each = local.buckets
  bucket   = aws_s3_bucket.bucket[each.key].id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid       = "DenyInsecureTransport"
        Effect    = "Deny"
        Principal = "*"
        Action    = "s3:*"
        Resource = [
          aws_s3_bucket.bucket[each.key].arn,
          "${aws_s3_bucket.bucket[each.key].arn}/*"
        ]
        Condition = {
          Bool = {
            "aws:SecureTransport" = "false"
          }
        }
      }
    ]
  })
}

resource "aws_cloudwatch_metric_alarm" "queue_backlog" {
  for_each            = var.queue_alarms
  alarm_name          = "academy-${var.environment}-${each.key}-queue-backlog"
  alarm_description   = "Alerts when queue ${each.key} backlog exceeds threshold for ${var.environment}."
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = lookup(each.value, "evaluation", 3)
  threshold           = each.value.max_visible
  metric_name         = "ApproximateNumberOfMessagesVisible"
  namespace           = "AWS/SQS"
  statistic           = "Sum"
  period              = lookup(each.value, "period", 60)
  treat_missing_data  = "notBreaching"
  dimensions = {
    QueueName = "${each.key}-${var.environment}"
  }
  tags = local.default_tags
}

resource "aws_ssm_parameter" "secrets" {
  for_each = var.secret_parameters
  name     = each.key
  type     = "SecureString"
  value    = each.value
  overwrite = true
  tags     = local.default_tags
}
