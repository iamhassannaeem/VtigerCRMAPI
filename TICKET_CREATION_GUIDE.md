# Ticket Creation API - Complete Field Guide

## 📝 POST /tickets - Create New Ticket

### Endpoint
```
POST /tickets
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

---

## 📋 All Available Fields

### Required Fields
| Field | Type | Description | Example |
|-------|------|-------------|---------|
| `title` | string | Ticket title/subject | "Email configuration issue" |

### Basic Information Fields
| Field | Type | Description | Default | Example |
|-------|------|-------------|---------|---------|
| `description` | string | Detailed description | "" | "Users cannot send emails..." |
| `solution` | string | Solution/resolution notes | "" | "Updated SMTP settings" |
| `status` | string | Ticket status | "Open" | "Open", "In Progress", "Closed" |
| `priority` | string | Ticket priority | "Normal" | "Low", "Normal", "High", "Urgent" |
| `category` | string | Ticket category | "" | "Technical", "Support" |
| `severity` | string | Ticket severity | "" | "Minor", "Major", "Critical" |

### Relationship Fields
| Field | Type | Description | Default | Example |
|-------|------|-------------|---------|---------|
| `account_id` | integer | Organization ID | null | 102 |
| `contact_id` | integer | Contact ID | null | 250 |
| `assigned_to` | integer | Assigned user ID | Current user | 5 |

### Time Tracking Fields
| Field | Type | Description | Default | Example |
|-------|------|-------------|---------|---------|
| `hours` | decimal | Hours spent | null | 3.5 |
| `days` | decimal | Days spent | null | 1.0 |
| `total_time_minutes` | integer | Total time in minutes | null | 210 |
| `end_time` | time | End time | null | "14:30:00" |
| `closing_time` | string | Closing time | "" | "2024-01-15 16:00:00" |

### Custom Fields (Organization & Issue)
| Field | Type | DB Column | Description | Example |
|-------|------|-----------|-------------|---------|
| `organization_priority` | string | cf_867 | Organization priority | "High", "Medium", "Low" |
| `services_issue` | string | cf_869 | Type of service issue | "Network Issue", "Software Bug" |
| `issue_end` | string | cf_871 | How issue was resolved | "Resolved", "Workaround" |

### Notification Checkboxes
| Field | Type | DB Column | Description | Values |
|-------|------|-----------|-------------|--------|
| `send_on_confirmation` | string | cf_875 | Send confirmation | "1" or "0" |
| `email_sms_assigned_to` | string | cf_877 | Email/SMS to assignee | "1" or "0" |
| `email_sms_to_customer` | string | cf_897 | Email/SMS to customer | "1" or "0" |

### Other Fields
| Field | Type | DB Column | Description | Values |
|-------|------|-----------|-------------|--------|
| `from_portal` | string | from_portal | Created from portal | "1" or "0" |

---

## 📤 Complete Request Example

### Full Request with All Fields

```json
{
  "title": "Email Configuration Problem",
  "description": "Users are unable to send emails from the CRM system. Getting SMTP connection timeout error.",
  "solution": "Updated SMTP server settings and tested successfully.",
  
  "status": "Open",
  "priority": "High",
  "category": "Technical",
  "severity": "Major",
  
  "account_id": 102,
  "contact_id": 250,
  "assigned_to": 5,
  
  "hours": 3.5,
  "days": 0.5,
  "total_time_minutes": 210,
  "end_time": "14:30:00",
  "closing_time": "2024-01-15 16:00:00",
  
  "organization_priority": "High",
  "services_issue": "Email System",
  "issue_end": "Resolved",
  
  "send_on_confirmation": "1",
  "email_sms_assigned_to": "1",
  "email_sms_to_customer": "1",
  
  "from_portal": "0"
}
```

### Minimal Request (Only Required Field)

```json
{
  "title": "Simple ticket"
}
```

### Typical Request (Common Fields)

```json
{
  "title": "Cannot access dashboard",
  "description": "User reports 404 error when accessing dashboard",
  "priority": "High",
  "category": "Technical",
  "account_id": 102,
  "contact_id": 250,
  "assigned_to": 5,
  "services_issue": "Software Bug",
  "email_sms_to_customer": "1"
}
```

---

## 🔍 Field Details

### Organization Name (account_id)
- **Purpose:** Link ticket to an organization/account
- **How to get:** Use `GET /organizations` API
- **Example:** `"account_id": 102`

### Contact Name (contact_id)
- **Purpose:** Link ticket to a contact person
- **How to get:** Use `GET /contacts?account_id=102` API
- **Example:** `"contact_id": 250`

### Priority
- **Purpose:** Set ticket priority
- **How to get:** Use `GET /picklists/priorities` API
- **Values:** "Low", "Normal", "High", "Urgent"
- **Example:** `"priority": "High"`

### Status
- **Purpose:** Set ticket status
- **How to get:** Use `GET /picklists/statuses` API
- **Values:** "Open", "In Progress", "Wait For Response", "Closed"
- **Default:** "Open"
- **Example:** `"status": "Open"`

### Assigned To
- **Purpose:** Assign ticket to a user
- **How to get:** Use `GET /picklists/users` API
- **Example:** `"assigned_to": 5`

### Services Issue (cf_869)
- **Purpose:** Categorize the type of service issue
- **How to get:** Use `GET /picklists/services-issue` API
- **Custom Field:** Yes (cf_869)
- **Example:** `"services_issue": "Network Issue"`

### Issue End (cf_871)
- **Purpose:** How the issue was resolved
- **How to get:** Use `GET /picklists/issue-end` API
- **Custom Field:** Yes (cf_871)
- **Example:** `"issue_end": "Resolved"`

### Organization Priority (cf_867)
- **Purpose:** Priority level for the organization
- **How to get:** Use `GET /picklists/organization-priority` API
- **Custom Field:** Yes (cf_867)
- **Example:** `"organization_priority": "High"`

### Category
- **Purpose:** Ticket category
- **How to get:** Use `GET /picklists/categories` API
- **Example:** `"category": "Technical"`

### Severity
- **Purpose:** Issue severity
- **How to get:** Use `GET /picklists/severities` API
- **Values:** "Minor", "Major", "Feature", "Critical"
- **Example:** `"severity": "Major"`

### Checkboxes (1 = checked, 0 = unchecked)
- `send_on_confirmation`: Send notification on confirmation
- `email_sms_assigned_to`: Send email/SMS to assigned user
- `email_sms_to_customer`: Send email/SMS to customer
- **Values:** "1" (checked) or "0" (unchecked)

### Time Fields
- `end_time`: Time in HH:MM:SS format (e.g., "14:30:00")
- `closing_time`: Timestamp or datetime string
- `hours`: Decimal hours spent (e.g., 3.5)
- `days`: Decimal days spent (e.g., 0.5)
- `total_time_minutes`: Integer minutes (e.g., 210)

---

## 🧪 CURL Examples

### Example 1: Complete Ticket with All Fields

```bash
curl -X POST http://localhost/vtiger-api/public/tickets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Email Configuration Problem",
    "description": "SMTP timeout error",
    "solution": "Updated settings",
    "status": "Open",
    "priority": "High",
    "category": "Technical",
    "severity": "Major",
    "account_id": 102,
    "contact_id": 250,
    "assigned_to": 5,
    "hours": 3.5,
    "organization_priority": "High",
    "services_issue": "Email System",
    "issue_end": "Resolved",
    "email_sms_to_customer": "1",
    "send_on_confirmation": "1"
  }'
