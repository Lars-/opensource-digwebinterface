# 🌐 Open Source DIG Web Interface

<div align="center">

![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-8892BF?style=for-the-badge&logo=php)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)
![Platform](https://img.shields.io/badge/Platform-Web-blue?style=for-the-badge)

A modern, secure web interface for DNS lookups using the `dig` command. Perfect for self-hosting your own DNS lookup tool with a clean, responsive interface.

[Features](#-features) • [Installation](#-installation) • [Usage](#-usage) • [Configuration](#%EF%B8%8F-configuration) • [Security](#-security)

</div>

## ✨ Features

### 🔍 DNS Query Capabilities
- **Multiple Record Types**: Query 50+ DNS record types including A, AAAA, MX, CNAME, NS, PTR, SOA, TXT, CAA, HTTPS, SVCB, and more
- **Batch Queries**: Look up multiple hostnames in a single request
- **Reverse DNS**: Automatic PTR record lookups for IP addresses
- **DNSSEC Support**: Validate DNSSEC signatures with the `+dnssec` option

### 🌍 Nameserver Options
- **15+ Public Resolvers**: Pre-configured popular DNS resolvers including:
  - Google (8.8.8.8, 8.8.4.4)
  - Cloudflare (1.1.1.1, 1.0.0.1)
  - Quad9 (9.9.9.9, 149.112.112.112)
  - OpenDNS, AdGuard, Yandex, and more
- **Custom Nameservers**: Add your own DNS servers
- **Authoritative Queries**: Query authoritative nameservers directly
- **NIC/Registry Queries**: Query TLD nameservers for domain information
- **Parallel Queries**: Query all resolvers simultaneously for comparison

### 🎨 User Interface
- **Modern Design**: Clean, responsive interface that works on all devices
- **Real-time AJAX**: Asynchronous queries with live progress updates
- **Syntax Highlighting**: Colorized output for different DNS record types
- **Clickable Results**: Click on IPs or domains to add them to your query
- **Dark Mode Ready**: Easy on the eyes with proper color contrast

### 🛠️ Advanced Features
- **URL/Email Parsing**: Automatically extract domains from URLs and email addresses
- **Query Options**: Support for dig flags like `+short`, `+trace`, `+tcp`, `+noquestion`
- **Share URLs**: Generate shareable links for specific queries
- **Command Display**: See the exact dig command being executed
- **Export Results**: Copy commands or results with one click
- **Keyboard Shortcuts**: 
  - `Ctrl+Enter`: Submit query
  - `Ctrl+L`: Clear form

### 🔧 Technical Features
- **PHP 8.3+ Compatible**: Uses modern PHP features with type declarations
- **Security First**: Input sanitization, command escaping, no shell injection
- **No Database Required**: Simple file-based configuration
- **Zero Dependencies**: No composer packages or external libraries needed
- **Progressive Enhancement**: Works without JavaScript, enhanced with it

## 📋 Requirements

### For Docker Installation (Recommended)
- **Docker** 20.10 or higher
- **Docker Compose** v2 or higher

### For Manual Installation
- **PHP 8.3** or higher with the following extensions:
  - `json` (for AJAX API)
  - `filter` (for input validation)
- **dig command** (part of `bind-utils` or `dnsutils` package)
- **Web server**: Apache, Nginx, or any PHP-compatible server
- **Modern browser**: Chrome, Firefox, Safari, or Edge

## 🚀 Installation

### Using Docker Compose (Recommended)

1. **Clone the repository**:
   ```bash
   git clone https://github.com/Lars-/opensource-digwebinterface.git
   cd opensource-digwebinterface
   ```

2. **Start the containers**:
   ```bash
   docker compose up -d
   ```

3. **Access the interface**:
   ```
   http://localhost:8080
   ```

4. **View logs** (optional):
   ```bash
   docker compose logs -f
   ```

5. **Stop the containers**:
   ```bash
   docker compose down
   ```

#### Docker Features
- **PHP 8.3** with FPM for optimal performance
- **Nginx** web server with optimized configuration
- **Alpine Linux** base for minimal image size
- **dig command** pre-installed and configured
- **Volume mounts** for easy development
- **Automatic permissions** handling for cache directory

### Using DDEV (Alternative for Development)

1. **Clone the repository**:
   ```bash
   git clone https://github.com/Lars-/opensource-digwebinterface.git
   cd opensource-digwebinterface
   ```

2. **Start DDEV**:
   ```bash
   ddev start
   ```

3. **Access the interface**:
   ```
   https://opensource-digwebinterface.ddev.site
   ```

### Manual Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/Lars-/opensource-digwebinterface.git
   cd opensource-digwebinterface
   ```

2. **Install dig command**:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install dnsutils
   
   # CentOS/RHEL/Fedora
   sudo yum install bind-utils
   
   # macOS (using Homebrew)
   brew install bind
   ```

3. **Configure your web server**:

   **Apache** (`.htaccess` example):
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteRule ^api/(.*)$ api/$1 [L]
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule ^(.*)$ index.php [QSA,L]
   </IfModule>
   ```

   **Nginx** configuration:
   ```nginx
   server {
       listen 80;
       server_name your-domain.com;
       root /path/to/opensource-digwebinterface;
       index index.php;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location /api {
           try_files $uri $uri/ /api/query.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
           fastcgi_index index.php;
           include fastcgi_params;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
       }
   }
   ```

4. **Set permissions**:
   ```bash
   chmod 755 cache/
   ```

## 🎯 Usage

### Basic Query
1. Enter a hostname (e.g., `example.com`) in the text area
2. Select a DNS record type (default: A)
3. Choose a resolver or use the default
4. Click "Dig!" or press `Ctrl+Enter`

### Advanced Queries

**Multiple Hostnames**:
```
google.com
cloudflare.com
github.com
```

**Query All Resolvers**:
- Select "All resolvers" to compare results across all configured DNS servers

**Authoritative Lookup**:
- Select "Authoritative" to query the domain's authoritative nameservers directly

**Custom Nameservers**:
```
8.8.8.8
1.1.1.1
9.9.9.9
```

**URL/Email Conversion**:
- Enable "Fix" to automatically extract domains:
  - `https://example.com/page` → `example.com`
  - `user@example.com` → `example.com`

## ⚙️ Configuration

Edit `config/config.php` to customize:

```php
return [
    // Site branding
    'site_name' => 'Your DNS Tool',
    'site_description' => 'Custom description',
    
    // dig command location (auto-detected in most cases)
    'dig_path' => '/usr/bin/dig',
    
    // Query timeout (seconds)
    'default_timeout' => 5,
    
    // Add custom resolvers
    'resolvers' => [
        'custom' => [
            'name' => 'My DNS Server',
            'servers' => ['192.168.1.1', '192.168.1.2']
        ],
        // ... existing resolvers
    ],
    
    // Limit simultaneous queries
    'max_hostnames' => 10,
    'max_nameservers' => 5,
];
```

## 🔒 Security

### Built-in Protections
- **Input Sanitization**: All user inputs are validated and sanitized
- **Command Injection Prevention**: Uses `escapeshellarg()` and `escapeshellcmd()`
- **No Direct Shell Access**: Commands are built programmatically
- **XSS Protection**: All output is HTML-escaped
- **CSRF Protection**: Can be added via middleware

### Deployment Security Checklist

1. **Restrict Access** (if needed):
   ```apache
   # .htaccess for IP restriction
   <RequireAll>
       Require ip 192.168.1.0/24
       Require ip 10.0.0.0/8
   </RequireAll>
   ```

2. **Add Rate Limiting**:
   ```php
   // In api/query.php
   session_start();
   $requests = $_SESSION['requests'] ?? [];
   $requests = array_filter($requests, fn($t) => $t > time() - 60);
   if (count($requests) > 30) {
       http_response_code(429);
       die(json_encode(['error' => 'Too many requests']));
   }
   $_SESSION['requests'] = [...$requests, time()];
   ```

3. **Enable HTTPS**:
   ```apache
   # Force HTTPS redirect
   RewriteCond %{HTTPS} off
   RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

4. **Add Authentication** (optional):
   ```php
   // Basic auth example
   $valid_users = ['user' => password_hash('password', PASSWORD_DEFAULT)];
   // Add to index.php and api/query.php
   ```

## 🎨 Customization

### Themes
The interface uses CSS custom properties for easy theming:

```css
:root {
    --primary: #0066cc;
    --primary-hover: #0052a3;
    --success: #00a651;
    --danger: #d32f2f;
    /* Modify these in main.css */
}
```

### Adding Record Types
Add new DNS record types in `config/config.php`:

```php
'record_types' => [
    'NEWTYPE' => 'NEWTYPE Description',
    // ... existing types
],
```

## 🐳 Docker Troubleshooting

### Common Issues

1. **Port 8080 already in use**:
   ```bash
   # Change the port in docker-compose.yml
   ports:
     - "8081:80"  # Use port 8081 instead
   ```

2. **Permission denied errors**:
   ```bash
   # Rebuild with proper permissions
   docker compose down
   docker compose build --no-cache
   docker compose up -d
   ```

3. **dig command not working**:
   ```bash
   # Test dig inside container
   docker compose exec php dig google.com
   
   # Check dig path
   docker compose exec php which dig
   ```

4. **Changes not reflecting**:
   ```bash
   # Restart services
   docker compose restart
   
   # Or rebuild if needed
   docker compose down
   docker compose up -d --build
   ```

### Docker Commands Reference

```bash
# Start services
docker compose up -d

# Stop services
docker compose down

# View logs
docker compose logs -f
docker compose logs -f php    # PHP logs only
docker compose logs -f nginx  # Nginx logs only

# Execute commands in container
docker compose exec php sh                    # Shell access
docker compose exec php dig example.com       # Run dig command
docker compose exec php php -v                # Check PHP version

# Rebuild images
docker compose build
docker compose build --no-cache               # Force rebuild

# Remove everything (including volumes)
docker compose down -v
```

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'feat: add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Add PHPDoc comments for new methods
- Test with PHP 8.3+
- Ensure no security vulnerabilities

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Built with PHP and vanilla JavaScript
- Uses the powerful `dig` command from ISC BIND
- Inspired by online DNS lookup tools
- Icon and emoji designs from OpenMoji

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/Lars-/opensource-digwebinterface/issues)
- **Discussions**: [GitHub Discussions](https://github.com/Lars-/opensource-digwebinterface/discussions)
- **Security**: Please report security issues privately

## ☕ Support the Developer

If you find this tool useful, consider buying me a coffee!

<div align="center">
<a href="https://www.buymeacoffee.com/Lars-" target="_blank">
<img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" width="200">
</a>
</div>

---

<div align="center">
Made with ❤️ for the DNS community
</div>