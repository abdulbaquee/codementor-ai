# Environment Configuration for Review System

## Overview

The review system now supports environment-based configuration for secure URL generation and flexible deployment across different environments.

## Environment Variables

### Base URL Configuration

#### `APP_URL` (Laravel Standard)
- **Purpose**: Primary URL for the Laravel application
- **Format**: `https://example.com` or `http://localhost:8000`
- **Priority**: Highest
- **Example**: `APP_URL=https://myapp.com`

#### `REVIEW_BASE_URL` (Review System Specific)
- **Purpose**: Override URL specifically for review system reports
- **Format**: `https://example.com` or `http://localhost:8000`
- **Priority**: High (if APP_URL not set)
- **Example**: `REVIEW_BASE_URL=https://reports.myapp.com`

### Port Configuration

#### `APP_PORT` (Laravel Standard)
- **Purpose**: Port number for the Laravel application
- **Format**: `8000`, `3000`, etc.
- **Priority**: Highest
- **Example**: `APP_PORT=8000`

#### `REVIEW_PORT` (Review System Specific)
- **Purpose**: Override port specifically for review system
- **Format**: `8000`, `3000`, etc.
- **Priority**: High (if APP_PORT not set)
- **Example**: `REVIEW_PORT=8080`

## Configuration Priority

The system checks for configuration in this order:

1. **Environment Variables** (highest priority)
   - `APP_URL` / `REVIEW_BASE_URL`
   - `APP_PORT` / `REVIEW_PORT`

2. **Laravel .env File**
   - Reads `APP_URL` and `APP_PORT` from `.env`

3. **Fallback Values** (lowest priority)
   - `http://localhost` (base URL)
   - Standard ports (80/443)

## Examples

### Development Environment
```bash
# .env file
APP_URL=http://localhost:8000
APP_PORT=8000

# Note: The system automatically converts common development ports (8000, 3000, 8080, 9000) 
# to standard ports (80/443) for localhost URLs to ensure compatibility with most web servers.
```

### Production Environment
```bash
# .env file
APP_URL=https://myapp.com
APP_PORT=443
```

### Custom Review Server
```bash
# Environment variables
REVIEW_BASE_URL=https://reports.myapp.com
REVIEW_PORT=8080
```

## Smart Port Handling

### Development Port Conversion
The system automatically handles common development scenarios:

- **Laravel Artisan Serve**: `http://localhost:8000` → `http://localhost`
- **Node.js Development**: `http://localhost:3000` → `http://localhost`
- **Custom Development**: `http://localhost:8080` → `http://localhost`

This ensures compatibility with most web server configurations while still allowing explicit port specification when needed.

## Security Considerations

### ✅ Secure Practices
- Uses HTTPS in production environments
- Respects Laravel's `APP_URL` configuration
- Falls back to localhost only in development
- Supports custom ports for different environments
- Smart port conversion for development environments

### ⚠️ Security Notes
- Reports are still accessible via web server
- Consider adding authentication for production reports
- Ensure proper file permissions on report directory

## Testing Configuration

You can test your configuration by running:

```bash
# Test with environment variables
REVIEW_BASE_URL=https://test.com php review-system/cli.php

# Test with Laravel .env
php review-system/cli.php
```

## Troubleshooting

### Common Issues

1. **Reports not accessible**
   - Check web server configuration
   - Verify file permissions on `review-system/reports/`
   - Ensure URL is correct for your environment

2. **Wrong URL generated**
   - Check environment variable spelling
   - Verify `.env` file format
   - Clear any cached configurations

3. **Port issues**
   - Ensure port is not blocked by firewall
   - Check if port is already in use
   - Verify web server is listening on correct port 