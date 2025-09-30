output "bucket_names" {
  description = "Map of logical bucket keys to provisioned bucket names."
  value = {
    for name, cfg in local.buckets : name => aws_s3_bucket.bucket[name].bucket
  }
}

output "kms_key_arns" {
  description = "KMS key ARNs backing each bucket."
  value = {
    for name, key in aws_kms_key.bucket : name => key.arn
  }
}

output "queue_alarm_arns" {
  description = "CloudWatch alarm ARNs monitoring queue backlogs."
  value       = { for name, alarm in aws_cloudwatch_metric_alarm.queue_backlog : name => alarm.arn }
}
