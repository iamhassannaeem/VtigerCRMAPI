# Project Structure

Complete overview of the Vtiger CRM REST API project structure.

## Directory Tree

```
vtiger-crm-api/
│
├── config/                          # Configuration files
│   ├── database.php                 # Database credentials (git-ignored)
│   ├── database.example.php         # Database config template
│   ├── jwt.php                      # JWT secret key (git-ignored)
│   └── jwt.example.php              # JWT config template
│
├── core/                            # Core system files
│   ├── Database.php                 # Database connection class (Singleton pattern)
│   └── Router.php                   # HTTP router with pattern matching
│
├── helpers/                         # Helper utilities
│   └── JWT.php                      # JWT encode/decode functions
│
├── models/                          # Data models (Business Logic)
│   ├── User.php                     # User authentication & management
│   ├── Ticket.php                   # Ticket CRUD operations
│   └── Comment.php                  # Comment management
│
├── controllers/                     # Request handlers
│   ├── AuthController.php           # Login, token verification
│   ├── DashboardController.php      # Dashboard statistics
│   ├── TicketController.php         # Ticket endpoints
│   └── ProfileController.php        # User profile endpoints
│
├── public/                          # Web-accessible directory (Document Root)
│   ├── index.php                    # Application entry point
│   └── .htaccess                    # URL rewriting rules
│
├── .htaccess                        # Root htaccess (redirects to public/)
├── .gitignore                       # Git ignore rules
│
├── README.md                        # Main documentation
├── API_EXAMPLES.md                  # Request/response examples
├── DEPLOYMENT.md                    # Deployment guide
├── PROJECT_STRUCTURE.md             # This file
└── POSTMAN_COLLECTION.json          # Postman import file
```

---

## File Descriptions

### Configuration (`/config`)

#### `database.php`
- **Purpose:** Database connection settings
- **Contains:** Host, database name, username, password, charset, port
- **Security:** Git-ignored (sensitive data)

#### `jwt.php`
- **Purpose:** JWT authentication settings
- **Contains:** Secret key, algorithm (HS256), token expiration time
- **Security:** Git-ignored (secret key)

---

### Core System (`/core`)

#### `Database.php`
- **Pattern:** Singleton
- **Responsibilities:**
  - Establish PDO connection to MySQL
  - Provide query execution methods
  - Transaction management
  - Prepared statement handling

**Key Methods:**
```php
getInstance()           // Get singleton instance
getConnection()         // Get PDO connection
query($sql, $params)    // Execute query
fetchAll($sql, $params) // Fetch multiple rows
fetchOne($sql, $params) // Fetch single row
beginTransaction()      // Start transaction
commit()                // Commit transaction
rollback()              // Rollback transaction
```

#### `Router.php`
- **Pattern:** Front Controller
- **Responsibilities:**
  - Route registration (GET, POST, PUT, DELETE)
  - URL pattern matching with parameters
  - Request dispatching
  - 404 handling

**Key Methods:**
```php
get($pattern, $callback)    // Register GET route
post($pattern, $callback)   // Register POST route
put($pattern, $callback)    // Register PUT route
delete($pattern, $callback) // Register DELETE route
dispatch()                  // Match and execute route
notFound($callback)         // Set 404 handler
```

---

### Helpers (`/helpers`)

#### `JWT.php`
- **Purpose:** JWT token generation and validation
- **Algorithm:** HS256 (HMAC with SHA-256)

**Key Methods:**
```php
generateToken($payload)  // Create JWT token
validateToken($token)    // Verify and decode token
extractToken()           // Extract token from Authorization header
```

**Token Structure:**
```
Header.Payload.Signature
```

---

### Models (`/models`)

#### `User.php`
- **Database Tables:** 
  - `vtiger_users`
  - `vtiger_role`
  - `vtiger_user2role`

**Key Methods:**
```php
authenticate($username, $password)  // Validate credentials
getUserById($userId)                // Get user details
getAssignedTicketsCount($userId)    // Count user's tickets
isAdmin($userId)                    // Check admin status
getFullName($userId)                // Get formatted name
```

