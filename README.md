# Farmer Assistance Management System (FAMS)

A web-based capstone project for the **Municipal Agriculture Office (MAO)** built with **Laravel 11**, **PHP 8.2+**, **SQLite**, **Blade**, **Bootstrap 5**, and **JavaScript**.

## Features

- **Authentication** – Laravel Breeze with login, registration, password reset, email verification, and role-based access
- **Farmer Registration** – Farmer profile with RSBSA, crop type, land size, association membership, 4Ps status, and document uploads
- **Program Management** – Create, update, activate, and deactivate assistance programs
- **Eligibility Tracking** – Automatic evaluation of eligible, partially eligible, and not eligible farmers
- **Recommendation Engine** – Program recommendations based on farmer eligibility rules
- **QR Code Generation** – Unique QR codes for farmer identification and distribution verification
- **Distribution Monitoring** – QR scanning, farmer verification, eligibility checking, application checking, benefit release, and duplicate prevention
- **Announcements** – Create and publish announcements
- **SMS Notifications** – SMS notification records and integration
- **Weather Monitoring** – OpenWeather API integration with weather alerts and advisories
- **Audit Trail** – Records important administrative actions for accountability and traceability
- **Database Validation** – Validation for farmer information, duplicate email registration, Gmail-only registration, and required profile information

## Requirements

- PHP 8.2+
- Composer
- SQLite
- Node.js & npm
- PHP GD Extension (required for QR code generation)

## Installation

Clone or copy the FAMS project, then open the project directory:

```bash
cd fams
```

Install PHP dependencies:

```bash
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

Create the SQLite database file:

```bash
type nul > database/database.sqlite
```

Run the database migrations and seeders:

```bash
php artisan migrate --seed
```

Create the public storage link:

```bash
php artisan storage:link
```

Install frontend dependencies:

```bash
npm install
```

Build frontend assets:

```bash
npm run build
```

Start the Laravel development server:

```bash
php artisan serve
```

Visit:

```text
http://127.0.0.1:8000
```

## Database

FAMS currently uses **SQLite** for local development.

Database file:

```text
database/database.sqlite
```

To inspect the database using Laravel Tinker:

```bash
php artisan tinker
```

View all database tables:

```php
Schema::getTableListing();
```

View table columns:

```php
Schema::getColumnListing('farmers');
```

View farmer records:

```php
\App\Models\Farmer::all();
```

View program records:

```php
\App\Models\Program::all();
```

View application records:

```php
\App\Models\Application::all();
```

View distribution records:

```php
\App\Models\Distribution::all();
```

View audit trail records:

```php
\App\Models\AuditLog::all();
```

Exit Tinker:

```php
exit
```

## Default Accounts

The following accounts are used for local development and testing:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@gmail.com | admin12345 |
| Farmer | testfarmer@gmail.com | farmer12345 |

> These credentials are for local development/testing only and should not be used for production deployment.

## API Configuration

Configure the required API keys in the `.env` file:

```env
SEMAPHORE_API_KEY=your_semaphore_api_key
OPENWEATHER_API_KEY=your_openweather_api_key
OPENWEATHER_BASE_URL=https://api.openweathermap.org/data/2.5
```

Do not commit actual API keys or other sensitive credentials to GitHub.

## PHP GD Extension

The GD extension is required for QR code generation.

Open the PHP configuration file:

```text
C:\xampp\php\php.ini
```

Make sure this extension is enabled:

```ini
extension=gd
```

Verify that GD is enabled:

```bash
php -m
```

The output should contain:

```text
gd
```

## Audit Trail

FAMS records important administrative actions using an audit logging mechanism.

Currently recorded critical actions include:

```text
APPLICATION_APPROVED
BENEFIT_RELEASED
```

Each audit record stores information such as:

- User who performed the action
- Action performed
- Affected model
- Affected record ID
- Action details
- Date and time

To inspect audit records:

```bash
php artisan tinker
```

Then:

```php
\App\Models\AuditLog::orderBy('id')->get([
    'id',
    'user_id',
    'action',
    'model',
    'model_id',
    'details',
    'created_at'
]);
```

This provides traceability and accountability for important administrative transactions.

## GitHub Backup and Version Control

FAMS uses Git and GitHub for source code version control and backup.

Check project changes:

```bash
git status
```

Review code changes:

```bash
git diff
```

Stage changes:

```bash
git add .
```

Commit changes:

```bash
git commit -m "Describe your changes"
```

Push changes to GitHub:

```bash
git push origin main
```

View commit history:

```bash
git log --oneline
```

Check the connected remote repository:

```bash
git remote -v
```

Get the latest version from GitHub:

```bash
git pull origin main
```

### Recommended Backup Workflow

```text
Make changes
     ↓
git status
     ↓
git diff
     ↓
git add .
     ↓
git commit -m "Description of changes"
     ↓
