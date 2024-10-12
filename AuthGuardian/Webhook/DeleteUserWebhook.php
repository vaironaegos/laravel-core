<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\AuthGuardian\Webhook;

use Illuminate\Support\Facades\DB;

trait DeleteUserWebhook
{
    public function syncUserDelete(array $data): void
    {
        DB::table('users')->delete($data['id']);
    }
}