```

### Example 2: Minimal Ticket

```bash
curl -X POST http://localhost/vtiger-api/public/tickets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Quick test ticket"
  }'
```

### Example 3: Typical Support Ticket

```bash
curl -X POST http://localhost/vtiger-api/public/tickets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Cannot access reports",
    "description": "User getting 404 error on reports page",
    "priority": "High",
    "category": "Technical",
    "account_id": 102,
    "contact_id": 250,
    "assigned_to": 5,
    "services_issue": "Software Bug",
    "email_sms_to_customer": "1"
  }'
```

---

## ✅ Success Response

```json
{
  "success": true,
  "message": "Ticket created successfully",
  "data": {
    "success": true,
    "ticket_id": 490186,
    "ticket_no": "TT0001"
  }
}
```

---

## ❌ Error Responses

### Missing Title
```json
{
  "success": false,
  "message": "Title is required"
}
```

### Invalid Token
```json
{
  "success": false,
  "message": "Invalid or expired token"
}
```

### Server Error
```json
{
  "success": false,
  "message": "Server error: Query failed..."
}
```

---

## 🎯 Workflow for Frontend

### Step 1: Load Dropdown Options (on page load)

```javascript
const dropdowns = await Promise.all([
  fetch('/picklists/priorities'),
  fetch('/picklists/statuses'),
  fetch('/picklists/categories'),
  fetch('/picklists/severities'),
  fetch('/picklists/users'),
  fetch('/picklists/services-issue'),
  fetch('/picklists/issue-end'),
  fetch('/picklists/organization-priority')
]);