git push origin main
```

## Project Structure

```text
fams/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   └── Farmer/
│   │   └── Middleware/
│   ├── Models/
│   └── Services/
│
├── database/
│   ├── factories/
│   ├── migrations/
│   ├── seeders/
│   └── database.sqlite
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   ├── auth/
│   │   ├── admin/
│   │   └── farmer/
│   ├── css/
│   └── js/
│
├── routes/
│   ├── web.php
│   └── auth.php
│
├── public/
├── storage/
├── tests/
├── vendor/
├── node_modules/
│
├── .env
├── artisan
├── composer.json
├── package.json
└── vite.config.js
```

## Testing

Run the Laravel automated test suite:

```bash
php artisan test
```

The test suite covers authentication, registration, email verification, password functionality, farmer portal flow, and farmer profile validation.

## License

MIT – Capstone academic project.

## Database Inspection Using Laravel Tinker

FAMS uses SQLite as its database during local development. Laravel Tinker can be used to inspect the database tables, table columns, and stored records.

### 1. Start Laravel Tinker

Open the VS Code terminal inside the FAMS project folder and run:

```bash
php artisan tinker
```

After running the command, the Laravel Tinker prompt will appear:

```text
>
```

---

### 2. View All Database Tables

To display all tables currently available in the FAMS SQLite database:

```php
Schema::getTableListing();
```

Example tables include:

```text
announcements
applications
audit_logs
distributions
eligibility_rules
farmers
programs
recommendations
sms_notifications
users
weather_predictions
```

Laravel system tables such as `cache`, `jobs`, `sessions`, and `migrations` may also appear.

---

### 3. View Table Columns

The following commands display the columns/fields of each FAMS table.

#### Users

```php
Schema::getColumnListing('users');
```

#### Farmers

```php
Schema::getColumnListing('farmers');
```

#### Programs

```php
Schema::getColumnListing('programs');
```

#### Eligibility Rules

```php
Schema::getColumnListing('eligibility_rules');
```

#### Recommendations

```php
Schema::getColumnListing('recommendations');
```

#### Applications

```php
Schema::getColumnListing('applications');
```

#### Distributions

```php
Schema::getColumnListing('distributions');
```

#### Audit Logs

```php
Schema::getColumnListing('audit_logs');
```

#### Weather Predictions

```php
Schema::getColumnListing('weather_predictions');
```

#### Announcements

```php
Schema::getColumnListing('announcements');
```

#### SMS Notifications

```php
Schema::getColumnListing('sms_notifications');
```

---

### 4. View Records Stored in the Database

Laravel Eloquent models can be used to view the records stored in each table.

#### Users

```php
\App\Models\User::all();
```

#### Farmers

```php
\App\Models\Farmer::all();
```

#### Programs

```php
\App\Models\Program::all();
```

#### Eligibility Rules

```php
\App\Models\EligibilityRule::all();
```

#### Recommendations

```php
\App\Models\Recommendation::all();
```

#### Applications

```php
\App\Models\Application::all();
```

#### Distributions

```php
\App\Models\Distribution::all();
```

#### Audit Logs

```php
\App\Models\AuditLog::all();
```

#### Weather Predictions

```php
\App\Models\WeatherPrediction::all();
```

#### Announcements

```php
\App\Models\Announcement::all();
```

#### SMS Notifications

```php
\App\Models\SmsNotification::all();
```

---

### 5. View Selected Fields Only

When a table contains many columns, selected fields can be displayed to make the output easier to read.

#### Users

```php
\App\Models\User::all([
    'id',
    'name',
    'email',
    'role',
    'email_verified_at'
]);
```

#### Farmers

```php
\App\Models\Farmer::all([
    'id',
    'user_id',
    'full_name',
    'barangay',
    'crop_type',
    'land_size',
    'rsbsa_status',
    'rsbsa_number',
    'association_membership',
    'four_ps_membership'
]);
```

#### Programs

```php
\App\Models\Program::all([
    'id',
    'name',
    'is_active'
]);
```

#### Applications

```php
\App\Models\Application::all([
    'id',
    'farmer_id',
    'program_id',
    'status',
    'applied_at',
    'reviewed_at'
]);
```

#### Distributions

```php
\App\Models\Distribution::all([
    'id',
    'farmer_id',
    'program_id',
    'released_by',
    'distributed_at'
]);
```

#### Audit Logs

```php
\App\Models\AuditLog::all([
    'id',
    'user_id',
    'action',
    'model',
    'model_id',
    'details',
    'created_at'
]);
```

---

### 6. Count Records in a Table

The `count()` method can be used to determine how many records are stored in a table.

```php
DB::table('users')->count();
```

```php
DB::table('farmers')->count();
```

```php
DB::table('programs')->count();
```

```php
DB::table('applications')->count();
```

```php
DB::table('distributions')->count();
```

```php
DB::table('audit_logs')->count();
```

```php
DB::table('weather_predictions')->count();
```

---

### 7. Check the Audit Trail

The audit log records important administrative actions performed in the FAMS system.

To view all audit records:

```php
\App\Models\AuditLog::orderBy('id')->get([
    'id',
    'user_id',
    'action',
    'model',
    'model_id',
    'details',
    'created_at'
]);
```

Important actions currently recorded include:

```text
APPLICATION_APPROVED
BENEFIT_RELEASED
```

These records provide traceability by identifying:

- The user who performed the action
- The action performed
- The affected record
- The details of the action
- The date and time of the action

---

### 8. Exit Laravel Tinker

After database inspection, exit Tinker using:

```php
exit
```

---

### Database Inspection Summary

Laravel Tinker provides a convenient way to inspect the FAMS SQLite database during development and testing.

The main commands are:

```text
php artisan tinker
        ↓
Schema::getTableListing()
        ↓
Schema::getColumnListing('table_name')
        ↓
Model::all()
        ↓
DB::table('table_name')->count()
        ↓
exit
```

These commands are intended for database inspection and testing and do not modify existing records.
