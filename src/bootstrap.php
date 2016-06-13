<?php
/**
 * Переводится как начальная загрузка
 */

//$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../src/Acme/DemoBundle/Entities"), $isDevMode, null, null, false);
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

//require_once "/../vendor/autoload.php"; // ОНо здесь нужно ведь уже и вждругом месте загружается

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/Entity"), $isDevMode);

// database configuration parameters
$conn = array(
    'driver'   => 'pdo_mysql', //TODO Может сделать sqlite что бы ребята могли запустить и проверить
    'host'     => '127.0.0.1',
    'dbname'   => 'xsolla',
    'user'     => 'root',
    'password' => 'qwer1234',
);

// obtaining the entity manager
$entityManager = EntityManager::create($conn, $config);
