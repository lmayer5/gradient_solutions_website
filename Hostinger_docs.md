<img src="https://r2cdn.perplexity.ai/pplx-full-logo-primary-dark%402x.png" style="height:64px;margin-right:32px"/>

# Do a search of the Hostinger documentation and provide me a detailed explanation of all of the features and UI so that I can get my agent to help me deploy the site that I made. The main things that I need the site to do with hostinger are 1. host a single index.html site that has a seperate branch for an admin control panel. The main issue I was having was that I was unable to configure the PHP files correctly to get the password and login to work when I deployed the site. The other thing I need is to be able to set up an email account that is attached to the web server. This will be used for sending and recieving emails to both the admin and the customer.

Hostinger’s hPanel lets you do everything you described: host your static + PHP site (with an admin area) and create domain-based email accounts for admin and customers.[^1][^2]

Below is a focused walkthrough of the key UI plus the exact places you’ll wire in your `index.html`, admin PHP login, and email.

***

## hPanel layout and key sections

Hostinger uses **hPanel** instead of cPanel, with the main nav sections at the top: Home, Websites, Domains, Emails, VPS, Billing, WordPress, and Marketplace.[^1]

- **Home**: High-level dashboard with widgets for site status, resource usage, and quick links like File Manager, DNS, backups, and email setup.[^3][^1]
- **Websites → Manage**: This is where you control a specific hosting plan: File Manager, Databases, PHP settings, SSL, redirects, cron jobs, etc.[^3][^1]
- **Domains**: DNS records (A, CNAME, MX, TXT), domain registration and transfers, nameservers for pointing external domains.[^4][^1]
- **Emails**: Central place to create mailboxes like `admin@yourdomain.com` and configure webmail/clients.[^2][^1]

You will mainly live in **Websites → Manage** for deploying the project and **Emails** for mailboxes.

***

## Hosting your index + admin panel

Your end state:

- `https://yourdomain.com/` → `index.html` (public site)
- `https://yourdomain.com/admin/` → PHP admin login \& control panel


### File Manager: where to put files

1. Go to **Websites → Manage** on your hosting plan.[^1]
2. Open **File Manager** and enter the `public_html` directory, which is the document root for your main domain.[^5][^6]
3. Upload and extract your project there (you can upload a ZIP and use the UI “Extract” button).[^6]

Basic structure you want:

- `public_html/index.html` → main site entry point.
- `public_html/admin/index.php` → admin login page.
- `public_html/admin/dashboard.php` (or similar) → actual control panel after login.

If your project currently assumes a different structure, adjust paths or `.htaccess` accordingly.

### Setting the default index file

Hostinger will treat `index.php` or `index.html` as the default directory index, in a configured order.[^6]

- If you only have `index.html` in `public_html`, that will load for `/`.[^6]
- If you add an `index.php` at the root and want HTML instead, either:
    - Remove/rename `index.php`, or
    - Use **Advanced → Index Manager** (or Directory Index via `.htaccess`) to prefer `index.html`.[^1][^6]

For the admin, `https://yourdomain.com/admin/` will automatically load `admin/index.php` if it exists.[^6]

***

## Getting the PHP login working

Your previous issue was that PHP login logic worked locally but not on Hostinger. Typical pain points on Hostinger are:

- Wrong PHP version
- Misconfigured paths / URLs
- Missing or misconfigured MySQL database
- Sessions or `$_POST` not working as expected


### PHP version and configuration

1. In **Websites → Manage**, look for **Advanced → PHP Configuration** or **PHP Version**.[^5][^1]
2. Set the PHP version to the one you used locally (e.g., 8.1 or 8.2).[^5][^6]
3. Under **PHP extensions**, ensure `mysqli` or `pdo_mysql` is enabled if you use MySQL.[^5]

Enable error display while debugging:

- Use **Advanced → PHP Configuration**’s options or temporarily add:

```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

at the top of your login script to see exactly what’s failing.[^5]


### Databases: creating and connecting

If your login checks a user table:

1. In **Websites → Manage**, open **Databases → MySQL Databases**.[^6][^5]
2. Create:
    - Database name
    - Database user
    - Password
and note the **host**, which on shared hosting is often something like `mysql.hostinger.com` (not `localhost` in some regions).[^5][^6]
3. Open **phpMyAdmin** from the same Databases section and import your `.sql` file (the DB schema/users) via the **Import** tab.[^6][^5]

Update your PHP config (e.g., `config.php`) to match Hostinger’s credentials:

```php
$host = 'your-mysql-host';
$dbname = 'u123456_dbname';
$user = 'u123456_dbuser';
$pass = 'your_strong_password';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
```


### Admin login URL routing and links

Common mistakes: hard-coded local URLs or wrong relative paths.

- Use paths like `/admin/index.php` or `./` within the `admin` folder so they work on the deployed structure.[^5]
- Make sure any redirects after login use your actual domain, e.g.:

```php
header('Location: /admin/dashboard.php');
exit;
```

rather than `http://localhost/...`.[^5]


