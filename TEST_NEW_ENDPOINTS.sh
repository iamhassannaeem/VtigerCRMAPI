#!/bin/bash

# Color codes for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# API Base URL
BASE_URL="http://localhost/vtiger-api/public"

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}Vtiger CRM API - New Endpoints Test${NC}"
echo -e "${YELLOW}========================================${NC}\n"

# Step 1: Login
echo -e "${YELLOW}[1/11] Testing Login...${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "${BASE_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"connectel@123"}')

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo -e "${RED}❌ Login failed!${NC}"
    echo "Response: $LOGIN_RESPONSE"
    exit 1
else
    echo -e "${GREEN}✅ Login successful!${NC}"
    echo "Token: ${TOKEN:0:50}..."
fi

echo ""

# Step 2: Test Organizations List
echo -e "${YELLOW}[2/11] Testing GET /organizations...${NC}"
ORG_RESPONSE=$(curl -s -X GET "${BASE_URL}/organizations?limit=5" \
  -H "Authorization: Bearer $TOKEN")

ORG_SUCCESS=$(echo $ORG_RESPONSE | grep -o '"success":true')
if [ -n "$ORG_SUCCESS" ]; then
    echo -e "${GREEN}✅ Organizations API working!${NC}"
    echo "Sample: $(echo $ORG_RESPONSE | cut -c1-100)..."
else
    echo -e "${RED}❌ Organizations API failed!${NC}"
    echo "Response: $ORG_RESPONSE"
fi

echo ""

# Step 3: Test Organizations Search
echo -e "${YELLOW}[3/11] Testing GET /organizations with search...${NC}"
ORG_SEARCH_RESPONSE=$(curl -s -X GET "${BASE_URL}/organizations?search=test&limit=5" \
  -H "Authorization: Bearer $TOKEN")

ORG_SEARCH_SUCCESS=$(echo $ORG_SEARCH_RESPONSE | grep -o '"success":true')
if [ -n "$ORG_SEARCH_SUCCESS" ]; then
    echo -e "${GREEN}✅ Organizations search working!${NC}"
else
    echo -e "${RED}❌ Organizations search failed!${NC}"
fi

echo ""

# Step 4: Test Contacts List
echo -e "${YELLOW}[4/11] Testing GET /contacts...${NC}"
CONTACT_RESPONSE=$(curl -s -X GET "${BASE_URL}/contacts?limit=5" \
  -H "Authorization: Bearer $TOKEN")

CONTACT_SUCCESS=$(echo $CONTACT_RESPONSE | grep -o '"success":true')
if [ -n "$CONTACT_SUCCESS" ]; then
    echo -e "${GREEN}✅ Contacts API working!${NC}"
    echo "Sample: $(echo $CONTACT_RESPONSE | cut -c1-100)..."
else
    echo -e "${RED}❌ Contacts API failed!${NC}"
    echo "Response: $CONTACT_RESPONSE"
fi

echo ""

# Step 5: Test Contacts with Organization Filter
echo -e "${YELLOW}[5/11] Testing GET /contacts with account_id filter...${NC}"
CONTACT_FILTER_RESPONSE=$(curl -s -X GET "${BASE_URL}/contacts?account_id=102&limit=5" \
  -H "Authorization: Bearer $TOKEN")

CONTACT_FILTER_SUCCESS=$(echo $CONTACT_FILTER_RESPONSE | grep -o '"success":true')
if [ -n "$CONTACT_FILTER_SUCCESS" ]; then
    echo -e "${GREEN}✅ Contacts filter by organization working!${NC}"
else
    echo -e "${RED}❌ Contacts filter failed!${NC}"
fi

echo ""

# Step 6: Test Priorities Picklist
echo -e "${YELLOW}[6/11] Testing GET /picklists/priorities...${NC}"
PRIORITIES_RESPONSE=$(curl -s -X GET "${BASE_URL}/picklists/priorities" \
  -H "Authorization: Bearer $TOKEN")

