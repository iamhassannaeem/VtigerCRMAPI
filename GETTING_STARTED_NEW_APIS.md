# Getting Started with New APIs

## 🎉 What's New?

You now have **14 new API endpoints** for Organizations, Contacts, and Picklists!

---

## ⚡ Quick Start (3 Steps)

### Step 1: Login
```bash
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"connectel@123"}'
```

**Copy the token** from the response.

### Step 2: Test Organizations API
```bash
curl -X GET "http://localhost/vtiger-api/public/organizations?limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Step 3: Test Contacts API (Filter by Organization)
```bash
curl -X GET "http://localhost/vtiger-api/public/contacts?account_id=102" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 📋 All New Endpoints

### Organizations (2 endpoints)
```bash
# List all organizations
GET /organizations?search=tech&limit=20

# Get organization by ID
GET /organizations/102
```

### Contacts (2 endpoints)
```bash
# List all contacts
GET /contacts?limit=50

# Filter contacts by organization (KEY FEATURE!)
GET /contacts?account_id=102

# Search contacts
GET /contacts?search=john

# Combined filter
GET /contacts?account_id=102&search=manager
```

### Picklists (7 endpoints)
```bash
GET /picklists/priorities      # Ticket priorities
GET /picklists/services-issue  # Services Issue options
GET /picklists/statuses        # Ticket statuses
GET /picklists/users           # Active users (for assignment)
GET /picklists/issue-end       # Issue End options
GET /picklists/categories      # Ticket categories
GET /picklists/severities      # Ticket severities
```

---

## 🎯 Common Use Cases

### Use Case 1: Load Organization and Contacts
```bash
# 1. Get organizations list
curl -X GET "http://localhost/vtiger-api/public/organizations" \
  -H "Authorization: Bearer $TOKEN"

# 2. Get contacts for organization ID 102
curl -X GET "http://localhost/vtiger-api/public/contacts?account_id=102" \
  -H "Authorization: Bearer $TOKEN"
```

### Use Case 2: Search Organizations
```bash
# Search for organizations containing "tech"
curl -X GET "http://localhost/vtiger-api/public/organizations?search=tech" \
  -H "Authorization: Bearer $TOKEN"

# Filter by account type
curl -X GET "http://localhost/vtiger-api/public/organizations?account_type=Customer" \
  -H "Authorization: Bearer $TOKEN"
```

### Use Case 3: Load Dropdown Options
```bash
# Get all dropdown options for a form
curl -X GET "http://localhost/vtiger-api/public/picklists/priorities" \
  -H "Authorization: Bearer $TOKEN"

curl -X GET "http://localhost/vtiger-api/public/picklists/statuses" \
  -H "Authorization: Bearer $TOKEN"

curl -X GET "http://localhost/vtiger-api/public/picklists/users" \
  -H "Authorization: Bearer $TOKEN"
```

---

## 📱 Frontend Integration Example

### JavaScript/TypeScript Example:

```javascript
// 1. Login
const loginResponse = await fetch('http://localhost/vtiger-api/public/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    username: 'admin',
    password: 'connectel@123'
  })
});
const { token } = await loginResponse.json();

// 2. Load Organizations
const orgsResponse = await fetch('http://localhost/vtiger-api/public/organizations?limit=100', {
  headers: { 'Authorization': `Bearer ${token}` }
});
const { data: organizations } = await orgsResponse.json();

// 3. When user selects an organization
const selectedOrgId = 102;
const contactsResponse = await fetch(
  `http://localhost/vtiger-api/public/contacts?account_id=${selectedOrgId}`,
  { headers: { 'Authorization': `Bearer ${token}` } }
);
const { data: contacts } = await contactsResponse.json();

