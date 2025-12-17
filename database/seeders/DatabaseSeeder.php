<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Section;
use App\Models\Task;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->withPersonalTeam(['name' => 'Oliver\'s Team'])->create([
            'name' => 'Oliver Servín',
            'email' => 'oliver@example.com',
        ]);

        $team = $user->teams()->first();

        // Create projects
        $websiteProject = Project::factory()->create([
            'name' => 'Company Website Redesign',
            'team_id' => $team->id,
        ]);

        $mobileAppProject = Project::factory()->create([
            'name' => 'Mobile App Development',
            'team_id' => $team->id,
        ]);

        $marketingProject = Project::factory()->create([
            'name' => 'Q1 Marketing Campaign',
            'team_id' => $team->id,
        ]);

        // Create sections for website project
        $designSection = Section::factory()->create([
            'title' => 'Design & UX',
            'project_id' => $websiteProject->id,
        ]);

        $developmentSection = Section::factory()->create([
            'title' => 'Development',
            'project_id' => $websiteProject->id,
        ]);

        $testingSection = Section::factory()->create([
            'title' => 'Testing & QA',
            'project_id' => $websiteProject->id,
        ]);

        // Create sections for mobile app project
        $planningSection = Section::factory()->create([
            'title' => 'Planning',
            'project_id' => $mobileAppProject->id,
        ]);

        $backendSection = Section::factory()->create([
            'title' => 'Backend Development',
            'project_id' => $mobileAppProject->id,
        ]);

        // Create tasks for website project
        Task::factory()->create([
            'title' => 'Create wireframes for homepage',
            'project_id' => $websiteProject->id,
            'section_id' => $designSection->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'completed_at' => now()->subDays(5),
            'completed_by' => $user->id,
            'description' => '<p>Create detailed <strong>wireframes</strong> for the new homepage design. Include:</p><ul><li>Header section with navigation</li><li>Hero section with call-to-action</li><li>Features showcase</li><li>Customer testimonials</li><li>Footer with links</li></ul>',
        ]);

        Task::factory()->create([
            'title' => 'Design color palette and typography',
            'project_id' => $websiteProject->id,
            'section_id' => $designSection->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'completed_at' => now()->subDays(3),
            'completed_by' => $user->id,
            'reopened_at' => now()->subDay(),
            'reopened_by' => $user->id,
        ]);

        Task::factory()->create([
            'title' => 'Build responsive HTML templates',
            'project_id' => $websiteProject->id,
            'section_id' => $developmentSection->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        Task::factory()->create([
            'title' => 'Implement contact form functionality',
            'project_id' => $websiteProject->id,
            'section_id' => $developmentSection->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'completed_at' => now()->subHours(2),
            'completed_by' => $user->id,
        ]);

        Task::factory()->create([
            'title' => 'Cross-browser testing',
            'project_id' => $websiteProject->id,
            'section_id' => $testingSection->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        // Create tasks for mobile app project
        Task::factory()->create([
            'title' => 'Define app requirements and features',
            'project_id' => $mobileAppProject->id,
            'section_id' => $planningSection->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'completed_at' => now()->subWeek(),
            'completed_by' => $user->id,
        ]);

        Task::factory()->create([
            'title' => 'Design user flow diagrams',
            'project_id' => $mobileAppProject->id,
            'section_id' => $planningSection->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        Task::factory()->create([
            'title' => 'Set up API endpoints',
            'project_id' => $mobileAppProject->id,
            'section_id' => $backendSection->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'completed_at' => now()->subDays(2),
            'completed_by' => $user->id,
        ]);

        Task::factory()->create([
            'title' => 'Implement user authentication',
            'project_id' => $mobileAppProject->id,
            'section_id' => $backendSection->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        // Create tasks for marketing project (no sections)
        Task::factory()->create([
            'title' => 'Develop social media strategy',
            'project_id' => $marketingProject->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'completed_at' => now()->subDays(4),
            'completed_by' => $user->id,
        ]);

        Task::factory()->create([
            'title' => 'Design marketing materials',
            'project_id' => $marketingProject->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        Task::factory()->create([
            'title' => 'Schedule email campaign',
            'project_id' => $marketingProject->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'completed_at' => now()->subDay(),
            'completed_by' => $user->id,
            'reopened_at' => now()->subHours(6),
            'reopened_by' => $user->id,
        ]);
    }
}
