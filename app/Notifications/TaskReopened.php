<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReopened extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Task Reopened: ' . $this->task->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('The task "' . $this->task->title . '" has been reopened.')
            ->line('**Project:** ' . $this->task->project->name)
            ->line('**Team:** ' . $this->task->team->name)
            ->action('View Project', route('projects.show', [$this->task->team->handle, $this->task->project->handle]))
            ->line('You are receiving this email because you are subscribed to this task.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'project_id' => $this->task->project_id,
            'team_id' => $this->task->team_id,
        ];
    }
}
