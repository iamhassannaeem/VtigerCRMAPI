# Mobile Dashboard API - Testing Guide

## Prerequisites

1. Database with Vtiger CRM schema
2. Updated role names with postfixes (_Field, _NOC, _Management, _Sales, _Others)
3. Test users assigned to roles with appropriate postfixes
4. API running on localhost or test server

---

## Step 1: Update Database Roles

```sql
-- View current roles
SELECT roleid, rolename FROM vtiger_role;

-- Update roles with postfixes
UPDATE vtiger_role SET rolename = CONCAT(rolename, '_NOC') WHERE roleid = 1;
UPDATE vtiger_role SET rolename = CONCAT(rolename, '_Sales') WHERE roleid = 2;
UPDATE vtiger_role SET rolename = CONCAT(rolename, '_Management') WHERE roleid = 3;
UPDATE vtiger_role SET rolename = CONCAT(rolename, '_Field') WHERE roleid = 4;
UPDATE vtiger_role SET rolename = CONCAT(rolename, '_Others') WHERE roleid = 5;

-- Verify
SELECT roleid, rolename FROM vtiger_role;
```

---

## Step 2: Test Authentication

### Test Login with Role Detection

```bash
# Test Field User
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "field_user",
    "password": "your_password"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 5,
    "username": "field_user",
    "role": "Field Technician_Field",
    "role_info": {
      "full_name": "Field Technician_Field",
      "base_name": "Field Technician",
      "type": "Field"
    }
  }
}
```

**Verify:**
- ✓ `role_info.type` should be "Field"
- ✓ Token should be present
- ✓ Login should succeed

---

## Step 3: Test Field Role Dashboard

```bash
# Set your token
TOKEN="your_jwt_token_here"

# Get Field Dashboard
curl -X GET http://localhost/vtiger-api/public/mobile/dashboard \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Response Structure:**
```json
{
  "success": true,
  "role_type": "Field",
  "data": {
    "quick_stats": {
      "my_open_tickets": 8,
      "in_progress": 3,
      "high_priority": 2,
      "closed_today": 5
    },
    "todays_schedule": [...],
    "recent_comments": [...]
  }
}
```

**Test Checklist:**
- [ ] `role_type` is "Field"
- [ ] `quick_stats` contains 4 numeric values
- [ ] `todays_schedule` is an array (may be empty)
- [ ] `recent_comments` is an array
- [ ] All ticket numbers in schedule are valid

---

## Step 4: Test NOC Role Dashboard

```bash
# Login as NOC user
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "noc_user",
    "password": "your_password"
  }'

# Get NOC Dashboard
TOKEN="noc_user_token"
curl -X GET http://localhost/vtiger-api/public/mobile/dashboard \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Response Structure:**
```json
{
  "success": true,
  "role_type": "NOC",
  "data": {
    "critical_metrics": {
      "total_open_tickets": 45,
      "unassigned_tickets": 8,
      "critical_issues": 3,
      "avg_response_time_hours": 2.5
    },
    "priority_breakdown": [...],
    "severity_breakdown": [...],
    "team_workload": [...],
    "tickets_waiting_24h": [...],
    "response_time_metrics": [...]
  }
}
```

**Test Checklist:**
- [ ] `role_type` is "NOC"
- [ ] `critical_metrics` has 4 numeric fields
- [ ] `priority_breakdown` groups by priority
- [ ] `severity_breakdown` groups by severity
- [ ] `team_workload` shows users with workload_indicator
- [ ] `tickets_waiting_24h` shows tickets >24 hours old
- [ ] `response_time_metrics` shows time distribution

**Validate Workload Indicators:**
- Light (0 tickets)
- Normal (1-4 tickets)
- Heavy (5-9 tickets)
- Overloaded (10+ tickets)

---

## Step 5: Test Management Role Dashboard

