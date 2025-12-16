<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Create main user with personal team
            $mainUser = User::factory()->withPersonalTeam(['name' => 'Oliver\'s Team'])->create([
                'name' => 'Oliver Servín',
                'email' => 'oliver@example.com',
            ]);

            $team = $mainUser->teams()->first();

            // Create additional team members
            $teamMembers = User::factory(3)->create();
            foreach ($teamMembers as $member) {
                $team->addMember($member);
            }

            // Create board
            $board = Board::factory()->create([
                'name' => 'Project Kanban Board',
                'team_id' => $team->id,
                'user_id' => $mainUser->id,
            ]);

            // Create traditional columns (excluding status containers)
            $columns = collect([
                ['name' => 'In Progress', 'position' => 1],
                ['name' => 'Review', 'position' => 2],
                ['name' => 'Testing', 'position' => 3],
            ])->map(fn ($column) => Column::factory()->create(array_merge($column, ['board_id' => $board->id])));

            // Create tags
            $tags = collect([
                'bug',
                'feature',
                'urgent',
                'documentation',
                'refactor',
                'performance',
                'security',
            ])->map(fn ($name) => Tag::factory()->create([
                'name' => $name,
                'team_id' => $team->id,
                'user_id' => $mainUser->id,
            ]));

            // Create cards with proper positioning
            $allUsers = collect([$mainUser, ...$teamMembers]);

            // Create cards for "Maybe?" status container (opened)
            $this->createOpenedCards($board, [
                ['title' => 'Add dark mode support', 'description' => 'Implement theme switching for better UX'],
                ['title' => 'Create mobile app', 'description' => 'Develop React Native or Flutter version'],
                ['title' => 'Implement real-time notifications', 'description' => 'Add WebSocket support for live updates'],
            ], $allUsers, $tags, 0);

            // Create cards for "Not Now" status container (postponed)
            $this->createPostponedCards($board, [
                ['title' => 'Research new authentication methods', 'description' => 'Investigate OAuth2, JWT, and other modern auth solutions'],
                ['title' => 'Plan Q2 roadmap', 'description' => 'Define priorities and timeline for next quarter'],
                ['title' => 'Evaluate monitoring tools', 'description' => 'Compare Sentry, Bugsnag, and custom solutions'],
            ], $allUsers, $tags, 0);

            // Cards for "In Progress" column
            $this->createCardsForColumn($board, $columns->firstWhere('name', 'In Progress'), [
                ['title' => 'Fix login redirect issue', 'description' => 'Users are not being redirected properly after login'],
                ['title' => 'Optimize database queries', 'description' => 'Slow loading times on dashboard need to be addressed'],
                ['title' => 'Add user profile page', 'description' => 'Create comprehensive profile management interface'],
            ], $allUsers, $tags, 0);

            // Cards for "Review" column
            $this->createCardsForColumn($board, $columns->firstWhere('name', 'Review'), [
                ['title' => 'API documentation update', 'description' => 'Update OpenAPI spec with new endpoints'],
                ['title' => 'Code review: payment integration', 'description' => 'Review Stripe integration implementation'],
            ], $allUsers, $tags, 0);

            // Cards for "Testing" column
            $this->createCardsForColumn($board, $columns->firstWhere('name', 'Testing'), [
                ['title' => 'Write unit tests for auth service', 'description' => 'Achieve 90% code coverage'],
                ['title' => 'Performance testing', 'description' => 'Load testing for 1000 concurrent users'],
            ], $allUsers, $tags, 0);

            // Create cards for "Done" status container (completed)
            $this->createCompletedCards($board, [
                ['title' => 'Setup CI/CD pipeline', 'description' => 'Configure GitHub Actions for automated testing and deployment'],
                ['title' => 'Implement user registration', 'description' => 'Complete signup flow with email verification'],
                ['title' => 'Add team management', 'description' => 'Create team creation and member management features'],
                ['title' => 'Database migration system', 'description' => 'Setup Laravel migrations for schema management'],
            ], $allUsers, $tags, 0);
        });
    }

    /**
     * Create cards for a specific column with proper positioning
     */
    private function createCardsForColumn(Board $board, Column $column, array $cardData, $users, $tags, int $startPosition): void
    {
        foreach ($cardData as $index => $data) {
            $card = Card::factory()->create([
                'title' => $data['title'],
                'description' => '<p>'.$data['description'].'</p>',
                'board_id' => $board->id,
                'column_id' => $column->id,
                'position' => $startPosition + $index,
                'user_id' => $users->random()->id,
            ]);

            // Randomly assign tags (0-3 tags per card)
            $selectedTags = $tags->random(rand(0, min(3, $tags->count())));
            if ($selectedTags->isNotEmpty()) {
                $card->tags()->attach($selectedTags->pluck('id'));
            }

            // Randomly assign users (0-2 assignees per card)
            if (rand(0, 1)) { // 50% chance of having assignees
                $assignees = $users->random(rand(1, min(2, $users->count())));
                $card->assignees()->attach($assignees->pluck('id'));
            }
        }
    }

    /**
     * Create cards for the "opened" status container
     */
    private function createOpenedCards(Board $board, array $cardData, $users, $tags, int $startPosition): void
    {
        foreach ($cardData as $index => $data) {
            $card = Card::factory()->create([
                'title' => $data['title'],
                'description' => '<p>'.$data['description'].'</p>',
                'board_id' => $board->id,
                'column_id' => null,
                'position' => $startPosition + $index,
                'user_id' => $users->random()->id,
                'postponed_at' => null,
                'completed_at' => null,
            ]);

            // Randomly assign tags (0-3 tags per card)
            $selectedTags = $tags->random(rand(0, min(3, $tags->count())));
            if ($selectedTags->isNotEmpty()) {
                $card->tags()->attach($selectedTags->pluck('id'));
            }

            // Randomly assign users (0-2 assignees per card)
            if (rand(0, 1)) { // 50% chance of having assignees
                $assignees = $users->random(rand(1, min(2, $users->count())));
                $card->assignees()->attach($assignees->pluck('id'));
            }
        }
    }

    /**
     * Create cards for the "postponed" status container
     */
    private function createPostponedCards(Board $board, array $cardData, $users, $tags, int $startPosition): void
    {
        foreach ($cardData as $index => $data) {
            $card = Card::factory()->create([
                'title' => $data['title'],
                'description' => '<p>'.$data['description'].'</p>',
                'board_id' => $board->id,
                'column_id' => null,
                'position' => $startPosition + $index,
                'user_id' => $users->random()->id,
                'postponed_at' => now(),
                'completed_at' => null,
            ]);

            // Randomly assign tags (0-3 tags per card)
            $selectedTags = $tags->random(rand(0, min(3, $tags->count())));
            if ($selectedTags->isNotEmpty()) {
                $card->tags()->attach($selectedTags->pluck('id'));
            }

            // Randomly assign users (0-2 assignees per card)
            if (rand(0, 1)) { // 50% chance of having assignees
                $assignees = $users->random(rand(1, min(2, $users->count())));
                $card->assignees()->attach($assignees->pluck('id'));
            }
        }
    }

    /**
     * Create cards for the "completed" status container
     */
    private function createCompletedCards(Board $board, array $cardData, $users, $tags, int $startPosition): void
    {
        foreach ($cardData as $index => $data) {
            $card = Card::factory()->create([
                'title' => $data['title'],
                'description' => '<p>'.$data['description'].'</p>',
                'board_id' => $board->id,
                'column_id' => null,
                'position' => $startPosition + $index,
                'user_id' => $users->random()->id,
                'postponed_at' => null,
                'completed_at' => now(),
            ]);

            // Randomly assign tags (0-3 tags per card)
            $selectedTags = $tags->random(rand(0, min(3, $tags->count())));
            if ($selectedTags->isNotEmpty()) {
                $card->tags()->attach($selectedTags->pluck('id'));
            }

            // Randomly assign users (0-2 assignees per card)
            if (rand(0, 1)) { // 50% chance of having assignees
                $assignees = $users->random(rand(1, min(2, $users->count())));
                $card->assignees()->attach($assignees->pluck('id'));
            }
        }
    }
}
