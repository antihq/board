# Antiboard

[![License: O'Saasy](https://img.shields.io/badge/License-O'Saasy-blue.svg)](https://osaasy.dev)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)

A collaborative project and task management platform featuring Kanban boards, team collaboration, rich task management, and automated workflows. Built with Laravel 12, Livewire 4, and Flux UI Pro.

## About Antiboard

Antiboard is a modern project management tool designed for teams and individuals who need powerful, flexible task organization. It combines the simplicity of Kanban boards with advanced collaboration features, making it perfect for software development, marketing campaigns, or any project that benefits from visual task tracking.

With real-time updates, rich task details, and automated workflows like auto-closing stale tasks, Antiboard helps teams stay organized and focused on what matters most.

## Key Features

### Team Collaboration

- **Team Management**: Create and manage multiple teams for different projects or clients
- **Member Roles**: Invite team members with owner and admin roles for granular permissions
- **Shareable Invitation Codes**: Generate unique codes for team access with configurable usage limits
- **Personal Workspaces**: Support for individual projects separate from team collaboration

### Project Management

- **Project Creation**: Organize work into projects within teams with unique, shareable handles
- **Access Control**: Restrict project access to specific team members for sensitive work
- **Project Settings**: Configure per-project settings including auto-close rules
- **Project Numbers**: Auto-incrementing task numbers per project (e.g., `myproject-123`)

### Kanban Board

- **Drag-and-Drop Interface**: Intuitive task movement between sections with visual feedback
- **Custom Sections**: Create unlimited sections to match your workflow (e.g., Backlog, In Review, QA)
- **Color-Coded Sections**: Assign colors to sections for better visual organization
- **Sortable Columns**: Reorder sections to match your preferred layout

### Task Management

- **Multiple Task States**: Pending, In Progress (custom sections), Completed, and Closed
- **Task Prioritization**: Mark important tasks as priority for quick identification
- **Rich Text Descriptions**: Create detailed task descriptions using Tiptap editor
- **Task Images**: Attach screenshots and files directly to tasks
- **Task Subscriptions**: Follow specific tasks to receive notifications on updates

### Task Collaboration

- **Comments**: Threaded discussions with teammates for each task
- **Image Attachments**: Add images to comments for clearer communication
- **Checklists**: Break down tasks into actionable checklist items
- **Tags**: Organize tasks with team-wide tags for filtering and categorization
- **Multiple Assignees**: Assign tasks to multiple team members
- **Comment Editing**: Track edits with timestamps and editor information

### Task Bookmarks

- **Save Tasks**: Bookmark important tasks for quick access
- **Browse Saved Tasks**: Dedicated page to view all saved tasks across projects
- **Team Filtering**: View saved tasks filtered by team

### Notifications

- **Task Updates**: Receive notifications when tasks are completed, closed, or reopened
- **Comment Notifications**: Get alerted when new comments are added to subscribed tasks
- **Real-time Updates**: Stay synchronized with team activity

### Auto-Close System

- **Team-Level Settings**: Configure auto-close days at the team level as a default
- **Project Overrides**: Override team settings per project for different workflows
- **Scheduled Cleanup**: Automatically close tasks that have been inactive for too long
- **Dry-Run Mode**: Preview which tasks would be closed before executing

### User Management

- **Authentication**: Secure login and registration with email verification
- **Magic Links**: Easy device login using shareable, time-limited links
- **Profile Photos**: Personalize your account with avatar images
- **Device Management**: View and manage all logged-in devices
- **Account Settings**: Customize profile information and appearance preferences

## Screenshots

Screenshots coming soon!

## Tech Stack

### Backend

- **Laravel 12**: Modern PHP framework with elegant syntax and powerful features
- **PHP 8.2+**: Latest PHP features and performance improvements
- **Tiptap PHP**: Rich text editor for task descriptions
- **Pest PHP**: Elegant testing framework

### Frontend

- **Livewire 4**: Full-stack framework for dynamic interfaces without complex JavaScript
- **Flux UI Pro**: Premium UI components for modern, responsive design
- **Tailwind CSS 4**: Utility-first CSS framework for rapid styling
- **Vite**: Lightning-fast build tool for frontend assets

### Database

- **SQLite**: Default for development (simple, no setup required)
- **MySQL/PostgreSQL**: Supported for production environments

### Authentication & Security

- **Spatie Laravel One-Time Passwords**: Magic link authentication
- **Spatie Laravel Login Link**: Secure, shareable login URLs

### Development Tools

- **Laravel Pint**: Automatic PHP code formatting
- **Prettier**: Code formatting for Blade templates
- **Husky**: Git hooks for code quality
- **Duster**: Comprehensive code quality tool
- **Laravel Debugbar**: Development debugging and profiling
- **Server Timing**: Performance monitoring
- **Honeybadger**: Error tracking and monitoring

> **Note**: This project requires a Flux UI Pro license. You can purchase one at [fluxui.dev](https://fluxui.dev/).

## Requirements

- **PHP 8.2 or higher**
- **Composer 2.x**
- **Node.js 18+ and npm**
- **SQLite 3**, **MySQL 5.7+**, or **PostgreSQL 10+**
- **Web server** (Apache, Nginx, or Laravel Valet for local development)

## Installation

### Step 1: Clone the Repository

```sh
git clone https://github.com/antihq/antiboard.git
cd antiboard
```

### Step 2: Install Dependencies

Install backend dependencies with Composer:

```sh
composer install
```

Install frontend dependencies with npm:

```sh
npm install
```

### Step 3: Environment Configuration

Copy the example environment file:

```sh
cp .env.example .env
```

Generate the application key:

```sh
php artisan key:generate
```

### Step 4: Database Setup

By default, Antiboard uses SQLite for development. No additional configuration is needed.

If you prefer MySQL or PostgreSQL, update your `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=antiboard
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Run the database migrations:

```sh
php artisan migrate
```

Optionally, seed the database with sample data:

```sh
php artisan db:seed
```

### Step 5: Start Development Servers

In one terminal, start the Vite development server:

```sh
npm run dev
```

In another terminal, start the Laravel server:

```sh
php artisan serve
```

Or use the all-in-one command to start everything (server, queue, logs, Vite):

```sh
composer dev
```

### Step 6: Access the Application

Open your browser and navigate to:

```
http://localhost:8000
```

Create your first user account and start building!

## Configuration

### Environment Variables

Key environment variables in `.env`:

- `APP_NAME`: Application name
- `APP_ENV`: Environment (local, production, etc.)
- `APP_URL`: Application URL
- `APP_DEBUG`: Enable/disable debug mode (false in production)
- `DB_CONNECTION`: Database type (sqlite, mysql, pgsql)
- `DB_*`: Database connection settings
- `MAIL_*`: Mail configuration for notifications
- `QUEUE_CONNECTION`: Queue driver (database, redis, etc.)

### Database Configuration

For MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=antiboard
DB_USERNAME=root
DB_PASSWORD=your_password
```

For PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=antiboard
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### Mail Configuration

To enable email notifications (magic links, task notifications), configure your mail settings:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Queue Configuration (Optional)

For better performance with notifications, configure a queue:

```env
QUEUE_CONNECTION=database
```

Then run the queue worker:

```sh
php artisan queue:work
```

## Usage Guide

### Getting Started

1. **Create Your First Team**: After registration, you'll have a personal team. Create additional teams for different projects or clients.

2. **Create a Project**: Within your team, create a project to organize tasks.

3. **Add Sections**: Create custom sections for your Kanban board (e.g., "To Do", "In Progress", "Review").

4. **Create Tasks**: Add tasks to your project with titles, descriptions, and assign them to team members.

5. **Invite Team Members**: Share your team's invitation code to bring collaborators on board.

### Core Workflows

#### Creating and Organizing Tasks

1. Click "Add task" to create a new task in the Pending section
2. Fill in task details including title, description, tags, and assignees
3. Drag tasks to different sections as they progress through your workflow
4. Use priorities to mark important tasks
5. Subscribe to tasks you want to follow

#### Using the Kanban Board

- Drag and drop tasks between sections
- Click the "Add section" button to create new columns
- Reorder sections by dragging their headers
- Color-code sections for better visual organization

#### Managing Team Members

1. Go to Team Settings > Members
2. Copy the invitation code or generate a new one
3. Share the code with team members via: `{team-handle}/join/{invitation-code}`
4. Manage member roles (owner/admin) in the members list

#### Setting Up Auto-Close Rules

1. Navigate to Team Settings > Auto-Close or Project Settings > Auto-Close
2. Set the number of days of inactivity before tasks auto-close
3. Project settings override team settings
4. Preview auto-close with the dry-run command:
    ```sh
    php artisan app:auto-close-tasks --dry-run
    ```

## Development

### Running Tests

Run the entire test suite:

```sh
composer test
```

Or use Pest directly:

```sh
./vendor/bin/pest
```

Run specific test files:

```sh
./vendor/bin/pest tests/Feature/TaskTest.php
```

### Code Formatting

Format PHP code with Laravel Pint:

```sh
./vendor/bin/pint
```

Format Blade templates with Prettier:

```sh
npm run format-blade
```

Format all code at once:

```sh
composer format
```

### Development Workflow

Start all development services in one command:

```sh
composer dev
```

This starts:

- Laravel server (port 8000)
- Queue worker
- Log viewer (Pail)
- Vite development server

### Artisan Commands

Available custom commands:

```sh
# Auto-close inactive tasks
php artisan app:auto-close-tasks

# Preview auto-close without executing
php artisan app:auto-close-tasks --dry-run
```

## Deployment

### Production Configuration

1. Set `APP_ENV=production` and `APP_DEBUG=false` in your `.env`
2. Set a secure `APP_KEY` (generate with `php artisan key:generate`)
3. Configure your production database
4. Set your production `APP_URL`

### Cache Optimization

Optimize your application for production:

```sh
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Queue Worker Setup

For production, run the queue worker with proper process management:

```sh
php artisan queue:work --tries=3 --timeout=90
```

Use Supervisor or systemd for process monitoring.

### Scheduler Setup

Add the Laravel scheduler to your crontab:

```cron
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

This is required for the auto-close feature to run automatically.

### File Permissions

Ensure the following directories are writable by the web server:

- `storage/`
- `bootstrap/cache/`

```sh
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Nginx Configuration Example

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path-to-your-project/public;

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

## Troubleshooting

### Installation Issues

**Composer install fails**: Update Composer to the latest version:

```sh
composer self-update
```

**npm install fails**: Clear npm cache:

```sh
npm cache clean --force
rm -rf node_modules package-lock.json
npm install
```

### Permission Issues

If you encounter permission errors:

```sh
sudo chown -R $USER:$USER .
chmod -R 775 storage bootstrap/cache
```

### Database Connection Issues

**SQLite**: Ensure the database file exists and is writable:

```sh
touch database/database.sqlite
chmod 664 database/database.sqlite
```

**MySQL/PostgreSQL**: Verify your `.env` credentials match your database configuration.

### Session Issues

If you're logged out frequently, clear the application cache:

```sh
php artisan cache:clear
php artisan config:clear
php artisan session:table
php artisan migrate
```

### Asset Issues

If CSS/JS doesn't load properly:

```sh
npm run build
php artisan view:clear
```

## Contributing

We welcome contributions to Antiboard! Please follow these guidelines:

1. **Fork the repository**
2. **Create a feature branch**: `git checkout -b feature/amazing-feature`
3. **Make your changes**
4. **Write tests** for new functionality
5. **Ensure all tests pass**: `composer test`
6. **Format your code**: `composer format`
7. **Commit your changes**: Use clear, descriptive commit messages
8. **Push to the branch**: `git push origin feature/amazing-feature`
9. **Open a Pull Request** with a clear description of your changes

### Code Style

- Follow PSR-12 coding standards
- Run `./vendor/bin/pint` before committing
- Use meaningful variable and function names
- Add comments for complex logic
- Write tests for all new features

## License

This project is licensed under the [O'Saasy License](https://osaasy.dev).

**Summary**:

- ✅ Free to use, modify, and distribute
- ✅ Perfect for internal tools, client projects, and learning
- ❌ Cannot use to compete with the original licensor by offering it as a hosted SaaS product
- ❌ Cannot offer as a managed or cloud service where the primary value is the software itself

See the [LICENSE.md](LICENSE.md) file for the full license text.

## Support & Community

- **Issues**: Report bugs and request features via [GitHub Issues](https://github.com/antihq/antiboard/issues)
- **Discussions**: Ask questions and share ideas in [GitHub Discussions](https://github.com/antihq/antiboard/discussions)
- **Documentation**: This README serves as the primary documentation. Additional documentation coming soon!

---

Built with ❤️ by [antihq](https://github.com/antihq)