const [
  priorities,
  statuses,
  categories,
  severities,
  users,
  servicesIssue,
  issueEnd,
  orgPriority
] = await Promise.all(dropdowns.map(r => r.json()));
```

### Step 2: Load Organizations

```javascript
const orgsResponse = await fetch('/organizations?limit=100');
const { data: organizations } = await orgsResponse.json();
```

### Step 3: When User Selects Organization → Load Contacts

```javascript
const selectedOrgId = 102;
const contactsResponse = await fetch(`/contacts?account_id=${selectedOrgId}`);
const { data: contacts } = await contactsResponse.json();
```

### Step 4: Create Ticket with Form Data

```javascript
const ticketData = {
  title: formData.title,
  description: formData.description,
  priority: formData.priority,
  status: formData.status,
  account_id: formData.organizationId,
  contact_id: formData.contactId,
  assigned_to: formData.assignedTo,
  services_issue: formData.servicesIssue,
  issue_end: formData.issueEnd,
  organization_priority: formData.orgPriority,
  email_sms_to_customer: formData.emailCustomer ? "1" : "0",
  send_on_confirmation: formData.sendConfirm ? "1" : "0"
};

const response = await fetch('/tickets', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(ticketData)
});

const result = await response.json();
```

---

## 🗺️ Field Mapping Reference

### Form Field → API Field → Database Column

| Form Field | API Field | Database Table | Database Column |
|------------|-----------|----------------|-----------------|
| Organization Name | account_id | vtiger_troubletickets | parent_id |
| Title | title | vtiger_troubletickets | title |
| Contact Name | contact_id | vtiger_troubletickets | contact_id |
| Priority | priority | vtiger_troubletickets | priority |
| Status | status | vtiger_troubletickets | status |
| Assigned To | assigned_to | vtiger_crmentity | smownerid |
| Services Issue | services_issue | vtiger_ticketcf | cf_869 |
| Issue End | issue_end | vtiger_ticketcf | cf_871 |
| Organization Priority | organization_priority | vtiger_ticketcf | cf_867 |
| End Time | end_time | vtiger_ticketcf | cf_873 |
| Closing Time | closing_time | vtiger_ticketcf | cf_879 |
| Send On Confirmation | send_on_confirmation | vtiger_ticketcf | cf_875 |
| Email SMS Assigned To | email_sms_assigned_to | vtiger_ticketcf | cf_877 |
| Email SMS to Customer | email_sms_to_customer | vtiger_ticketcf | cf_897 |
| Description | description | vtiger_crmentity | description |
| Solution | solution | vtiger_troubletickets | solution |
| Total Time Minutes | total_time_minutes | (calculated field) | - |

---

## 🎨 Frontend Form Example (HTML)

```html
<form id="ticketForm">
  <!-- Organization & Contact -->
  <select name="account_id" required>
    <option value="">Select Organization</option>
    <!-- Loaded from /organizations -->
  </select>
  
  <select name="contact_id">
    <option value="">Select Contact</option>
    <!-- Loaded from /contacts?account_id=X -->
  </select>
  
  <!-- Title -->
  <input type="text" name="title" required placeholder="Ticket Title">
  
  <!-- Priority, Status, etc. -->
  <select name="priority">
    <!-- Loaded from /picklists/priorities -->
  </select>
  
  <select name="status">
    <!-- Loaded from /picklists/statuses -->
  </select>
  
  <select name="assigned_to">
    <!-- Loaded from /picklists/users -->
  </select>
  
  <!-- Custom Fields -->
  <select name="services_issue">
    <!-- Loaded from /picklists/services-issue -->
  </select>
  
  <select name="issue_end">
    <!-- Loaded from /picklists/issue-end -->
  </select>
  
  <select name="organization_priority">
    <!-- Loaded from /picklists/organization-priority -->
  </select>
  
  <!-- Checkboxes -->
  <input type="checkbox" name="email_sms_to_customer" value="1">
  <input type="checkbox" name="send_on_confirmation" value="1">
  <input type="checkbox" name="email_sms_assigned_to" value="1">
  
  <!-- Time fields -->
  <input type="time" name="end_time">
  <input type="number" name="hours" step="0.1">
  <input type="number" name="total_time_minutes">
  
  <!-- Text areas -->
  <textarea name="description" placeholder="Description"></textarea>
  <textarea name="solution" placeholder="Solution"></textarea>
  
  <button type="submit">Create Ticket</button>
