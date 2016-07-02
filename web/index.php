<?php

//IMPORT
use Silex\Application;

use Silex\Provider\SecurityServiceProvider;
use Symfony\Component\HttpFoundation\Response;

//INITIALISATION
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../src/bootstrap.php';
require_once __DIR__.'/../src/Security/ApiKeyUserServiceProvider.php';
require_once __DIR__.'/../src/Security/ApiKeyAuthenticationServiceProvider.php';

//APP CONFING
$app = new Silex\Application();

//EXCEPTIONS
$app['debug'] = true;

//ERROR MESSAGES
$app[400] = 'Bad request';
$app[404] = 'File not found';
$app[500] = 'Internal Server Error';

//FILES STORAGE
$app['store_dir'] =  __DIR__.'/../users_files/';

$app->error(function (\Exception $e, $code) {
    //TODO log and stat
    return new Response('Internal Server Error', 500);
});

$app['em'] = $entityManager; // from bootstrap.php
$app['quota'] = 1000000 ;    // 1 мегабайт


//SECURITY
$app->register(new Services\Security\ApiKeyUserServiceProvider($app));
$app->register(new Services\Security\ApiKeyAuthenticationServiceProvider(), array(
    'security.apikey.param' => 'apikey',
));

$app->register(new SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'api' => array( // firewall name
            'apikey'    => true,
            'pattern'   => '^.*$',
            'stateless' => true,
        ),
    )
));

//ROUTES AND METHODS
$app->post('/files/{filename}', 'Services\\FileService::createFile');
$app->put('/files/{filename}', 'Services\\FileService::updateFile');
$app->get('/files/{filename}', 'Services\\FileService::getFileContent');
$app->get('/files/{filename}/meta', 'Services\\FileService::getFileMeta');
$app->get('/files', 'Services\\FileService::filesList');

//START
$app->run();


