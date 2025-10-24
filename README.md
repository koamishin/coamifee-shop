# ☕ Coffee Shop Management System

A modern, full-featured Coffee Shop Management System built with [Laravel 12](https://laravel.com/) and [Livewire 3](https://livewire.laravel.com/). This system provides comprehensive management for inventory, orders, POS operations, customer relations, and business analytics.

## 🚀 Quick Start

Get your coffee shop running in minutes with our automated installation:

```bash
# Clone the repository
git clone <your-repo-url> coamishop
cd coamishop

# Install and configure automatically
composer install
composer run project:install

# Start development servers
composer run coffee:serve
```

**Access your system:**
- Admin Panel: `http://127.0.0.1:8000/admin`
- Default Login: `admin@admin.com` / `password`

---

## 📋 Table of Contents

1. [Features](#-features)
2. [Technology Stack](#-technology-stack)
3. [Installation Guide](#-installation-guide)
4. [Development Workflow](#-development-workflow)
5. [Project Structure](#-project-structure)
6. [Available Commands](#-available-commands)
7. [Testing](#-testing)
8. [Development Roadmap](#-development-roadmap)
9. [Contributing](#-contributing)

---

## ✨ Features

### 🛒 Core Operations
- **Product Management**: Complete inventory tracking with categories and variants
- **Point of Sale (POS)**: Touch-friendly interface for quick order processing
- **Order Management**: Real-time order tracking and status updates
- **Customer Management**: Customer profiles, order history, and loyalty programs

### 📊 Business Intelligence
- **Analytics Dashboard**: Sales reports, popular products, and trend analysis
- **Inventory Insights**: Stock level monitoring and automated alerts
- **Customer Analytics**: Purchase patterns and preferences tracking

### 🔧 Administration
- **Filament Admin Panel**: Beautiful, intuitive admin interface
- **Role-Based Access**: Granular permissions for admin, barista, and manager roles
- **System Configuration**: Flexible settings for business operations

### 🎨 User Experience
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile
- **Real-Time Updates**: Live order status and inventory changes
- **Modern UI**: Built with Tailwind CSS for a clean, professional look

---

## 🛠️ Technology Stack

| Component | Technology | Version |
|-----------|------------|---------|
| **Backend** | Laravel | 12.x |
| **Frontend** | Livewire | 3.x |
| **Styling** | Tailwind CSS | 4.x |
| **Admin Panel** | Filament | 4.x |
| **Database** | PostgreSQL | 15+ |
| **Cache/Queue** | Redis | 7+ |
| **Authentication** | Laravel Sanctum | Latest |
| **Testing** | Pest | 4.x |
| **Code Style** | Laravel Pint | Latest |

---

## 📦 Installation Guide

### Prerequisites

- PHP 8.4+
- Composer 2.0+
- Node.js 18+
- PostgreSQL 15+
- Redis 7+

### Option 1: Automated Installation (Recommended)

```bash
# Clone and install automatically
git clone <your-repo-url> coamishop
cd coamishop
composer install
composer run project:install

# Start development servers
composer run coffee:serve
```

### Option 2: Manual Installation

```bash
# 1. Clone repository
git clone <your-repo-url> coamishop
cd coamishop

# 2. Install dependencies
composer install
npm install

# 3. Environment setup
cp .env.example .env
php artisan key:generate

# 4. Configure database in .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=coamishop
# DB_USERNAME=postgres
# DB_PASSWORD=your_password

# 5. Run installation
php artisan project:install --seed --demo

# 6. Build frontend assets
npm run build

# 7. Start development servers
composer run coffee:serve
```

### Environment Configuration

Key environment variables to configure:

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=coamishop
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Application
APP_NAME="Coffee Shop Management"
APP_ENV=local
APP_KEY=base64:your_generated_key
APP_DEBUG=true
APP_URL=http://localhost:8000
```

---

## 🔄 Development Workflow

### Daily Development Commands

```bash
# Start all development services
composer run coffee:serve

# Run tests with coverage
composer run coffee:test

# Format code according to standards
vendor/bin/pint

# Watch for frontend changes
npm run dev

# Clear all caches
php artisan optimize:clear
```

### Git Workflow

```bash
# Create feature branch
git checkout -b feature/product-management

# Make changes and commit
git add .
git commit -m "feat: add product management with Livewire"

# Push and create PR
git push origin feature/product-management
```

### Code Quality Standards

- Follow [Laravel Conventions](https://laravel.com/docs/contributions)
- Use [Laravel Pint](https://laravel.com/docs/pint) for code formatting
- Write tests for all new features
- Document complex business logic

---

## 📁 Project Structure

```
coamishop/
├── app/
│   ├── Actions/          # Business logic actions
│   ├── Http/Controllers/ # HTTP controllers
│   ├── Livewire/         # Livewire components
│   ├── Models/           # Eloquent models
│   └── Providers/        # Service providers
├── database/
│   ├── factories/        # Model factories
│   ├── migrations/       # Database migrations
│   └── seeders/          # Database seeders
├── resources/
│   ├── views/            # Blade templates
│   ├── css/              # Tailwind CSS
│   └── js/               # JavaScript
├── routes/               # Route definitions
├── tests/                # Test suites
│   ├── Feature/          # Feature tests
│   ├── Unit/             # Unit tests
│   └── Browser/          # Browser tests
└── config/               # Configuration files
```

---

## ⚡ Available Commands

### Project Management

```bash
# Interactive installation
php artisan project:install

# Fresh install with demo data
composer run project:demo

# Development environment setup
composer run setup:dev

# Production environment setup
composer run setup:prod

# Complete reset and reinstall
composer run project:fresh
```

### Development Tools

```bash
# Start development servers (PHP + Node)
composer run coffee:serve

# Run complete test suite
composer run coffee:test

# Format code and check style
composer run lint

# Optimize for production
composer run coffee:deploy

# Reset database and rebuild
composer run coffee:reset
```

### User Management

```bash
# Create admin user
php artisan make:user "John Doe" "john@example.com" --admin --password=secure123

# Create regular user
php artisan make:user "Jane Smith" "jane@example.com"
```

### Installation Options

```bash
php artisan project:install [options]

Options:
  --force          Force installation even if already installed
  --env=ENV        Environment to setup (local, staging, production)
  --seed           Run database seeding after installation
  --demo           Install with demo data
  --fresh          Fresh install (drop existing tables)

Examples:
  php artisan project:install --env=local --seed --demo
  php artisan project:install --env=production --fresh --force
```

---

## 🧪 Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ProductTest.php

# Run tests with coverage
php artisan test --coverage

# Run browser tests
php artisan test --testsuite=Browser
```

### Test Structure

```php
// Feature Test Example
it('can create a new product', function () {
    $admin = User::factory()->admin()->create();
    
    $this->actingAs($admin)
        ->post('/products', [
            'name' => 'Espresso',
            'price' => 2.50,
            'category_id' => Category::factory()->create()->id,
        ])
        ->assertRedirect('/products');
        
    $this->assertDatabaseHas('products', [
        'name' => 'Espresso',
        'price' => 2.50,
    ]);
});

// Livewire Test Example
it('can add item to cart', function () {
    Livewire::test(CartManager::class)
        ->call('addItem', Product::factory()->create()->id)
        ->assertSet('cartCount', 1);
});

// Browser Test Example
it('can complete customer order flow', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/menu')
            ->click('@add-espresso-to-cart')
            ->click('@view-cart')
            ->click('@checkout')
            ->assertSee('Order confirmed');
    });
});
```

---

## 🗺️ Development Roadmap

### Phase 1: Core Infrastructure ✅ (Completed)
**Foundation for the entire system**

#### 1.1 Project Setup ✅
- [x] Laravel 12 installation with proper configuration
- [x] Database setup with PostgreSQL
- [x] Redis configuration for caching and queues
- [x] Frontend build system (Vite + Tailwind CSS 4)
- [x] Development environment with Docker/Sail

#### 1.2 Authentication & Authorization ✅
- [x] Laravel Fortify integration
- [x] User roles and permissions system
- [x] Two-factor authentication
- [x] Email verification
- [x] Password reset functionality

#### 1.3 Admin Panel ✅
- [x] Filament installation and configuration
- [x] User management interface
- [x] Basic resource CRUD operations
- [x] Role-based access control

### Phase 2: Core Business Features 🚧 (In Progress)
**Essential coffee shop operations**

#### 2.1 Product Management 🚧
**Priority: HIGH**
```php
// Tasks for developers:
- [ ] Create Product model with relationships
- [ ] Implement Category system (drinks, food, merchandise)
- [ ] Build ProductVariants for sizes/options
- [ ] Create Livewire components for:
  * ProductList (with search/filter)
  * ProductForm (create/edit)
  * CategoryManager
- [ ] Add Filament resources for admin management
- [ ] Implement product image uploads
- [ ] Create ProductFactory for testing
```

#### 2.2 Inventory Management 📋
**Priority: HIGH**
```php
// Tasks for developers:
- [ ] Create Inventory model with stock tracking
- [ ] Implement stock movement system (in/out)
- [ ] Build low-stock alerts system
- [ ] Create Livewire components:
  * InventoryManager
  * StockAdjustmentForm
  * InventoryReport
- [ ] Add inventory transactions logging
- [ ] Create automated stock calculations
```

#### 2.3 Order Processing 📋
**Priority: CRITICAL**
```php
// Tasks for developers:
- [ ] Create Order, OrderItem, and Payment models
- [ ] Implement order status workflow
- [ ] Build Livewire POS interface:
  * Product selection grid
  * Cart management
  * Payment processing
  * Receipt generation
- [ ] Create order queue system
- [ ] Implement kitchen display interface
- [ ] Add order history and filtering
```

#### 2.4 Customer Management 📋
**Priority: MEDIUM**
```php
// Tasks for developers:
- [ ] Create Customer model with profiles
- [ ] Implement customer registration/login
- [ ] Build customer dashboard
- [ ] Create order history tracking
- [ ] Add customer preferences system
- [ ] Implement basic loyalty program
```

### Phase 3: Advanced Features 📅 (Planned)
**Enhancing the coffee shop experience**

#### 3.1 Advanced POS Features 📅
**Priority: MEDIUM**
```php
// Tasks for developers:
- [ ] Split payment functionality
- [ ] Discount and coupon system
- [ ] Gift card management
- [ ] Tip calculation and tracking
- [ ] Quick sale buttons
- [ ] Cash drawer management
- [ ] Shift management system
```

#### 3.2 Analytics & Reporting 📅
**Priority: MEDIUM**
```php
// Tasks for developers:
- [ ] Sales dashboard with charts
- [ ] Daily/weekly/monthly reports
- [ ] Product performance analytics
- [ ] Customer purchase patterns
- [ ] Inventory turnover reports
- [ ] Employee performance metrics
- [ ] Export functionality for reports
```

#### 3.3 Inventory Optimization 📅
**Priority: LOW**
```php
// Tasks for developers:
- [ ] Supplier management system
- [ ] Purchase order creation
- [ ] Automated reordering
- [ ] Waste tracking system
- [ ] Expiration date tracking
- [ ] Inventory forecasting
```

#### 3.4 Customer Experience 📅
**Priority: LOW**
```php
// Tasks for developers:
- [ ] Online ordering portal
- [ ] Mobile-responsive menu
- [ ] Customer feedback system
- [ ] Email/SMS notifications
- [ ] Loyalty rewards program
- [ ] Customer mobile app API
```

### Phase 4: Enterprise Features 🔮 (Future)
**Scaling for multiple locations**

#### 4.1 Multi-Location Support 🔮
```php
// Tasks for developers:
- [ ] Location management system
- [ ] Inventory transfer between locations
- [ ] Location-specific reporting
- [ ] Centralized user management
- [ ] Cross-location analytics
```

#### 4.2 Integrations 🔮
```php
// Tasks for developers:
- [ ] Payment gateway integrations (Stripe, Square)
- [ ] Accounting software integration
- [ ] Email marketing integration
- [ ] Social media integration
- [ ] Third-party delivery APIs
```

#### 4.3 Advanced Analytics 🔮
```php
// Tasks for developers:
- [ ] AI-powered sales predictions
- [ ] Customer behavior analysis
- [ ] Real-time dashboards
- [ ] Custom report builder
- [ ] Data export in multiple formats
```

---

## 🎯 Developer Guidelines for Each Phase

### Starting Work on a Phase
1. **Review the task list** for your assigned phase
2. **Create a feature branch** for your work: `git checkout -b feature/phase-x-task-name`
3. **Set up local environment** with required dependencies
4. **Review existing code** to understand patterns and conventions
5. **Implement tests first** (TDD approach recommended)

### Development Standards
- Follow Laravel conventions and best practices
- Write tests for all new features (unit, feature, and browser tests)
- Use proper error handling and validation
- Document complex business logic
- Ensure responsive design for all user interfaces
- Optimize database queries to prevent N+1 problems

### Code Review Process
1. **Self-review** your code before submitting PR
2. **Ensure all tests pass**: `php artisan test`
3. **Format code**: `vendor/bin/pint`
4. **Update documentation** as needed
5. **Submit PR** with clear description of changes
6. **Address feedback** promptly

### Testing Requirements
- **Unit Tests**: For business logic and model methods
- **Feature Tests**: For user workflows and API endpoints
- **Browser Tests**: For critical user journeys
- **Performance Tests**: For database-intensive operations
- **Minimum Coverage**: 80% for all new code

---

## 📅 Timeline Estimates

| Phase | Duration | Start Date | Target Completion |
|-------|----------|------------|-------------------|
| Phase 1 | 2 weeks | Completed | ✅ Completed |
| Phase 2 | 4-6 weeks | Current | 6-8 weeks from start |
| Phase 3 | 6-8 weeks | After Phase 2 | 14-16 weeks from start |
| Phase 4 | 8-10 weeks | After Phase 3 | 22-26 weeks from start |

**Note**: These are estimates and may be adjusted based on team size and complexity.

---

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Add tests for your changes
5. Ensure all tests pass (`php artisan test`)
6. Format your code (`vendor/bin/pint`)
7. Commit your changes (`git commit -m 'Add amazing feature'`)
8. Push to the branch (`git push origin feature/amazing-feature`)
9. Open a Pull Request

### Code Standards

- Follow PSR-12 coding standards
- Use Laravel conventions
- Write descriptive commit messages
- Add tests for new functionality
- Update documentation as needed

---

## 📊 Success Metrics

### Development Metrics
- ✅ Test coverage > 80%
- ✅ Zero critical bugs in production
- ✅ Code follows Laravel conventions
- ✅ All features documented

### Performance Metrics
- ✅ Page load time < 2 seconds
- ✅ API response time < 500ms
- ✅ Database queries optimized
- ✅ Efficient Livewire updates

### Business Metrics
- ✅ Order processing time < 30 seconds
- ✅ Inventory accuracy > 99%
- ✅ Customer satisfaction > 4.5/5
- ✅ Daily revenue tracking

---

## 📝 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

---

## 🆘 Support

If you encounter any issues or have questions:

1. Check our [Documentation](docs/)
2. Search [Existing Issues](issues)
3. Create a [New Issue](issues/new)
4. Contact our support team

---

**Ready to build your coffee shop empire with Laravel and Livewire? Let's get brewing! ☕**
