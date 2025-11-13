# 🐾 Pawsitive Systems - Veterinary Clinic Management System

<p align="center">
    # 🐾
</p>

<p align="center">
  <a href="#"><img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel" alt="Laravel Version"></a>
  <a href="#"><img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php" alt="PHP Version"></a>
  <a href="#"><img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License"></a>
  <a href="#"><img src="https://img.shields.io/badge/PRs-welcome-brightgreen.svg" alt="PRs Welcome"></a>
</p>

## 📋 Overview

**Pawsitive Systems** is a comprehensive veterinary clinic management system designed to streamline operations for modern veterinary practices. Built with Laravel 12, this robust backend API powers pet management, appointment scheduling, medical records, billing, inventory, and more.

### 🎯 Key Features

- **🐕 Pet Management** - Comprehensive pet profiles with medical history, weight tracking, and vaccination records
- **📅 Appointment Scheduling** - Smart booking system with calendar views and automated reminders
- **💉 Medical Records** - Complete medical history tracking with diagnoses, prescriptions, and procedures
- **💰 Billing & Invoicing** - Integrated invoicing system with payment tracking and PDF generation
- **📦 Inventory Management** - Real-time stock tracking with low-stock alerts and movement history
- **👥 Multi-User Management** - Role-based access control (Admin, Vet, Receptionist, Cashier)
- **📊 Dashboard Analytics** - Real-time insights into clinic operations
- **🔔 Notifications** - Automated email notifications for appointments, invoices, and vaccination reminders
- **📄 Document Management** - Secure storage for medical documents and files
- **🌐 Multi-Language Support** - Arabic and English language support

---

## 🛠️ Tech Stack

- **Framework:** Laravel 12.x
- **PHP Version:** 8.2+
- **Authentication:** Laravel Sanctum
- **Permissions:** Spatie Laravel Permission
- **PDF Generation:** DomPDF
- **Database:** MySQL / PostgreSQL
- **Cache:** Redis (optional)
- **Queue:** Database/Redis
- **Frontend:** Vite, TailwindCSS
- **API:** RESTful API with versioning

---

## 📦 Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0+ or PostgreSQL 13+
- Node.js 18+ and npm
- Redis (optional, for caching and queues)

### Quick Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/kareem-codes/pawsitive-systems.git
   cd pawsitive-systems
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure your `.env` file**
   ```env
   APP_NAME="Pawsitive Systems"
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=pawsitive_systems
   DB_USERNAME=root
   DB_PASSWORD=

   MAIL_MAILER=smtp
   MAIL_HOST=smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=null
   MAIL_PASSWORD=null
   ```

6. **Run migrations and seeders**
   ```bash
   php artisan migrate --seed
   ```

7. **Create storage symlink**
   ```bash
   php artisan storage:link
   ```

8. **Build assets**
   ```bash
   npm run build
   ```

9. **Start the development server**
   ```bash
   php artisan serve
   ```

Your API will be available at `http://localhost:8000`

---

## 🚀 Quick Start Scripts

For easier setup, use the composer scripts:

```bash
# Complete setup (install, migrate, build)
composer setup

# Start development server
npm run dev
```

---

## 📁 Project Structure

```
├── app/
│   ├── Http/
│   │   ├── Controllers/      # API Controllers
│   │   ├── Middleware/       # Custom Middleware
│   │   └── Requests/         # Form Request Validation
│   ├── Models/               # Eloquent Models
│   ├── Notifications/        # Email & SMS Notifications
│   ├── Observers/            # Model Observers
│   ├── Policies/             # Authorization Policies
│   └── Services/             # Business Logic Services
├── config/                   # Configuration Files
├── database/
│   ├── factories/            # Model Factories
│   ├── migrations/           # Database Migrations
│   └── seeders/              # Database Seeders
├── routes/
│   ├── api.php              # API Routes
│   └── web.php              # Web Routes
├── storage/
│   ├── app/public/          # Public Storage
│   └── logs/                # Application Logs
└── tests/                   # Unit & Feature Tests
```

---

## 🔐 Authentication & Authorization

The system uses **Laravel Sanctum** for API authentication and **Spatie Laravel Permission** for role-based access control.

### Default Roles

