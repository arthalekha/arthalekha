# Arthalekha

**Arthalekha** (Sanskrit: अर्थलेखा - "wealth record") is an expense management application designed for families and groups to track their finances collaboratively.

## About

Arthalekha helps households and groups manage their finances by providing:

- **Individual Tracking** - Personal expense and income management for each member
- **Group-Level Overview** - Consolidated view of family or group finances
- **Expense Categories** - Organize spending by customizable categories
- **Income Tracking** - Monitor all sources of income alongside expenses

## Requirements

- PHP 8.5+
- Composer
- Node.js & NPM
- SQLite (default) or MySQL/PostgreSQL

## Installation

```bash
# Clone the repository
git clone <repository-url>
cd arthalekha

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run build
```

## Development

```bash
# Start the development server
php artisan serve

# Watch for asset changes
npm run dev
```

## Tech Stack

- **Backend**: Laravel 12
- **Frontend**: Blade, Tailwind CSS 4, daisyUI 5
- **Authentication**: Laravel Fortify
- **Testing**: Pest

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