```bash
# Login as Management user
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "manager",
    "password": "your_password"
  }'

# Get Management Dashboard
TOKEN="manager_token"
curl -X GET http://localhost/vtiger-api/public/mobile/dashboard \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Response Structure:**
```json
{
  "success": true,
  "role_type": "Management",
  "data": {
    "kpis": {
      "total_tickets": 1250,
      "open_tickets": 45,
      "resolution_rate": 87.5,
      "avg_resolution_time_hours": 18.5,
      "customer_satisfaction": {
        "score": "High",
        "percentage": 82.5
      }
    },
    "trends": {
      "tickets_created_vs_resolved": [...],
      "priority_trends": [...]
    },
    "team_performance": [...],
    "customer_insights": {
      "top_customers": [...],
      "tickets_by_category": [...],
      "tickets_by_severity": [...]
    }
  }
}
```

**Test Checklist:**
- [ ] `role_type` is "Management"
- [ ] `kpis` contains all 5 metrics
- [ ] `resolution_rate` is a percentage (0-100)
- [ ] `customer_satisfaction.score` is High/Medium/Low
- [ ] `trends` contains 30-day data
- [ ] `team_performance` shows all active users
- [ ] `customer_insights` has 3 sections

**Validate Customer Satisfaction:**
- Score: High (>=80%), Medium (60-79%), Low (<60%)
- Distribution: high, medium, low counts

---

## Step 6: Test Sales Role Dashboard

```bash
# Login as Sales user
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "sales_user",
    "password": "your_password"
  }'

# Get Sales Dashboard
TOKEN="sales_user_token"
curl -X GET http://localhost/vtiger-api/public/mobile/dashboard \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Response Structure:**
```json
{
  "success": true,
  "role_type": "Sales",
  "data": {
    "sales_overview": {
      "total_customers": 250,
      "new_this_month": 8,
      "active_customers": 120,
      "customers_with_open_tickets": 35
    },
    "customer_health": {
      "high_priority_customers": [...],
      "customers_at_risk": [...]
    },
    "tickets_by_organization": [...],
    "customer_activity": [...],
    "high_value_customers": [...]
  }
}
```

**Test Checklist:**
- [ ] `role_type` is "Sales"
- [ ] `sales_overview` has 4 counts
- [ ] `high_priority_customers` shows customers with high/urgent tickets
- [ ] `customers_at_risk` shows risky accounts
- [ ] `tickets_by_organization` grouped by account (last 90 days)
- [ ] `customer_activity` shows recent 10 tickets (last 7 days)
- [ ] `high_value_customers` sorted by activity score

**Validate Health Indicators:**
- Critical (urgent + open)
- Warning (high/urgent + open)
- Good (closed)
- Normal (other)

**Validate Risk Levels:**
- At risk: >=3 open tickets OR urgent tickets OR critical severity

---

## Step 7: Test Others Role Dashboard

```bash
# Login as Others user (no specific postfix)
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "normal_user",
    "password": "your_password"
  }'

# Get Others Dashboard
TOKEN="others_user_token"
curl -X GET http://localhost/vtiger-api/public/mobile/dashboard \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Response Structure:**
```json
{
  "success": true,
  "role_type": "Others",
  "data": {
    "my_tickets_summary": {
      "open_tickets": 12,
      "closed_tickets": 45,
      "total_tickets": 57
    },
    "status_breakdown": [...],
    "recent_activity": {
      "recent_tickets": [...],
      "recent_comments": [...]
    }
  }
}
```

**Test Checklist:**
- [ ] `role_type` is "Others"
- [ ] `my_tickets_summary` has 3 counts
- [ ] `status_breakdown` groups by status
- [ ] `recent_activity` has tickets and comments
- [ ] Only user's own tickets are shown

---

## Step 8: Error Handling Tests

### Test Missing Token
```bash
curl -X GET http://localhost/vtiger-api/public/mobile/dashboard
```

**Expected:** 401 Unauthorized
```json
{
  "success": false,
  "message": "No token provided"
}
```

### Test Invalid Token
```bash
curl -X GET http://localhost/vtiger-api/public/mobile/dashboard \
  -H "Authorization: Bearer invalid_token_here"
```

**Expected:** 401 Unauthorized
```json
{
  "success": false,
  "message": "Invalid or expired token"
}
```

### Test Expired Token
```bash
# Use a token older than 24 hours
curl -X GET http://localhost/vtiger-api/public/mobile/dashboard \
  -H "Authorization: Bearer expired_token"
