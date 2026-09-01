<?php

namespace App\Mail;

use App\Models\Pengujian;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HasilSiapMail extends Mailable
{
    use Queueable, SerializesModels;

    public Pengujian $pengujian;

    /**
     * Create a new message instance.
     */
    public function __construct(Pengujian $pengujian)
    {
        $this->pengujian = $pengujian;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hasil Pengujian Laboratorium Selesai - BRMP Biogen',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.hasil_siap',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
