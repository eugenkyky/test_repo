<?php

namespace Services\Security;

use Silex\Application,
    Silex\ServiceProviderInterface;

require 'ApiKeyUserProvider.php';

class ApiKeyUserServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): bool
    {
        $app['security.user_provider.apikey'] = $app->protect(function () use ($app) {
            return new ApiKeyUserProvider($app['em']);
        });

        return true;
    }
    public function boot(Application $app)
    {
    }
}