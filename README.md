# Rewrite for nice URLs

Either in apache config, or in .htaccess

```
RewriteEngine on
RewriteRule ^/([0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12})$ /index.php?location=$1
```

# Forbid export.php
```
<Directory /path/to/export.php>
        Order Deny,Allow
        Deny from all
        Allow from 192.168.0.0/24
</Directory>
```
