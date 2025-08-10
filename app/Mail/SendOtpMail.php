<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otpCode;
    public int $year;
    public string $firstname;

    /**
     * Crée une nouvelle instance du mail avec le code OTP.
     */
    public function __construct(string $otpCode, string $firstname)
    {
        $this->otpCode = $otpCode;
        $this->year = now()->year;
        $this->firstname = $firstname;
    }

    /**
     * Définit l'enveloppe (titre du mail).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vérification de votre email - TechFuturists',
        );
    }

    /**
     * Définit le contenu du mail.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.otp_email',
            with: [
                'otp_code' => $this->otpCode,
                'year' => $this->year,
                'firstname' => $this->firstname,
            ],
        );
    }

    /**
     * Aucun fichier joint.
     */
    public function attachments(): array
    {
        return [];
    }
}
