# AWS Migration Report: Frontend/API Separation

**Date:** 2026-03-20
**Status:** COMPLETED

---

## Summary

Successfully separated the Laravel backend and Vue.js frontend into independent deployments:
- **app.agenchq.com** → CloudFront → S3 (static frontend)
- **api.agenchq.com** → ALB → ECS (Laravel API)

---

## What Changed

### 1. New AWS Resources Created

| Resource | Name/ID | Purpose |
|----------|---------|---------|
| S3 Bucket | `agenchq-frontend` | Static frontend hosting |
| CloudFront Distribution | `E16JXL5MNZFIYK` | CDN for app.agenchq.com |
| Route53 Record | `app.agenchq.com` | Points to CloudFront |
| Route53 Record | `api.agenchq.com` | Points to ALB |

### 2. Existing Resources Used

| Resource | Value |
|----------|-------|
| ACM Certificate | `*.agenchq.com` (covers both subdomains) |
| ALB | `agencyhq-alb-326671770.us-east-1.elb.amazonaws.com` |
| ECS Cluster | `agencyhq-cluster` |
| ECS Service | `agencyhq-service` |

---

## CLI Commands Run

### Inspection
```bash
aws cloudfront list-distributions --region us-east-1 --no-cli-pager
aws elbv2 describe-load-balancers --region us-east-1 --no-cli-pager
aws elbv2 describe-listeners --load-balancer-arn <arn> --region us-east-1 --no-cli-pager
aws elbv2 describe-target-groups --region us-east-1 --no-cli-pager
aws route53 list-hosted-zones --region us-east-1 --no-cli-pager
aws route53 list-resource-record-sets --hosted-zone-id Z09236092LH12WY229SBG --no-cli-pager
aws acm list-certificates --region us-east-1 --no-cli-pager
aws s3 ls --region us-east-1 --no-cli-pager
aws ecs describe-services --cluster agencyhq-cluster --services agencyhq-service --no-cli-pager
```

### Creation
```bash
# S3 Bucket
aws s3api create-bucket --bucket agenchq-frontend --region us-east-1 --no-cli-pager
aws s3api put-public-access-block --bucket agenchq-frontend --public-access-block-configuration "BlockPublicAcls=false,IgnorePublicAcls=false,BlockPublicPolicy=false,RestrictPublicBuckets=false" --no-cli-pager
aws s3api put-bucket-policy --bucket agenchq-frontend --policy file://s3-bucket-policy.json --no-cli-pager
aws s3api put-bucket-website --bucket agenchq-frontend --website-configuration file://s3-website-config.json --no-cli-pager

# CloudFront Distribution
aws cloudfront create-distribution --distribution-config file://cloudfront-config.json --no-cli-pager

# Route53 Records
aws route53 change-resource-record-sets --hosted-zone-id Z09236092LH12WY229SBG --change-batch file://route53-changes.json --no-cli-pager

# Deploy Frontend
aws s3 sync backend/public/build s3://agenchq-frontend --region us-east-1 --no-cli-pager

# Invalidate CloudFront Cache
aws cloudfront create-invalidation --distribution-id E16JXL5MNZFIYK --paths "/*" --no-cli-pager
```

---

## Files Changed

### `backend/resources/js/bootstrap.js`
Added API base URL for cross-domain requests:
```javascript
const API_BASE = window.location.hostname === 'app.agenchq.com' || window.location.hostname === 'agenchq.com'
    ? 'https://api.agenchq.com'
    : '';
if (API_BASE) {
    window.axios.defaults.baseURL = API_BASE;
}
```

### `backend/resources/js/stores/auth.js`
Updated login fetch to use API domain:
```javascript
const apiBase = window.location.hostname === 'app.agenchq.com' || window.location.hostname === 'agenchq.com'
    ? 'https://api.agenchq.com'
    : '';
const res = await fetch(`${apiBase}/api/login`, { ... });
```

### `backend/config/cors.php`
Added frontend origins:
```php
'allowed_origins' => array_filter([
    // ... existing
    'https://app.agenchq.com',
    'https://agenchq.com',
    // ...
]),
```

### `backend/public/build/index.html` (NEW)
Static index.html for S3 deployment with hardcoded asset paths.

---

## DNS Changes

| Record | Type | Before | After |
|--------|------|--------|-------|
| `app.agenchq.com` | A | (none) | CloudFront `d290jpjbd6hzg0.cloudfront.net` |
| `api.agenchq.com` | A | (none) | ALB `agencyhq-alb-326671770.us-east-1.elb.amazonaws.com` |
| `agenchq.com` | A | ALB | (unchanged - still points to ALB) |
| `*.agenchq.com` | A | ALB | (unchanged - still points to ALB) |

