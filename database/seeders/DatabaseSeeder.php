<?php

namespace Database\Seeders;

use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Section;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
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
            'email' => 'oliver@antihq.com',
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

        // Create tags for the team
        $this->createTeamTags($team);

        // Add comments to tasks
        $this->addCommentsToTasks($team, $user);

        // Add checklist items to tasks
        $this->addChecklistItemsToTasks();

        // Assign tags to tasks
        $this->assignTagsToTasks($team);
    }

    /**
     * Add comments to existing tasks to demonstrate the commenting feature.
     */
    private function addCommentsToTasks($team, $user): void
    {
        // Get some tasks to add comments to
        $wireframeTask = Task::where('title', 'Create wireframes for homepage')->first();
        $contactFormTask = Task::where('title', 'Implement contact form functionality')->first();
        $apiTask = Task::where('title', 'Set up API endpoints')->first();
        $marketingTask = Task::where('title', 'Design marketing materials')->first();

        // Comments for wireframe task (completed task with feedback)
        Comment::factory()->create([
            'task_id' => $wireframeTask->id,
            'user_id' => $user->id,
            'content' => '<p>The <strong>wireframes</strong> look great! I especially like the hero section design.</p><p>One suggestion: maybe we should add a customer logos section below the features?</p>',
            'created_at' => now()->subDays(4),
        ]);

        Comment::factory()->create([
            'task_id' => $wireframeTask->id,
            'user_id' => $user->id,
            'content' => '<p>Good point! I\'ll add that section to the wireframes. It will help with social proof.</p>',
            'created_at' => now()->subDays(3),
        ]);

        Comment::factory()->create([
            'task_id' => $wireframeTask->id,
            'user_id' => $user->id,
            'content' => '<p>✅ Updated! The customer logos section has been added to the bottom of the homepage wireframe.</p>',
            'created_at' => now()->subDays(2),
        ]);

        // Comments for contact form task (technical discussion)
        Comment::factory()->create([
            'task_id' => $contactFormTask->id,
            'user_id' => $user->id,
            'content' => '<p>Should we use <strong>reCAPTCHA</strong> for spam protection on the contact form?</p>',
            'created_at' => now()->subHours(3),
        ]);

        Comment::factory()->create([
            'task_id' => $contactFormTask->id,
            'user_id' => $user->id,
            'content' => '<p>Yes, definitely. Let\'s implement v3 with the invisible version to keep the user experience smooth.</p>',
            'created_at' => now()->subHours(2),
        ]);

        // Comments for API task (development progress)
        Comment::factory()->create([
            'task_id' => $apiTask->id,
            'user_id' => $user->id,
            'content' => '<p>🚀 API endpoints for user authentication and profile management are now live!</p>',
            'created_at' => now()->subDays(1),
        ]);

        Comment::factory()->create([
            'task_id' => $apiTask->id,
            'user_id' => $user->id,
            'content' => '<p>Great work! Can you also add the endpoints for <strong>data synchronization</strong>?</p>',
            'created_at' => now()->subHours(12),
        ]);

        // Comments for marketing materials task (ongoing work)
        Comment::factory()->create([
            'task_id' => $marketingTask->id,
            'user_id' => $user->id,
            'content' => '<p>Starting work on the marketing materials. I\'m thinking of using our brand colors consistently across all designs.</p>',
            'created_at' => now()->subHours(6),
        ]);

        Comment::factory()->create([
            'task_id' => $marketingTask->id,
            'user_id' => $user->id,
            'content' => '<p>Perfect! Let me know if you need any assets from the design team.</p>',
            'created_at' => now()->subHours(4),
        ]);

        Comment::factory()->create([
            'task_id' => $marketingTask->id,
            'user_id' => $user->id,
            'content' => '<p>Thanks! I\'ll need the <strong>logo files</strong> in different formats and the brand guidelines document.</p>',
            'created_at' => now()->subHours(2),
        ]);
    }

    /**
     * Add example checklist items to existing tasks to demonstrate checklist functionality.
     */
    private function addChecklistItemsToTasks(): void
    {
        // Get specific tasks to add checklist items to
        $wireframeTask = Task::where('title', 'Create wireframes for homepage')->first();
        $contactFormTask = Task::where('title', 'Implement contact form functionality')->first();
        $apiTask = Task::where('title', 'Set up API endpoints')->first();
        $marketingTask = Task::where('title', 'Design marketing materials')->first();

        // Checklist items for wireframe task (completed task with detailed breakdown)
        ChecklistItem::factory()->create([
            'task_id' => $wireframeTask->id,
            'content' => 'Header section with navigation menu',
            'completed' => true,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $wireframeTask->id,
            'content' => 'Hero section with call-to-action button',
            'completed' => true,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $wireframeTask->id,
            'content' => 'Features showcase grid layout',
            'completed' => true,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $wireframeTask->id,
            'content' => 'Customer testimonials section',
            'completed' => true,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $wireframeTask->id,
            'content' => 'Customer logos section (added based on feedback)',
            'completed' => true,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $wireframeTask->id,
            'content' => 'Footer with company links and social media',
            'completed' => true,
        ]);

        // Checklist items for contact form task (some completed, some in progress)
        ChecklistItem::factory()->create([
            'task_id' => $contactFormTask->id,
            'content' => 'Create HTML form structure',
            'completed' => true,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $contactFormTask->id,
            'content' => 'Add client-side validation',
            'completed' => true,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $contactFormTask->id,
            'content' => 'Integrate reCAPTCHA v3 invisible version',
            'completed' => false,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $contactFormTask->id,
            'content' => 'Set up email sending functionality',
            'completed' => false,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $contactFormTask->id,
            'content' => 'Add success/error message handling',
            'completed' => false,
        ]);

        // Checklist items for API task (mix of completed and pending)
        ChecklistItem::factory()->create([
            'task_id' => $apiTask->id,
            'content' => 'User authentication endpoints',
            'completed' => true,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $apiTask->id,
            'content' => 'User profile management endpoints',
            'completed' => true,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $apiTask->id,
            'content' => 'Data synchronization endpoints',
            'completed' => false,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $apiTask->id,
            'content' => 'API documentation with examples',
            'completed' => false,
        ]);

        // Checklist items for marketing task (mostly pending)
        ChecklistItem::factory()->create([
            'task_id' => $marketingTask->id,
            'content' => 'Create social media post templates',
            'completed' => true,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $marketingTask->id,
            'content' => 'Design Instagram story templates',
            'completed' => false,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $marketingTask->id,
            'content' => 'Create LinkedIn article graphics',
            'completed' => false,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $marketingTask->id,
            'content' => 'Design email newsletter template',
            'completed' => false,
        ]);

        ChecklistItem::factory()->create([
            'task_id' => $marketingTask->id,
            'content' => 'Create print ad designs for magazines',
            'completed' => false,
        ]);
    }

    /**
     * Create example tags for the team.
     */
    private function createTeamTags($team): void
    {
        $team->tags()->createMany([
            ['name' => 'Bug Fix'],
            ['name' => 'Feature'],
            ['name' => 'Design'],
            ['name' => 'Development'],
            ['name' => 'Testing'],
            ['name' => 'Documentation'],
            ['name' => 'Marketing'],
            ['name' => 'Research'],
            ['name' => 'High Priority'],
            ['name' => 'Low Priority'],
        ]);
    }

    /**
     * Assign tags to existing tasks to demonstrate tag functionality.
     */
    private function assignTagsToTasks($team): void
    {
        // Get the created tags
        $bugFixTag = $team->tags()->where('name', 'Bug Fix')->first();
        $featureTag = $team->tags()->where('name', 'Feature')->first();
        $designTag = $team->tags()->where('name', 'Design')->first();
        $developmentTag = $team->tags()->where('name', 'Development')->first();
        $testingTag = $team->tags()->where('name', 'Testing')->first();
        $documentationTag = $team->tags()->where('name', 'Documentation')->first();
        $marketingTag = $team->tags()->where('name', 'Marketing')->first();
        $researchTag = $team->tags()->where('name', 'Research')->first();
        $highPriorityTag = $team->tags()->where('name', 'High Priority')->first();
        $lowPriorityTag = $team->tags()->where('name', 'Low Priority')->first();

        // Get specific tasks to assign tags to
        $wireframeTask = Task::where('title', 'Create wireframes for homepage')->first();
        $colorPaletteTask = Task::where('title', 'Design color palette and typography')->first();
        $htmlTemplatesTask = Task::where('title', 'Build responsive HTML templates')->first();
        $contactFormTask = Task::where('title', 'Implement contact form functionality')->first();
        $crossBrowserTask = Task::where('title', 'Cross-browser testing')->first();
        $requirementsTask = Task::where('title', 'Define app requirements and features')->first();
        $userFlowTask = Task::where('title', 'Design user flow diagrams')->first();
        $apiTask = Task::where('title', 'Set up API endpoints')->first();
        $authTask = Task::where('title', 'Implement user authentication')->first();
        $socialMediaTask = Task::where('title', 'Develop social media strategy')->first();
        $marketingMaterialsTask = Task::where('title', 'Design marketing materials')->first();
        $emailCampaignTask = Task::where('title', 'Schedule email campaign')->first();

        // Assign tags to website project tasks
        if ($wireframeTask) {
            $wireframeTask->tags()->attach([$designTag->id, $highPriorityTag->id]);
        }

        if ($colorPaletteTask) {
            $colorPaletteTask->tags()->attach([$designTag->id]);
        }

        if ($htmlTemplatesTask) {
            $htmlTemplatesTask->tags()->attach([$developmentTag->id, $featureTag->id]);
        }

        if ($contactFormTask) {
            $contactFormTask->tags()->attach([$developmentTag->id, $bugFixTag->id]);
        }

        if ($crossBrowserTask) {
            $crossBrowserTask->tags()->attach([$testingTag->id, $bugFixTag->id]);
        }

        // Assign tags to mobile app project tasks
        if ($requirementsTask) {
            $requirementsTask->tags()->attach([$researchTag->id, $documentationTag->id]);
        }

        if ($userFlowTask) {
            $userFlowTask->tags()->attach([$designTag->id, $researchTag->id]);
        }

        if ($apiTask) {
            $apiTask->tags()->attach([$developmentTag->id, $featureTag->id]);
        }

        if ($authTask) {
            $authTask->tags()->attach([$developmentTag->id, $highPriorityTag->id]);
        }

        // Assign tags to marketing project tasks
        if ($socialMediaTask) {
            $socialMediaTask->tags()->attach([$marketingTag->id, $researchTag->id]);
        }

        if ($marketingMaterialsTask) {
            $marketingMaterialsTask->tags()->attach([$marketingTag->id, $designTag->id]);
        }

        if ($emailCampaignTask) {
            $emailCampaignTask->tags()->attach([$marketingTag->id, $lowPriorityTag->id]);
        }
    }
}
