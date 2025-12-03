@echo off
REM Vtiger CRM API - New Endpoints Test Script for Windows
REM Requires: curl (available in Windows 10+)

SET BASE_URL=http://localhost/vtiger-api/public

echo ========================================
echo Vtiger CRM API - New Endpoints Test
echo ========================================
echo.

REM Step 1: Login
echo [1/11] Testing Login...
curl -s -X POST "%BASE_URL%/auth/login" ^
  -H "Content-Type: application/json" ^
  -d "{\"username\":\"admin\",\"password\":\"connectel@123\"}" > login_response.txt

REM Extract token (simplified - manual copy recommended)
echo Please copy the token from login_response.txt and set it below:
echo SET TOKEN=your_token_here
echo.
SET /p TOKEN="Enter your JWT token: "

echo.

REM Step 2: Test Organizations
echo [2/11] Testing GET /organizations...
curl -s -X GET "%BASE_URL%/organizations?limit=5" ^
  -H "Authorization: Bearer %TOKEN%"
echo.
echo.

REM Step 3: Test Organizations Search
echo [3/11] Testing GET /organizations with search...
curl -s -X GET "%BASE_URL%/organizations?search=test&limit=5" ^
  -H "Authorization: Bearer %TOKEN%"
echo.
echo.

REM Step 4: Test Contacts
echo [4/11] Testing GET /contacts...
curl -s -X GET "%BASE_URL%/contacts?limit=5" ^
  -H "Authorization: Bearer %TOKEN%"
echo.
echo.

REM Step 5: Test Contacts with Organization Filter
echo [5/11] Testing GET /contacts with account_id filter...
curl -s -X GET "%BASE_URL%/contacts?account_id=102" ^
  -H "Authorization: Bearer %TOKEN%"
echo.
echo.

REM Step 6: Test Priorities
echo [6/11] Testing GET /picklists/priorities...
curl -s -X GET "%BASE_URL%/picklists/priorities" ^
  -H "Authorization: Bearer %TOKEN%"
echo.
echo.

REM Step 7: Test Services Issue
echo [7/11] Testing GET /picklists/services-issue...
curl -s -X GET "%BASE_URL%/picklists/services-issue" ^
  -H "Authorization: Bearer %TOKEN%"
echo.
echo.

REM Step 8: Test Statuses
echo [8/11] Testing GET /picklists/statuses...
curl -s -X GET "%BASE_URL%/picklists/statuses" ^
  -H "Authorization: Bearer %TOKEN%"
echo.
echo.

REM Step 9: Test Users
echo [9/11] Testing GET /picklists/users...
curl -s -X GET "%BASE_URL%/picklists/users" ^
  -H "Authorization: Bearer %TOKEN%"
echo.
echo.

REM Step 10: Test Issue End
echo [10/11] Testing GET /picklists/issue-end...
curl -s -X GET "%BASE_URL%/picklists/issue-end" ^
  -H "Authorization: Bearer %TOKEN%"
echo.
echo.

REM Step 11: Test Categories
echo [11/11] Testing GET /picklists/categories...
curl -s -X GET "%BASE_URL%/picklists/categories" ^
  -H "Authorization: Bearer %TOKEN%"
echo.
echo.

echo ========================================
echo Testing Complete!
echo ========================================
echo.
echo For detailed documentation, see:
echo   - NEW_ENDPOINTS.md
echo   - API_EXAMPLES.md
echo   - POSTMAN_COLLECTION.json
echo.

REM Cleanup
del login_response.txt

pause



