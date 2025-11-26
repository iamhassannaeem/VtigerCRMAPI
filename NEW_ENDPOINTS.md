# New API Endpoints Documentation

This document describes all the newly created API endpoints for Organizations, Contacts, and Picklists.

## Summary of Changes

### New Files Created:
1. `models/Organization.php` - Organization/Account data model
2. `models/Contact.php` - Contact data model
3. `models/Picklist.php` - Picklist data model
4. `controllers/OrganizationController.php` - Organization endpoints
5. `controllers/ContactController.php` - Contact endpoints
6. `controllers/PicklistController.php` - Picklist endpoints

### Files Modified:
1. `public/index.php` - Added routes for all new endpoints
2. `POSTMAN_COLLECTION.json` - Added Postman examples
3. `API_EXAMPLES.md` - Added documentation and CURL examples

---

## Organizations API

### 1. List All Organizations
**Endpoint:** `GET /organizations`

**Query Parameters:**
- `search` (optional) - Search in name, email, phone, account number
- `account_type` (optional) - Filter by account type
- `industry` (optional) - Filter by industry
- `limit` (optional, default: 50) - Number of results
- `offset` (optional, default: 0) - Pagination offset

**Example:**
```bash
curl -X GET "http://localhost/vtiger-api/public/organizations?search=tech&limit=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 2. Get Organization by ID
**Endpoint:** `GET /organizations/{id}`

**Example:**
```bash
curl -X GET http://localhost/vtiger-api/public/organizations/102 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Contacts API

### 1. List All Contacts
**Endpoint:** `GET /contacts`

**Query Parameters:**
- `account_id` (optional) - **Filter contacts by organization ID**
- `search` (optional) - Search in name, email, phone
- `limit` (optional, default: 50) - Number of results
- `offset` (optional, default: 0) - Pagination offset

**Example:**
```bash
# Get all contacts for organization ID 102
curl -X GET "http://localhost/vtiger-api/public/contacts?account_id=102" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Search contacts
curl -X GET "http://localhost/vtiger-api/public/contacts?search=john" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 2. Get Contact by ID
**Endpoint:** `GET /contacts/{id}`

**Example:**
```bash
curl -X GET http://localhost/vtiger-api/public/contacts/250 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Picklists API

All picklist endpoints return a list of options with id, value, color, and sort order.

### 1. Get Priorities
**Endpoint:** `GET /picklists/priorities`

Returns ticket priority options (Low, Normal, High, Urgent)

**Example:**
```bash
curl -X GET http://localhost/vtiger-api/public/picklists/priorities \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 2. Get Services Issue
**Endpoint:** `GET /picklists/services-issue`

Returns custom picklist options for Services Issue field (cf_869)

**Example:**
```bash
curl -X GET http://localhost/vtiger-api/public/picklists/services-issue \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. Get Statuses
**Endpoint:** `GET /picklists/statuses`

Returns ticket status options (Open, In Progress, Wait For Response, Closed)

**Example:**
```bash
curl -X GET http://localhost/vtiger-api/public/picklists/statuses \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 4. Get Users
**Endpoint:** `GET /picklists/users`

Returns list of active users for ticket assignment

**Example:**
```bash
curl -X GET http://localhost/vtiger-api/public/picklists/users \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 5. Get Issue End
**Endpoint:** `GET /picklists/issue-end`

Returns custom picklist options for Issue End field (cf_871)

**Example:**
```bash
curl -X GET http://localhost/vtiger-api/public/picklists/issue-end \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 6. Get Categories
**Endpoint:** `GET /picklists/categories`

Returns ticket category options

**Example:**
```bash
curl -X GET http://localhost/vtiger-api/public/picklists/categories \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 7. Get Severities
**Endpoint:** `GET /picklists/severities`

Returns ticket severity options (Minor, Major, Feature, Critical)

**Example:**
```bash
curl -X GET http://localhost/vtiger-api/public/picklists/severities \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Response Format

All picklist endpoints return data in this format:

```json
{
  "success": true,
  "count": 4,
  "data": [
    {
      "id": 1,
      "value": "Option Name",
      "color": "#FF0000",
      "sortorderid": 0
    }
  ]
}
```

---

## Authentication

All endpoints (except `/auth/login`) require JWT authentication via the `Authorization` header:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

Get your token by calling the login endpoint:

```bash
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"connectel@123"}'
```

---

## Complete Endpoint List

### Organizations
- `GET /organizations` - List all organizations with search
- `GET /organizations/{id}` - Get organization details

### Contacts
- `GET /contacts` - List all contacts (filter by account_id)
- `GET /contacts/{id}` - Get contact details

### Picklists
- `GET /picklists/priorities` - Get ticket priorities
- `GET /picklists/services-issue` - Get services issue options
- `GET /picklists/statuses` - Get ticket statuses
- `GET /picklists/users` - Get active users
- `GET /picklists/issue-end` - Get issue end options
- `GET /picklists/categories` - Get ticket categories
- `GET /picklists/severities` - Get ticket severities

---

## Testing

You can test all endpoints using:

1. **Postman** - Import the `POSTMAN_COLLECTION.json` file
2. **CURL** - Use the examples above
3. **Browser/API Client** - Any HTTP client

Remember to:
1. Login first to get your JWT token
2. Include the token in the Authorization header for all protected endpoints
3. Check the response for success/error messages


