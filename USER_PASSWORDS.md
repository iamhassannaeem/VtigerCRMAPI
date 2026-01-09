# User Passwords Reference

## Default Passwords for Role Assignment Users

**⚠️ IMPORTANT:** The passwords stored in the database are hashed. The original plain-text passwords cannot be retrieved from the hashes. Below is information about password management.

### Users Requiring Role Assignments

| Username | User ID | Role to Assign | Status |
|----------|---------|----------------|--------|
| admin | 1 | H2 (CEO) | Already assigned |
| adeel.zahoor | 36 | H6 (Network Manager) | Needs assignment |
| Khawar | 83 | H7 (Team Leads) | Needs assignment |
| Kamran | 134 | H9 (External Team Leads) | Needs assignment |
| qasim.ali | 11 | H5 (Sales Person) | Needs assignment |

### Password Information

**Note:** The passwords in the database are stored as hashed values using:
- `user_password`: PHP5.3MD5 crypt format (e.g., `$1$salt$hash`)
- `user_hash`: MD5 hash
- `crypt_type`: 'PHP5.3MD5'

**The actual plain-text passwords cannot be determined from the hashes.**

### Recommended Approach

1. **Generate New Passwords:**
   - Run the script: `php scripts/generate_passwords.php`
   - This will generate new password hashes for all users
   - You can set a default password (e.g., "Password123!")

2. **Set New Passwords:**
   - Use the generated SQL queries from the script
   - Or manually update passwords through the application

3. **Default Password Options:**
   - **Option 1:** Use a common default password for all users (e.g., "Password123!")
   - **Option 2:** Generate unique random passwords for each user
   - **Option 3:** Let users reset passwords via email (if functionality exists)

### Password Generation Script Usage

```bash
# Generate passwords with default password "Password123!"
php scripts/generate_passwords.php > passwords.txt

# This will output:
# - Individual password information
# - SQL UPDATE queries to set passwords
# - Verification details
```

### Manual Password Hash Generation

If you want to generate hashes manually in PHP:

```php
require_once 'helpers/PasswordHelper.php';

$password = 'Password123!';
$hashes = PasswordHelper::generatePasswordHash($password);

echo "user_password: " . $hashes['user_password'] . "\n";
echo "user_hash: " . $hashes['user_hash'] . "\n";
```

### Testing Passwords

To test if a password matches a stored hash:

```php
$password = 'your_password_here';
$storedHash = '$1$ad000000$YtrRnV8m55Q8kLzYfUWG21';

$isValid = (crypt($password, $storedHash) === $storedHash);
echo $isValid ? 'Password matches' : 'Password does not match';
```

### Security Best Practices

1. **Change Default Passwords:** Immediately after setup, change all default passwords
2. **Use Strong Passwords:** Minimum 12 characters with mixed case, numbers, and symbols
3. **Unique Passwords:** Each user should have a unique password
4. **Password Policy:** Enforce password complexity requirements
5. **Regular Updates:** Require password changes periodically

### Common Vtiger Default Passwords

If this is a Vtiger system, common default passwords might be:
- `admin` (for admin user)
- `password` or `Password1`
- Check with your system administrator for actual defaults

### Quick Password Reset

To reset a user's password to a known value:

```sql
-- Example: Set admin password to "admin123"
-- First generate the hash using PHP script, then update:

UPDATE vtiger_users SET
  user_password = '[generated_crypt_hash]',
  user_hash = '[generated_md5_hash]',
  confirm_password = '[generated_crypt_hash]',
  crypt_type = 'PHP5.3MD5'
WHERE id = 1;
```

---

**Last Updated:** Generated automatically
**Script Location:** `scripts/generate_passwords.php`

