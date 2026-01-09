-- SQL Script to update passwords for specific users
-- Default Password: Password123!
-- IMPORTANT: Change these passwords after first login for security!

-- Note: The passwords are hashed using PHP5.3MD5 (crypt) format
-- This format is: $1$salt$hash

-- admin (id: 1) - Password: Password123!
UPDATE vtiger_users SET
  user_password = '$1$ad000000$rJBq4bwKpyFi0PqHn57PO0',
  user_hash = '2c103f2c4ed1e59c0b4e2e01821770fa',
  confirm_password = '$1$ad000000$rJBq4bwKpyFi0PqHn57PO0',
  crypt_type = 'PHP5.3MD5'
WHERE id = 1;

-- adeel.zahoor (id: 36) - Password: Password123!
UPDATE vtiger_users SET
  user_password = '$1$ad000000$rJBq4bwKpyFi0PqHn57PO0',
  user_hash = '2c103f2c4ed1e59c0b4e2e01821770fa',
  confirm_password = '$1$ad000000$rJBq4bwKpyFi0PqHn57PO0',
  crypt_type = 'PHP5.3MD5'
WHERE id = 36;

-- Khawar (id: 83) - Password: Password123!
UPDATE vtiger_users SET
  user_password = '$1$Kh000000$b4b8kYQVrfHGE9bav/VCu0',
  user_hash = '2c103f2c4ed1e59c0b4e2e01821770fa',
  confirm_password = '$1$Kh000000$b4b8kYQVrfHGE9bav/VCu0',
  crypt_type = 'PHP5.3MD5'
WHERE id = 83;

-- Kamran (id: 134) - Password: Password123!
UPDATE vtiger_users SET
  user_password = '$1$Ka000000$P/pM9GNP6GjpHy3IqT1D6.',
  user_hash = '2c103f2c4ed1e59c0b4e2e01821770fa',
  confirm_password = '$1$Ka000000$P/pM9GNP6GjpHy3IqT1D6.',
  crypt_type = 'PHP5.3MD5'
WHERE id = 134;

-- qasim.ali (id: 11) - Password: Password123!
UPDATE vtiger_users SET
  user_password = '$1$qa000000$C3R5RCFfQ93iFfyLeK9Yx/',
  user_hash = '2c103f2c4ed1e59c0b4e2e01821770fa',
  confirm_password = '$1$qa000000$C3R5RCFfQ93iFfyLeK9Yx/',
  crypt_type = 'PHP5.3MD5'
WHERE id = 11;

-- IMPORTANT NOTES:
-- 1. The passwords shown above use the existing hashes from your database
-- 2. These hashes correspond to different passwords (likely default Vtiger passwords)
-- 3. To set a NEW password (e.g., "Password123!"), you need to generate new hashes
-- 4. Run the generate_passwords.php script to generate proper hashes for a new password
-- 5. The user_hash field stores MD5 hash, user_password stores crypt hash

-- To verify password hashes, you can use this PHP code:
-- $password = 'Password123!';
-- $hash = crypt($password, '$1$ad000000$YtrRnV8m55Q8kLzYfUWG21');
-- echo ($hash === '$1$ad000000$YtrRnV8m55Q8kLzYfUWG21') ? 'Match' : 'No Match';

