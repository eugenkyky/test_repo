<?php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$isDevMode = true;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/Entity"), $isDevMode);

// database configuration parameters
$conn = array(
    'driver'   => 'pdo_mysql',
    'host'     => '127.0.0.1',
    'dbname'   => 'xsolla',
    'user'     => 'root',
    'password' => 'qwer1234',
);

// obtaining the entity manager
$entityManager = EntityManager::create($conn, $config);
