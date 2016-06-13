<?php
/**
 * ApiKeyUserServiceProvider
 */
namespace Services\Security;

use Silex\Application,
    Silex\ServiceProviderInterface;

use Services\Entity\User,
    Services\Security\ApiKeyUserProvider;

require 'ApiKeyUserProvider.php';

class ApiKeyUserServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        /*$app['user.repository'] = $app->share(function() use ($app) {
            return $app['em'];//->getRepository('Services\Entity\User'); //
        });*/
        $app['security.user_provider.apikey'] = $app->protect(function () use ($app) { //This is why Pimple allows you to protect your closures from being executed, by using the protect method:
            return new ApiKeyUserProvider($app['em']);
        });
        return true;
    }
    public function boot(Application $app)
    {
    }
}