```

**Expected:** 401 Unauthorized
```json
{
  "success": false,
  "message": "Invalid or expired token"
}
```

---

## Step 9: Performance Tests

### Test Response Time
```bash
# Time the dashboard request
time curl -X GET http://localhost/vtiger-api/public/mobile/dashboard \
  -H "Authorization: Bearer $TOKEN"
```

**Expected:**
- Field Role: <500ms
- NOC Role: <1000ms (more complex queries)
- Management Role: <1500ms (trends and analytics)
- Sales Role: <1000ms
- Others Role: <300ms (simplest)

### Test with Multiple Users
```bash
# Simulate 5 concurrent requests
for i in {1..5}; do
  curl -X GET http://localhost/vtiger-api/public/mobile/dashboard \
    -H "Authorization: Bearer $TOKEN" &
done
wait
```

---

## Step 10: Data Validation Tests

### Verify Field Role Data
```sql
-- Check user's open tickets
SELECT COUNT(*) FROM vtiger_troubletickets tt
INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
WHERE ce.deleted = 0 AND ce.smownerid = 5
AND tt.status NOT IN ('Closed');

-- Check closed today
SELECT COUNT(*) FROM vtiger_troubletickets tt
INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
WHERE ce.deleted = 0 AND ce.smownerid = 5
AND tt.status = 'Closed' AND DATE(ce.modifiedtime) = CURDATE();
```

### Verify NOC Role Data
```sql
-- Check unassigned tickets
SELECT COUNT(*) FROM vtiger_troubletickets tt
INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
WHERE ce.deleted = 0 
AND (ce.smownerid IS NULL OR ce.smownerid = 0)
AND tt.status NOT IN ('Closed');

