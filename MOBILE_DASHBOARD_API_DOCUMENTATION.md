# Mobile Dashboard API Documentation

## Overview

The Mobile Dashboard API provides role-specific dashboard data for the mobile application. The API automatically detects the user's role type from their JWT token and returns appropriate dashboard data tailored to their role.

## Role Types

The system supports five role types based on role name postfix:
- **Field** (_Field)
- **NOC** (_NOC)
- **Management** (_Management)
- **Sales** (_Sales)
- **Others** (_Others or no postfix)

## Authentication

All dashboard endpoints require JWT authentication. The role type is automatically extracted from the JWT token payload.

```
Authorization: Bearer <your_jwt_token>
```

---

## Endpoint

### GET /mobile/dashboard

Returns role-specific dashboard data based on the authenticated user's role type.

**Headers:**
```
Authorization: Bearer <token>
```

**Response Format:**
```json
{
  "success": true,
  "role_type": "Field|NOC|Management|Sales|Others",
  "data": { /* role-specific data */ }
}
```

---

## Role-Specific Responses

### 1. Field Role Dashboard

**Role Type:** `Field`

**Purpose:** Mobile-first dashboard for field technicians with today's schedule and quick stats.

**Response Structure:**
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
    "todays_schedule": [
      {
        "ticketid": 456,
        "ticket_no": "TT0456",
        "title": "Network Issue at Site A",
        "priority": "High",
        "status": "In Progress",
        "customer_name": "ABC Corp",
        "location": "123 Main St, San Francisco, CA",
        "createdtime": "2024-01-15 08:30:00",
        "time_scheduled": "08:30"
      }
    ],
    "recent_comments": [
      {
        "commentid": 123,
        "ticketid": 456,
        "comments": "Heading to customer site now",
        "createdtime": "2024-01-15 09:00:00",
        "created_by_name": "John Doe",
        "ticket_no": "TT0456",
        "ticket_title": "Network Issue at Site A"
      }
    ]
  }
}
```

**Key Metrics:**
- `my_open_tickets` - Total open tickets assigned to the user
- `in_progress` - Tickets currently in progress
- `high_priority` - High and urgent priority tickets not closed
- `closed_today` - Tickets closed today by the user
- `todays_schedule` - Top 5 tickets created today (scheduled work)
- `recent_comments` - Last 10 comments on user's tickets

---

### 2. NOC Role Dashboard

**Role Type:** `NOC`

**Purpose:** Network Operations Center dashboard with system-wide monitoring and team workload.

**Response Structure:**
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
    "priority_breakdown": [
      {
        "priority": "Urgent",
        "count": 5
      },
      {
        "priority": "High",
        "count": 12
      },
      {
        "priority": "Normal",
        "count": 20
      },
      {
        "priority": "Low",
        "count": 8
      }
    ],
    "severity_breakdown": [
      {
        "severity": "Critical",
        "count": 3
      },
      {
        "severity": "Major",
        "count": 15
      },
      {
        "severity": "Minor",
        "count": 20
      }
    ],
    "team_workload": [
      {
        "user_id": 5,
        "user_name": "John Doe",
        "department": "Support",
        "open_tickets": 12,
        "closed_today": 3,
        "workload_indicator": "Heavy"
      }
    ],
    "tickets_waiting_24h": [
      {
        "ticketid": 789,
        "ticket_no": "TT0789",
        "title": "System Performance Issue",
        "priority": "High",
        "status": "Open",
        "createdtime": "2024-01-13 10:00:00",
        "hours_waiting": 50,
        "assigned_to": "Jane Smith",
        "accountname": "XYZ Company"
      }
    ],
    "response_time_metrics": [
      {
        "response_time_range": "< 1 hour",
        "count": 25
      },
      {
        "response_time_range": "1-4 hours",
        "count": 35
      },
      {
        "response_time_range": "4-24 hours",
        "count": 20
      },
      {
        "response_time_range": "1-3 days",
        "count": 10
      },
      {
        "response_time_range": "> 3 days",
        "count": 5
      }
    ]
  }
}
```

**Key Metrics:**
- `critical_metrics` - Overall system health indicators
- `priority_breakdown` - Tickets grouped by priority (for pie chart)
- `severity_breakdown` - Tickets grouped by severity (for bar chart)
- `team_workload` - User workload with indicators (Light, Normal, Heavy, Overloaded)
- `tickets_waiting_24h` - Tickets waiting more than 24 hours (top 20)
- `response_time_metrics` - Distribution of response times (last 30 days)

**Workload Indicators:**
- `Light` - 0 open tickets
- `Normal` - 1-4 open tickets
- `Heavy` - 5-9 open tickets
- `Overloaded` - 10+ open tickets

---

### 3. Management Role Dashboard

**Role Type:** `Management`

**Purpose:** Executive dashboard with KPIs, trends, and performance analytics.