#### `Ticket.php`
- **Database Tables:**
  - `vtiger_troubletickets`
  - `vtiger_ticketcf` (custom fields)
  - `vtiger_crmentity` (entity metadata)
  - `vtiger_account`
  - `vtiger_contactdetails`
  - `vtiger_users`

**Key Methods:**
```php
getTickets($filters, $userId, $isAdmin)      // List with filters
getTicketById($ticketId, $userId, $isAdmin)  // Get single ticket
createTicket($data, $userId)                 // Create new ticket
getTicketStats($userId, $isAdmin)            // Dashboard statistics
getNextTicketNumber()                        // Generate ticket number
```

#### `Comment.php`
- **Database Tables:**
  - `vtiger_ticketcomments`
  - `vtiger_users`

**Key Methods:**
```php
getCommentsByTicketId($ticketId, $limit)  // Get ticket comments
getLastComment($ticketId)                 // Get most recent comment
addComment($ticketId, $comment, $userId)  // Add new comment
getRecentComments($userId, $isAdmin, $limit)  // Dashboard comments
deleteComment($commentId, $userId, $isAdmin)  // Delete comment
```

---

### Controllers (`/controllers`)

#### `AuthController.php`
- **Endpoints:**
  - `POST /auth/login` - User authentication
  - `GET /auth/verify` - Token validation

**Authentication Flow:**
1. Receive username/password
2. Hash password with MD5
3. Query vtiger_users table
4. Generate JWT token
5. Return token + user data

#### `DashboardController.php`
- **Endpoints:**
  - `GET /dashboard` - Statistics overview

**Returns:**
- Total open tickets
- Total closed tickets
- My assigned tickets (non-admin only)
- Tickets grouped by status
- Recent comments (last 5)

#### `TicketController.php`
- **Endpoints:**
  - `GET /tickets` - List all tickets
  - `GET /tickets/{id}` - Ticket details
  - `POST /tickets` - Create ticket
  - `POST /tickets/{id}/comments` - Add comment

**Access Control:**
- Admin: All tickets
- User: Only assigned tickets

#### `ProfileController.php`
- **Endpoints:**
  - `GET /me` - Current user profile

**Returns:**
- User information
- Role and department
- Assigned tickets count
- Contact details

---

### Public Directory (`/public`)

#### `index.php`
- **Purpose:** Application entry point
- **Responsibilities:**
  - Set HTTP headers (CORS, Content-Type)
  - Load all required files
  - Define routes
  - Handle exceptions
  - Dispatch requests

**Flow:**
1. Set headers
2. Load classes (autoload)
3. Initialize router
4. Register routes
5. Set exception handlers
6. Dispatch request

#### `.htaccess`
- **Purpose:** URL rewriting
- **Configuration:**
  ```apache
  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^(.*)$ index.php [QSA,L]
  ```

---

## Database Schema Overview

### Key Tables

#### `vtiger_users`
- **Primary Key:** `id`
- **Authentication:** `user_name`, `user_password` (MD5)
- **Admin Flag:** `is_admin`
- **Status:** `status`, `deleted`

#### `vtiger_crmentity`
- **Primary Key:** `crmid`
- **Owner:** `smownerid` (user assigned to)
- **Creator:** `smcreatorid`
- **Type:** `setype` (HelpDesk for tickets)
- **Timestamps:** `createdtime`, `modifiedtime`
- **Soft Delete:** `deleted`

#### `vtiger_troubletickets`
- **Primary Key:** `ticketid`
- **Foreign Keys:** 
  - `parent_id` → `vtiger_account.accountid`
  - `contact_id` → `vtiger_contactdetails.contactid`
- **Fields:** `ticket_no`, `title`, `status`, `priority`, `severity`, `category`

#### `vtiger_ticketcf`
- **Primary Key:** `ticketid`
- **Purpose:** Custom fields (cf_*)

#### `vtiger_ticketcomments`
- **Primary Key:** `commentid`
- **Foreign Key:** `ticketid`
- **Fields:** `comments`, `ownerid`, `createdtime`

