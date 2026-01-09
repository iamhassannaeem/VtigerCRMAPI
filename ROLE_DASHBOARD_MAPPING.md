# Role to Dashboard Mapping Reference

This document shows how user roles are mapped to dashboard types.

## Mapping Table

| Role Name | Dashboard Type |
|-----------|----------------|
| **Organization** | Management |
| **CEO** | Management |
| **Admin** | Management |
| **Sales Manager** | Sales |
| **Sales Person** | Sales |
| **Wireless Team Lead** | NOC |
| **Assistant Manager NOC** | NOC |
| **Internal Team Member** | NOC |
| **Networks Manager** | NOC |
| **Team Leads** | NOC |
| **Team Members** | NOC |
| **External Team Leads** | Field |
| **Human Resource** | Others |
| **Accounts Team** | Others |
| **IPTV** | Others |
| **Office Manager** | Others |
| **Store Manager** | Others |
| **PBX/Voice** | Others |
| **GIS** | Others |
| **Any other role** | Others (default) |

## How It Works

1. **During Login**: `RoleHelper::getRoleType()` maps the user's role name to a dashboard type
2. **JWT Token**: The `role_type` is embedded in the JWT token payload
3. **Dashboard Route**: `/mobile/dashboard` uses `role_type` from JWT to return the correct dashboard data

## Example Login Response

```json
{
  "success": true,
  "message": "Login successful",
  "token": "...",
  "user": {
    "id": 1,
    "username": "admin",
    "role": "CEO",
    "role_info": {
      "full_name": "CEO",
      "base_name": "CEO",
      "type": "Management"  // ← Maps to Management dashboard
    }
  }
}
```

## Dashboard Types

- **Management**: Executive dashboard with KPIs, trends, team performance
- **Sales**: Customer-focused dashboard with sales overview and customer health
- **NOC**: Network operations dashboard with critical metrics and team workload
- **Field**: Mobile-first dashboard with today's schedule and quick stats
- **Others**: Standard dashboard with personal ticket summary

---

**Last Updated**: Role mapping updated to use direct role name matching instead of postfix pattern.

