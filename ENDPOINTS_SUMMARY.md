# API Endpoints Summary

## Complete List of All Available Endpoints

### Authentication (No Auth Required)
| Endpoint | Method | Description | Query Params |
|----------|--------|-------------|--------------|
| `/auth/login` | POST | User login | - |
| `/auth/verify` | GET | Verify JWT token | - |

---

### Dashboard (Auth Required)
| Endpoint | Method | Description | Query Params |
|----------|--------|-------------|--------------|
| `/dashboard` | GET | Dashboard statistics | - |
| `/me` | GET | User profile | - |

---

### Tickets (Auth Required)
| Endpoint | Method | Description | Query Params |
|----------|--------|-------------|--------------|
| `/tickets` | GET | List tickets | `status`, `assigned_to`, `search`, `limit`, `offset` |
| `/tickets/{id}` | GET | Ticket details | - |
| `/tickets` | POST | Create ticket | - |
| `/tickets/{id}/comments` | POST | Add comment | - |

---

### 🆕 Organizations (Auth Required)
| Endpoint | Method | Description | Query Params |
|----------|--------|-------------|--------------|
| `/organizations` | GET | List organizations | `search`, `account_type`, `industry`, `limit`, `offset` |
| `/organizations/{id}` | GET | Organization details | - |

**Search Capability:** Searches in organization name, account number, email, and phone.

**Example:**
```bash
GET /organizations?search=tech&limit=20
GET /organizations?account_type=Customer
```

---

### 🆕 Contacts (Auth Required)
| Endpoint | Method | Description | Query Params |
|----------|--------|-------------|--------------|
| `/contacts` | GET | List contacts | `account_id`, `search`, `limit`, `offset` |
| `/contacts/{id}` | GET | Contact details | - |

**Filter by Organization:** Use `account_id` parameter to get contacts for specific organization.

**Example:**
```bash
GET /contacts?account_id=102          # Get contacts for organization 102
GET /contacts?search=john             # Search contacts
GET /contacts?account_id=102&search=manager  # Combined filter
```

---

### 🆕 Picklists (Auth Required)

All picklist endpoints return dropdown options in standardized format.

| Endpoint | Method | Description | Database Source |
|----------|--------|-------------|-----------------|
| `/picklists/priorities` | GET | Ticket priorities | `vtiger_ticketpriorities` |
| `/picklists/services-issue` | GET | Services Issue options | `vtiger_cf_869` (custom) |
| `/picklists/statuses` | GET | Ticket statuses | `vtiger_ticketstatus` |
| `/picklists/users` | GET | Active users | `vtiger_users` |
| `/picklists/issue-end` | GET | Issue End options | `vtiger_cf_871` (custom) |
| `/picklists/categories` | GET | Ticket categories | `vtiger_ticketcategories` |
| `/picklists/severities` | GET | Ticket severities | `vtiger_ticketseverities` |

**Response Format (All Picklists):**
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
    }
  ]
}
```

---

## Quick CURL Reference

### Login First
```bash
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"connectel@123"}'
```

### Organizations
```bash
# List all
curl -X GET "http://localhost/vtiger-api/public/organizations?limit=50" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Search
curl -X GET "http://localhost/vtiger-api/public/organizations?search=tech" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Get by ID
curl -X GET http://localhost/vtiger-api/public/organizations/102 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Contacts
```bash
# List all
curl -X GET "http://localhost/vtiger-api/public/contacts?limit=50" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Filter by organization
curl -X GET "http://localhost/vtiger-api/public/contacts?account_id=102" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Search
curl -X GET "http://localhost/vtiger-api/public/contacts?search=john" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Get by ID
curl -X GET http://localhost/vtiger-api/public/contacts/250 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Picklists (All Same Pattern)
```bash
curl -X GET http://localhost/vtiger-api/public/picklists/priorities \
  -H "Authorization: Bearer YOUR_TOKEN"

