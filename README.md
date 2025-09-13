# HomeLearnAI 🎓

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![HTMX](https://img.shields.io/badge/HTMX-1.9-3366CC?style=for-the-badge&logo=htmx&logoColor=white)](https://htmx.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15%2B-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Tests](https://img.shields.io/badge/Tests-100%25_Passing-4CAF50?style=for-the-badge)](https://github.com/buger/homelearnai)

An intelligent homeschool learning management system that adapts to each child's unique learning journey. Built with modern web technologies and educational best practices.

## ✨ Features

### 🧒 Multi-Child Management
- **Grade-based profiles** (PreK through 12th grade)
- **Independence levels** for age-appropriate interfaces
- **Kids Mode** with PIN-protected parent controls
- **Individual progress tracking** per child

### 📚 Comprehensive Curriculum Planning
- **Hierarchical structure**: Subjects → Units → Topics → Sessions
- **Flexible scheduling** with time blocks and commitment types
- **Age-appropriate recommendations** and quality heuristics
- **ICS calendar import** for external activities

### 🧠 Smart Learning System
- **Spaced repetition reviews** with automatic scheduling
- **Performance-based interval adjustments**
- **Catch-up sessions** for missed content
- **Multiple flashcard types** (basic, multiple choice, cloze, true/false)

### 🎯 Advanced Features
- **Bulk flashcard import** (Anki, Quizlet, CSV formats)
- **Multi-language support** (i18n ready)
- **Real-time updates** with HTMX
- **Mobile-responsive design**
- **Comprehensive caching** for performance

## 🚀 Quick Start

### Prerequisites
- PHP 8.2 or higher
- PostgreSQL 15+
- Node.js 18+
- Composer 2.x

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/buger/homelearnai.git
cd homelearnai
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure database**
Edit `.env` with your PostgreSQL credentials:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=homelearnai
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Run migrations**
```bash
php artisan migrate
php artisan db:seed  # Optional: Add sample data
```

6. **Build assets**
```bash
npm run build
```

7. **Start the development server**
```bash
php artisan serve
npm run dev  # In another terminal for hot-reload
```

Visit `http://localhost:8000` to access the application.

## 🧪 Testing

The project includes comprehensive test coverage (100% pass rate):

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run E2E tests
npm run test:e2e

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

## 📖 Documentation

### Project Structure
```
homelearnai/
├── app/
│   ├── Http/Controllers/   # Request handlers
│   ├── Models/             # Eloquent models
│   ├── Services/           # Business logic
│   └── Http/Middleware/    # Request middleware
├── resources/
│   ├── views/              # Blade templates
│   ├── js/                 # JavaScript files
│   └── css/                # Stylesheets
├── database/
│   ├── migrations/         # Database migrations
│   └── factories/          # Model factories
├── tests/
│   ├── Feature/            # Feature tests
│   ├── Unit/               # Unit tests
│   └── e2e/                # End-to-end tests
└── routes/
    └── web.php             # Web routes
```

### Key Technologies

- **Backend**: Laravel 11 with PHP 8.2+
- **Database**: PostgreSQL with Eloquent ORM
- **Frontend**: HTMX for dynamic interactions, Alpine.js for reactivity
- **Styling**: Tailwind CSS for modern, responsive design
- **Testing**: PHPUnit for backend, Playwright for E2E
- **Development**: Vite for asset bundling, Laravel Pint for code formatting

### Core Concepts

#### 1. Learning Hierarchy
```
Subject (e.g., Mathematics)
  └── Unit (e.g., Algebra Basics)
      └── Topic (e.g., Linear Equations)
          └── Session (e.g., Practice Problems)
              └── Flashcards (Study materials)
```

#### 2. Review System
- **Spaced Repetition**: Automatically schedules reviews based on performance
- **Intervals**: 1 day → 3 days → 7 days → 14 days → 30 days
- **Performance Tracking**: Adjusts intervals based on student success

#### 3. Kids Mode
- Simplified interface for younger learners
- PIN-protected exit to parent dashboard
- Age-appropriate content and interactions
- Progress celebration and rewards

## 🛠️ Development

### Running Locally
```bash
# Start all services
make s  # Custom makefile command

# Or run individually
php artisan serve       # Laravel server
npm run dev            # Vite dev server
php artisan queue:work # Queue worker
```

### Code Quality
```bash
# PHP formatting
./vendor/bin/pint

# JavaScript linting
npm run lint
npm run lint:fix

# Type checking
npm run type-check

# Run all quality checks
npm run test:all
```

### Database Management
```bash
# Create new migration
php artisan make:migration create_example_table

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Refresh database (WARNING: Deletes all data)
php artisan migrate:fresh --seed
```

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards
- Follow PSR-12 for PHP code
- Use Laravel best practices
- Write tests for new features
- Update documentation as needed

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Built with [Laravel](https://laravel.com) - The PHP Framework for Web Artisans
- Interactive UI powered by [HTMX](https://htmx.org)
- Styled with [Tailwind CSS](https://tailwindcss.com)
- Icons from [Heroicons](https://heroicons.com)

## 📞 Support

For issues, questions, or suggestions:
- Open an [issue](https://github.com/buger/homelearnai/issues)
- Documentation: [Wiki](https://github.com/buger/homelearnai/wiki)
- Visit us at: [homelearnai.com](https://homelearnai.com)

## 🚦 Project Status

![Tests](https://img.shields.io/badge/Tests-467_Passing-success)
![Coverage](https://img.shields.io/badge/Coverage-High-green)
![Status](https://img.shields.io/badge/Status-Active_Development-blue)

---

**Made with ❤️ for homeschool families everywhere**