-- Check critical issues
SELECT COUNT(*) FROM vtiger_troubletickets tt
INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
WHERE ce.deleted = 0 AND tt.severity = 'Critical'
AND tt.status NOT IN ('Closed');
```

### Verify Management KPIs
```sql
-- Check resolution rate calculation
SELECT 
  (SELECT COUNT(*) FROM vtiger_troubletickets tt
   INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
   WHERE ce.deleted = 0 AND tt.status = 'Closed'
   AND DATE(ce.modifiedtime) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as closed,
  (SELECT COUNT(*) FROM vtiger_troubletickets tt
   INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
   WHERE ce.deleted = 0
   AND DATE(ce.createdtime) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as created;
```

### Verify Sales Data
```sql
-- Check new customers this month
SELECT COUNT(*) FROM vtiger_account a
INNER JOIN vtiger_crmentity ce ON a.accountid = ce.crmid
WHERE ce.deleted = 0
AND MONTH(ce.createdtime) = MONTH(CURDATE())
AND YEAR(ce.createdtime) = YEAR(CURDATE());

-- Check customers at risk
SELECT COUNT(DISTINCT a.accountid) FROM vtiger_account a
INNER JOIN vtiger_troubletickets tt ON a.accountid = tt.parent_id
INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
WHERE ce.deleted = 0 AND tt.status NOT IN ('Closed')
GROUP BY a.accountid
HAVING COUNT(*) >= 3 OR 
       SUM(CASE WHEN tt.priority = 'Urgent' THEN 1 ELSE 0 END) > 0;
```

---

## Step 11: Integration Test Script

Save as `test_mobile_dashboard.sh`:

```bash
#!/bin/bash

BASE_URL="http://localhost/vtiger-api/public"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo "=== Mobile Dashboard API Test Suite ==="
echo ""

# Test 1: Login
echo "Test 1: Login with Field user..."
LOGIN_RESPONSE=$(curl -s -X POST $BASE_URL/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"field_user","password":"password"}')

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -n "$TOKEN" ]; then
    echo -e "${GREEN}✓ Login successful${NC}"
    echo "Token: ${TOKEN:0:20}..."
else
    echo -e "${RED}✗ Login failed${NC}"
    exit 1
fi

# Test 2: Get Dashboard
echo ""
echo "Test 2: Get Mobile Dashboard..."
DASHBOARD_RESPONSE=$(curl -s -X GET $BASE_URL/mobile/dashboard \
  -H "Authorization: Bearer $TOKEN")

ROLE_TYPE=$(echo $DASHBOARD_RESPONSE | grep -o '"role_type":"[^"]*' | cut -d'"' -f4)

if [ "$ROLE_TYPE" = "Field" ]; then
    echo -e "${GREEN}✓ Dashboard returned with role_type: $ROLE_TYPE${NC}"
else
    echo -e "${RED}✗ Dashboard failed or wrong role_type${NC}"
    exit 1
fi

# Test 3: Check Data Structure
echo ""
echo "Test 3: Validate data structure..."

if echo $DASHBOARD_RESPONSE | grep -q "quick_stats"; then
    echo -e "${GREEN}✓ quick_stats present${NC}"
else
    echo -e "${RED}✗ quick_stats missing${NC}"
fi

if echo $DASHBOARD_RESPONSE | grep -q "todays_schedule"; then
    echo -e "${GREEN}✓ todays_schedule present${NC}"
else
    echo -e "${RED}✗ todays_schedule missing${NC}"
fi

if echo $DASHBOARD_RESPONSE | grep -q "recent_comments"; then
    echo -e "${GREEN}✓ recent_comments present${NC}"
else
    echo -e "${RED}✗ recent_comments missing${NC}"
fi

echo ""
echo "=== All Tests Completed ==="
```

Make executable and run:
```bash
chmod +x test_mobile_dashboard.sh
./test_mobile_dashboard.sh
```

---

## Troubleshooting

### Issue: Role type is always "Others"
**Solution:** Check that role names in database have correct postfix (_Field, _NOC, etc.)

```sql
SELECT u.user_name, r.rolename 
FROM vtiger_users u
LEFT JOIN vtiger_user2role u2r ON u.id = u2r.userid
LEFT JOIN vtiger_role r ON u2r.roleid = r.roleid
WHERE u.user_name = 'your_username';
```

### Issue: Dashboard returns empty arrays
**Solution:** Ensure user has tickets assigned and database has sample data

```sql
-- Create test ticket
INSERT INTO vtiger_crmentity (crmid, smownerid, setype, createdtime, deleted)
VALUES (999999, 5, 'HelpDesk', NOW(), 0);

INSERT INTO vtiger_troubletickets (ticketid, ticket_no, title, status, priority)
VALUES (999999, 'TT9999', 'Test Ticket', 'Open', 'High');
```

### Issue: JWT token expires quickly
**Solution:** Check JWT configuration in `config/jwt.php`

```php
return [
    'secret_key' => 'your_secret',
    'algorithm' => 'HS256',
    'expiration' => 86400  // 24 hours in seconds
];
```

### Issue: Response time is slow
**Solution:** Check database indexes

```sql
-- Add indexes if missing
CREATE INDEX idx_crmentity_smownerid ON vtiger_crmentity(smownerid);
CREATE INDEX idx_crmentity_deleted ON vtiger_crmentity(deleted);
CREATE INDEX idx_troubletickets_status ON vtiger_troubletickets(status);
CREATE INDEX idx_troubletickets_priority ON vtiger_troubletickets(priority);
```

---

## Success Criteria

All tests pass if:
- [ ] Login returns valid token with role_info
- [ ] Dashboard endpoint returns 200 OK
- [ ] role_type matches user's role postfix
- [ ] All required data sections are present
- [ ] Data counts match database queries
- [ ] Response time is acceptable (<2 seconds)
- [ ] Error handling works correctly
- [ ] No PHP errors in logs

---

## Production Readiness Checklist

Before deploying to production:
- [ ] All role names updated with correct postfixes
- [ ] Test users created for each role type
- [ ] Sample data exists in database
- [ ] Database indexes are optimal
- [ ] Error logging is configured
- [ ] JWT secret key is strong and secure
- [ ] API response times are acceptable
- [ ] All endpoints are protected by authentication
- [ ] CORS headers configured for production domain
- [ ] SSL/HTTPS enabled

