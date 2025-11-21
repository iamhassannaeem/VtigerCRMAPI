# Quick Reference Card

## 🚀 Quick Start

```bash
# 1. Configure database
cp config/database.example.php config/database.php
nano config/database.php

# 2. Configure JWT
cp config/jwt.example.php config/jwt.php
nano config/jwt.php

# 3. Test
curl http://localhost/vtiger-api/public/
```

## 📍 API Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/auth/login` | POST | ❌ | Login |
| `/dashboard` | GET | ✅ | Statistics |
| `/tickets` | GET | ✅ | List tickets |
| `/tickets/{id}` | GET | ✅ | Ticket details |
| `/tickets` | POST | ✅ | Create ticket |
| `/tickets/{id}/comments` | POST | ✅ | Add comment |
| `/me` | GET | ✅ | User profile |

## 🔑 Authentication

```bash
# Login
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}'

# Use token
curl -X GET http://localhost/vtiger-api/public/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 📊 Response Format

### Success
```json
{
  "success": true,
  "data": { ... }
}
```

### Error
```json
{
  "success": false,
  "message": "Error description"
}
```

## 🔧 Configuration Files

- `config/database.php` - Database settings
- `config/jwt.php` - JWT secret key

## 📂 Project Structure

```
vtiger-api/
├── config/          # Configuration
├── core/            # Database & Router
├── helpers/         # JWT helper
├── models/          # Business logic
├── controllers/     # Request handlers
└── public/          # Entry point
    └── index.php
```

## 🐛 Troubleshooting

### 404 Errors
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Database Connection
```bash
# Test connection
mysql -u root -p

# Check config
cat config/database.php
```

### Permissions
```bash
chmod -R 755 .
chown -R www-data:www-data .
```

### Logs
```bash
# Apache error log
tail -f /var/log/apache2/error.log
```

## 📝 Common Queries

### List open tickets
```
GET /tickets?status=Open&limit=10
```

### Search tickets
```
GET /tickets?search=email&limit=20
```

### Filter by user (admin only)
```
GET /tickets?assigned_to=5
```

## 🔒 Security

- ✅ JWT tokens expire in 24 hours
- ✅ Passwords hashed with MD5
- ✅ SQL injection protected
- ✅ Admin/user access control
- ✅ Config files git-ignored

## 📦 Files Created

**Total: 32 files**

- 4 Config files
- 2 Core files
- 1 Helper file
- 3 Models
- 4 Controllers
- 2 Public files
- 3 Root files (.htaccess, .gitignore)
- 8 Documentation files
- 1 Postman collection

## 🎯 HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `500` - Server Error

## 💾 Database Tables

Main tables used:
- `vtiger_users`
- `vtiger_crmentity`
- `vtiger_troubletickets`
- `vtiger_ticketcf`
- `vtiger_ticketcomments`
- `vtiger_account`
- `vtiger_contactdetails`
- `vtiger_role`

## 🧪 Testing

### Import Postman Collection
1. Open Postman
2. Import `POSTMAN_COLLECTION.json`
3. Update `base_url` variable
4. Run "Login" first
5. Test other endpoints

### Manual Testing
```bash
# Health check
curl http://localhost/vtiger-api/public/

# Login
curl -X POST http://localhost/vtiger-api/public/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}'

# Dashboard
curl -X GET http://localhost/vtiger-api/public/dashboard \
  -H "Authorization: Bearer TOKEN"
```

## 📖 Documentation

- `README.md` - Main docs
- `API_EXAMPLES.md` - Examples
- `DEPLOYMENT.md` - Deployment
- `INSTALLATION.md` - Quick install
- `PROJECT_STRUCTURE.md` - Architecture
- `COMPLETE_SUMMARY.md` - Full summary
- `QUICK_REFERENCE.md` - This file

## ⚡ Performance Tips

1. Enable OPcache
2. Use database indexes
3. Enable gzip compression
4. Monitor slow queries
5. Cache static data

## 🌐 URLs

### Development
```
http://localhost/vtiger-api/public/
```

### Production
```
https://api.yourdomain.com/
```

## 🔐 Production Checklist

- [ ] Change JWT secret key
- [ ] Disable error display
- [ ] Enable HTTPS
- [ ] Strong database password
- [ ] Set file permissions (755/644)
- [ ] Enable firewall
- [ ] Setup backups
- [ ] Monitor logs

## 💡 Tips

1. **Token expires?** Login again to get new token
2. **403/404?** Check mod_rewrite is enabled
3. **500 error?** Check Apache error logs
4. **Admin vs User?** Check `is_admin` field in database
5. **Custom fields?** Automatically included in ticket details

---

**Need help?** Check the full documentation in `README.md` or `COMPLETE_SUMMARY.md`

