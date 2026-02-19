# Database Schema Documentation - vconn

## Database Connection Details
- **Host**: localhost
- **Database**: vconn
- **Port**: 3306
- **Charset**: utf8mb4
- **Engine**: InnoDB

---

## Core Tables

### 1. vtiger_users
**Purpose**: Stores user account information and authentication data

**Key Columns**:
- `id` (int, PRIMARY KEY, AUTO_INCREMENT) - User ID
- `user_name` (varchar(255)) - Username for login
- `user_password` (varchar(200)) - Encrypted password
- `first_name`, `last_name` (varchar(30)) - User's name
- `email1`, `email2` (varchar(100)) - Email addresses
- `is_admin` (varchar(3)) - 'on' for admin, '0' for regular user
- `status` (varchar(25)) - 'Active' or 'Inactive'
- `deleted` (int) - 0 = active, 1 = deleted
- `department` (varchar(50)) - User's department

**Important Notes**:
- Active users have `deleted = 0` AND `status = 'Active'`
- Password is stored as MD5 hash or crypt format
- Used for authentication and user identification

---

### 2. vtiger_role
**Purpose**: Defines roles in the system

**Key Columns**:
- `roleid` (varchar(255), PRIMARY KEY) - Unique role identifier
- `rolename` (varchar(200)) - Role name (e.g., "Fiber Manager - 1", "Fiber Team Lead - 1")
- `parentrole` (varchar(255)) - Parent role for hierarchy
- `depth` (int) - Role hierarchy depth

**Important Notes**:
- Role names are used for permission checking
- Examples: "Fiber Manager - 1", "Fiber Team Lead - 1", "CEO", "Sales Manager"

---

### 3. vtiger_user2role
**Purpose**: Maps users to their roles (many-to-one relationship)

**Key Columns**:
- `userid` (int, PRIMARY KEY) - References vtiger_users.id
- `roleid` (varchar(255)) - References vtiger_role.roleid

**Important Notes**:
- Each user has exactly one role
- Used to determine user permissions and dashboard type

**Query Example**:
```sql
SELECT u.id, u.user_name, r.rolename
FROM vtiger_users u
INNER JOIN vtiger_user2role u2r ON u.id = u2r.userid
INNER JOIN vtiger_role r ON u2r.roleid = r.roleid
WHERE u.deleted = 0 AND u.status = 'Active'
```

---

### 4. vtiger_troubletickets
**Purpose**: Stores ticket/trouble ticket information

**Key Columns**:
- `ticketid` (int, PRIMARY KEY) - Ticket ID (references vtiger_crmentity.crmid)
- `ticket_no` (varchar(100)) - Ticket number
- `title` (varchar(255)) - Ticket title
- `status` (varchar(200)) - Ticket status
- `priority` (varchar(200)) - Ticket priority
- `severity` (varchar(200)) - Ticket severity
- `category` (varchar(200)) - Ticket category
- `parent_id` (varchar(100)) - Account ID (references vtiger_account.accountid)
- `contact_id` (int) - Contact ID (references vtiger_contactdetails.contactid)
- `solution` (text) - Solution description
- `update_log` (text) - Update history

**Important Notes**:
- `ticketid` must exist in `vtiger_crmentity` table
- `parent_id` links to account/customer
- `contact_id` links to contact person

---

### 5. vtiger_crmentity
**Purpose**: Core entity table - tracks all CRM records (tickets, accounts, contacts, etc.)

**Key Columns**:
- `crmid` (int, PRIMARY KEY) - Entity ID (matches ticketid, accountid, contactid, etc.)
- `smownerid` (int) - Assigned user ID (references vtiger_users.id)
- `smcreatorid` (int) - Creator user ID
- `setype` (varchar(100)) - Entity type (e.g., 'HelpDesk' for tickets)
- `createdtime` (datetime) - Creation timestamp
- `modifiedtime` (datetime) - Last modification timestamp
- `deleted` (int) - 0 = active, 1 = deleted

**Important Notes**:
- **CRITICAL**: All tickets must have a corresponding entry here
- `smownerid` determines who the ticket is assigned to
- Used for filtering tickets by assigned user
- `deleted = 0` means the record is active

**Query Example** (Get tickets assigned to a user):
```sql
SELECT tt.*, ce.smownerid, ce.createdtime
FROM vtiger_troubletickets tt
INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
WHERE ce.smownerid = ? AND ce.deleted = 0
```

---

### 6. vtiger_account
**Purpose**: Stores account/customer information

