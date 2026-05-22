<?php
namespace App\Notifications;
use App\Models\TrainingAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TrainingAssignedNotification extends Notification
{
    use Queueable;
    public function __construct(public TrainingAssignment $assignment) {}
    public function via($notifiable): array { return ['database','mail']; }
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Training Assigned — {$this->assignment->training_type}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been assigned to a training session.")
            ->line("Training: {$this->assignment->training_type}")
            ->line("Date: {$this->assignment->training_date->format('D, d M Y')}")
            ->line("Duration: {$this->assignment->duration_days} day(s)")
            ->line("Provider: " . ($this->assignment->provider ?? 'TBA'));
    }
    public function toDatabase($notifiable): array
    {
        return [
            'type'    => 'training_assigned',
            'title'   => 'Training Assigned',
            'message' => "You have been assigned to {$this->assignment->training_type} on {$this->assignment->training_date->format('d M Y')}.",
            'url'     => '/driver/dashboard',
        ];
    }
}