</form>
```

---

## 📊 Response Format

### Success Response (201 Created)

```json
{
  "success": true,
  "message": "Ticket created successfully",
  "data": {
    "success": true,
    "ticket_id": 490186,
    "ticket_no": "TT0001"
  }
}
```

### Error Response (400 Bad Request)

```json
{
  "success": false,
  "message": "Title is required"
}
```

---

## 🧪 Testing Examples

### Test 1: Minimal Ticket
```bash
curl -X POST http://localhost/vtiger-api/public/tickets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title": "Test Ticket"}'
```

### Test 2: With Organization and Contact
```bash
curl -X POST http://localhost/vtiger-api/public/tickets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Support Request",
    "description": "Need help with setup",
    "priority": "Normal",
    "account_id": 102,
    "contact_id": 250
  }'
```

### Test 3: With All Custom Fields
```bash
curl -X POST http://localhost/vtiger-api/public/tickets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Production Issue",
    "description": "Critical issue in production",
    "priority": "Urgent",
    "severity": "Critical",
    "status": "Open",
    "category": "Technical",
    "account_id": 102,
    "contact_id": 250,
    "assigned_to": 5,
    "services_issue": "Network Issue",
    "issue_end": "Resolved",
    "organization_priority": "High",
    "hours": 4.5,
    "email_sms_to_customer": "1",
    "send_on_confirmation": "1"
  }'
```

---

## 📋 Picklist API Endpoints Reference

To populate the dropdown fields, use these endpoints:

```bash
GET /picklists/priorities              # For priority field
GET /picklists/statuses                # For status field
GET /picklists/categories              # For category field
GET /picklists/severities              # For severity field
GET /picklists/users                   # For assigned_to field
GET /picklists/services-issue          # For services_issue field (cf_869)
GET /picklists/issue-end               # For issue_end field (cf_871)
GET /picklists/organization-priority   # For organization_priority field (cf_867)
GET /organizations                     # For account_id field
GET /contacts?account_id=X             # For contact_id field
```

---

## 💡 Best Practices

### 1. Load Picklists Once
```javascript
// Load on app initialization, cache in state/store
const picklists = await loadAllPicklists();
// Reuse throughout the app
```

### 2. Cascade Organization → Contacts
```javascript
// When organization changes, reload contacts
onOrganizationChange(orgId) {
  const contacts = await fetch(`/contacts?account_id=${orgId}`);
  // Update contact dropdown
}
```

### 3. Checkbox Values
```javascript
// Convert checkbox to API format
const checkboxValue = isChecked ? "1" : "0";
```

### 4. Validation
```javascript
// Client-side validation before API call
if (!formData.title) {
  alert("Title is required");
  return;
}

// Optional: Validate required organization/contact
if (!formData.account_id) {
  alert("Please select an organization");
  return;
}
```

---

## 🔄 Field Dependencies

### Organization → Contact
When `account_id` is selected, load contacts for that organization:
```javascript
GET /contacts?account_id={selected_account_id}
```

### Priority Levels
- **Ticket Priority:** Low, Normal, High, Urgent (standard)
- **Organization Priority:** Custom values (cf_867)

---

## ✅ Validation Rules

| Field | Validation | Error Message |
|-------|-----------|---------------|
| title | Required, not empty | "Title is required" |
| account_id | Optional, must be valid ID | - |
| contact_id | Optional, must be valid ID | - |
| assigned_to | Optional, must be valid user ID | - |
| priority | Optional, must match picklist value | - |
| status | Optional, must match picklist value | - |
| hours | Optional, must be decimal | - |
| days | Optional, must be decimal | - |
| checkboxes | Must be "1" or "0" | - |

---

## 🎯 Summary

**Total Fields Supported:** 25+ fields

**Required Fields:** Only `title`

**All Other Fields:** Optional

**Picklist Fields:** 8 dropdowns
- Priority
- Status
- Category
- Severity
- Assigned To (Users)
- Services Issue
- Issue End
- Organization Priority

**Relationship Fields:** 2
- Organization (account_id)
- Contact (contact_id)

**Custom Fields:** 6
- Organization Priority (cf_867)
- Services Issue (cf_869)
- Issue End (cf_871)
- End Time (cf_873)
- Checkboxes (cf_875, cf_877, cf_897)
- Closing Time (cf_879)

---

## 📖 See Also

- `API_EXAMPLES.md` - General API examples
- `NEW_ENDPOINTS.md` - Organizations and Contacts documentation
- `POSTMAN_COLLECTION.json` - Import to test in Postman
- `API_QUICK_TEST.md` - Quick testing guide

---

**Your ticket creation endpoint now supports ALL fields from your form! 🎉**