**Key Columns**:
- `accountid` (int, PRIMARY KEY) - Account ID (references vtiger_crmentity.crmid)
- `accountname` (varchar(100)) - Account/customer name
- `account_no` (varchar(100)) - Account number
- `email1`, `email2` (varchar(100)) - Email addresses
- `phone` (varchar(30)) - Phone number
- `website` (varchar(100)) - Website URL

**Important Notes**:
- Linked to tickets via `vtiger_troubletickets.parent_id`
- Used for Sales role filtering (tickets from their customers)

---

### 7. vtiger_contactdetails
**Purpose**: Stores contact person information

**Key Columns**:
- `contactid` (int, PRIMARY KEY) - Contact ID (references vtiger_crmentity.crmid)
- `firstname`, `lastname` (varchar) - Contact name
- `email` (varchar(100)) - Email address
- `phone`, `mobile` (varchar(50)) - Phone numbers
- `accountid` (int) - Related account ID (references vtiger_account.accountid)

**Important Notes**:
- Linked to tickets via `vtiger_troubletickets.contact_id`
- Can be linked to an account

---

## Key Relationships

### User → Role
```
vtiger_users.id → vtiger_user2role.userid → vtiger_user2role.roleid → vtiger_role.roleid
```

### Ticket → User (Assigned To)
```
vtiger_troubletickets.ticketid → vtiger_crmentity.crmid → vtiger_crmentity.smownerid → vtiger_users.id
```

### Ticket → Account
```
vtiger_troubletickets.parent_id → vtiger_account.accountid
```

### Ticket → Contact
```
vtiger_troubletickets.contact_id → vtiger_contactdetails.contactid
```

### Account → User (Sales Assignment)
```
vtiger_account.accountid → vtiger_crmentity.crmid → vtiger_crmentity.smownerid → vtiger_users.id
```

---

## Common Queries

### Get User's Role
```sql
SELECT r.rolename
FROM vtiger_users u
INNER JOIN vtiger_user2role u2r ON u.id = u2r.userid
INNER JOIN vtiger_role r ON u2r.roleid = r.roleid
WHERE u.id = ? AND u.deleted = 0
LIMIT 1
```

### Get All Users with a Specific Role
```sql
SELECT DISTINCT u.id, u.user_name, u.first_name, u.last_name
FROM vtiger_users u
INNER JOIN vtiger_user2role u2r ON u.id = u2r.userid
INNER JOIN vtiger_role r ON u2r.roleid = r.roleid
WHERE r.rolename = ? AND u.deleted = 0 AND u.status = 'Active'
```

### Get Tickets Assigned to User
```sql
SELECT tt.ticketid, tt.ticket_no, tt.title, tt.status, ce.createdtime
FROM vtiger_troubletickets tt
INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
WHERE ce.smownerid = ? AND ce.deleted = 0
```

### Get Tickets Assigned to Multiple Users (for Managers)
```sql
SELECT tt.ticketid, tt.ticket_no, tt.title, tt.status, ce.smownerid
FROM vtiger_troubletickets tt
INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
WHERE ce.smownerid IN (?, ?, ?) AND ce.deleted = 0
```

---

## Role-Based Access Patterns

### Fiber Manager Access
- Manager role: "Fiber Manager - 1" or "Fiber Manager - 2"
- Can see tickets assigned to:
  1. Themselves (their own user ID)
  2. All users with corresponding "Fiber Team Lead - X" role

### Sales User Access
- Can see tickets assigned to:
  1. Themselves
  2. Tickets from accounts assigned to them (via vtiger_account → vtiger_crmentity.smownerid)

### Admin/NOC/Management Access
- Can see all tickets (no filtering by smownerid)

---

## Important Indexes

- `vtiger_crmentity.crm_ownerid_del_setype_idx` - Optimizes ticket queries by owner
- `vtiger_crmentity.deleted_idx` - Fast filtering of active records
- `vtiger_user2role.roleid_idx` - Fast role lookups
- `vtiger_troubletickets.status_idx` - Fast status filtering

---

## Notes for API Development

1. **Always check `deleted = 0`** when querying active records
2. **Always check `status = 'Active'`** for users
3. **Use `vtiger_crmentity.smownerid`** to determine ticket assignment
4. **Role names are case-sensitive** - use exact matches
5. **User-Role relationship is 1:1** (one user, one role)
6. **Ticket assignment is via `smownerid`**, not directly in troubletickets table

---

**Last Updated**: Based on vconn.sql dump from MySQL 8.0.41
