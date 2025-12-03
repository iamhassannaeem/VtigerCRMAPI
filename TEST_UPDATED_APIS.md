# Test Updated APIs - Quick Guide

## ✅ All Changes Applied Successfully!

### What Was Fixed:
1. ✅ Ticket status list now shows ALL statuses (not just "Hold")
2. ✅ Ticket category list now shows ALL categories
3. ✅ New GET endpoint for ticket comments created
4. ✅ Organization address added to ticket details
5. ✅ Custom field labels added to ticket details
6. ✅ Duplicate entity ID error fixed

---

## 🧪 Test All Updated Features

### Step 1: Login (Get Your Token)

```bash
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"connectel@123"}'
```

**Copy the token from the response!**

---

### Step 2: Test Fixed Status Picklist

```bash
curl -X GET http://localhost/vtiger-api/public/picklists/statuses \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Result:** Should now show ALL statuses (Open, In Progress, Wait For Response, Closed, Hold)

**Before Fix:** Only showed "Hold"
**After Fix:** Shows all 5+ statuses

---

### Step 3: Test New GET Comments Endpoint 🆕

```bash
curl -X GET http://localhost/vtiger-api/public/tickets/456/comments \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Result:**
```json
{
  "success": true,
  "count": 3,
  "data": [
    {
      "commentid": 125,
      "ticketid": 456,
      "comments": "The issue has been resolved.",
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

### Step 4: Test Enhanced Ticket Detail (With Org Address)

```bash
curl -X GET http://localhost/vtiger-api/public/tickets/456 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**New Fields Added:**
- `organization_details.billing_address` (street, city, state, country, postal_code)
- `organization_details.shipping_address` (street, city, state, country, postal_code)
- `organization_details.phone`, `email`, `website`
- `contact_details.email`, `phone`, `mobile`
- `custom_fields_labeled` (readable names for all custom fields)

**Expected Result:**
```json
{
  "success": true,
  "data": {
    "summary": { ... },
    "organization_details": {
      "name": "TechCorp Inc",
      "phone": "+1 (555) 987-6543",
      "email": "contact@techcorp.com",
      "website": "https://techcorp.com",
      "billing_address": {
        "street": "123 Tech Street",
        "city": "San Francisco",
        "state": "California",
        "country": "USA",
        "postal_code": "94105"
      },
      "shipping_address": { ... }
    },
    "contact_details": {
      "name": "Mike Johnson",
      "email": "mike.johnson@techcorp.com",
      "phone": "+1 (555) 111-2222",
      "mobile": "+1 (555) 333-4444"
    },
    "details": {
      "description": "...",
      "solution": "...",
      "custom_fields": {
        "cf_867": "High",
        "cf_869": "Email System"
      },
      "custom_fields_labeled": {
        "organization_priority": {
          "field_name": "cf_867",
          "label": "Organization Priority",
          "value": "High"
        },
        "services_issue": {
          "field_name": "cf_869",
          "label": "Services Issue",
          "value": "Email System"
        }
      }
    }
  }
}
```

---

### Step 5: Test Creating Ticket (Should Not Get Duplicate Error)

```bash
curl -X POST http://localhost/vtiger-api/public/tickets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Ticket After Fix",
    "description": "Testing duplicate ID fix",
    "priority": "High",
    "status": "Open",
    "account_id": 102,
    "contact_id": 250,
    "services_issue": "Network Issue",
    "organization_priority": "High",
    "email_sms_to_customer": "1"
  }'
```

**Expected Result:**
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

**Before Fix:** Got duplicate key error
**After Fix:** Creates ticket successfully

---

## 📋 All Picklist Endpoints (Test All)

```bash
# Test all picklists
curl -X GET http://localhost/vtiger-api/public/picklists/priorities \
  -H "Authorization: Bearer YOUR_TOKEN"

curl -X GET http://localhost/vtiger-api/public/picklists/statuses \
  -H "Authorization: Bearer YOUR_TOKEN"

curl -X GET http://localhost/vtiger-api/public/picklists/categories \
  -H "Authorization: Bearer YOUR_TOKEN"

curl -X GET http://localhost/vtiger-api/public/picklists/severities \
  -H "Authorization: Bearer YOUR_TOKEN"

curl -X GET http://localhost/vtiger-api/public/picklists/users \
  -H "Authorization: Bearer YOUR_TOKEN"

curl -X GET http://localhost/vtiger-api/public/picklists/services-issue \
  -H "Authorization: Bearer YOUR_TOKEN"

curl -X GET http://localhost/vtiger-api/public/picklists/issue-end \
  -H "Authorization: Bearer YOUR_TOKEN"

curl -X GET http://localhost/vtiger-api/public/picklists/organization-priority \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🎯 Frontend Integration Example

```javascript
// Load all dropdown options
const loadDropdowns = async () => {
  const [
    priorities,
    statuses,
    categories,
    severities,
    users,
    servicesIssue,
    issueEnd,
    orgPriority
  ] = await Promise.all([
    fetch('/picklists/priorities'),
    fetch('/picklists/statuses'),        // Now shows all statuses!
    fetch('/picklists/categories'),      // Now shows all categories!
    fetch('/picklists/severities'),
    fetch('/picklists/users'),
    fetch('/picklists/services-issue'),
    fetch('/picklists/issue-end'),
    fetch('/picklists/organization-priority')
  ].map(p => p.then(r => r.json())));
  
  return {
    priorities: priorities.data,
    statuses: statuses.data,
    categories: categories.data,
    severities: severities.data,
    users: users.data,
    servicesIssue: servicesIssue.data,
    issueEnd: issueEnd.data,
    orgPriority: orgPriority.data
  };
};

