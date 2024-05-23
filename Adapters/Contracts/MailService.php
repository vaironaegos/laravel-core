<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Adapters\Contracts;

interface MailService
{
    /**
     * Send mail to a user
     * @param string $to
     * @param string $templateName
     * @param array $params
     * @return void
     */
    public function send(string $to, string $templateName, array $params): void;
}