// 4. Load picklists for form dropdowns
const [priorities, statuses, users] = await Promise.all([
  fetch('http://localhost/vtiger-api/public/picklists/priorities', 
    { headers: { 'Authorization': `Bearer ${token}` } }),
  fetch('http://localhost/vtiger-api/public/picklists/statuses',
    { headers: { 'Authorization': `Bearer ${token}` } }),
  fetch('http://localhost/vtiger-api/public/picklists/users',
    { headers: { 'Authorization': `Bearer ${token}` } })
]);
```

---

## 🧪 Testing

### Option 1: Postman (Recommended)
1. Open Postman
2. Import `POSTMAN_COLLECTION.json`
3. Run "Login" request (token is auto-saved)
4. Test all new endpoints in "Organizations", "Contacts", and "Picklists" folders

### Option 2: Test Script
**Windows:**
```batch
TEST_NEW_ENDPOINTS.bat
```

**Linux/Mac:**
```bash
chmod +x TEST_NEW_ENDPOINTS.sh
./TEST_NEW_ENDPOINTS.sh
```

### Option 3: Manual cURL
See examples in `API_EXAMPLES.md`

---

## 📊 Response Format

### Success Response
```json
{
  "success": true,
  "count": 2,
  "data": [
    { "id": 1, "name": "Item 1" },
    { "id": 2, "name": "Item 2" }
  ]
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description here"
}
```

---

## 🔍 Query Parameters

### Organizations
- `search` - Search in name, email, phone, account number
- `account_type` - Filter by account type
- `industry` - Filter by industry
- `limit` - Results per page (default: 50)
- `offset` - Pagination offset (default: 0)

### Contacts
- `account_id` - **Filter by organization ID** ⭐
- `search` - Search in name, email, phone
- `limit` - Results per page (default: 50)
- `offset` - Pagination offset (default: 0)

### Picklists
No query parameters needed - returns all active options.

---

## 💡 Pro Tips

### 1. Pagination Example
```bash
# Get first 20 records
GET /organizations?limit=20&offset=0

# Get next 20 records
GET /organizations?limit=20&offset=20

# Get records 41-60
GET /organizations?limit=20&offset=40
```

### 2. Combined Filters
```bash
# Organization by type and search
GET /organizations?account_type=Customer&search=tech

# Contacts by organization and search
GET /contacts?account_id=102&search=manager
```

### 3. Cache Picklists
Picklist data doesn't change frequently, so cache it in your frontend:
```javascript
// Load once on app initialization
const picklists = {
  priorities: await loadPicklist('priorities'),
  statuses: await loadPicklist('statuses'),
  users: await loadPicklist('users'),
  // ... etc
};

// Reuse throughout the app
```

---

## 🚨 Common Issues & Solutions

### Issue 1: "No token provided"
**Solution:** Make sure you include the Authorization header:
```
Authorization: Bearer YOUR_TOKEN
```

### Issue 2: "Invalid or expired token"
**Solution:** Login again to get a fresh token (expires after 24 hours)

### Issue 3: Empty data array
**Solution:** Check if there's data in the database:
```sql
SELECT COUNT(*) FROM vtiger_account WHERE deleted = 0;
SELECT COUNT(*) FROM vtiger_contactdetails cd 
JOIN vtiger_crmentity ce ON cd.contactid = ce.crmid 
WHERE ce.deleted = 0;
```

### Issue 4: 404 Not Found
**Solution:** Check mod_rewrite is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## 📖 Documentation

For more details, check:

- **NEW_ENDPOINTS.md** - Detailed documentation for new APIs
- **API_EXAMPLES.md** - Complete request/response examples
- **ENDPOINTS_SUMMARY.md** - Complete endpoint reference
- **POSTMAN_COLLECTION.json** - Import into Postman
- **API_IMPLEMENTATION_SUMMARY.md** - Technical implementation details

---

## ✅ Checklist

Before using the new APIs:

- [ ] Server is running (Apache/PHP)
- [ ] Database is accessible
- [ ] Login works and returns token
- [ ] Token is included in Authorization header
- [ ] mod_rewrite is enabled
- [ ] Config files are set up correctly

---

## 🎯 Next Steps

1. **Test the APIs** using Postman or cURL
2. **Read the documentation** in NEW_ENDPOINTS.md
3. **Integrate with your frontend** application
4. **Use the test scripts** to verify everything works

---

**All APIs are ready to use!** 🚀

For questions, check the comprehensive documentation files or test using the Postman collection.


