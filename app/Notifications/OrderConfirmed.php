<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderConfirmed extends Notification
{
    public function __construct(public $order)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $order = $this->order;

        $mailMessage = (new MailMessage)
            ->subject("Goddess Club Store — Order #{$order->invoice_number} Confirmed")
            ->greeting("Halo {$notifiable->name},")
            ->line("Terima kasih telah memesan merchandise resmi di Goddess Club Store!")
            ->line("Nomor Invoice: #{$order->invoice_number}")
            ->line("Total Pembayaran: \${$order->grand_total}")
            ->line("Status Pesanan: " . ucfirst(str_replace('_', ' ', $order->status)))
            ->line("Kurir Pengiriman: " . ($order->courier_code ?: 'JNE Express'))
            ->line("Estimasi Pengiriman: 2–4 hari kerja")
            ->line("Rincian pesananmu:")
            ->line("");

        foreach ($order->items as $item) {
            $mailMessage->line("• {$item->product_name} ({$item->sku}) × {$item->quantity} — \${$item->unit_price}");
        }

        $mailMessage->line("")
            ->action("Lacak Pesananmu &nearr;", route('track', ['order' => $order->invoice_number]))
            ->line("THE MUSIC STAYS. SO DO WE.")
            ->salutation("Salam hangat, Goddess Club Store Team");

        return $mailMessage;
    }
}