### Sessions and authentication

If you use `$_SESSION` for auth:

- Ensure `session_start();` is at the top of all pages that rely on session data.[^5]
- Confirm that your admin folder does not have restrictive `.htaccess` rules blocking PHP execution or sessions.[^6]

At this point, your login should:

1. POST credentials from `admin/index.php`.
2. Query the Hostinger MySQL DB using the correct host/user/pass.
3. Set a session or token and redirect to `admin/dashboard.php` on success.

***

## Setting up domain email for admin + customer

You want addresses like `admin@yourdomain.com` and something for customer communications.

### Enabling email service and creating mailboxes

1. Go to the **Emails** tab in hPanel.[^2][^1]
2. Click **Set up free email service → Set up** for your domain.[^2]
3. Choose your domain and confirm.[^2]
4. Select **Hostinger Free** (included mailbox), which is enough for transactional and basic use.[^2]
5. Create accounts:
    - `admin@yourdomain.com`
    - `support@yourdomain.com` or `orders@yourdomain.com`
by clicking **Create** and setting username + password.[^7][^2]

Once created, use **Webmail** from the Emails section to send/receive directly in the browser.[^2]

### DNS and MX records

If your domain is at Hostinger and uses Hostinger nameservers, MX records are auto-configured for email.[^1][^2]

If your domain is external (e.g., Namecheap, Cloudflare):

- In **Domains → DNS** at the registrar, set Hostinger’s nameservers so hPanel’s email config applies.[^4][^1]
- Or manually set MX records and SPF/TXT as indicated under Emails → DNS settings for the domain.[^2]


### Using email from your PHP code (contact forms, order notices)

In a simple setup, you have two patterns:

- **Send form to admin only**
- **Send confirmation to both admin and customer**

To avoid deliverability problems and auth issues, use `admin@yourdomain.com` as the sender (`From`) and the customer’s email as the recipient or `Reply-To`.[^2]

Example using `mail()` (basic):

```php
$adminEmail = 'admin@yourdomain.com';
$customerEmail = $_POST['email'];
$subject = 'New order from website';
$message = 'Order details...';

$headers  = "From: admin@yourdomain.com\r\n";
$headers .= "Reply-To: " . $customerEmail . "\r\n";

mail($adminEmail, $subject, $message, $headers);
```

For sending a confirmation to the customer:

```php
$subjectCustomer = 'We received your order';
$messageCustomer = 'Thanks, we will contact you soon.';

$headersCustomer  = "From: admin@yourdomain.com\r\n";
$headersCustomer .= "Reply-To: admin@yourdomain.com\r\n";

mail($customerEmail, $subjectCustomer, $messageCustomer, $headersCustomer);
```

If you need more reliability (DKIM, better spam handling), hPanel’s Emails section exposes SMTP settings, and you can send via SMTP in PHP (e.g., PHPMailer) using `smtp.hostinger.com`, your mailbox and password.[^2]

***

## Where your agent should focus in hPanel

When you wire this into an agent workflow or scripted steps, these UI targets are key:

- **Websites → Manage → File Manager → public_html**
    - Upload \& manage `index.html` and `admin/` PHP code.[^6]
- **Websites → Manage → Databases → MySQL + phpMyAdmin**
    - Create DB, user, import schema, and ensure connection credentials for login.[^6][^5]
- **Websites → Manage → PHP Configuration**
    - Ensure correct PHP version and extensions, enable error display while debugging.[^1][^5]
- **Emails → (your domain)**
    - Set up free email service, create `admin@yourdomain.com` and customer-facing mailbox, view SMTP settings.[^1][^2]
- **Domains → DNS** (if needed)
    - Make sure MX records and nameservers are correct so email actually works.[^4][^1]

If you paste your current admin login PHP (with any secrets stripped) and describe how the project is laid out on disk, the next step can be a concrete “drop this in Hostinger and it will work” checklist tailored to your exact code.
<span style="display:none">[^10][^8][^9]</span>

<div align="center">⁂</div>

[^1]: https://www.hostinger.in/tutorials/hpanel-tutorial

[^2]: https://www.hostinger.com/support/hostinger-email-creating-an-email-account-setting-up-email-clients-and-troubleshooting/

[^3]: https://www.reddit.com/r/YourDigitalJourney/comments/1kozxos/complete_beginners_guide_to_hostingers_hpanel/

[^4]: https://elementor.com/blog/a-guide-to-hpanel/

[^5]: https://www.youtube.com/watch?v=zBfn1rcpnAs

[^6]: https://www.youtube.com/watch?v=gtKakiKsTFE

[^7]: https://www.hostinger.com/support/4437981-how-to-create-and-manage-email-accounts-on-cpanel-at-hostinger/

[^8]: https://www.hostinger.com/support/1583483-comprehensive-guide-to-hpanel-at-hostinger/

[^9]: https://www.hostinger.com/tutorials/video-hostinger-hpanel-walk-through-and-important-settings

[^10]: https://www.youtube.com/watch?v=_7bHvKKtqGM

