-- DEBUG: Field User Ticket Assignment
-- Run these queries to diagnose why dashboard shows zero tickets

-- STEP 1: Get the user_id from the username
-- Replace 'Kamran' with the actual username you're testing
SELECT 
    id as user_id,
    user_name,
    CONCAT(first_name, ' ', last_name) as full_name,
    status,
    is_admin
FROM vtiger_users
WHERE user_name = 'Kamran';  -- CHANGE THIS
-- Note the user_id from this result

-- STEP 2: Check how many tickets this user has
-- Replace 134 with the user_id from STEP 1
SELECT 
    COUNT(*) as total_tickets,
    COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) as open_tickets,
    COUNT(CASE WHEN tt.status = 'Closed' THEN 1 END) as closed_tickets,
    COUNT(CASE WHEN ce.deleted = 1 THEN 1 END) as deleted_tickets
FROM vtiger_troubletickets tt
INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
WHERE ce.smownerid = 134;  -- CHANGE THIS

-- STEP 3: Show sample tickets for this user
-- Replace 134 with the user_id
SELECT 
    tt.ticketid,
    tt.ticket_no,
    tt.title,
    tt.status,
    tt.priority,
    ce.smownerid,
    ce.deleted,
    ce.createdtime
FROM vtiger_troubletickets tt
INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
WHERE ce.smownerid = 134  -- CHANGE THIS
AND ce.deleted = 0
LIMIT 10;

-- STEP 4: Compare with tickets API query
-- This is what the working tickets API uses
SELECT 
    tt.ticketid,
    tt.ticket_no,
    tt.title,
    tt.status,
    tt.priority,
    tt.severity,
    tt.category,
    ce.createdtime,
    ce.modifiedtime,
    ce.smownerid,
    CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name,
    a.accountname as account_name
FROM vtiger_troubletickets tt
INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
LEFT JOIN vtiger_users u ON ce.smownerid = u.id
LEFT JOIN vtiger_account a ON tt.parent_id = a.accountid
WHERE ce.deleted = 0
AND ce.smownerid = 134  -- CHANGE THIS
LIMIT 10;

-- STEP 5: Check if user has role assignment
-- Replace 'Kamran' with username
SELECT 
    u.id,
    u.user_name,
    r.roleid,
    r.rolename,
    CASE 
        WHEN r.rolename LIKE '%_Field' THEN 'Field'
        WHEN r.rolename LIKE '%_NOC' THEN 'NOC'
        WHEN r.rolename LIKE '%_Sales' THEN 'Sales'
        WHEN r.rolename LIKE '%_Management' THEN 'Management'
        WHEN r.rolename LIKE '%_Others' THEN 'Others'
        ELSE 'Unknown'
    END as dashboard_type
FROM vtiger_users u
LEFT JOIN vtiger_user2role ur ON u.id = ur.userid
LEFT JOIN vtiger_role r ON ur.roleid = r.roleid
WHERE u.user_name = 'Kamran';  -- CHANGE THIS

