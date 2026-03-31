# Multi-Tenant Deployment & Subdomain Routing

This document explains how to deploy the HealthFlow multi-tenant architecture and how the system handles organization isolation.

## 1. Domain Architecture
HealthFlow uses **domain-based multi-tenancy**.
- **Platform Admin:** Usually accessed via the main domain (e.g., `goodwillstaffing.ca`).
- **Organizations:** Each organization has its own subdomain or custom domain (e.g., `demo.goodwillstaffing.ca` or `clinic-a.com`).

## 2. Subdomain Routing Logic
The frontend resolves the current organization by looking at `window.location.host`.

### Frontend (`frontend/lib/api.ts`)
The `useApi` hook attaches an `X-Org-Host` header to every outgoing request:
```typescript
const orgHost = window.location.host; // e.g., "demo.healthflow.ca"
headers['X-Org-Host'] = orgHost;
```

### Backend (`backend/app/Http/Middleware/ResolveOrganization.php`)
The backend middleware intercepts every request and:
1.  Reads the `X-Org-Host` header.
2.  Queries the `organization_domains` table for a matching, active domain.
3.  Attaches the `Organization` model and `organization_id` to the request attributes.
4.  Enforces that if a user is logged in, their `organization_id` must match the domain's organization.

## 3. Postgres Isolation (Supabase)
For deployment on Supabase or any standard Postgres instance:
- **Global Scoping:** Every operational table contains an `organization_id` column.
- **Query Scoping:** Controllers use `Org::id($request)` to filter results:
  ```php
  $query->where('organization_id', $orgId);
  ```
- **Constraint Safety:** Organization IDs are validated at the middleware level, preventing cross-tenant data leaks even if an ID is guessed.

## 4. Local Development Testing
To test a specific organization locally:
1.  Add a domain to the `organization_domains` table (e.g., `demo.localhost`).
2.  Update your `frontend/.env.local`:
    ```bash
    VITE_ORG_HOST=demo.localhost
    ```
3.  Restart the Vite dev server.

## 5. Production Infrastructure Guidance
- **Vercel (Frontend):** Use "Wildcard Subdomains" in Project Settings.
- **Laravel (Backend):** Ensure `SESSION_DOMAIN` and `SANCTUM_STATEFUL_DOMAINS` in `.env` include your root domain (e.g., `.goodwillstaffing.ca`) to allow cookie-based auth across subdomains.
