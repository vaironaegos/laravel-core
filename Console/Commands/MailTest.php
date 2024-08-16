<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use MailTestMessage;
use Symfony\Component\Mailer\SentMessage;

final class MailTest extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'mail:test';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Script to test smtp send mail';

    public function handle()
    {
        try {
            $this->info('--------------');
            $this->info('SMTP DATA');
            $this->info('Host: ' . config('mail.mailers.smtp.host'));
            $this->info('Port: ' . config('mail.mailers.smtp.port'));
            $this->info('Encryption: ' . config('mail.mailers.smtp.encryption'));
            $this->info('Username: ' . config('mail.mailers.smtp.username'));
            $this->info('Password: ' . config('mail.mailers.smtp.password'));
            $this->info('--------------');

            $this->info('Sending email...');

            /** @var SentMessage $emailInformation */
            $emailInformation = Mail::to('dersonsena@gmail.com')->send(new MailTestMessage(
                fromEmail: config('mail.from.address'),
                fromName: 'Auth Guardian',
            ));

            if (!$emailInformation) {
                $this->info('Erro on send email:');
                $this->info('Message: ' . $emailInformation->getMessage()->toString());
                $this->info('Debug: ' . $emailInformation->getDebug());
                return Command::FAILURE;
            }

            $this->info('Message Sent');
            $this->info('Debug: ' . $emailInformation->getDebug());
            return Command::SUCCESS;
        } catch (Throwable $e) {
            throw $e;
        }
    }
}
