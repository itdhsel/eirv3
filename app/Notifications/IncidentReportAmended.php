<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\IncidentReport;

class IncidentReportAmended extends Notification
{
    use Queueable;

    public $report;
    public $message;

    public function __construct(IncidentReport $report, string $message)
    {
        $this->report = $report;
        $this->message = $message; // e.g., "RCA Form needs amendment"
    }

    public function via(object $notifiable): array
    {
        return ['database']; // Add 'mail' if SMTP is configured
    }

    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'message' => $this->message,
            'state' => (string) $this->report->state,
        ];
    }
}