# Cache Directory

This directory stores temporary cache files to improve API performance.

## What gets cached:
- Dashboard data (5-10 minutes TTL)
- Other expensive queries as needed

## Maintenance:
- Cache files are automatically cleaned up when expired
- No manual intervention needed
- Safe to delete all `.cache` files if needed

## Security:
- `.htaccess` denies direct web access
- Cache files use MD5 hashed names
- Contains serialized PHP data only

## Clear Cache:
You can clear all cache by calling:
```
GET /debug/cache/clear
```

