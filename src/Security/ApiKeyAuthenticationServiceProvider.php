<?php

namespace Services\Security;
use Silex\{Application, ServiceProviderInterface};
use Symfony\Component\Security\Core\Authentication\Provider\SimpleAuthenticationProvider,
    Symfony\Component\Security\Http\Firewall\SimplePreAuthenticationListener;

require_once 'ApiKeyAuthenticator.php';

class ApiKeyAuthenticationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): bool
    {
        $app['security.apikey.authenticator'] = $app->protect(function () use ($app) {
            return new ApiKeyAuthenticator(
                $app['security.user_provider.apikey'](),
                $app['security.apikey.param']
            );
        });

        $app['security.authentication_listener.factory.apikey'] = $app->protect(function ($name, $options) use ($app) {

            $app['security.authentication_provider.'.$name.'.apikey'] = $app->share(function () use ($app, $name) {
                return new SimpleAuthenticationProvider(
                    $app['security.apikey.authenticator'](),
                    $app['security.user_provider.apikey'](),
                    $name
                );
            });

            $app['security.authentication_listener.'.$name.'.apikey'] = $app->share(function () use ($app, $name, $options) {
                return new SimplePreAuthenticationListener(
                    $app['security'],
                    $app['security.authentication_manager'],
                    $name,
                    $app['security.apikey.authenticator'](),
                    $app['logger']
                );
            });

            return array(
                'security.authentication_provider.'.$name.'.apikey',
                'security.authentication_listener.'.$name.'.apikey',
                null,
                'pre_auth'
            );
        });

        return true;
    }

    public function boot(Application $app)
    {
    }
}

