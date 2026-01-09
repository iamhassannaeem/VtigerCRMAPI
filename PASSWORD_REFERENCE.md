# User Passwords and Role Assignments - Quick Reference

## Summary

This document provides a quick reference for user passwords and role assignments for the Vtiger API system.

## Default Passwords

**⚠️ IMPORTANT:** All users mentioned below have been assigned the default password:

### **Password: `Password123!`**

This password works for all the following users:

| Username | User ID | Full Name | Assigned Role |
|----------|---------|-----------|---------------|
| `admin` | 1 | ConnecTEL Private Limited | H2 (CEO) |
| `adeel.zahoor` | 36 | Adeel Zahoor (NOC) | H6 (Network Manager) |
| `Khawar` | 83 | Khawar Ejaz (NOC) | H7 (Team Leads) |
| `Kamran` | 134 | Kamran Shah (Fiber) | H9 (External Team Leads) |
| `qasim.ali` | 11 | Qasim Ali (Sales) | H5 (Sales Person) |

## Quick Login Credentials

```
Username: admin
Password: Password123!
Role: CEO (H2)
```

```
Username: adeel.zahoor
Password: Password123!
Role: Network Manager (H6)
```

```
Username: Khawar
Password: Password123!
Role: Team Leads (H7)
```

```
Username: Kamran
Password: Password123!
Role: External Team Leads (H9)
```

```
Username: qasim.ali
Password: Password123!
Role: Sales Person (H5)
```

## SQL Scripts Location

All SQL scripts are located in the `sql/` directory:

- **`sql/COMPLETE_SETUP.sql`** - Complete setup script (roles + passwords)
- **`sql/user_role_mappings.sql`** - Role assignments only
- **`sql/update_user_passwords.sql`** - Password updates only

## Generating New Passwords

If you need to set a different default password:

1. **Edit the script:**
   ```bash
   # Open scripts/generate_password_hashes.php
   # Change line: $defaultPassword = 'Password123!';
   ```

2. **Run the script:**
   ```bash
   php scripts/generate_password_hashes.php > new_passwords.sql
   ```

3. **Execute the generated SQL:**
   ```bash
   mysql -u username -p database_name < new_passwords.sql
   ```

## Password Hash Format

The system uses **PHP5.3MD5** crypt format:
- Format: `$1$salt$hash`
- Example: `$1$ad000000$rJBq4bwKpyFi0PqHn57PO0`

The `user_hash` field stores the MD5 hash of the password.

## Security Recommendations

1. ✅ **Change default passwords immediately** after first login
2. ✅ **Use strong passwords** (minimum 12 characters, mixed case, numbers, symbols)
3. ✅ **Don't share passwords** between users
4. ✅ **Enable password policy** in the application if available
5. ✅ **Regular password updates** (e.g., every 90 days)

## Testing Passwords

To test if a password works:

```php
$password = 'Password123!';
$storedHash = '$1$ad000000$rJBq4bwKpyFi0PqHn57PO0';

$isValid = (crypt($password, $storedHash) === $storedHash);
echo $isValid ? '✓ Password matches' : '✗ Password does not match';
```

Or use the API:
```bash
curl -X POST http://your-domain/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"Password123!"}'
```

## Troubleshooting

**Problem:** Password not working after SQL update
- **Solution:** Ensure you've updated all three fields: `user_password`, `user_hash`, and `confirm_password`

**Problem:** Role not showing up
- **Solution:** Verify the INSERT query succeeded and check `vtiger_user2role` table

**Problem:** User cannot login
- **Solution:** Check that `status = 'Active'` and `deleted = 0` in `vtiger_users` table

## Related Files

- `helpers/PasswordHelper.php` - PHP class for password hash generation
- `scripts/generate_password_hashes.php` - Standalone password generator
- `scripts/generate_passwords.php` - Database-connected password generator
- `USER_PASSWORDS.md` - Detailed password documentation

---

**Last Updated:** Generated automatically  
**Default Password:** `Password123!`  
**All passwords must be changed after first login!**

