<?php

namespace App\Mail;

use App\Models\SalarySlip;
use App\Services\SlipPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SlipGajiMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SalarySlip $salarySlip) {}

    public function envelope(): Envelope
    {
        $slip = $this->salarySlip->toSlipArray();

        return new Envelope(
            subject: 'Surat Keterangan Gaji - '.$slip['employee']['name'].' - '.$slip['nama_bulan'].' '.$slip['tahun'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.slip-gaji',
            with: ['slip' => $this->salarySlip->toSlipArray()],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => SlipPdfService::generateForEmail($this->salarySlip),
                SlipPdfService::filename($this->salarySlip),
            )->withMime('application/pdf'),
        ];
    }
}
