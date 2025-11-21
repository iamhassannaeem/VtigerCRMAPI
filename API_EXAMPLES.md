# API Request/Response Examples

This document contains detailed examples of all API endpoints with real request/response samples.

## Table of Contents

1. [Authentication](#authentication)
2. [Dashboard](#dashboard)
3. [Tickets](#tickets)
4. [Profile](#profile)

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

