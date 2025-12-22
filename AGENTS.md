## IMPORTANT

## Tool Calling

- ALWAYS USE PARALLEL TOOLS WHEN APPLICABLE. Here is an example illustrating how to execute 3 parallel file reads in this chat environment:

```json
{
    "recipient_name": "multi_tool_use.parallel",
    "parameters": {
        "tool_uses": [
            {
                "recipient_name": "functions.read",
                "parameters": {
                    "filePath": "path/to/File.php"
                }
            },
            {
                "recipient_name": "functions.read",
                "parameters": {
                    "filePath": "path/to/file.blade.php"
                }
            },
            {
                "recipient_name": "functions.read",
                "parameters": {
                    "filePath": "path/to/file.md"
                }
            }
        ]
    }
}
```

### Model Standards

- All models should use `$guarded = []` instead of `$fillable`
- This allows mass assignment for all attributes

### Migration Standards

- Migrations should not include foreign key constraints
- Do not use `constrained()` method
- Do not use `onDelete('cascade')` or similar cascade delete operations

### Artisan Commands

- Always use `artisan make:*` commands to create Laravel resources
- This ensures correct stubs and following Laravel conventions
- Examples: `artisan make:model`, `artisan make:migration`, `artisan make:controller`, etc.

### Livewire Components

- This project uses view-based single-file components only (⚡ emoji in filename)
- Use `pages::` namespace for components that serve as full pages: `php artisan make:livewire pages::post.create`
- Page components are created in `resources/views/pages/` directory
- Regular components are created in `resources/views/livewire/` directory
- Route page components using `Route::livewire('/posts/create', 'pages::post.create')`
- Page components support route parameters and model binding automatically

### Livewire Component Testing

- Generate components with test files using `--test` flag: `php artisan make:livewire post.create --test`
- For view-based components: creates `resources/views/livewire/post/create.test.php`
- For page components with tests: `php artisan make:livewire pages::post.create --test`
- Generated test includes basic assertion: `Livewire::test('component-name')->assertStatus(200);`
- All tests are created alongside component files in the same directory

### Livewire Computed Properties

- Use `#[Computed]` attribute to create memoized properties: `#[Computed] public function posts() { return Post::all(); }`
- Access computed properties in templates using `$this->propertyName`: `@foreach ($this->posts as $post)`
- Computed properties are memoized for single request duration, not between requests
- Clear memoization using `unset($this->propertyName)` when underlying data changes
- Use `#[Computed(persist: true)]` to cache between requests (default 3600 seconds)
- Use `#[Computed(cache: true)]` to cache across all component instances
- Import required: `use Livewire\Attributes\Computed;`

### Laravel Policies

- Policies are auto-discovered by Laravel's framework in `app/Policies` directory
- No manual registration needed - just create Policy class following naming convention: `ModelPolicy`
- Follow existing authorization patterns from TeamPolicy and ProjectPolicy