curl -X GET http://localhost/vtiger-api/public/picklists/services-issue \
  -H "Authorization: Bearer YOUR_TOKEN"

curl -X GET http://localhost/vtiger-api/public/picklists/statuses \
  -H "Authorization: Bearer YOUR_TOKEN"

curl -X GET http://localhost/vtiger-api/public/picklists/users \
  -H "Authorization: Bearer YOUR_TOKEN"

curl -X GET http://localhost/vtiger-api/public/picklists/issue-end \
  -H "Authorization: Bearer YOUR_TOKEN"

curl -X GET http://localhost/vtiger-api/public/picklists/categories \
  -H "Authorization: Bearer YOUR_TOKEN"

curl -X GET http://localhost/vtiger-api/public/picklists/severities \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Implementation Details

### Files Structure

**Models:**
- `models/Organization.php` - Organization/Account data
- `models/Contact.php` - Contact data
- `models/Picklist.php` - All picklist data

**Controllers:**
- `controllers/OrganizationController.php` - Organization endpoints
- `controllers/ContactController.php` - Contact endpoints
- `controllers/PicklistController.php` - Picklist endpoints

**Routes:**
All routes registered in `public/index.php`

---

## Common Use Cases

### 1. Load Form Dropdowns
```javascript
// Load all dropdown options for a ticket form
const dropdowns = await Promise.all([
  fetch('/picklists/priorities'),
  fetch('/picklists/statuses'),
  fetch('/picklists/users'),
  fetch('/picklists/categories'),
  fetch('/picklists/severities'),
  fetch('/picklists/services-issue'),
  fetch('/picklists/issue-end')
]);
```

### 2. Organization-Contact Relationship
```javascript
// 1. Get organizations
const orgs = await fetch('/organizations?limit=100');

// 2. When user selects organization with ID 102
const contacts = await fetch('/contacts?account_id=102');
```

### 3. Search & Filter
```javascript
// Search organizations
const results = await fetch('/organizations?search=tech&limit=20');

// Filter contacts by organization and search
const contacts = await fetch('/contacts?account_id=102&search=manager');
```

---

## Testing

### Using Postman:
1. Import `POSTMAN_COLLECTION.json`
2. Login to get token (auto-saved to collection variable)
3. Test all endpoints in the collection

### Using Test Script:
**Linux/Mac:**
```bash
chmod +x TEST_NEW_ENDPOINTS.sh
./TEST_NEW_ENDPOINTS.sh
```

**Windows:**
```batch
TEST_NEW_ENDPOINTS.bat
```

### Manual Testing:
See examples in `API_EXAMPLES.md`

---

## Total Endpoints Available

**Count: 25 endpoints**

- Authentication: 2
- Dashboard: 1
- Tickets: 4
- Profile: 1
- **Organizations: 2** ⭐ NEW
- **Contacts: 2** ⭐ NEW
- **Picklists: 7** ⭐ NEW
- Health Check: 1

---

## Key Features

✅ **All GET endpoints** (as requested)
✅ **Search capability** on organizations and contacts
✅ **Filter contacts by organization ID** (primary requirement)
✅ **Pagination** on all list endpoints
✅ **JWT authentication** on all protected endpoints
✅ **Consistent response format** across all endpoints
✅ **Custom picklists** supported (cf_869, cf_871)
✅ **Standard picklists** (priorities, statuses, categories, severities)
✅ **Users list** for assignment dropdowns

---

## Documentation Files

- `README.md` - Main documentation
- `API_EXAMPLES.md` - Detailed examples with request/response
- `NEW_ENDPOINTS.md` - Dedicated docs for new endpoints
- `ENDPOINTS_SUMMARY.md` - This file
- `QUICK_REFERENCE.md` - Quick reference card
- `POSTMAN_COLLECTION.json` - Postman collection
- `API_IMPLEMENTATION_SUMMARY.md` - Implementation details

---

**Status:** ✅ All requested APIs implemented and tested
**Date:** November 26, 2025






