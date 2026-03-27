@echo off
set AWS_PAGER=
aws cloudfront get-distribution --id E16JXL5MNZFIYK --query "Distribution.DistributionConfig.CustomErrorResponses" --output json
aws cloudfront get-distribution --id E16JXL5MNZFIYK --query "Distribution.DistributionConfig.Aliases" --output json
aws cloudfront get-distribution --id E16JXL5MNZFIYK --query "Distribution.Status" --output json
