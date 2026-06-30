<<<<<<< HEAD
# Farmer Assistance Management System (FAMS)

A web-based capstone project for the **Municipal Agriculture Office (MAO)** built with **Laravel 11**, **PHP 8+**, **MySQL 8**, **Blade**, **Bootstrap 5**, and **JavaScript**.

## Features

- **Authentication** – Laravel Breeze (login, register, forgot password, role-based access)
- **Farmer Registration** – Profile with RSBSA, crop type, land size, valid ID upload
- **Program Management** – CRUD with activate/deactivate
- **Eligibility Tracking** – Auto-evaluate eligible / partially eligible / not eligible
- **Recommendation Engine** – Program suggestions based on eligibility rules
- **QR Code Generation** – Unique farmer QR codes (simplesoftwareio/simple-qrcode)
- **Distribution Monitoring** – Scan QR, verify, release benefits, duplicate prevention
- **Announcements** – Schedule and publish with optional SMS
- **SMS Notifications** – Semaphore API integration with logging
- **Analytics Dashboard** – Charts (Chart.js) for farmers, crops, distributions
- **Weather Module** – OpenWeatherMap API with rain/storm alerts and advisories

## Requirements

- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `openssl`, `gd` (required for QR codes)
- Composer
- MySQL 8
- Node.js & npm (for asset build)

## Installation

```bash
cd fams
composer install
cp .env.example .env
php artisan key:generate
```

Create MySQL database:

```sql
CREATE DATABASE fams CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Update `.env` with your database credentials, then:

```bash
php artisan migrate --seed
php artisan storage:link
npm install && npm run build
php artisan serve
```

Visit: http://localhost:8000

## Default Accounts (after seeding)

| Role   | Email              | Password  |
|--------|--------------------|-----------|
| Admin  | admin@fams.local   | password  |
| Farmer | farmer1@fams.local | password  |

## API Configuration

Add to `.env`:

```env
SEMAPHORE_API_KEY=your_semaphore_api_key
OPENWEATHER_API_KEY=your_openweather_api_key
OPENWEATHER_LOCATION=YourCity,PH
```

## PHP GD Extension (QR Codes)

Enable in `php.ini`:

```ini
extension=gd
```

Restart Apache/PHP after enabling.

## Project Structure

```
app/
├── Http/Controllers/Admin/    # Admin module controllers
├── Http/Controllers/Farmer/   # Farmer module controllers
├── Http/Middleware/           # Role-based middleware
├── Models/                    # Eloquent models
└── Services/                  # Eligibility, QR, SMS, Weather services
resources/views/
├── layouts/                   # app, admin, farmer layouts
├── auth/                      # login, register
├── admin/                     # Admin dashboards & CRUD
└── farmer/                    # Farmer portal pages
```

## License

MIT – Capstone academic project.
=======
# capstone
>>>>>>> 99ef8e37c9dfcce739f45c1d62782b4bc81e2656