**Response Structure:**
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
        "percentage": 82.5,
        "distribution": {
          "high": 150,
          "medium": 80,
          "low": 20
        }
      }
    },
    "trends": {
      "tickets_created_vs_resolved": [
        {
          "date": "2024-01-01",
          "created": 25,
          "resolved": 22
        },
        {
          "date": "2024-01-02",
          "created": 30,
          "resolved": 28
        }
      ],
      "priority_trends": [
        {
          "date": "2024-01-01",
          "priority": "High",
          "count": 8
        }
      ]
    },
    "team_performance": [
      {
        "user_id": 5,
        "user_name": "John Doe",
        "department": "Support",
        "total_assigned": 150,
        "total_resolved": 135,
        "currently_open": 15,
        "resolution_percentage": 90.0,
        "avg_resolution_hours": 16.5
      }
    ],
    "customer_insights": {
      "top_customers": [
        {
          "accountid": 102,
          "accountname": "ABC Corp",
          "ticket_count": 45,
          "open_tickets": 5,
          "high_priority_tickets": 2
        }
      ],
      "tickets_by_category": [
        {
          "category": "Technical",
          "count": 120
        }
      ],
      "tickets_by_severity": [
        {
          "severity": "Major",
          "count": 85
        }
      ]
    }
  }
}
```

**Key Metrics:**
- `kpis` - Key performance indicators including customer satisfaction
- `trends` - 30-day trends for tickets and priorities
- `team_performance` - Individual user performance metrics
- `customer_insights` - Top customers and ticket distribution

**Customer Satisfaction Calculation:**
- Based on resolution time for tickets closed in last 30 days
- **High**: <24 hours (100 points)
- **Medium**: 24-72 hours (70 points)
- **Low**: >72 hours (40 points)
- Score: Weighted average converted to High/Medium/Low

---

### 4. Sales Role Dashboard

**Role Type:** `Sales`

**Purpose:** Customer-focused dashboard with health indicators and account management.

**Response Structure:**
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
      "high_priority_customers": [
        {
          "accountid": 102,
          "accountname": "ABC Corp",
          "high_priority_count": 3,
          "open_tickets": 5
        }
      ],
      "customers_at_risk": [
        {
          "accountid": 105,
          "accountname": "XYZ Inc",
          "open_tickets": 8,
          "urgent_tickets": 2,
          "critical_tickets": 1,
          "risk_level": "High"
        }
      ]
    },
    "tickets_by_organization": [
      {
        "accountid": 102,
        "accountname": "ABC Corp",
        "account_type": "Customer",
        "industry": "Technology",
        "total_tickets": 45,
        "open_tickets": 5,
        "closed_tickets": 40,
        "high_priority_tickets": 2,
        "last_ticket_date": "2024-01-15 10:30:00"
      }
    ],
    "customer_activity": [
      {
        "ticketid": 456,
        "ticket_no": "TT0456",
        "title": "Email Configuration",
        "status": "Open",
        "priority": "High",
        "accountid": 102,
        "accountname": "ABC Corp",
        "createdtime": "2024-01-15 09:00:00",
        "assigned_to": "John Doe",
        "health_indicator": "Warning"
      }
    ],
    "high_value_customers": [
      {
        "accountid": 102,
        "accountname": "ABC Corp",
        "account_type": "Customer",
        "industry": "Technology",
        "rating": "Active",
        "ticket_activity_score": 45,
        "open_tickets": 5,
        "status": "Needs Attention"
      }
    ]
  }
}
```

**Key Metrics:**
- `sales_overview` - Customer counts and activity
- `customer_health` - At-risk and high-priority customers
- `tickets_by_organization` - Detailed ticket breakdown per customer (last 90 days)
- `customer_activity` - Recent ticket activity (last 7 days)
- `high_value_customers` - Customers with highest ticket activity (last 90 days)

**Health Indicators:**
- `Critical` - Urgent priority + Open status
- `Warning` - High/Urgent priority + Open status
- `Good` - Closed status
- `Normal` - Other combinations

**Customer Risk Levels:**
- At-risk if: >=3 open tickets OR any urgent tickets OR any critical severity tickets

**Customer Status:**
- `Critical` - Has urgent open tickets
- `At Risk` - Has high/urgent open tickets
- `Needs Attention` - Has >3 open tickets
- `Healthy` - Other cases

---

### 5. Others Role Dashboard

**Role Type:** `Others`

**Purpose:** Basic dashboard for users without specific role postfix.

