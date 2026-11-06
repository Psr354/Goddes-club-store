<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ShippingDispatched extends Notification
{
    public function __construct(public $order)
    {
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $order = $this->order;
        $mailMessage = (new MailMessage)
            ->subject("Order #{$order->invoice_number} Dispatched")
            ->greeting("Halo {$notifiable->name},")
            ->line("Order Anda telah dikirim.")
            ->line("Invoice: {$order->invoice_number}")
            ->line("Estimated delivery: 5-7 working days")
            ->line("Track your order: url('/orders/{$order->id}/track')")
            ->line("")
            ->line("Items shipped:")
            ->line("")
            ->action("Lihat Detail Order", url("/orders/{$order->id}"))
            ->line("")
            ->salutation("Terima kasih, Goddess Club Store Team");

        return $mailMessage;
    }
}