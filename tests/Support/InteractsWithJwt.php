<?php

namespace Tests\Support;

use App\Modules\Users\Models\User;

trait InteractsWithJwt
{
    /**
     * Logs the user in through the real login endpoint and returns the Bearer header.
     */
    protected function bearer(User $user): array
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password', // UserFactory default
        ])->json('access_token');

        // The test app is reused between requests, and the JWT guard caches the
        // user it saw first. Clear it so the next request is authenticated only
        // by the token it carries.
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.$token];
    }
}
