<?php

namespace App\Notifications;

use App\Models\PostMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\User;

class PostMessaged extends Notification
{
    use Queueable;

    /** @var PostMessage */
    protected $postMessage;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(PostMessage $postMessage)
    {
        $this->postMessage = $postMessage;
    }


    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'post_message_id' => $this->postMessage->id,
            'user_id' => $this->postMessage->user_id
        ];
    }
}
