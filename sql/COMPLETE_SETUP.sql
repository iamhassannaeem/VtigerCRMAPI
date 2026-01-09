-- ============================================================================
-- COMPLETE SQL SETUP SCRIPT FOR USER ROLE ASSIGNMENTS AND PASSWORD UPDATES
-- ============================================================================
-- 
-- This script contains:
-- 1. Role assignment queries (vtiger_user2role table)
-- 2. Password update queries (vtiger_users table)
--
-- Execute this script in your MySQL database to:
-- - Assign proper roles to specific users
-- - Set default passwords for all users (Password: Password123!)
--
-- IMPORTANT: Change passwords after first login!
-- ============================================================================

-- ============================================================================
-- PART 1: ROLE ASSIGNMENTS
-- ============================================================================
-- Assign roles to specific users in vtiger_user2role table

-- 1. admin (id: 1) - CEO role (H2)
-- Note: admin may already have this role, but INSERT IGNORE will skip if exists
INSERT IGNORE INTO vtiger_user2role (userid, roleid) VALUES ('1', 'H2');

-- 2. adeel.zahoor (id: 36) - Network Manager role (H6)
INSERT IGNORE INTO vtiger_user2role (userid, roleid) VALUES ('36', 'H6');

-- 3. Khawar (id: 83) - Team Leads role (H7)
INSERT IGNORE INTO vtiger_user2role (userid, roleid) VALUES ('83', 'H7');

-- 4. Kamran (id: 134) - External Team Leads role (H9)
INSERT IGNORE INTO vtiger_user2role (userid, roleid) VALUES ('134', 'H9');

-- 5. qasim.ali (id: 11) - Sales Person role (H5)
INSERT IGNORE INTO vtiger_user2role (userid, roleid) VALUES ('11', 'H5');

-- ============================================================================
-- PART 2: PASSWORD UPDATES
-- ============================================================================
-- Set default password "Password123!" for all specified users
-- 
-- Note: Run scripts/generate_password_hashes.php to generate new hashes
-- if you want to use a different password

-- admin (ID: 1) - Password: Password123!
UPDATE vtiger_users SET
  user_password = '$1$ad000000$rJBq4bwKpyFi0PqHn57PO0',
  user_hash = '2c103f2c4ed1e59c0b4e2e01821770fa',
  confirm_password = '$1$ad000000$rJBq4bwKpyFi0PqHn57PO0',
  crypt_type = 'PHP5.3MD5'
WHERE id = 1;

-- adeel.zahoor (ID: 36) - Password: Password123!
UPDATE vtiger_users SET
  user_password = '$1$ad000000$rJBq4bwKpyFi0PqHn57PO0',
  user_hash = '2c103f2c4ed1e59c0b4e2e01821770fa',
  confirm_password = '$1$ad000000$rJBq4bwKpyFi0PqHn57PO0',
  crypt_type = 'PHP5.3MD5'
WHERE id = 36;

-- Khawar (ID: 83) - Password: Password123!
UPDATE vtiger_users SET
  user_password = '$1$Kh000000$b4b8kYQVrfHGE9bav/VCu0',
  user_hash = '2c103f2c4ed1e59c0b4e2e01821770fa',
  confirm_password = '$1$Kh000000$b4b8kYQVrfHGE9bav/VCu0',
  crypt_type = 'PHP5.3MD5'
WHERE id = 83;

-- Kamran (ID: 134) - Password: Password123!
UPDATE vtiger_users SET
  user_password = '$1$Ka000000$P/pM9GNP6GjpHy3IqT1D6.',
  user_hash = '2c103f2c4ed1e59c0b4e2e01821770fa',
  confirm_password = '$1$Ka000000$P/pM9GNP6GjpHy3IqT1D6.',
  crypt_type = 'PHP5.3MD5'
WHERE id = 134;

-- qasim.ali (ID: 11) - Password: Password123!
UPDATE vtiger_users SET
  user_password = '$1$qa000000$C3R5RCFfQ93iFfyLeK9Yx/',
  user_hash = '2c103f2c4ed1e59c0b4e2e01821770fa',
  confirm_password = '$1$qa000000$C3R5RCFfQ93iFfyLeK9Yx/',
  crypt_type = 'PHP5.3MD5'
WHERE id = 11;

-- ============================================================================
-- PART 3: VERIFICATION QUERIES
-- ============================================================================
-- Run these queries to verify the role assignments and user details

-- Verify role assignments
SELECT 
    u.id,
    u.user_name,
    u.first_name,
    u.last_name,
    r.roleid,
    r.rolename,
    r.parentrole
FROM vtiger_users u
LEFT JOIN vtiger_user2role u2r ON u.id = u2r.userid
LEFT JOIN vtiger_role r ON u2r.roleid = r.roleid
WHERE u.id IN ('1', '36', '83', '134', '11')
ORDER BY u.id;

-- Verify password hashes (should show updated crypt_type)
SELECT 
    id,
    user_name,
    LEFT(user_password, 20) as password_hash_preview,
    LEFT(user_hash, 20) as md5_hash_preview,
    crypt_type
FROM vtiger_users
WHERE id IN ('1', '36', '83', '134', '11')
ORDER BY id;

-- ============================================================================
-- NOTES:
-- ============================================================================
-- 1. The default password for all users is: Password123!
-- 2. Passwords are hashed using PHP5.3MD5 (crypt) format
-- 3. The user_hash field contains MD5 hash of the password
-- 4. To generate new password hashes, run: php scripts/generate_password_hashes.php
-- 5. IMPORTANT: Users should change their passwords after first login!
-- 6. Role assignments use INSERT IGNORE to avoid duplicate key errors
-- ============================================================================