PRIORITIES_SUCCESS=$(echo $PRIORITIES_RESPONSE | grep -o '"success":true')
if [ -n "$PRIORITIES_SUCCESS" ]; then
    echo -e "${GREEN}✅ Priorities picklist working!${NC}"
    echo "Sample: $(echo $PRIORITIES_RESPONSE | cut -c1-100)..."
else
    echo -e "${RED}❌ Priorities picklist failed!${NC}"
fi

echo ""

# Step 7: Test Services Issue Picklist
echo -e "${YELLOW}[7/11] Testing GET /picklists/services-issue...${NC}"
SERVICES_ISSUE_RESPONSE=$(curl -s -X GET "${BASE_URL}/picklists/services-issue" \
  -H "Authorization: Bearer $TOKEN")

SERVICES_ISSUE_SUCCESS=$(echo $SERVICES_ISSUE_RESPONSE | grep -o '"success":true')
if [ -n "$SERVICES_ISSUE_SUCCESS" ]; then
    echo -e "${GREEN}✅ Services Issue picklist working!${NC}"
else
    echo -e "${RED}❌ Services Issue picklist failed!${NC}"
fi

echo ""

# Step 8: Test Statuses Picklist
echo -e "${YELLOW}[8/11] Testing GET /picklists/statuses...${NC}"
STATUSES_RESPONSE=$(curl -s -X GET "${BASE_URL}/picklists/statuses" \
  -H "Authorization: Bearer $TOKEN")

STATUSES_SUCCESS=$(echo $STATUSES_RESPONSE | grep -o '"success":true')
if [ -n "$STATUSES_SUCCESS" ]; then
    echo -e "${GREEN}✅ Statuses picklist working!${NC}"
else
    echo -e "${RED}❌ Statuses picklist failed!${NC}"
fi

echo ""

# Step 9: Test Users Picklist
echo -e "${YELLOW}[9/11] Testing GET /picklists/users...${NC}"
USERS_RESPONSE=$(curl -s -X GET "${BASE_URL}/picklists/users" \
  -H "Authorization: Bearer $TOKEN")

USERS_SUCCESS=$(echo $USERS_RESPONSE | grep -o '"success":true')
if [ -n "$USERS_SUCCESS" ]; then
    echo -e "${GREEN}✅ Users picklist working!${NC}"
    echo "Sample: $(echo $USERS_RESPONSE | cut -c1-100)..."
else
    echo -e "${RED}❌ Users picklist failed!${NC}"
fi

echo ""

# Step 10: Test Issue End Picklist
echo -e "${YELLOW}[10/11] Testing GET /picklists/issue-end...${NC}"
ISSUE_END_RESPONSE=$(curl -s -X GET "${BASE_URL}/picklists/issue-end" \
  -H "Authorization: Bearer $TOKEN")

ISSUE_END_SUCCESS=$(echo $ISSUE_END_RESPONSE | grep -o '"success":true')
if [ -n "$ISSUE_END_SUCCESS" ]; then
    echo -e "${GREEN}✅ Issue End picklist working!${NC}"
else
    echo -e "${RED}❌ Issue End picklist failed!${NC}"
fi

echo ""

# Step 11: Test Categories Picklist
echo -e "${YELLOW}[11/11] Testing GET /picklists/categories...${NC}"
CATEGORIES_RESPONSE=$(curl -s -X GET "${BASE_URL}/picklists/categories" \
  -H "Authorization: Bearer $TOKEN")

CATEGORIES_SUCCESS=$(echo $CATEGORIES_RESPONSE | grep -o '"success":true')
if [ -n "$CATEGORIES_SUCCESS" ]; then
    echo -e "${GREEN}✅ Categories picklist working!${NC}"
else
    echo -e "${RED}❌ Categories picklist failed!${NC}"
fi

echo ""
echo -e "${YELLOW}========================================${NC}"
echo -e "${GREEN}✅ Testing Complete!${NC}"
echo -e "${YELLOW}========================================${NC}"
echo ""
echo "All new endpoints have been tested."
echo "For detailed documentation, see:"
echo "  - NEW_ENDPOINTS.md"
echo "  - API_EXAMPLES.md"
echo "  - POSTMAN_COLLECTION.json"


