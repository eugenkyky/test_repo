<?php
/**
 * ApiKeyAuthenticator for the Symfony Security Component
 */
namespace Services\Security;

use Silex\{Application, ServiceProviderInterface};

use Symfony\Component\Security\Core\Exception\AuthenticationException,
    Symfony\Component\Security\Core\Authentication\Provider\SimpleAuthenticationProvider,
    Symfony\Component\Security\Http\Firewall\SimplePreAuthenticationListener;
use Symfony\Component\Security\Core\Security;

require 'ApiKeyAuthenticator.php';


class ApiKeyAuthenticationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app) {
        $app['security.apikey.authenticator'] = $app->protect(function () use ($app) {
            return new ApiKeyAuthenticator(
                $app['security.user_provider.apikey'](),
                $app['security.apikey.param']//,
               // $app['logger']
            );
        });

        $app['security.authentication_listener.factory.apikey'] = $app->protect(function ($name, $options) use ($app) {

            // throw new \Exception($name.'<b>THERE</b>'); // name = api

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
                    $app['logger'] //TODO я его сюда не даю
                );
            });

            return array(
                'security.authentication_provider.'.$name.'.apikey',
                'security.authentication_listener.'.$name.'.apikey',
                null,       // entrypoint //TODO WHAaaaaaAAAT?
                'pre_auth'  // position of the listener in the stack
            );
        });

        return true;
    }

    public function boot(Application $app)
    {
    }
}

