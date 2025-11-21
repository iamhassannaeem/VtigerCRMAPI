# Vtiger CRM REST API

A modern, lightweight REST API built with pure PHP 8+ for Vtiger CRM. This API provides authentication, ticket management, dashboard statistics, and user profile endpoints.

## 🚀 Features

- **JWT Authentication** (HS256)
- **Role-based Access Control** (Admin gets full access, users get only their assigned records)
- **Ticket Management** (List, Create, View, Comment)
- **Dashboard Statistics**
- **User Profile Management**
- **Pure PHP** (No frameworks)
- **JSON Responses**

## 📁 Project Structure

```
vtiger-crm-api/
├── config/
│   ├── database.php      # Database configuration
│   └── jwt.php           # JWT configuration
├── core/
│   ├── Database.php      # Database connection class
│   └── Router.php        # Simple router
├── helpers/
│   └── JWT.php           # JWT helper functions
├── models/
│   ├── User.php          # User model
│   ├── Ticket.php        # Ticket model
│   └── Comment.php       # Comment model
├── controllers/
│   ├── AuthController.php      # Authentication
│   ├── DashboardController.php # Dashboard
│   ├── TicketController.php    # Ticket management
│   └── ProfileController.php   # User profile
├── public/
│   ├── index.php         # Entry point
│   └── .htaccess         # URL rewriting
├── .htaccess             # Root htaccess
└── README.md
```

## 🔧 Installation

### Prerequisites

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB
- Apache with mod_rewrite enabled
- Vtiger CRM database

### Setup Steps

1. **Clone or download the project**
   ```bash
   cd /var/www/html
   git clone <repository-url> vtiger-api
   ```

2. **Configure Database**
   
   Edit `config/database.php`:
   ```php
   return [
       'host' => 'localhost',
       'database' => 'vconn',
       'username' => 'root',
       'password' => 'your_password',
       'charset' => 'utf8mb4',
       'port' => 3306
   ];
   ```

3. **Configure JWT Secret**
   
   Edit `config/jwt.php`:
   ```php
   return [
       'secret_key' => 'your_secret_key_here',
       'algorithm' => 'HS256',
       'expiration' => 86400 // 24 hours
   ];
   ```

4. **Set Permissions**
   ```bash
   chmod -R 755 /var/www/html/vtiger-api
   chown -R www-data:www-data /var/www/html/vtiger-api
   ```

5. **Enable Apache mod_rewrite**
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

6. **Access the API**
   ```
   http://localhost/vtiger-api/public/
   ```

## 📚 API Documentation

### Base URL
```
http://localhost/vtiger-api/public
```

### Authentication

All endpoints except `/auth/login` require JWT token in the Authorization header:
```
Authorization: Bearer <your_jwt_token>
```

---

## 🔐 Authentication Endpoints

### 1. Login

**POST** `/auth/login`

**Request Body:**
```json
{
  "username": "admin",
  "password": "password123"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "username": "admin",
    "first_name": "John",
    "last_name": "Doe",
    "email": "admin@example.com",
    "is_admin": true,
    "role": "CEO",
    "department": "Management"
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

---

## 📊 Dashboard Endpoint

### 2. Get Dashboard Statistics

**GET** `/dashboard`

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "total_open_tickets": 45,
    "total_closed_tickets": 120,
    "my_assigned_tickets": 12,
    "tickets_by_status": [
      { "status": "Open", "count": 30 },
      { "status": "In Progress", "count": 10 },
      { "status": "Closed", "count": 120 },
      { "status": "Wait For Response", "count": 5 }
    ],
    "recent_comments": [
      {
        "commentid": 45,
        "ticketid": 123,
        "comments": "Issue has been resolved",
        "createdtime": "2024-01-15 10:30:00",
        "created_by_name": "John Doe",
        "ticket_no": "TT0123",
        "ticket_title": "Login Issue"
      }
    ]
  }
}
```

---

## 🎫 Ticket Endpoints

### 3. List Tickets

**GET** `/tickets`

**Query Parameters:**
- `status` (optional): Filter by status (Open, Closed, In Progress, etc.)
- `assigned_to` (optional, admin only): Filter by assigned user ID
- `search` (optional): Search in ticket title and ticket number
- `limit` (optional, default: 50): Number of records per page
- `offset` (optional, default: 0): Pagination offset

**Example Request:**
```
GET /tickets?status=Open&limit=10&offset=0
```