---

## API Flow Diagram

```
Client Request
     ↓
public/index.php (Entry Point)
     ↓
Router → Match URL Pattern
     ↓
Controller (Auth Check via JWT)
     ↓
Model (Database Query)
     ↓
Database (Vtiger Tables)
     ↓
Model (Process Data)
     ↓
Controller (Format Response)
     ↓
JSON Response to Client
```

---

## Authentication Flow

```
1. POST /auth/login
   └→ AuthController::login()
      └→ User::authenticate()
         └→ Query vtiger_users
            └→ If valid:
               ├→ JWT::generateToken()
               └→ Return token + user data

2. Subsequent Requests
   └→ Extract Authorization header
      └→ JWT::extractToken()
         └→ JWT::validateToken()
            └→ If valid:
               ├→ Extract user_id
               └→ Proceed to endpoint
            └→ If invalid:
               └→ 401 Unauthorized
```

---

## Security Features

### 1. JWT Authentication
- HS256 algorithm
- Token expiration (24 hours)
- Signature verification

### 2. SQL Injection Protection
- PDO prepared statements
- Parameter binding

### 3. Access Control
- Admin vs. regular user permissions
- Row-level security (users see only their records)

### 4. Input Sanitization
- JSON input parsing
- Type validation
- Required field checks

### 5. Password Security
- MD5 hashing (Vtiger compatible)
- Secure comparison

---

## API Response Format

### Success Response
```json
{
  "success": true,
  "message": "Optional message",
  "data": { /* Response data */ }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description"
}
```

---

## HTTP Status Codes Used

- **200** - OK (Success)
- **201** - Created (Resource created)
- **400** - Bad Request (Invalid input)
- **401** - Unauthorized (Invalid/missing token)
- **404** - Not Found (Resource not found)
- **500** - Internal Server Error

---

## Dependencies

### Required PHP Extensions
- `pdo` - Database abstraction
- `pdo_mysql` - MySQL driver
- `json` - JSON encoding/decoding
- `mbstring` - Multibyte string handling
- `openssl` - Cryptographic functions

### No External Libraries
- Pure PHP implementation
- No Composer dependencies
- No framework overhead

---

## Development vs. Production

### Development Settings
```php
// public/index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Production Settings
```php
// public/index.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Strong JWT secret
// Secure database credentials
// HTTPS enabled
```

---

## File Permissions

```
Directories: 755 (rwxr-xr-x)
Files:       644 (rw-r--r--)
```

### Commands:
```bash
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
```

---

## Extending the API

### Adding New Endpoint

1. **Create Model** (if needed)
   ```php
   // models/NewModel.php
   class NewModel {
       public function getData() { /* ... */ }
   }
   ```

2. **Create Controller**
   ```php
   // controllers/NewController.php
   class NewController {
       public function index() { /* ... */ }
   }
   ```

3. **Register Route**
   ```php
   // public/index.php
   $router->get('/new-endpoint', function() {
       $controller = new NewController();
       $controller->index();
   });
   ```

---

## Performance Considerations

### Database Optimization
- Proper indexes on foreign keys
- Efficient JOIN queries
- Pagination (LIMIT/OFFSET)

### Caching Opportunities
- User role data
- Ticket status lists
- Dashboard statistics (short TTL)

### Query Optimization
- SELECT only needed columns
- Use prepared statements
- Minimize JOIN complexity

---

## Testing Checklist

- [ ] Health check endpoint (/)
- [ ] Login with valid credentials
- [ ] Login with invalid credentials
- [ ] Dashboard as admin
- [ ] Dashboard as regular user
- [ ] List tickets (with filters)
- [ ] Get ticket details
- [ ] Create new ticket
- [ ] Add comment to ticket
- [ ] Get user profile
- [ ] Test token expiration
- [ ] Test invalid token

---

This structure provides a clean, maintainable, and secure REST API for Vtiger CRM with clear separation of concerns and comprehensive functionality.