// Create ticket with all fields
const createTicket = async (formData) => {
  const response = await fetch('/tickets', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      title: formData.title,
      description: formData.description,
      priority: formData.priority,
      status: formData.status,              // Can now select from all statuses!
      category: formData.category,          // Can now select from all categories!
      account_id: formData.organizationId,
      contact_id: formData.contactId,
      assigned_to: formData.assignedTo,
      services_issue: formData.servicesIssue,
      issue_end: formData.issueEnd,
      organization_priority: formData.orgPriority,
      hours: formData.hours,
      email_sms_to_customer: formData.emailCustomer ? "1" : "0",
      send_on_confirmation: formData.sendConfirm ? "1" : "0"
    })
  });
  
  return await response.json();
};

// Get ticket with full details (including org address)
const getTicketDetails = async (ticketId) => {
  const response = await fetch(`/tickets/${ticketId}`);
  const data = await response.json();
  
  // Access organization address
  const orgAddress = data.data.organization_details.billing_address;
  console.log(`City: ${orgAddress.city}, State: ${orgAddress.state}`);
  
  // Access custom fields with labels
  const customFields = data.data.details.custom_fields_labeled;
  console.log(`Services Issue: ${customFields.services_issue.value}`);
  
  return data;
};

// Get ticket comments (NEW!)
const getTicketComments = async (ticketId) => {
  const response = await fetch(`/tickets/${ticketId}/comments`);
  const data = await response.json();
  
  return data.data; // Array of comments
};
```

---

## 📊 Response Format Examples

### GET /picklists/statuses (FIXED)
```json
{
  "success": true,
  "count": 5,
  "data": [
    { "id": 1, "value": "Open", "color": "#FF0000", "sortorderid": 0 },
    { "id": 2, "value": "In Progress", "color": "#FFA500", "sortorderid": 1 },
    { "id": 3, "value": "Wait For Response", "color": "#FFFF00", "sortorderid": 2 },
    { "id": 4, "value": "Closed", "color": "#00FF00", "sortorderid": 3 },
    { "id": 5, "value": "Hold", "color": "#808080", "sortorderid": 4 }
  ]
}
```

### GET /tickets/{id}/comments (NEW)
```json
{
  "success": true,
  "count": 2,
  "data": [
    {
      "commentid": 125,
      "ticketid": 456,
      "comments": "Issue resolved",
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

## ✅ Verification Checklist

Test each item to verify all fixes are working:

- [ ] Login returns JWT token
- [ ] GET /picklists/statuses shows multiple statuses (not just "Hold")
- [ ] GET /picklists/categories shows multiple categories
- [ ] POST /tickets creates ticket without duplicate error
- [ ] GET /tickets/{id} includes organization_details with addresses
- [ ] GET /tickets/{id} includes contact_details with phone/email
- [ ] GET /tickets/{id} includes custom_fields_labeled
- [ ] GET /tickets/{id}/comments returns comment list
- [ ] POST /tickets/{id}/comments adds comment successfully

---

## 🎉 Summary of Updates

**Files Modified:** 4
- `models/Picklist.php` - Fixed status & category presence filter
- `models/Ticket.php` - Added org addresses, fixed entity ID sequence
- `controllers/TicketController.php` - Added GET comments, enhanced detail response
- `public/index.php` - Added GET comments route

**New Endpoint Added:** 1
- `GET /tickets/{id}/comments`

**Enhanced Endpoints:** 3
- `GET /tickets/{id}` - Now includes org addresses & custom field labels
- `GET /picklists/statuses` - Now shows all statuses
- `GET /picklists/categories` - Now shows all categories

**Bugs Fixed:** 2
- Duplicate entity ID error
- Status/category picklist showing wrong data

---

**All APIs are now updated and ready to use! 🚀**

See `COMPLETE_API_LIST.md` for the full endpoint reference!

