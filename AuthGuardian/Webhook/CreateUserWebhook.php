<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\AuthGuardian\Webhook;

use Illuminate\Support\Facades\DB;

trait CreateUserWebhook
{
    public function syncUserCreation(array $data): void
    {
        DB::table('users')->insert([
            'external_id' => $data['id'],
            'name' => $data['name'],
            'login' => $data['login'],
            'email' => $data['email'],
            'active' => $data['active'],
            'extra_fields' => $data['extra_fields'],
            'created_at' => $data['createdAt'],
            'updated_at' => $data['updatedAt'],
            'group' => json_encode([
                'id' => $data['group']['id'],
                'name' => $data['group']['name'],
            ]),
            'permissions' => json_encode($data['group']['permissions'])
        ]);
    }
}