**Response (200 OK):**
```json
{
  "success": true,
  "count": 10,
  "data": [
    {
      "ticketid": 123,
      "ticket_no": "TT0123",
      "title": "Login Issue",
      "status": "Open",
      "priority": "High",
      "severity": "Major",
      "category": "Technical",
      "createdtime": "2024-01-15 09:00:00",
      "modifiedtime": "2024-01-15 10:30:00",
      "smownerid": 5,
      "assigned_to_name": "John Doe",
      "account_name": "ABC Corp",
      "contact_name": "Jane Smith"
    }
  ]
}
```

---

### 4. Get Ticket Details

**GET** `/tickets/{id}`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "summary": {
      "ticket_id": 123,
      "ticket_no": "TT0123",
      "title": "Login Issue",
      "status": "Open",
      "priority": "High",
      "severity": "Major",
      "category": "Technical",
      "assigned_to": "John Doe",
      "created_by": "Admin User",
      "account": "ABC Corp",
      "contact": "Jane Smith",
      "created_time": "2024-01-15 09:00:00",
      "modified_time": "2024-01-15 10:30:00"
    },
    "details": {
      "description": "User cannot login to the system",
      "solution": "Reset password and clear cache",
      "hours": 2.5,
      "days": 0,
      "custom_fields": {
        "cf_867": "value1",
        "cf_869": "value2"
      }
    },
    "last_comment": {
      "commentid": 45,
      "comments": "Issue resolved",
      "createdtime": "2024-01-15 11:00:00",
      "created_by_name": "John Doe"
    },
    "all_comments": [...]
  }
}
```

---

### 5. Create Ticket

**POST** `/tickets`

**Request Body:**
```json
{
  "title": "Cannot access reports",
  "description": "Getting 404 error when accessing reports section",
  "priority": "High",
  "category": "Technical",
  "account_id": 45,
  "assigned_to": 5
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Ticket created successfully",
  "data": {
    "success": true,
    "ticket_id": 456,
    "ticket_no": "TT0456"
  }
}
```

---

### 6. Add Comment to Ticket

**POST** `/tickets/{id}/comments`

**Request Body:**
```json
{
  "comment": "I've investigated the issue and found the root cause."
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Comment added successfully",
  "data": [
    {
      "commentid": 67,
      "ticketid": 123,
      "comments": "I've investigated the issue and found the root cause.",
      "ownerid": 5,
      "ownertype": "user",
      "createdtime": "2024-01-15 14:30:00",
      "created_by_name": "John Doe",
      "created_by_email": "john@example.com"
    }
  ]
}
```

---

## 👤 Profile Endpoint

### 7. Get User Profile

**GET** `/me`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "username": "johndoe",
    "full_name": "John Doe",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "department": "IT Support",
    "title": "Support Engineer",
    "role": "Support Team",
    "is_admin": false,
    "assigned_tickets_count": 12,
    "status": "Active"
  }
}
```

---

## 🔒 Security Features

- **JWT Token Authentication**: Secure token-based authentication
- **MD5 Password Hashing**: Compatible with Vtiger's password storage
- **SQL Injection Protection**: Prepared statements with PDO
- **XSS Protection**: JSON output with proper headers
- **Role-based Access**: Admin vs. regular user permissions
- **Input Sanitization**: All inputs are validated and sanitized

## 🎯 Access Control

### Admin Users
- Can view all tickets in the system
- Can filter tickets by assigned user
- Can access all dashboard statistics
- Full CRUD operations

### Regular Users
- Can only view tickets assigned to them
- Dashboard shows only their assigned tickets
- Can create tickets
- Can comment on their assigned tickets

## ⚠️ Error Responses

All errors follow this format:

```json
{
  "success": false,
  "message": "Error description"
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `500` - Internal Server Error

## 🧪 Testing

Use Postman, Insomnia, or curl to test the API:

```bash
# Login
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}'

# Get Dashboard (with token)
curl -X GET http://localhost/vtiger-api/public/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## 📝 Notes

- Password hashing uses MD5 to match Vtiger CRM's authentication system
- The API connects directly to Vtiger database tables
- Custom fields (cf_*) are dynamically included in ticket details
- JWT tokens expire after 24 hours (configurable)

## 🤝 Support

For issues or questions, please contact the development team.

## 📄 License

This project is proprietary and confidential.