**Response Structure:**
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
    "status_breakdown": [
      {
        "status": "Open",
        "count": 5
      },
      {
        "status": "In Progress",
        "count": 4
      },
      {
        "status": "Closed",
        "count": 45
      }
    ],
    "recent_activity": {
      "recent_tickets": [
        {
          "ticketid": 456,
          "ticket_no": "TT0456",
          "title": "Email Issue",
          "status": "Open",
          "priority": "High",
          "createdtime": "2024-01-15 09:00:00",
          "type": "ticket"
        }
      ],
      "recent_comments": [
        {
          "commentid": 123,
          "ticketid": 456,
          "comments": "Working on resolution",
          "createdtime": "2024-01-15 10:00:00",
          "ticket_no": "TT0456",
          "type": "comment"
        }
      ]
    }
  }
}
```

**Key Metrics:**
- `my_tickets_summary` - Overview of user's tickets
- `status_breakdown` - Tickets grouped by status
- `recent_activity` - Last 5 tickets and last 5 comments

---

## Login Response Enhancement

The login endpoint now includes role information:

```json
POST /auth/login

Response:
{
  "success": true,
  "message": "Login successful",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 5,
    "username": "john.doe",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "is_admin": false,
    "role": "Field Technician_Field",
    "role_info": {
      "full_name": "Field Technician_Field",
      "base_name": "Field Technician",
      "type": "Field"
    },
    "department": "Field Services"
  }
}
```

The mobile app should use `role_info.type` to determine which dashboard UI to display.

---

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "message": "No token provided"
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "Server error: [error details]"
}
```

---

## Implementation Notes

### Database Queries

1. **Response Time**: Time from ticket creation to first comment
2. **Resolution Time**: Time from ticket creation to closure (modifiedtime)
3. **Today's Schedule**: Tickets created today assigned to the user
4. **Unassigned Tickets**: Where `smownerid IS NULL OR smownerid = 0`
5. **Active Customers**: Customers with tickets in last 30 days
6. **At-Risk Customers**: >=3 open tickets OR urgent/critical tickets

### Performance Considerations

- All queries use proper indexes on `crmid`, `smownerid`, and `deleted` columns
- Date filtering uses `DATE()` function for consistent comparisons
- Limits are applied to large result sets (default: 5-20 items)
- Trends data limited to 30-90 days to maintain performance

### Role Detection

Role type is automatically extracted from the JWT token payload, which is set during login based on the role name postfix in the `vtiger_role` table.

**Example Role Names:**
- "Network Administrator_NOC"
- "Sales Manager_Sales"
- "CEO_Management"
- "Field Technician_Field"
- "Support Staff_Others"

---

## Testing Examples

### Test Field Role Dashboard
```bash
# Login as field user
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"field_user","password":"password"}'

# Get dashboard
curl -X GET http://localhost/vtiger-api/public/mobile/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Test NOC Role Dashboard
```bash
# Login as NOC user
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"noc_user","password":"password"}'

# Get dashboard
curl -X GET http://localhost/vtiger-api/public/mobile/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Test Management Role Dashboard
```bash
# Login as management user
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"manager","password":"password"}'

# Get dashboard
curl -X GET http://localhost/vtiger-api/public/mobile/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Database Setup

To use role-based dashboards, update role names in the database:

```sql
-- Update role names with appropriate postfixes
UPDATE vtiger_role SET rolename = 'Network Administrator_NOC' WHERE roleid = 1;
UPDATE vtiger_role SET rolename = 'Sales Manager_Sales' WHERE roleid = 2;
UPDATE vtiger_role SET rolename = 'CEO_Management' WHERE roleid = 3;
UPDATE vtiger_role SET rolename = 'Field Technician_Field' WHERE roleid = 4;
UPDATE vtiger_role SET rolename = 'Support Staff_Others' WHERE roleid = 5;

-- Verify updates
SELECT roleid, rolename FROM vtiger_role;
```

---

## Mobile App Integration Guide

### 1. Login Flow
```javascript
// Login
const response = await fetch('/auth/login', {
  method: 'POST',
  body: JSON.stringify({ username, password })
});

const data = await response.json();
const roleType = data.user.role_info.type; // Field, NOC, Management, Sales, Others

// Store token and role type
localStorage.setItem('token', data.token);
localStorage.setItem('roleType', roleType);
```

### 2. Dashboard Selection
```javascript
const roleType = localStorage.getItem('roleType');

switch(roleType) {
  case 'Field':
    navigate('/field-dashboard');
    break;
  case 'NOC':
    navigate('/noc-dashboard');
    break;
  case 'Management':
    navigate('/management-dashboard');
    break;
  case 'Sales':
    navigate('/sales-dashboard');
    break;
  default:
    navigate('/normal-dashboard');
}
```

### 3. Fetch Dashboard Data
```javascript
const token = localStorage.getItem('token');

const response = await fetch('/mobile/dashboard', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const dashboard = await response.json();
// dashboard.role_type confirms the role
// dashboard.data contains role-specific data
```

---

## Summary

The Mobile Dashboard API provides a unified endpoint that intelligently routes to role-specific dashboards based on JWT token authentication. Each role receives tailored data optimized for their workflow and responsibilities, creating a personalized mobile experience.

**Total Endpoints:** 1 (unified)
**Supported Roles:** 5 (Field, NOC, Management, Sales, Others)
**Response Time:** Optimized with indexed queries and limited result sets
**Authentication:** JWT-based with automatic role detection