---

## CloudFront Configuration

| Setting | Value |
|---------|-------|
| Distribution ID | `E16JXL5MNZFIYK` |
| Domain | `d290jpjbd6hzg0.cloudfront.net` |
| Alias | `app.agenchq.com` |
| Origin | `agenchq-frontend.s3-website-us-east-1.amazonaws.com` |
| Certificate | `arn:aws:acm:us-east-1:788184849448:certificate/4bc09bf9-4307-47cb-adb4-7fd74b7b7cfc` |
| SSL | TLSv1.2_2021 |
| HTTP Version | HTTP/2 |

---

## Verification Results

| Check | Result |
|-------|--------|
| `https://app.agenchq.com` loads | ✅ 200 OK |
| `https://api.agenchq.com/up` responds | ✅ 200 OK |
| CORS preflight for `app.agenchq.com` | ✅ `Access-Control-Allow-Origin: https://app.agenchq.com` |
| `POST /api/login` reaches ECS | ✅ 422 (validation error = endpoint working) |

---

## Remaining Risks

1. **Backend CORS changes not deployed yet** - The CORS config changes are in code but need to be deployed to ECS via the GitHub Actions workflow. The current running container may not have the updated CORS config.

2. **Frontend build is static** - Any future frontend changes require:
   - `npm run build` in backend directory
   - `aws s3 sync backend/public/build s3://agenchq-frontend`
   - CloudFront invalidation

3. **Root domain `agenchq.com` still points to ALB** - Users accessing the root domain will still hit the old mixed routing. Consider redirecting root to `app.agenchq.com`.

---

## Rollback Steps

### To revert to previous architecture:

1. **Delete Route53 records:**
```bash
aws route53 change-resource-record-sets --hosted-zone-id Z09236092LH12WY229SBG --change-batch '{
  "Comment": "Rollback - remove app/api subdomains",
  "Changes": [
    {"Action": "DELETE", "ResourceRecordSet": {"Name": "app.agenchq.com.", "Type": "A", "AliasTarget": {"HostedZoneId": "Z2FDTNDATAQYW2", "DNSName": "d290jpjbd6hzg0.cloudfront.net.", "EvaluateTargetHealth": false}}},
    {"Action": "DELETE", "ResourceRecordSet": {"Name": "api.agenchq.com.", "Type": "A", "AliasTarget": {"HostedZoneId": "Z35SXDOTRQ7X7K", "DNSName": "agencyhq-alb-326671770.us-east-1.elb.amazonaws.com.", "EvaluateTargetHealth": false}}}
  ]
}' --no-cli-pager
```

2. **Disable and delete CloudFront distribution:**
```bash
aws cloudfront update-distribution --id E16JXL5MNZFIYK --if-match <ETag> --default-root-object "index.html" --enabled false
# Wait for status to change, then:
aws cloudfront delete-distribution --id E16JXL5MNZFIYK --if-match <ETag>
```

3. **Empty and delete S3 bucket:**
```bash
aws s3 rm s3://agenchq-frontend --recursive
aws s3api delete-bucket --bucket agenchq-frontend --region us-east-1
```

4. **Revert code changes:**
```bash
git revert d61fbd7
git push origin main
```

---

## Next Steps

1. **Trigger GitHub Actions build** to deploy updated backend with CORS changes
2. **Test login** at `https://app.agenchq.com` with real credentials
3. **Verify console logs** show `login payload` with `hasToken: true, hasUser: true`
4. **Consider adding redirect** from `agenchq.com` to `app.agenchq.com`

---

## Architecture Diagram (After)

```
┌─────────────────┐     ┌─────────────────┐
│  app.agenchq.com│     │  api.agenchq.com│
└────────┬────────┘     └────────┬────────┘
         │                       │
         ▼                       ▼
┌─────────────────┐     ┌─────────────────┐
│   CloudFront    │     │      ALB        │
│  (CDN + SSL)    │     │  (HTTPS:443)    │
└────────┬────────┘     └────────┬────────┘
         │                       │
         ▼                       ▼
┌─────────────────┐     ┌─────────────────┐
│   S3 Bucket     │     │   ECS Fargate   │
│  (Vue.js SPA)   │     │  (Laravel API)  │
└─────────────────┘     └─────────────────┘

Frontend calls API via: https://api.agenchq.com/api/*
CORS allows: https://app.agenchq.com
Auth: Stateless token-based (no cookies/sessions)
```