- **Admin** - Full system access
- **Vet** - Medical records, prescriptions, appointments
- **Receptionist** - Appointments, pet management, basic billing
- **Cashier** - POS, invoicing, payment processing

### API Authentication

```bash
# Login
POST /api/v1/login
{
  "email": "admin@clinic.com",
  "password": "password"
}

# Protected routes require Bearer token
Authorization: Bearer {your-token}
```

---

## 📡 API Endpoints

### Core Modules

| Module | Endpoint | Description |
|--------|----------|-------------|
| **Pets** | `/api/v1/pets` | Pet management (CRUD) |
| **Appointments** | `/api/v1/appointments` | Appointment scheduling |
| **Medical Records** | `/api/v1/medical-records` | Medical history tracking |
| **Invoices** | `/api/v1/invoices` | Billing and invoicing |
| **Products** | `/api/v1/products` | Inventory management |
| **Vaccinations** | `/api/v1/vaccinations` | Vaccination tracking |
| **Users** | `/api/v1/users` | User management |
| **Documents** | `/api/v1/documents` | Document management |
| **Clinic Settings** | `/api/v1/clinic-settings` | Clinic configuration |

For complete API documentation, visit `/api/documentation` (if configured).

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

---

## 📧 Notifications

The system supports automated notifications for:

- **Appointment Reminders** - Sent 24 hours before appointments
- **Vaccination Due Reminders** - Sent when vaccinations are due
- **Invoice Created** - Sent when new invoices are generated

Configure mail settings in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@pawsitive.com
MAIL_FROM_NAME="Pawsitive Systems"
```

---

## 🔄 Queue Management

For better performance, run background jobs:

```bash
# Start queue worker
php artisan queue:work

# Process failed jobs
php artisan queue:retry all

# Monitor queues
php artisan queue:monitor
```

---

## 🗄️ Database Models

### Core Models

- **User** - System users (staff)
- **Pet** - Pet profiles
- **Appointment** - Appointment bookings
- **MedicalRecord** - Medical history entries
- **Vaccination** - Vaccination records
- **Invoice** - Billing invoices
- **InvoiceItem** - Line items on invoices
- **Payment** - Payment transactions
- **Product** - Inventory products
- **StockMovement** - Inventory movement tracking
- **Document** - File attachments
- **WeightRecord** - Pet weight tracking
- **CommunicationLog** - Communication history
- **ClinicSetting** - Clinic configurations
- **AuditLog** - System activity logs

---

## 🎨 Frontend Integration

This backend is designed to work with:

- **Vue Dashboard** (`/vue-dashboard-front`) - Admin/Staff interface
- **React Owner Portal** (`/react-owner-front`) - Pet owner interface

See respective README files in each frontend directory.

---

## 🐳 Docker Deployment

```bash
# Using Laravel Sail
./vendor/bin/sail up

# Or with custom Docker setup
docker-compose up -d
```

---

## 🔧 Configuration

### Key Configuration Files

- `config/app.php` - Application settings
- `config/database.php` - Database connections
- `config/mail.php` - Email configuration
- `config/permission.php` - Role & permission settings
- `config/filesystems.php` - File storage configuration

---

## 📊 Performance Optimization

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize

# Clear all caches
php artisan optimize:clear
```

---

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards

- Follow PSR-12 coding standards
- Write meaningful commit messages
- Add tests for new features
- Update documentation as needed

```bash
# Run code formatting
./vendor/bin/pint
```

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - The PHP Framework
- [Spatie](https://spatie.be) - Laravel Permission Package
- [DomPDF](https://github.com/barryvdh/laravel-dompdf) - PDF Generation

---

## 📞 Support

For issues, questions, or suggestions:

- Open an [issue](https://github.com/yourusername/pawsitive-systems/issues)
- Email: support@pawsitive.com
- Documentation: [Wiki](https://github.com/yourusername/pawsitive-systems/wiki)

---

## 🗺️ Roadmap

- [ ] WhatsApp integration for notifications
- [ ] Online appointment booking portal
- [ ] Telemedicine video consultations
- [ ] Mobile app (iOS/Android)
- [ ] Multi-clinic support (SaaS)
- [ ] Advanced analytics and reporting
- [ ] Integration with lab systems
- [ ] Automated prescription refills

---

<p align="center">Made with ❤️ for veterinary professionals</p>
