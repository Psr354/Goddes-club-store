<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShippingDispatched extends Notification
{
    public function __construct(public $order) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $order = $this->order;
        $trackUrl = route('track', ['order' => $order->invoice_number]);

        return (new MailMessage)
            ->subject("Order #{$order->invoice_number} Dispatched")
            ->greeting("Halo {$notifiable->name},")
            ->line('Order Anda telah dikirim.')
            ->line("Invoice: {$order->invoice_number}")
            ->line('Estimated delivery: 5-7 working days')
            ->line("Nomor Resi: {$order->tracking_number}")
            ->action('Lacak Pengiriman', $trackUrl)
            ->salutation('Terima kasih, Goddess Club Store Team');
    }
}
