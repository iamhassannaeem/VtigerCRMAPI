# API Endpoints Documentation

## Firebase Notification & JWT Refresh Token Endpoints

### Authentication Endpoints

#### 1. Login (with Refresh Token)
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
  "refresh_token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6",
  "expires_in": 86400,
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

#### 2. Refresh Access Token
**POST** `/auth/refresh`

**Request Body:**
```json
{
  "refresh_token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "expires_in": 86400
}
```

**Error Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Invalid or expired refresh token"
}
```

#### 3. Logout
**POST** `/auth/logout`

**Request Body:**
```json
{
  "refresh_token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

#### 4. Verify Token
**GET** `/auth/verify`

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Token is valid",
  "user_id": 1
}
```

---

### Firebase Cloud Messaging (FCM) Endpoints

#### 5. Register FCM Token
**POST** `/api/fcm/register`

**Headers:**
```
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "fcm_token": "dGhpcyBpcyBhbiBmY20gdG9rZW4gZXhhbXBsZQ...",
  "device_type": "android",
  "device_id": "device_unique_id_123"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "FCM token registered successfully"
}
```

**Error Responses:**
- **401 Unauthorized:** Missing or invalid access token
- **400 Bad Request:** Missing FCM token

---

#### 6. Send FCM Notification
**POST** `/api/notifications/send`

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "user_id": 123,
  "title": "New Ticket Assignment",
  "message": "Hi John Doe, You have been assigned a new ticket #TKT-12345 for ABC Company.",
  "ticket_id": 12345,
  "ticket_number": "TKT-12345",
  "type": "ticket_assigned"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Notification sent successfully",
  "notification_id": "projects/your-project/messages/0:1234567890",
  "results": [
    {
      "token": "dGhpcyBpcyBhbiBmY20...",
      "success": true,
      "messageId": "projects/your-project/messages/0:1234567890"
    }
  ]
}
```

**Error Responses:**

**400 Bad Request - Missing Fields:**
```json
{
  "success": false,
  "error": "Missing required field: user_id",
  "code": "MISSING_FIELDS"
}
```

**400 Bad Request - Invalid Type:**
```json
{
  "success": false,
  "error": "Invalid notification type. Must be one of: ticket_assigned, ticket_updated",
  "code": "INVALID_TYPE"
}
```

**404 Not Found - User Not Found:**
```json
{
  "success": false,
  "error": "User not found",
  "code": "USER_NOT_FOUND"
}
```

**404 Not Found - No FCM Tokens:**
```json
{
  "success": false,
  "error": "No FCM tokens found for user",
  "code": "NO_TOKENS"
}
```

**500 Internal Server Error:**
```json
{
  "success": false,
  "error": "Failed to send notification to any device",
  "code": "SEND_FAILED",
  "results": [
    {
      "token": "dGhpcyBpcyBhbiBmY20...",
      "success": false,
      "error": "FCM error: InvalidRegistration"
    }
  ]
}
```

---

## Notification Types

Allowed notification types:
- `ticket_assigned` - When a ticket is assigned to a user
- `ticket_updated` - When a ticket is updated

---

## Error Codes

| Code | Description |
|------|-------------|
| `MISSING_FIELDS` | Required fields are missing |
| `INVALID_TYPE` | Invalid notification type |
| `USER_NOT_FOUND` | User ID does not exist |
| `NO_TOKENS` | No active FCM tokens found for user |
| `SEND_FAILED` | Failed to send notification to any device |
| `INTERNAL_ERROR` | Internal server error |

---

## Setup Instructions

1. **Run Database Migration:**
   ```sql
   -- Execute: database/migrations/create_fcm_refresh_tokens_tables.sql
   ```

2. **Configure Firebase:**
   - Copy `config/firebase.example.php` to `config/firebase.php`
   - Add your Firebase Server Key

3. **Configure JWT:**
   - Ensure `config/jwt.php` includes `refresh_expiration` (default: 604800 seconds = 7 days)

4. **Test Endpoints:**
   - Use Postman or curl to test the endpoints
   - Register FCM token after login
   - Send notifications via `/api/notifications/send`

---

## Example Usage Flow

1. **User Login:**
   ```bash
   POST /auth/login
   # Returns: access_token + refresh_token
   ```

2. **Register FCM Token:**
   ```bash
   POST /api/fcm/register
   Authorization: Bearer <access_token>
   # Registers device FCM token
   ```

3. **Send Notification (from Vtiger CRM):**
   ```bash
   POST /api/notifications/send
   # Sends push notification to user's devices
   ```

4. **Refresh Token (when access token expires):**
   ```bash
   POST /auth/refresh
   # Returns new access_token
   ```

5. **Logout:**
   ```bash
   POST /auth/logout
   # Revokes refresh token
   ```
