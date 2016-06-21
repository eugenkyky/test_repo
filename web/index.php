<?php

//IMPORT
use Silex\Application;

use Silex\Provider\SecurityServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

//INITIALISATION
require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
require_once __DIR__.'/../src/bootstrap.php';
require __DIR__.'/../src/Security/ApiKeyUserServiceProvider.php';
require __DIR__.'/../src/Security/ApiKeyAuthenticationServiceProvider.php';

//APP CONFING //TODO to app/app.php и вообще структуру
$app = new Silex\Application();
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;

//EXCEPTIONS
$app['debug'] = true;

//ERROR MESSAGES
$app[400] = 'Bad request';
$app[404] = 'File not found';
$app[500] = 'Internal Server Error';

//FILES STORE
$app['store_dir'] =  __DIR__.'/../users_files/';

/*
//https://github.com/silexphp/Silex/issues/1016
// Convert simple errors into nice Exception, automaticaly handled by Silex.
Symfony\Component\Debug\ErrorHandler::register();
// Now, the hard part, handle fatal error.
$handler = Symfony\Component\Debug\ExceptionHandler::register($app['debug']);
$app->error(function (\Throwable $exception, $code) {
    // Something that build a nice \Symfony\Component\HttpFoundation\Response. This part is up to you.
    //$response = MyExceptionFormatter::format($exception, $code);
    //$response = MyExceptionFormatter::format($exception, $code);
    // A Silex exception handler must return a Response.
    //TODO log
    return new Response($exception->getMessage(), 500);
});

$handler->setHandler(function ($exception) use ($app) {


    // Create an ExceptionEvent with all the informations needed.
    $event = new Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent(
        $app,
        $app['request'],
        Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST,
        $exception
    );

    // Hey Silex ! We have something for you, can you handle it with your exception handler ?
    $app['dispatcher']->dispatch(Symfony\Component\HttpKernel\KernelEvents::EXCEPTION, $event);

    // And now, just display the response ;)
    $response = $event->getResponse();
    $response->sendHeaders();
    $response->sendContent();
    //$response->send(); We can't do that, something happened with the buffer, and Symfony still return its HTML.
});*/

$app->error(function (\Exception $e, $code) {
    //TODO log and stat
    return new Response('Internal Server Error', 500);
});


$app['em'] = $entityManager; // from bootstrap.php
$app['quota'] = 1000000 ;    // 1 мегабайт

//SECURITY
$app->register(new Services\Security\ApiKeyUserServiceProvider($app)); //Providers allow the developer to reuse parts of an application into another one.
$app->register(new Services\Security\ApiKeyAuthenticationServiceProvider(), array(
    'security.apikey.param' => 'apikey',
));

$app->register(new SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'api' => array( // ВОТ ОН NAME файервола
            'apikey'    => true,  // toggle all registered authentication mechanis  m
            'pattern'   => '^.*$',
            'stateless' => true,
        ),
    )
    /*'security.access_rules' => array(
        array(new RequestMatcher('^/api/subscribers'    , null, 'GET'   ), array('ROLE_ADMIN')),
        array(new RequestMatcher('^/api/subscribers'    , null, 'POST'  ), array('ROLE_ADMIN', 'ROLE_USER')),
        array(new RequestMatcher('^/api/subscribers/\d+', null, 'GET'   ), array('ROLE_ADMIN')),
        array(new RequestMatcher('^/api/subscribers/\d+', null, 'PATCH' ), array('ROLE_ADMIN')),
        array(new RequestMatcher('^/api/subscribers/\d+', null, 'DELETE'), array('ROLE_ADMIN')),
    ),*/
));

//ROUTES AND METHODS
$app->post('/files/{filename}', 'Services\\FileService::createFile');
$app->put('/files/{filename}', 'Services\\FileService::updateFile');
$app->get('/files/{filename}', 'Services\\FileService::getFileContent');
$app->get('/files/{filename}/meta', 'Services\\FileService::getFileMeta');
$app->get('/files', 'Services\\FileService::filesList');

//START
$app->run();


