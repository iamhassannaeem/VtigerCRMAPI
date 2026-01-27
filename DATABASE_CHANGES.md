# Database Changes for Firebase Notifications & JWT Refresh Tokens

## Migration File
**File:** `database/migrations/create_fcm_refresh_tokens_tables.sql`

Execute this SQL file to create the required tables.

---

## New Tables

### 1. `user_fcm_tokens`
Stores FCM (Firebase Cloud Messaging) tokens for each user's devices.

**Columns:**
- `id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `user_id` (INT, NOT NULL) - Foreign key to vtiger_users.id
- `fcm_token` (VARCHAR(255), NOT NULL) - Firebase device token
- `device_type` (VARCHAR(50), NULL) - Device type: 'android', 'ios', 'web'
- `device_id` (VARCHAR(255), NULL) - Unique device identifier
- `is_active` (TINYINT(1), DEFAULT 1) - Whether token is active
- `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

**Indexes:**
- PRIMARY KEY: `id`
- UNIQUE KEY: `unique_user_device` (`user_id`, `fcm_token`)
- INDEX: `idx_user_id` (`user_id`)
- INDEX: `idx_active` (`is_active`)
- INDEX: `idx_fcm_token` (`fcm_token`)

**Purpose:**
- Store FCM tokens for push notifications
- Support multiple devices per user
- Track device information
- Mark tokens as inactive when invalid

---

### 2. `refresh_tokens`
Stores refresh tokens for JWT authentication.

**Columns:**
- `id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `user_id` (INT, NOT NULL) - Foreign key to vtiger_users.id
- `token` (VARCHAR(255), NOT NULL, UNIQUE) - Refresh token string
- `expires_at` (TIMESTAMP, NOT NULL) - Token expiration time
- `is_revoked` (TINYINT(1), DEFAULT 0) - Whether token is revoked
- `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- `revoked_at` (TIMESTAMP, NULL) - When token was revoked

**Indexes:**
- PRIMARY KEY: `id`
- UNIQUE KEY: `token`
- INDEX: `idx_user_id` (`user_id`)
- INDEX: `idx_token` (`token`)
- INDEX: `idx_expires_at` (`expires_at`)
- INDEX: `idx_is_revoked` (`is_revoked`)

**Purpose:**
- Store refresh tokens for JWT authentication
- Enable token refresh without re-login
- Support token revocation on logout
- Track token expiration

---

### 3. `notification_logs`
Logs all FCM notification attempts for auditing and debugging.

**Columns:**
- `id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `user_id` (INT, NOT NULL) - Foreign key to vtiger_users.id
- `ticket_id` (INT, NULL) - Related ticket ID
- `notification_type` (VARCHAR(50), NULL) - Type: 'ticket_assigned', 'ticket_updated'
- `title` (VARCHAR(255), NULL) - Notification title
- `message` (TEXT, NULL) - Notification message
- `fcm_token` (VARCHAR(255), NULL) - FCM token used
- `status` (VARCHAR(20), NULL) - Status: 'sent', 'failed', 'token_invalid'
- `error_message` (TEXT, NULL) - Error message if failed
- `fcm_message_id` (VARCHAR(255), NULL) - Firebase message ID
- `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

**Indexes:**
- PRIMARY KEY: `id`
- INDEX: `idx_user_id` (`user_id`)
- INDEX: `idx_ticket_id` (`ticket_id`)
- INDEX: `idx_created_at` (`created_at`)
- INDEX: `idx_status` (`status`)

**Purpose:**
- Audit trail for all notification attempts
- Debug notification failures
- Track notification delivery status
- Monitor notification performance

---

## Database Schema Diagram

```
vtiger_users
    │
    ├── user_fcm_tokens (user_id → id)
    │   └── Stores device FCM tokens
    │
    ├── refresh_tokens (user_id → id)
    │   └── Stores JWT refresh tokens
    │
    └── notification_logs (user_id → id)
        └── Logs notification attempts
```

---

## Installation Steps

1. **Backup your database** (recommended)

2. **Run the migration SQL:**
   ```bash
   mysql -u username -p database_name < database/migrations/create_fcm_refresh_tokens_tables.sql
   ```
   
   Or execute the SQL file directly in your MySQL client.

3. **Verify tables were created:**
   ```sql
   SHOW TABLES LIKE '%fcm%';
   SHOW TABLES LIKE '%refresh%';
   SHOW TABLES LIKE '%notification_log%';
   ```

4. **Check table structure:**
   ```sql
   DESCRIBE user_fcm_tokens;
   DESCRIBE refresh_tokens;
   DESCRIBE notification_logs;
   ```

---

## Maintenance Queries

### Clean up expired refresh tokens:
```sql
DELETE FROM refresh_tokens 
WHERE expires_at < NOW() OR is_revoked = 1;
```

### Find inactive FCM tokens:
```sql
SELECT * FROM user_fcm_tokens 
WHERE is_active = 0;
```

### View notification logs for a user:
```sql
SELECT * FROM notification_logs 
WHERE user_id = 123 
ORDER BY created_at DESC 
LIMIT 50;
```

### Count notifications by status:
```sql
SELECT status, COUNT(*) as count 
FROM notification_logs 
GROUP BY status;
```

### Find users with multiple devices:
```sql
SELECT user_id, COUNT(*) as device_count 
FROM user_fcm_tokens 
WHERE is_active = 1 
GROUP BY user_id 
HAVING device_count > 1;
```

---

## Notes

- All tables use `utf8mb4` charset for better Unicode support
- All tables use `InnoDB` engine for transaction support
- Foreign key relationships are logical (not enforced) to maintain compatibility with existing Vtiger schema
- Indexes are optimized for common query patterns
- Timestamps are automatically managed by MySQL
