# Anti Starter Kit

<!--  -->

## Development

### Setting up

First, get everything installed and configured with:

```sh
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

And then run the development server:

```sh
npm run dev
```

In another terminal, start the Laravel server:

```sh
php artisan serve
```

You'll be able to access the app in development at http://localhost:8000.

### Running tests

For fast feedback loops, tests can be run with:

```sh
composer test
```

Or using Pest directly:

```sh
./vendor/bin/pest
```

### Database configuration

This starter kit works with SQLite by default and supports MySQL and PostgreSQL too. You can switch adapters by updating the `DB_CONNECTION` environment variable in your `.env` file.

For example, to develop locally against MySQL:

```sh
DB_CONNECTION=mysql
# Then update DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD accordingly
```

### Code formatting

This starter kit uses Laravel Pint for PHP code formatting and Prettier for frontend code:

```sh
# Format PHP code
./vendor/bin/pint

# Format Blade templates
npm run format-blade

# Format all code
composer format
```

## Tech Stack

This starter kit is built with:

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Livewire 4, Flux UI Pro, Tailwind CSS 4
- **Database**: SQLite (development), MySQL/PostgreSQL (production)
- **Testing**: Pest PHP

**Note**: This project requires a Flux UI Pro license. You can purchase one at [fluxui.dev](https://fluxui.dev/).

## Contributing

We welcome contributions! Please ensure all code follows the project's coding standards and passes all tests before submitting.

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and ensure they pass
5. Format your code
6. Submit a pull request

## License

This starter kit is released under the [O'Saasy License](LICENSE.md).
