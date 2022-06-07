# Nextcloud Limit Login to Ip

Similar to the official [limit_login_to_ip](https://github.com/nextcloud/limit_login_to_ip) nextcloud app  but applies ip limit only to specific groups. 

<div style="text-align: center; font-size: 24px; font-weight: bold;"> No support, no proper ui ⚠️ </div>

# Installation

Place this app in **nextcloud/apps/**

# Configuration

No ui, available. Configurate via config.php like this:

```php
$CONFIG = array(
    // ...
    'limit_login_ip_groups' => array(
        'GROUPNAME' => ['192.168.1.1/24', '10.8.0.1/24'] // whitelist of allowed ip ranges for GROUPNAME
    )
    // ...
);

```
