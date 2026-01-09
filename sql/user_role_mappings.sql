-- SQL Script to assign roles to specific users
-- Execute these queries to update role mappings

-- 1. admin (id: 1) - Assign CEO role (H2)
-- Note: admin already has H2 role according to existing data, but adding here for reference
-- INSERT IGNORE INTO vtiger_user2role (userid, roleid) VALUES ('1', 'H2');

-- 2. adeel.zahoor (id: 36) - Assign Network Manager role (H6)
INSERT IGNORE INTO vtiger_user2role (userid, roleid) VALUES ('36', 'H6');

-- 3. Khawar (id: 83) - Assign Team Leads role (H7)
INSERT IGNORE INTO vtiger_user2role (userid, roleid) VALUES ('83', 'H7');

-- 4. Kamran (id: 134) - Assign External Team Leads role (H9)
INSERT IGNORE INTO vtiger_user2role (userid, roleid) VALUES ('134', 'H9');

-- 5. qasim.ali (id: 11) - Assign Sales Person role (H5)
INSERT IGNORE INTO vtiger_user2role (userid, roleid) VALUES ('11', 'H5');

-- Verification queries (run after INSERT to verify)
-- SELECT u.id, u.user_name, u.first_name, u.last_name, r.roleid, r.rolename 
-- FROM vtiger_users u
-- LEFT JOIN vtiger_user2role u2r ON u.id = u2r.userid
-- LEFT JOIN vtiger_role r ON u2r.roleid = r.roleid
-- WHERE u.id IN ('1', '36', '83', '134', '11')
-- ORDER BY u.id;

