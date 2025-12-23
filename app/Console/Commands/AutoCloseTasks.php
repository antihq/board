<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class AutoCloseTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-close-tasks {--dry-run : Show what would be closed without actually closing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-close tasks that have been inactive for too long';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN: Showing tasks that would be auto-closed');
        } else {
            $this->info('Auto-closing inactive tasks...');
        }

        $count = 0;

        Task::with(['project', 'team'])
            ->shouldAutoClose()
            ->cursor()
            ->each(function ($task) use ($dryRun, &$count) {
                if (! $task->needsClose()) {
                    return;
                }

                $count++;
                $autoCloseDays = $task->autoCloseDays();
                $daysSinceUpdate = $task->updated_at->diffInDays(now());

                $this->line("- Task #{$task->id}: {$task->title}");
                $this->line("  Project: {$task->project->name}");
                $this->line("  Last updated: {$task->updated_at->format('Y-m-d H:i')} ({$daysSinceUpdate} days ago)");
                $this->line("  Auto-close threshold: {$autoCloseDays} days");
                $this->line('');

                if (! $dryRun) {
                    $task->close();
                    $this->info('  ✓ Task auto-closed');
                }
            });

        if ($count === 0) {
            $this->info('No tasks found for auto-closing.');

            return 0;
        }

        if (! $dryRun) {
            $this->info("Successfully auto-closed {$count} task(s).");
        } else {
            $this->info("Found {$count} task(s) to process.");
            $this->info('Use without --dry-run to actually close these tasks.');
        }

        return 0;
    }
}
