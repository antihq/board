<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCommented extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Comment $comment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Comment: ' . $this->comment->task->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->comment->user->name . ' commented on task "' . $this->comment->task->title . '"')
            ->line('**Comment:**')
            ->line(strip_tags($this->comment->content))
            ->line('**Project:** ' . $this->comment->task->project->name)
            ->line('**Team:** ' . $this->comment->task->team->name)
            ->action('View Project', route('projects.show', [$this->comment->task->team->handle, $this->comment->task->project->handle]))
            ->line('You are receiving this email because you are subscribed to this task.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'comment_id' => $this->comment->id,
            'task_id' => $this->comment->task->id,
            'task_title' => $this->comment->task->title,
            'user_id' => $this->comment->user->id,
            'user_name' => $this->comment->user->name,
        ];
    }
}
