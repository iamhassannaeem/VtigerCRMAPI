# API Request/Response Examples

This document contains detailed examples of all API endpoints with real request/response samples.

## Table of Contents

1. [Authentication](#authentication)
2. [Dashboard](#dashboard)
3. [Tickets](#tickets)
4. [Profile](#profile)
5. [Organizations](#organizations)
6. [Contacts](#contacts)
7. [Picklists](#picklists)

---

## Authentication

### Login Request

```http
POST /auth/login HTTP/1.1
Host: localhost
Content-Type: application/json

{
  "username": "admin",
  "password": "admin123"
}
```

**Success Response:**

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "message": "Login successful",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJ1c2VybmFtZSI6ImFkbWluIiwiaXNfYWRtaW4iOnRydWUsImlhdCI6MTcwNTMxNDAwMCwiZXhwIjoxNzA1NDAwNDAwfQ.abcdefghijklmnopqrstuvwxyz1234567890",
  "user": {
    "id": 1,
    "username": "admin",
    "first_name": "Administrator",
    "last_name": "User",
    "email": "admin@example.com",
    "is_admin": true,
    "role": "CEO",
    "department": "Management"
  }
}
```

**Error Response (Invalid Credentials):**

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{
  "success": false,
  "message": "Invalid credentials"
}
```

**Error Response (Missing Fields):**

```http
HTTP/1.1 400 Bad Request
Content-Type: application/json

{
  "success": false,
  "message": "Username and password are required"
}
```

---

## Dashboard

### Get Dashboard Statistics

```http
GET /dashboard HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response (Admin User):**

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "data": {
    "total_open_tickets": 45,
    "total_closed_tickets": 120,
    "tickets_by_status": [
      {
        "status": "Open",
        "count": 30
      },
      {
        "status": "In Progress",
        "count": 10
      },
      {
        "status": "Wait For Response",
        "count": 5
      },
      {
        "status": "Closed",
        "count": 120
      }
    ],
    "recent_comments": [
      {
        "commentid": 125,
        "ticketid": 456,
        "comments": "The issue has been resolved. Please verify.",
        "createdtime": "2024-01-15 14:30:00",
        "created_by_name": "John Smith",
        "ticket_no": "TT0456",
        "ticket_title": "Email Configuration Problem"
      },
      {
        "commentid": 124,
        "ticketid": 455,
        "comments": "Investigating the root cause.",
        "createdtime": "2024-01-15 13:15:00",
        "created_by_name": "Jane Doe",
        "ticket_no": "TT0455",
        "ticket_title": "Report Generation Error"
      }
    ]
  }
}
```

**Success Response (Regular User):**

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "data": {
    "total_open_tickets": 12,
    "total_closed_tickets": 35,
    "my_assigned_tickets": 12,
    "tickets_by_status": [
      {
        "status": "Open",
        "count": 8
      },
      {
        "status": "In Progress",
        "count": 4
      },
      {
        "status": "Closed",
        "count": 35
      }
    ],
    "recent_comments": [...]
  }
}
```

---

## Tickets

### List All Tickets

```http
GET /tickets?status=Open&limit=5&offset=0 HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response:**

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "count": 5,
  "data": [
    {
      "ticketid": 456,
      "ticket_no": "TT0456",
      "title": "Email Configuration Problem",
      "status": "Open",
      "priority": "High",
      "severity": "Major",
      "category": "Technical",
      "createdtime": "2024-01-15 09:00:00",
      "modifiedtime": "2024-01-15 14:30:00",
      "smownerid": 5,
      "assigned_to_name": "John Smith",
      "account_name": "TechCorp Inc",
      "contact_name": "Mike Johnson"
    },
    {
      "ticketid": 457,
      "ticket_no": "TT0457",
      "title": "Cannot Access Dashboard",
      "status": "Open",
      "priority": "Normal",
      "severity": "Minor",
      "category": "UI Issue",
      "createdtime": "2024-01-15 10:15:00",
      "modifiedtime": "2024-01-15 10:15:00",
      "smownerid": 3,
      "assigned_to_name": "Sarah Williams",
      "account_name": "Global Solutions",
      "contact_name": "Emma Brown"
    }
  ]
}
```

### Get Ticket Details

```http
GET /tickets/456 HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response:**

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "data": {
    "summary": {
      "ticket_id": 456,
      "ticket_no": "TT0456",
      "title": "Email Configuration Problem",
      "status": "Open",
      "priority": "High",
      "severity": "Major",
      "category": "Technical",
      "assigned_to": "John Smith",
      "created_by": "Administrator User",
      "account": "TechCorp Inc",
      "contact": "Mike Johnson",
      "created_time": "2024-01-15 09:00:00",
      "modified_time": "2024-01-15 14:30:00"
    },
    "details": {
      "description": "Users are unable to send emails from the CRM system. Getting SMTP connection timeout error.",
      "solution": "Checked SMTP server settings and updated the configuration. Issue resolved.",
      "hours": 3.5,
      "days": 0,
      "custom_fields": {
        "cf_867": "Internal",
        "cf_869": "Production",
        "cf_871": "Email System"
      }
    },
    "last_comment": {
      "commentid": 125,
      "ticketid": 456,
      "comments": "The issue has been resolved. Please verify.",
      "ownerid": 5,
      "ownertype": "user",
      "createdtime": "2024-01-15 14:30:00",
      "created_by_name": "John Smith",
      "created_by_email": "john.smith@example.com"
    },
    "all_comments": [
      {
        "commentid": 125,
        "ticketid": 456,
        "comments": "The issue has been resolved. Please verify.",
        "ownerid": 5,
        "ownertype": "user",
        "createdtime": "2024-01-15 14:30:00",
        "created_by_name": "John Smith",
        "created_by_email": "john.smith@example.com"
      },
      {
        "commentid": 123,
        "ticketid": 456,
        "comments": "Investigating the SMTP server configuration.",
        "ownerid": 5,
        "ownertype": "user",
        "createdtime": "2024-01-15 10:15:00",
        "created_by_name": "John Smith",
        "created_by_email": "john.smith@example.com"
      },
      {
        "commentid": 122,
        "ticketid": 456,
        "comments": "Ticket assigned to technical team.",
        "ownerid": 1,
        "ownertype": "user",
        "createdtime": "2024-01-15 09:05:00",
        "created_by_name": "Administrator User",
        "created_by_email": "admin@example.com"
      }
    ]
  }
}
```

**Error Response (Not Found or No Access):**

```http
HTTP/1.1 404 Not Found
Content-Type: application/json

{
  "success": false,
  "message": "Ticket not found or access denied"
}
```

### Create New Ticket

```http
POST /tickets HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json

{
  "title": "Report Export Not Working",
  "description": "When trying to export reports to PDF, the system shows an error message.",
  "priority": "High",
  "category": "Reports",
  "account_id": 102,
  "assigned_to": 5
}
```

**Success Response:**

```http
HTTP/1.1 201 Created
Content-Type: application/json

{
  "success": true,
  "message": "Ticket created successfully",
  "data": {
    "success": true,
    "ticket_id": 458,
    "ticket_no": "TT0458"
  }
}
```

**Error Response (Missing Title):**

```http
HTTP/1.1 400 Bad Request
Content-Type: application/json

{
  "success": false,
  "message": "Title is required"
}
```

### Add Comment to Ticket

```http
POST /tickets/456/comments HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json

{
  "comment": "I've tested the email system and it's working perfectly now. Thanks!"
}
```

**Success Response:**

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "message": "Comment added successfully",
  "data": [
    {
      "commentid": 126,
      "ticketid": 456,
      "comments": "I've tested the email system and it's working perfectly now. Thanks!",
      "ownerid": 1,
      "ownertype": "user",
      "createdtime": "2024-01-15 15:00:00",
      "created_by_name": "Administrator User",
      "created_by_email": "admin@example.com"
    },
    {
      "commentid": 125,
      "ticketid": 456,
      "comments": "The issue has been resolved. Please verify.",
      "ownerid": 5,
      "ownertype": "user",
      "createdtime": "2024-01-15 14:30:00",
      "created_by_name": "John Smith",
      "created_by_email": "john.smith@example.com"
    }
  ]
}
```

---

## Profile

### Get User Profile

```http
GET /me HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response:**

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "data": {
    "id": 5,
    "username": "john.smith",
    "full_name": "John Smith",
    "first_name": "John",
    "last_name": "Smith",
    "email": "john.smith@example.com",
    "phone": "+1 (555) 123-4567",
    "department": "Technical Support",
    "title": "Senior Support Engineer",
    "role": "Support Team",
    "is_admin": false,
    "assigned_tickets_count": 12,
    "status": "Active"
  }
}
```

---

## Error Responses

### Unauthorized (No Token)

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{
  "success": false,
  "message": "No token provided"
}
```

### Unauthorized (Invalid/Expired Token)

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{
  "success": false,
  "message": "Invalid or expired token"
}
```

### Server Error

```http
HTTP/1.1 500 Internal Server Error
Content-Type: application/json

{
  "success": false,
  "message": "Server error: Database connection failed"
}
```

### Not Found

```http
HTTP/1.1 404 Not Found
Content-Type: application/json

{
  "success": false,
  "message": "Endpoint not found"
}
```

---

## CURL Examples

### Login
```bash
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### Get Dashboard
```bash
curl -X GET http://localhost/vtiger-api/public/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### List Tickets
```bash
curl -X GET "http://localhost/vtiger-api/public/tickets?status=Open&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Ticket Details
```bash
curl -X GET http://localhost/vtiger-api/public/tickets/456 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Create Ticket
```bash
curl -X POST http://localhost/vtiger-api/public/tickets \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "New Issue",
    "description": "Description here",
    "priority": "High",
    "category": "Technical"
  }'
```

### Add Comment
```bash
curl -X POST http://localhost/vtiger-api/public/tickets/456/comments \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"comment": "This is a test comment"}'
```

### Get Profile
```bash
curl -X GET http://localhost/vtiger-api/public/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Organizations

### List All Organizations

```http
GET /organizations?search=tech&limit=10&offset=0 HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response:**

```json
{
  "success": true,
  "count": 10,
  "data": [
    {
      "accountid": 102,
      "account_no": "ACC001",
      "accountname": "TechCorp Inc",
      "phone": "+1 (555) 987-6543",
      "email1": "contact@techcorp.com",
      "email2": null,
      "website": "https://techcorp.com",
      "account_type": "Customer",
      "industry": "Technology",
      "rating": "Active",
      "employees": 250,
      "bill_city": "San Francisco",
      "bill_state": "California",
      "bill_country": "USA",
      "bill_street": "123 Tech Street",
      "createdtime": "2023-12-01 10:00:00",
      "modifiedtime": "2024-01-10 15:30:00",
      "assigned_to_name": "John Smith"
    }
  ]
}
```

**CURL Example:**

```bash
# List all organizations
curl -X GET "http://localhost/vtiger-api/public/organizations?limit=50" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Search organizations
curl -X GET "http://localhost/vtiger-api/public/organizations?search=tech&limit=20" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Filter by account type
curl -X GET "http://localhost/vtiger-api/public/organizations?account_type=Customer" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Organization by ID

```http
GET /organizations/102 HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response:**

```json
{
  "success": true,
  "data": {
    "accountid": 102,
    "account_no": "ACC001",
    "accountname": "TechCorp Inc",
    "phone": "+1 (555) 987-6543",
    "email1": "contact@techcorp.com",
    "website": "https://techcorp.com",
    "account_type": "Customer",
    "industry": "Technology",
    "rating": "Active",
    "employees": 250,
    "bill_city": "San Francisco",
    "bill_state": "California",
    "bill_country": "USA",
    "bill_street": "123 Tech Street",
    "bill_code": "94105",
    "bill_pobox": null,
    "ship_city": "San Francisco",
    "ship_state": "California",
    "ship_country": "USA",
    "ship_street": "123 Tech Street",
    "createdtime": "2023-12-01 10:00:00",
    "modifiedtime": "2024-01-10 15:30:00",
    "assigned_to_name": "John Smith",
    "created_by_name": "Administrator User"
  }
}
```

**CURL Example:**

```bash
curl -X GET http://localhost/vtiger-api/public/organizations/102 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Contacts

### List All Contacts

```http
GET /contacts?account_id=102&limit=10&offset=0 HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response:**

```json
{
  "success": true,
  "count": 3,
  "data": [
    {
      "contactid": 250,
      "contact_no": "CON001",
      "firstname": "Mike",
      "lastname": "Johnson",
      "full_name": "Mike Johnson",
      "email": "mike.johnson@techcorp.com",
      "phone": "+1 (555) 111-2222",
      "mobile": "+1 (555) 333-4444",
      "title": "IT Manager",
      "department": "IT",
      "accountid": 102,
      "accountname": "TechCorp Inc",
      "mailingcity": "San Francisco",
      "mailingstate": "California",
      "mailingcountry": "USA",
      "createdtime": "2023-12-05 11:00:00",
      "modifiedtime": "2024-01-12 09:15:00",
      "assigned_to_name": "Sarah Williams"
    }
  ]
}
```

**CURL Examples:**

```bash
# List all contacts
curl -X GET "http://localhost/vtiger-api/public/contacts?limit=50" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Filter contacts by organization
curl -X GET "http://localhost/vtiger-api/public/contacts?account_id=102" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Search contacts
curl -X GET "http://localhost/vtiger-api/public/contacts?search=mike" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Contact by ID

```http
GET /contacts/250 HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response:**

```json
{
  "success": true,
  "data": {
    "contactid": 250,
    "contact_no": "CON001",
    "firstname": "Mike",
    "lastname": "Johnson",
    "email": "mike.johnson@techcorp.com",
    "phone": "+1 (555) 111-2222",
    "mobile": "+1 (555) 333-4444",
    "title": "IT Manager",
    "department": "IT",
    "accountid": 102,
    "accountname": "TechCorp Inc",
    "mailingcity": "San Francisco",
    "mailingstate": "California",
    "mailingcountry": "USA",
    "mailingstreet": "456 Main St",
    "createdtime": "2023-12-05 11:00:00",
    "modifiedtime": "2024-01-12 09:15:00",
    "assigned_to_name": "Sarah Williams",
    "created_by_name": "Administrator User"
  }
}
```

**CURL Example:**

```bash
curl -X GET http://localhost/vtiger-api/public/contacts/250 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Picklists

### Get Priorities

```http
GET /picklists/priorities HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response:**

```json
{
  "success": true,
  "count": 4,
  "data": [
    {
      "id": 1,
      "value": "Low",
      "color": "#00FF00",
      "sortorderid": 0
    },
    {
      "id": 2,
      "value": "Normal",
      "color": "#FFFF00",
      "sortorderid": 1
    },
    {
      "id": 3,
      "value": "High",
      "color": "#FFA500",
      "sortorderid": 2
    },
    {
      "id": 4,
      "value": "Urgent",
      "color": "#FF0000",
      "sortorderid": 3
    }
  ]
}
```

### Get Services Issue

```http
GET /picklists/services-issue HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response:**

```json
{
  "success": true,
  "count": 5,
  "data": [
    {
      "id": 1,
      "value": "Network Issue",
      "color": null,
      "sortorderid": 0
    },
    {
      "id": 2,
      "value": "Software Bug",
      "color": null,
      "sortorderid": 1
    },
    {
      "id": 3,
      "value": "Hardware Problem",
      "color": null,
      "sortorderid": 2
    }
  ]
}
```

### Get Statuses

```http
GET /picklists/statuses HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response:**

```json
{
  "success": true,
  "count": 5,
  "data": [
    {
      "id": 1,
      "value": "Open",
      "color": "#FF0000",
      "sortorderid": 0
    },
    {
      "id": 2,
      "value": "In Progress",
      "color": "#FFA500",
      "sortorderid": 1
    },
    {
      "id": 3,
      "value": "Wait For Response",
      "color": "#FFFF00",
      "sortorderid": 2
    },
    {
      "id": 4,
      "value": "Closed",
      "color": "#00FF00",
      "sortorderid": 3
    }
  ]
}
```

### Get Users

```http
GET /picklists/users HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response:**

```json
{
  "success": true,
  "count": 5,
  "data": [
    {
      "id": 1,
      "username": "admin",
      "full_name": "Administrator User",
      "first_name": "Administrator",
      "last_name": "User",
      "email1": "admin@example.com",
      "department": "Management",
      "is_admin": "on",
      "status": "Active"
    },
    {
      "id": 5,
      "username": "john.smith",
      "full_name": "John Smith",
      "first_name": "John",
      "last_name": "Smith",
      "email1": "john.smith@example.com",
      "department": "Technical Support",
      "is_admin": "off",
      "status": "Active"
    }
  ]
}
```

### Get Issue End

```http
GET /picklists/issue-end HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response:**

```json
{
  "success": true,
  "count": 3,
  "data": [
    {
      "id": 1,
      "value": "Resolved",
      "color": null,
      "sortorderid": 0
    },
    {
      "id": 2,
      "value": "Workaround Provided",
      "color": null,
      "sortorderid": 1
    },
    {
      "id": 3,
      "value": "Cannot Reproduce",
      "color": null,
      "sortorderid": 2
    }
  ]
}
```

### Get Categories

```http
GET /picklists/categories HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response:**

```json
{
  "success": true,
  "count": 3,
  "data": [
    {
      "id": 1,
      "value": "Big Problem",
      "color": null,
      "sortorderid": 0
    },
    {
      "id": 2,
      "value": "Small Problem",
      "color": null,
      "sortorderid": 1
    },
    {
      "id": 3,
      "value": "Other Problem",
      "color": null,
      "sortorderid": 2
    }
  ]
}
```

### Get Severities

```http
GET /picklists/severities HTTP/1.1
Host: localhost
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Success Response:**

```json
{
  "success": true,
  "count": 4,
  "data": [
    {
      "id": 1,
      "value": "Minor",
      "color": null,
      "sortorderid": 0
    },
    {
      "id": 2,
      "value": "Major",
      "color": null,
      "sortorderid": 1
    },
    {
      "id": 3,
      "value": "Feature",
      "color": null,
      "sortorderid": 2
    },
    {
      "id": 4,
      "value": "Critical",
      "color": null,
      "sortorderid": 3
    }
  ]
}
```

**CURL Examples for Picklists:**

```bash
# Get priorities
curl -X GET http://localhost/vtiger-api/public/picklists/priorities \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get services issue options
curl -X GET http://localhost/vtiger-api/public/picklists/services-issue \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get statuses
curl -X GET http://localhost/vtiger-api/public/picklists/statuses \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get users
curl -X GET http://localhost/vtiger-api/public/picklists/users \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get issue end options
curl -X GET http://localhost/vtiger-api/public/picklists/issue-end \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get categories
curl -X GET http://localhost/vtiger-api/public/picklists/categories \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Get severities
curl -X GET http://localhost/vtiger-api/public/picklists/severities \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```



