import subprocess
import json
import os

os.environ['AWS_PAGER'] = ''

def run_aws(args):
    result = subprocess.run(['aws'] + args, capture_output=True, text=True)
    if result.returncode != 0:
        print(f"Error: {result.stderr}")
        return None
    return result.stdout

# Check CloudFront distribution status
print("=== CloudFront Distribution Status ===")
status = run_aws(['cloudfront', 'get-distribution', '--id', 'E16JXL5MNZFIYK', '--query', 'Distribution.Status', '--output', 'json'])
print(f"Status: {status}")

# Check custom error responses
print("\n=== Custom Error Responses ===")
errors = run_aws(['cloudfront', 'get-distribution', '--id', 'E16JXL5MNZFIYK', '--query', 'Distribution.DistributionConfig.CustomErrorResponses', '--output', 'json'])
print(f"Error Responses: {errors}")

# Check aliases
print("\n=== Aliases ===")
aliases = run_aws(['cloudfront', 'get-distribution', '--id', 'E16JXL5MNZFIYK', '--query', 'Distribution.DistributionConfig.Aliases', '--output', 'json'])
print(f"Aliases: {aliases}")

# Check ETag
print("\n=== ETag ===")
etag = run_aws(['cloudfront', 'get-distribution-config', '--id', 'E16JXL5MNZFIYK', '--query', 'ETag', '--output', 'json'])
print(f"ETag: {etag}")
