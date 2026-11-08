<?php

use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;

require dirname(__DIR__).'/vendor/autoload.php';

/** Replace inherited development credentials without changing the pinned test target. */
$testCredentialNames = ['APP_KEY', 'DB_USERNAME', 'DB_PASSWORD'];

foreach ($testCredentialNames as $testCredentialName) {
    putenv($testCredentialName);
    unset($_ENV[$testCredentialName], $_SERVER[$testCredentialName]);
}

$testEnvironment = Dotenv::create(
    RepositoryBuilder::createWithDefaultAdapters()
        ->addAdapter(PutenvAdapter::class)
        ->allowList($testCredentialNames)
        ->make(),
    dirname(__DIR__),
    '.env.testing',
);

$testEnvironment->load();
$testEnvironment->required($testCredentialNames)->notEmpty();
