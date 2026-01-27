use vconn;

-- Create notifications table
CREATE TABLE IF NOT EXISTS `vtiger_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int DEFAULT NULL COMMENT 'ID of related ticket/comment/etc',
  `related_type` varchar(50) NOT NULL COMMENT 'Type of related entity: ticket, comment, other',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_related_type` (`related_type`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;



-- ============================================
-- DATABASE OPTIMIZATION QUERIES
-- Execute these in order for fastest performance
-- ============================================

-- ============================================
-- 1. VTIGER_CRMENTITY INDEXES (Most Critical)
-- ============================================

-- Primary join index (ticketid = crmid)
ALTER TABLE vtiger_crmentity ADD INDEX idx_crmentity_crmid_deleted (crmid, deleted);

-- User-specific queries (most common filter)
ALTER TABLE vtiger_crmentity ADD INDEX idx_crmentity_smownerid_deleted (smownerid, deleted);

-- Date range queries with deleted filter
ALTER TABLE vtiger_crmentity ADD INDEX idx_crmentity_deleted_createdtime (deleted, createdtime);

-- Composite for user + date + deleted (dashboard queries)
ALTER TABLE vtiger_crmentity ADD INDEX idx_crmentity_smownerid_deleted_createdtime (smownerid, deleted, createdtime);

-- For setype filtering (HelpDesk)
ALTER TABLE vtiger_crmentity ADD INDEX idx_crmentity_setype_smownerid (setype, smownerid);

-- For creator lookups
ALTER TABLE vtiger_crmentity ADD INDEX idx_crmentity_smcreatorid (smcreatorid);

-- ============================================
-- 2. VTIGER_TROUBLETICKETS INDEXES
-- ============================================

-- Status filtering (most common)
ALTER TABLE vtiger_troubletickets ADD INDEX idx_troubletickets_status (status);

-- Priority filtering
ALTER TABLE vtiger_troubletickets ADD INDEX idx_troubletickets_priority (priority);

-- Severity filtering
ALTER TABLE vtiger_troubletickets ADD INDEX idx_troubletickets_severity (severity);

-- Category filtering
ALTER TABLE vtiger_troubletickets ADD INDEX idx_troubletickets_category (category);

-- Account/Organization joins
ALTER TABLE vtiger_troubletickets ADD INDEX idx_troubletickets_parent_id (parent_id);

-- Contact joins
ALTER TABLE vtiger_troubletickets ADD INDEX idx_troubletickets_contact_id (contact_id);

-- Composite: Status + Priority (common filter combination)
ALTER TABLE vtiger_troubletickets ADD INDEX idx_troubletickets_status_priority (status, priority);

-- Composite: Status + Priority + Severity (for advanced filtering)
ALTER TABLE vtiger_troubletickets ADD INDEX idx_troubletickets_status_priority_severity (status, priority, severity);

-- ============================================
-- 3. VTIGER_ACCOUNT INDEXES
-- ============================================

-- Primary lookup
ALTER TABLE vtiger_account ADD INDEX idx_account_accountid_deleted (accountid, deleted);

-- ============================================
-- 4. VTIGER_USERS INDEXES
-- ============================================

-- Active user filtering
ALTER TABLE vtiger_users ADD INDEX idx_users_status_deleted (status, deleted);

-- User ID lookup (should be primary key, but ensure index exists)
ALTER TABLE vtiger_users ADD INDEX idx_users_id (id);

-- ============================================
-- 5. VTIGER_TICKETCOMMENTS INDEXES
-- ============================================

-- Ticket comments lookup (most common)
ALTER TABLE vtiger_ticketcomments ADD INDEX idx_ticketcomments_ticketid_createdtime (ticketid, createdtime);

-- Owner lookup
ALTER TABLE vtiger_ticketcomments ADD INDEX idx_ticketcomments_ownerid (ownerid);

-- Comment ID lookup
ALTER TABLE vtiger_ticketcomments ADD INDEX idx_ticketcomments_commentid (commentid);

-- ============================================
-- 6. UPDATE TABLE STATISTICS (Fast Operation)
-- ============================================

ANALYZE TABLE vtiger_crmentity;
ANALYZE TABLE vtiger_troubletickets;
ANALYZE TABLE vtiger_account;
ANALYZE TABLE vtiger_users;
ANALYZE TABLE vtiger_ticketcomments;

-- ============================================
-- 7. VERIFY INDEXES WERE CREATED
-- ============================================

-- Check all indexes on critical tables
SHOW INDEX FROM vtiger_crmentity;
SHOW INDEX FROM vtiger_troubletickets;
SHOW INDEX FROM vtiger_account;
SHOW INDEX FROM vtiger_users;
SHOW INDEX FROM vtiger_ticketcomments;