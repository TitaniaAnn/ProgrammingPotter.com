# 🏺 Pottery Portfolio Website

A full PHP + MySQL portfolio website with GitHub OAuth admin backend, portfolio gallery, and shop with print-on-demand support.

---

## Project Structure

```
pottery/
├── config/
│   └── config.php           # DB + OAuth credentials (reads .env)
├── includes/
│   ├── bootstrap.php        # Loaded by all pages
│   ├── Database.php         # PDO database helper
│   ├── Auth.php             # GitHub OAuth
│   └── ImageUpload.php      # Image upload + thumbnail
├── public/
│   ├── index.php            # Homepage
│   ├── portfolio.php        # Portfolio gallery + lightbox
│   ├── shop.php             # Shop (pots + merch)
│   ├── about.php            # About page
│   ├── templates/
│   │   ├── nav.php          # Public nav
│   │   └── footer.php       # Public footer
│   ├── css/style.css        # Main stylesheet
│   ├── js/main.js           # Nav + misc JS
│   ├── js/portfolio.js      # Lightbox
│   ├── uploads/             # (created automatically)
│   └── admin/
│       ├── login.php        # GitHub login
│       ├── logout.php
│       ├── dashboard.php
│       ├── auth/callback.php  # GitHub OAuth callback
│       ├── pottery/         # Portfolio CRUD
│       ├── shop/            # Shop CRUD
│       ├── social/          # Social posts + links
│       ├── settings/        # Site content settings
│       ├── css/admin.css
│       ├── js/admin.js
│       └── partials/        # Sidebar, topbar
└── sql/init.sql             # Canonical database schema
```

---

## Setup Instructions

### 1. Database

```bash
mysql -u root -p < sql/init.sql
```

### 2. Config

Copy `.env.example` to `.env` (or create `.env`) at the project root:
```
DB_HOST=localhost
DB_NAME=pottery_portfolio
DB_USER=your_db_user
DB_PASS=your_db_password
GITHUB_CLIENT_ID=your_client_id
GITHUB_CLIENT_SECRET=your_client_secret
ALLOWED_GITHUB_USERS=your-github-username
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

`config/config.php` reads these via `vlucas/phpdotenv` and fails fast if any required value is missing.

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

Sign in with GitHub. Only usernames listed in `ALLOWED_GITHUB_USERS` (in `.env`) can access the admin.

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

- GitHub OAuth only allows usernames listed in `ALLOWED_GITHUB_USERS` (`.env`)
- All admin POST handlers and GET-style delete endpoints validate a per-session CSRF token
- Sessions regenerate their ID on login and run with `httponly` + `samesite=Lax`
- All user input is escaped with `e()`/`htmlspecialchars` or parameterised queries
- Stripe webhooks are deduplicated by event id in `stripe_webhook_events`
- `includes/`, `config/`, `sql/` carry `.htaccess` deny rules
- Enable HTTPS at the web server level
