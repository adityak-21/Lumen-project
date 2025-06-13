<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenericMail extends Mailable implements ShouldQueue
{
    public $bladeView;
    public $data;
    public $subjectLine;
    public $fromEmail;
    public $fromName;

    public function __construct($bladeView, $data, $subjectLine, $fromEmail, $fromName)
    {
        $this->bladeView = $bladeView;
        $this->data = $data;
        $this->subjectLine = $subjectLine;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    public function build()
    {
        return $this
            ->view($this->bladeView)
            ->subject($this->subjectLine)
            ->from($this->fromEmail, $this->fromName)
            ->with($this->data);
    }
}