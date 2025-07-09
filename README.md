# Open Source DIG Web Interface

A PHP-based web interface for DNS lookups using the `dig` command. This is an open-source alternative to online DIG tools, designed for local hosting.

## Features

- Multiple DNS record type queries (A, AAAA, MX, CNAME, NS, PTR, SOA, TXT, etc.)
- Custom nameserver configuration
- Multiple hostname queries in a single request
- Various dig options (+short, +trace, +dnssec, etc.)
- Colorized output
- Clickable hostnames and IP addresses in results
- URL-based query sharing
- Keyboard shortcuts (Ctrl+Enter to submit, Ctrl+L to clear)
- Automatic URL/email to hostname conversion

## Requirements

- PHP 7.4 or higher
- `dig` command installed (part of `bind-utils` or `dnsutils` package)
- Web server (Apache, Nginx, etc.)

## Installation

### Using DDEV (Recommended for Development)

1. Clone this repository:
   ```bash
   git clone <repository-url> opensource-digwebinterface
   cd opensource-digwebinterface
   ```

2. Start DDEV:
   ```bash
   ddev start
   ```

3. The application will be available at:
   ```
   https://opensource-digwebinterface.ddev.site
   ```

That's it! DDEV automatically installs all dependencies including the `dig` command.

### Manual Installation

1. Clone or download this repository to your web server directory
2. Ensure the `dig` command is installed:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install dnsutils
   
   # CentOS/RHEL/Fedora
   sudo yum install bind-utils
   ```
3. Configure your web server to point to the project directory
4. Update `config/config.php` if needed (especially the `dig_path` if it's not at `/usr/bin/dig`)

## Configuration

Edit `config/config.php` to customize:
- Site name and description
- Path to dig executable
- Default timeout
- Default nameservers
- Available record types
- Maximum number of hostnames/nameservers per query

## Usage

1. Open the interface in your web browser
2. Enter one or more hostnames or IP addresses
3. Optionally specify custom nameservers
4. Select the DNS record type
5. Choose additional options
6. Click "Dig!" or press Ctrl+Enter

## Security

This tool is designed for local/private network use. If exposing to the internet:
- Implement rate limiting
- Add authentication
- Sanitize all inputs (already implemented)
- Consider using a reverse proxy

## License

MIT License