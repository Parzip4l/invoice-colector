<?php

namespace App\Mail;

use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceTransactionReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Transaction $transaction)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Dokumen Lengkap - '.$this->transaction->registration_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-verification.transaction-received',
        );
    }
}
