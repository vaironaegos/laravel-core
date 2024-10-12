<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\AuthGuardian\Webhook;

use Astrotech\Core\Laravel\Http\ControllerBase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

final class AuthGuardianWebhookController extends ControllerBase
{
    use CreateUserWebhook;
    use DeleteUserWebhook;

    public function __invoke(Request $request): JsonResponse
    {
        $post = $request->post();
        Log::info('auth-guardian-webhook', $post);

        if (!isset($post['action']) || !isset($post['data']) || !isset($post['timestamp'])) {
            return $this->answerSuccess([]);
        }

        $userTableExists = Schema::hasTable('users');

        if ($post['action'] === 'USER_CREATION' && $userTableExists) {
            $this->syncUserCreation($post['data']);
        }

        if ($post['action'] === 'USER_DELETE' && $userTableExists) {
            $this->syncUserDelete($post['data']);
        }

        return $this->answerSuccess([]);
    }
}
