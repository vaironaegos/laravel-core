<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Adapters;

use Astrotech\Core\Laravel\Adapters\Contracts\MailService;
use Illuminate\Support\Facades\Mail;

final class LaravelMailService implements MailService
{
    public function send(string $to, string $templateName, array $params): void
    {
        $mailsMapper = require base_path('config/mails.php');
        Mail::to($to)->send(new $mailsMapper[$templateName]($params));
    }
}
