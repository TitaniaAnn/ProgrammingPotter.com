# 🏺 Pottery Portfolio Website

A full PHP + MySQL portfolio website with Google OAuth admin backend, portfolio gallery, and shop with print-on-demand support.

---

## Project Structure

```
pottery/
├── config/
│   └── config.php           # DB + OAuth credentials
├── includes/
│   ├── bootstrap.php        # Loaded by all pages
│   ├── Database.php         # PDO database helper
│   ├── Auth.php             # Google OAuth
│   └── ImageUpload.php      # Image upload + thumbnail
├── templates/
│   ├── nav.php              # Public nav
│   └── footer.php           # Public footer
├── public/
│   ├── index.php            # Homepage
│   ├── portfolio.php        # Portfolio gallery + lightbox
│   ├── shop.php             # Shop (pots + merch)
│   ├── about.php            # About page
│   ├── css/style.css        # Main stylesheet
│   ├── js/main.js           # Nav + misc JS
│   ├── js/portfolio.js      # Lightbox
│   └── uploads/             # (created automatically)
├── admin/
│   ├── login.php            # Google login
│   ├── logout.php
│   ├── dashboard.php
│   ├── auth/callback.php    # Google OAuth callback
│   ├── pottery/             # Portfolio CRUD
│   ├── shop/                # Shop CRUD
│   ├── social/              # Social posts + links
│   ├── settings/            # Site content settings
│   ├── css/admin.css
│   ├── js/admin.js
│   └── partials/            # Sidebar, topbar
└── schema.sql               # Database schema
```

---

## Setup Instructions

### 1. Database

```bash
mysql -u root -p < schema.sql
```

### 2. Config

Edit `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pottery_portfolio');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('SITE_URL', 'https://yourdomain.com');

define('GOOGLE_CLIENT_ID',     'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('ALLOWED_ADMIN_EMAILS', 'your@gmail.com');
```

### 3. GitHub OAuth Setup

1. Go to https://github.com/settings/developers
2. Click **OAuth Apps → New OAuth App**
   - Application name: `My Pottery Admin` (anything)
   - Homepage URL: `https://yourdomain.com`
   - Authorization callback URL: `https://yourdomain.com/admin/auth/callback.php`
3. Click **Register application**
4. Copy **Client ID** and generate a **Client Secret**
5. Paste both into `config.php`:

```php
define('GITHUB_CLIENT_ID',     'your_client_id');
define('GITHUB_CLIENT_SECRET', 'your_client_secret');
define('ALLOWED_GITHUB_USERS', 'your-github-username');
```

### 4. Web Server

**Apache** — Point document root to the project root, or set up a VirtualHost:
```apache
<VirtualHost *:80>
    DocumentRoot /var/www/pottery
    ServerName yourdomain.com
    <Directory /var/www/pottery>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/pottery;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$args; }
    location ~ \.php$ { fastcgi_pass unix:/run/php/php8.2-fpm.sock; include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; }
    location ~* \.(jpg|jpeg|png|gif|webp|ico|css|js)$ { expires 30d; }
}
```

### 5. Uploads directory

```bash
mkdir -p public/uploads/pottery public/uploads/products
chmod -R 755 public/uploads
chown -R www-data:www-data public/uploads  # Linux
```

### 6. PHP Requirements
- PHP 8.0+
- Extensions: PDO, PDO_MySQL, GD (for thumbnails), cURL (for OAuth)

---

## Admin Access

Visit: `https://yourdomain.com/admin/login.php`

Sign in with your Google account. Only emails listed in `ALLOWED_ADMIN_EMAILS` can access the admin.

---

## Admin Features

| Section | What you can do |
|---|---|
| **Portfolio** | Add/edit/delete pottery pieces with photos, technique, dimensions, year. Mark as featured. |
| **Shop → Pots** | Add individual pots for sale with price, availability, enquiry email. |
| **Shop → Merch** | Add print-on-demand products with provider, product URL (Printful, Printify, Redbubble). |
| **Social Posts** | Add posts by URL + thumbnail to show on homepage. |
| **Social Links** | Manage Instagram, TikTok, Pinterest, YouTube links. |
| **Settings** | Edit site name, tagline, bio, hero text, about text. |

---

## Print-on-Demand Setup

### Printful
1. Create your products at printful.com
2. Copy the product URL or your storefront URL
3. In Admin → Add Product → Type: Merch → Provider: Printful → Paste URL

### Printify
Same process — use your Printify store URL or individual product links.

### Redbubble
Add your Redbubble shop/product URLs. Customers click "Buy Now" and go to Redbubble.

---

## Social Media Posts

Since Instagram/TikTok restrict direct API access, the recommended workflow is:

1. **Thumbnail method**: Upload your post image somewhere (Cloudinary, your own server, etc.), paste the image URL + post URL in Admin → Social Posts.
2. **Embed method**: Paste the embed `<iframe>` code directly (works for TikTok, YouTube).

The homepage shows posts marked as "featured".

---

## Customisation

- **Colours**: Edit `:root` variables in `public/css/style.css`
- **Fonts**: Change the Google Fonts import at the top of any page
- **Adding pages**: Create a new `.php` file in the root, include `bootstrap.php` and the nav/footer templates
- **Categories**: Manage shop categories directly in the database (`shop_categories` table)

---

## Security Notes

- Google OAuth only allows your specific email(s) in `ALLOWED_ADMIN_EMAILS`
- Uploaded files are stored outside web-accessible directories (configure your server)
- All user input is escaped with `htmlspecialchars` or parameterised queries
- Enable HTTPS and uncomment the redirect in `.htaccess`
