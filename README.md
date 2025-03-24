# Tin Refinery ERP System

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-green.svg)](https://vuejs.org)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![Xero](https://img.shields.io/badge/Xero-API-green.svg)](https://developer.xero.com/)

A comprehensive Enterprise Resource Planning (ERP) system for tin refinery operations, built with Laravel and Vue.js.

## Table of Contents
- [Project Overview](#project-overview)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Key Features](#key-features)
- [Setup Instructions](#setup-instructions)
- [Development Workflow](#development-workflow)
- [API Documentation](#api-documentation)
- [Database Schema](#database-schema)
- [Deployment Guide](#deployment-guide)
- [Contributing](#contributing)
- [Changelog](#changelog)
- [Future Features](#future-features)
- [Development Guidelines](#development-guidelines)
- [License](#license)

## Project Overview

This system manages the entire tin refinery process, including:
- Batch management
- Process tracking
- Inventory management
- Yield calculations
- Sn content tracking
- Currency exchange tracking with Xero integration
- Purchase order management
- User authentication and authorization

## Recent Updates and Progress

### Completed Features
1. Basic Application Setup
   - Laravel 10.x installation with Vite
   - Database configuration and migrations
   - Authentication system implementation
   - Basic UI with Tailwind CSS

2. Batch Management System
   - Batch creation and tracking
   - Batch number generation system
   - Quality control parameters
   - Batch status tracking

3. Infrastructure Setup
   - Ngrok integration for external access
   - CORS configuration
   - HTTPS enforcement
   - Asset handling with Vite
   - Automatic asset path updates

4. Xero API Integration
   - OAuth2 authentication flow implemented
   - Secure token storage and management
   - Automatic token refresh mechanism
   - Tenant ID management
   - Currency exchange integration
   - Bank transaction creation

### In Progress
1. Currency Exchange & Purchase Order System
   - Currency exchange tracking
   - Xero integration for currency exchanges
   - Purchase order management
   - Inventory cost basis tracking

### Next Steps

#### 1. Complete Purchase Order Module
- [ ] Design database schema for PO management
- [ ] Create PO CRUD operations
- [ ] Implement PO status workflow
- [ ] Add PO approval system
- [ ] Integrate with Xero for PO creation
- [ ] Add email notifications for PO status changes

#### 2. Inventory Management Enhancement
- [ ] Link inventory to currency exchanges via POs
- [ ] Implement cost basis tracking
- [ ] Integrate with Xero for inventory transactions
- [ ] Add inventory reporting

#### 3. Additional Features
- [ ] Implement reporting system
- [ ] Add data export functionality
- [ ] Create dashboard with key metrics
- [ ] Implement user roles and permissions
- [ ] Add audit logging

## Tech Stack

### Backend
- PHP 8.2+
- Laravel 12.x
- MySQL/PostgreSQL
- Laravel Sanctum (API Authentication)
- Inertia.js (Server-side rendering)

### Frontend
- Vue.js 3.x
- Inertia.js
- TailwindCSS
- HeadlessUI
- Vite

### Integrations
- Xero API (Accounting)
- Ngrok (Secure tunneling for development)

## Project Structure

```plaintext
tin-refinery-laravel/
├── app/
│   ├── Domain/                 # Domain-Driven Design structure
│   │   ├── Batch/             # Batch management domain
│   │   │   ├── Models/        # Batch-related models
│   │   │   ├── Services/      # Batch business logic
│   │   │   └── Providers/     # Service providers
│   │   ├── Process/           # Process management domain
│   │   │   ├── Models/        # Process-related models
│   │   │   ├── Services/      # Process business logic
│   │   │   └── Providers/     # Service providers
│   │   ├── ExchangeRate/      # Currency exchange domain
│   │   │   ├── Models/        # Exchange-related models
│   │   │   ├── Services/      # Exchange business logic
│   │   │   └── Providers/     # Service providers
│   │   └── Inventory/         # Inventory management domain
│   │       ├── Models/        # Inventory-related models
│   │       ├── Services/      # Inventory business logic
│   │       └── Providers/     # Service providers
│   ├── Http/
│   │   ├── Controllers/       # HTTP Controllers
│   │   └── Middleware/        # HTTP Middleware
│   ├── Services/              # Application services
│   │   └── XeroService.php    # Xero API integration service
│   └── Providers/             # Service Providers
│       └── XeroServiceProvider.php # Xero API service provider
├── public/
│   ├── build/                 # Compiled assets (Vite output)
│   │   └── assets/            # Hashed asset files
│   └── index.php              # Application entry point
├── resources/
│   ├── js/
│   │   ├── Components/        # Vue Components
│   │   ├── Layouts/           # Layout components
│   │   ├── Pages/             # Inertia Pages
│   │   │   ├── Auth/          # Authentication pages
│   │   │   ├── CurrencyExchanges/ # Currency exchange pages
│   │   │   └── Dashboard.vue  # Dashboard page
│   │   └── types/             # TypeScript types
│   ├── css/                   # CSS files
│   └── views/                 # Blade templates
│       └── xero/              # Xero integration views
├── routes/
│   ├── api.php                # API routes
│   ├── web.php                # Web routes
│   └── auth.php               # Authentication routes
├── config/
│   └── xero.php               # Xero API configuration
└── database/
    ├── migrations/            # Database migrations
    ├── seeders/               # Database seeders
    └── factories/             # Model factories
```

## Key Features

### Batch Management
- Create and manage production batches
- Track batch status and progress
- Generate unique batch numbers
- Associate processes with batches

### Process Management
- Track different processing types (Kaldo Furnace, Refining Kettle)
- Record input and output quantities
- Calculate Sn content percentages
- Track yield and recovery rates
- Automatic inventory integration:
  - Deducts from input inventory items when selected
  - Creates output inventory items for processed materials
  - Tracks Sn content changes throughout the process
  - Maintains inventory transaction history

### Currency Exchange Management
- Track currency exchanges between USD and COP
- Calculate and store exchange rates
- Track bank fees
- Integration with Xero for accounting
- Syncing of exchange transactions to Xero

### Inventory Management
- Track tin and slag inventory
- Monitor Sn content in inventory items
- Record inventory transactions
- Manage stock levels
- Cost basis tracking in multiple currencies

## Setup Instructions

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js 16+ and npm
- MySQL 8.0+ or PostgreSQL 13+
- Git
- Ngrok for external access and Xero API integration

### Installation Steps

1. Clone the repository:
```bash
git clone [repository-url]
cd tin-refinery-laravel
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Node.js dependencies:
```bash
npm install
```

4. Set up environment variables:
```bash
cp .env.example .env
php artisan key:generate
```

5. Configure your database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tin_refinery
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. Configure Xero API integration in `.env`:
```env
XERO_CLIENT_ID=your_xero_client_id
XERO_CLIENT_SECRET=your_xero_client_secret
XERO_REDIRECT_URI=https://your-ngrok-domain.ngrok-free.app/xero/iframe-callback
XERO_WEBHOOK_KEY=
XERO_CREDENTIAL_DISK=local

# Xero Account Codes - Update these to match your Xero Chart of Accounts
XERO_USD_BANK_ACCOUNT=1000
XERO_COP_BANK_ACCOUNT=1001
XERO_MAIN_BANK_ACCOUNT=1000
XERO_BANK_FEES_ACCOUNT=6000
XERO_INVENTORY_ASSET_ACCOUNT=1200
XERO_COGS_ACCOUNT=5000
XERO_REVENUE_ACCOUNT=4000
```

7. Run migrations and seeders:
```bash
php artisan migrate
php artisan db:seed
```

8. Build frontend assets:
```bash
npm run build
```

9. Start the development server:
```bash
# Start Laravel server on port 8001
php artisan serve --port=8001

# If using Vite dev server for HMR
npm run dev
```

10. Set up Ngrok for external access and Xero integration:
```bash
# Install Ngrok if you haven't already
# Then start an Ngrok tunnel pointing to your local server
ngrok http 8001

# Update your .env file with the Ngrok URL
APP_URL=https://your-ngrok-domain.ngrok-free.app
```

11. Configure Xero App in the [Xero Developer Portal](https://developer.xero.com/):
- Create a new app in the Xero Developer Portal
- Set the redirect URL to `https://your-ngrok-domain.ngrok-free.app/xero/iframe-callback`
- Copy the Client ID and Client Secret to your `.env` file

## Development Workflow

### Git Workflow
```bash
# Create a feature branch
git checkout -b feature/feature-name

# Make your changes and commit them
git add .
git commit -m "Description of changes"

# Push to the remote repository
git push origin feature/feature-name

# Create a pull request to the dev branch
# After review, merge into dev

# For production releases
git checkout main
git merge dev
git push origin main
```

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Domain/Batch/BatchTest.php

# Run with coverage report
php artisan test --coverage-html coverage
```

### Asset Management
The project uses Vite for asset compilation and management. When building for production or when using Ngrok, the assets are automatically processed with proper path handling.

```bash
# Build assets for production/Ngrok
npm run build

# Development with HMR
npm run dev

# Clear Laravel caches
npm run clear-cache
```

### Code Style
The project uses Laravel Pint for PHP code styling:
```bash
# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

### Database Changes
When making database changes:
1. Create a new migration:
```bash
php artisan make:migration [migration_name]
```

2. Define the changes in the migration file:
```php
public function up()
{
    Schema::table('table_name', function (Blueprint $table) {
        // Add your changes here
    });
}
```

3. Run the migration:
```bash
php artisan migrate
```

### Frontend Development
- Components are located in `resources/js/Components/`
- Pages are located in `resources/js/Pages/`
- Use the `DecimalInput` component for numeric inputs
- Follow Vue.js 3 Composition API patterns

## API Documentation

### Batch Management

#### List All Batches
```http
GET /api/batches
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "batchNumber": "230323-001",
            "date": "2024-03-23",
            "status": "in_progress",
            "processes": [...]
        }
    ]
}
```

#### Create New Batch
```http
POST /api/batches
Content-Type: application/json

{
    "batchNumber": "230323-001",
    "date": "2024-03-23",
    "processes": [...]
}
```

### Process Management

#### Add Process to Batch
```http
POST /api/batches/{id}/processes
Content-Type: application/json

{
    "processingType": "kaldo_furnace",
    "inputTinKilos": 1000,
    "inputTinSnContent": 75.5,
    ...
}
```

### Inventory Management

#### List Inventory Items
```http
GET /api/inventory
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Raw Tin",
            "quantity": 5000,
            "sn_content": 75.5,
            "status": "active"
        }
    ]
}
```

## Database Schema

### Batches Table
```sql
CREATE TABLE batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(20) UNIQUE NOT NULL,
    date DATE NOT NULL,
    status ENUM('in_progress', 'completed', 'cancelled') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### Processes Table
```sql
CREATE TABLE processes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id BIGINT UNSIGNED NOT NULL,
    process_number INT NOT NULL,
    processing_type ENUM('kaldo_furnace', 'refining_kettle') NOT NULL,
    input_tin_kilos DECIMAL(10,2) NOT NULL,
    input_tin_sn_content DECIMAL(5,4) NOT NULL,
    output_tin_kilos DECIMAL(10,2) NOT NULL,
    output_tin_sn_content DECIMAL(5,4) NOT NULL,
    input_slag_kilos DECIMAL(10,2) NOT NULL,
    input_slag_sn_content DECIMAL(5,4) NOT NULL,
    output_slag_kilos DECIMAL(10,2) NOT NULL,
    output_slag_sn_content DECIMAL(5,4) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (batch_id) REFERENCES batches(id)
);
```

### Inventory Items Table
```sql
CREATE TABLE inventory_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    sn_content DECIMAL(5,4) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

## Deployment Guide

### Production Requirements
- PHP 8.2+
- MySQL 8.0+ or PostgreSQL 13+
- Node.js 16+
- Composer
- Nginx/Apache

### Deployment Steps

1. Clone the repository:
```bash
git clone [repository-url]
cd tin-refinery-laravel
```

2. Install dependencies:
```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

3. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Update `.env` for production:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

5. Optimize Laravel:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

6. Set up web server (Nginx example):
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/tin-refinery-laravel/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation as needed
- Use meaningful commit messages

## Changelog

### [Unreleased]
- Implemented Xero API integration for currency exchanges
- Added multi-currency support
- Enhanced asset handling for ngrok deployments
- Improved error handling and logging
- Added automatic asset path updates
- Implemented tenant ID management for Xero API
- Added dashboard with currency exchange status

### [0.2.0] - 2024-03-24
- Added Xero API integration
- Implemented OAuth2 authentication flow
- Created XeroService for API interactions
- Added currency exchange tracking
- Implemented bank transaction creation in Xero
- Added configuration for Xero account codes

### [0.1.0] - 2024-03-23
- Initial release
- Basic batch management
- Process tracking
- Inventory management
- User authentication

## Future Features

### Financial Integration
- Xero Accounting Integration
  - Purchase Order Management
  - Sales Order Processing
  - Cost Management
  - Cost Basis Tracking
  - Financial Reporting
  - Invoice Generation
  - Payment Processing

### Enhanced Inventory Management
- Advanced Stock Level Monitoring
- Automated Reorder Points
- Supplier Management
- Quality Control Tracking
- Lot Tracking
- Warehouse Management

### Reporting & Analytics
- Custom Report Builder
- Real-time Analytics Dashboard
- Performance Metrics
- Yield Analysis
- Cost Analysis
- Trend Forecasting

### Process Optimization
- AI-powered Yield Prediction
- Process Parameter Optimization
- Quality Control Automation
- Energy Usage Monitoring
- Environmental Impact Tracking

### System Enhancements
- Multi-currency Support
- Multi-language Support
- Mobile Application
- API Rate Limiting
- Advanced User Permissions
- Audit Logging
- Backup & Recovery

## Development Guidelines

### Code Organization
- Follow Domain-Driven Design principles
- Keep controllers thin, business logic in services
- Use repositories for data access
- Implement interfaces for service contracts
- Follow SOLID principles

### Frontend Development
- Use Vue 3 Composition API
- Implement TypeScript for type safety
- Follow component composition patterns
- Use props and emits for component communication
- Implement proper form validation

### Database Design
- Use migrations for all schema changes
- Include foreign key constraints
- Implement proper indexing
- Use appropriate data types
- Follow naming conventions

### Testing Strategy
- Write unit tests for services
- Write feature tests for controllers
- Test edge cases and error conditions
- Mock external services
- Use factories for test data

### Security Considerations
- Validate all user inputs
- Implement proper authentication
- Use CSRF protection
- Sanitize database queries
- Follow OWASP guidelines

### Performance Optimization
- Cache frequently accessed data
- Optimize database queries
- Use eager loading for relationships
- Implement proper indexing
- Monitor query performance

### Deployment Process
- Use environment variables
- Implement proper logging
- Set up monitoring
- Configure backup systems
- Use CI/CD pipelines

### Documentation
- Document all API endpoints
- Include code comments
- Maintain changelog
- Update README for major changes
- Document database schema changes

### Version Control
- Use meaningful commit messages
- Create feature branches
- Review code before merging
- Keep commits atomic
- Tag releases properly

### Error Handling
- Implement proper exception handling
- Log errors appropriately
- Provide user-friendly error messages
- Monitor error rates
- Set up error alerts

### Maintenance
- Regular dependency updates
- Security patches
- Performance monitoring
- Database optimization
- Log rotation

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
