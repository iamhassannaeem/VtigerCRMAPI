-- =====================================================
-- Database Indexes for Sales Person Ticket Queries
-- Performance Optimization for Mobile App
-- =====================================================
-- 
-- IMPORTANT: Run this script in your MySQL database
-- These indexes will significantly improve the performance
-- of Sales person ticket queries (UNION approach)
--
-- Expected Improvement: 3-10x faster query execution
-- 
-- How to run:
-- 1. Open phpMyAdmin or MySQL command line
-- 2. Select your database
-- 3. Run this entire script
-- 4. If you get "Duplicate key name" errors, those indexes already exist (safe to ignore)
-- =====================================================

-- =====================================================
-- 1. vtiger_crmentity Table Indexes
-- =====================================================

-- Index for tickets assigned to user (Part 1 of UNION)
-- Used in: WHERE ce.smownerid = ? AND ce.deleted = 0
ALTER TABLE vtiger_crmentity 
    ADD INDEX idx_smownerid_deleted (smownerid, deleted);

-- Index for account ownership filtering (Part 2 of UNION)  
-- Used in: WHERE ce_account.smownerid = ? AND ce_account.setype = 'Accounts' AND ce_account.deleted = 0
ALTER TABLE vtiger_crmentity 
    ADD INDEX idx_setype_smownerid_deleted (setype, smownerid, deleted);

-- Index for ordering by createdtime
-- Used in: ORDER BY ce.createdtime DESC
ALTER TABLE vtiger_crmentity 
    ADD INDEX idx_createdtime (createdtime);

-- Composite index for ticket entity lookups
-- Used in: JOIN conditions and WHERE ce.deleted = 0
ALTER TABLE vtiger_crmentity 
    ADD INDEX idx_crmid_deleted (crmid, deleted);

-- =====================================================
-- 2. vtiger_troubletickets Table Indexes
-- =====================================================

-- Index for parent_id (account relationship)
-- Used in: JOIN vtiger_account a ON tt.parent_id = a.accountid
ALTER TABLE vtiger_troubletickets 
    ADD INDEX idx_parent_id (parent_id);

-- Index for status filtering
-- Used in: WHERE tt.status = ?
ALTER TABLE vtiger_troubletickets 
    ADD INDEX idx_status (status);

-- Index for priority filtering
ALTER TABLE vtiger_troubletickets 
    ADD INDEX idx_priority (priority);

-- Index for search filtering (prefix index for title - first 100 chars)
-- Used in: WHERE tt.title LIKE ?
ALTER TABLE vtiger_troubletickets 
    ADD INDEX idx_title (title(100));

-- =====================================================
-- 3. vtiger_account Table Indexes
-- =====================================================

-- Index for account lookups (if accountid is not already PRIMARY KEY)
-- Used in: JOIN vtiger_account a ON tt.parent_id = a.accountid
-- Note: If accountid is already PRIMARY KEY, this will fail (safe to ignore)
ALTER TABLE vtiger_account 
    ADD INDEX idx_accountid (accountid);

-- =====================================================
-- 4. Verify Indexes Were Created
-- =====================================================

-- Run these queries to verify all indexes exist:
-- 
-- SHOW INDEXES FROM vtiger_crmentity WHERE Key_name LIKE 'idx_%';
-- SHOW INDEXES FROM vtiger_troubletickets WHERE Key_name LIKE 'idx_%';
-- SHOW INDEXES FROM vtiger_account WHERE Key_name LIKE 'idx_%';

-- =====================================================
-- 5. Test Query Performance
-- =====================================================

-- After adding indexes, test with EXPLAIN (replace 11 with actual user ID):
-- 
-- EXPLAIN 
-- SELECT * FROM (
--     (
--         SELECT tt.ticketid, tt.ticket_no, tt.title, tt.status, ce.createdtime
--         FROM vtiger_troubletickets tt
--         INNER JOIN vtiger_crmentity ce 
--             ON tt.ticketid = ce.crmid
--             AND ce.deleted = 0
--             AND ce.smownerid = 11
--     )
--     UNION
--     (
--         SELECT tt.ticketid, tt.ticket_no, tt.title, tt.status, ce.createdtime
--         FROM vtiger_troubletickets tt
--         INNER JOIN vtiger_account a ON tt.parent_id = a.accountid
--         INNER JOIN vtiger_crmentity ce_account 
--             ON a.accountid = ce_account.crmid
--             AND ce_account.setype = 'Accounts'
--             AND ce_account.deleted = 0
--             AND ce_account.smownerid = 11
--         INNER JOIN vtiger_crmentity ce 
--             ON tt.ticketid = ce.crmid
--             AND ce.deleted = 0
--     )
-- ) AS tickets
-- ORDER BY createdtime DESC
-- LIMIT 50;

-- Expected results:
-- - type: ref or range (GOOD!)
-- - key: idx_smownerid_deleted or idx_setype_smownerid_deleted
-- - rows: < 10,000 (much fewer rows scanned)
-- - Extra: Using index (GOOD!)

-- =====================================================
-- Notes:
-- =====================================================
-- 1. Indexes will take a few minutes to create on large tables
-- 2. Indexes use additional disk space (~5-10% of table size)
-- 3. INSERT/UPDATE operations will be slightly slower (negligible)
-- 4. SELECT queries will be MUCH faster (worth the trade-off)
-- 5. These indexes are safe to add - no data loss risk
-- 6. If you get "Duplicate key name" errors, the index already exists (safe to ignore)
