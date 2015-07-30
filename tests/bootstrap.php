<?php

$loader = require "vendor/autoload.php";
require "tests/model_helpers.php";
mb_internal_encoding('UTF-8');
error_reporting( E_ALL );
if (extension_loaded('xhprof') ) {
    ini_set('xhprof.output_dir','/tmp');
}
$loader->add(null,'tests');
$loader->add(null,'tests/src');

use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\ConfigLoader;
use CLIFramework\Logger;

$config = ConfigLoader::getInstance();
$config->loadFromSymbol(true);

$logger = new Logger;
$logger->info("Building schema class files...");

// build schema class files
$schemas = array(
    new \TestApp\Model\UserSchema,
    new \TestApp\Model\IDNumberSchema,
    new \TestApp\Model\NameSchema,
    new \AuthorBooks\Model\AddressSchema,
    new \AuthorBooks\Model\BookSchema,
    new \AuthorBooks\Model\AuthorSchema,
    new \AuthorBooks\Model\AuthorBookSchema,
    new \AuthorBooks\Model\PublisherSchema,
    new \MetricApp\Model\MetricValueSchema,
    new \PageApp\Model\PageSchema,
);
$g = new \LazyRecord\Schema\SchemaGenerator($config, $logger);
$g->setForceUpdate(true);
$g->generate($schemas);
// $logger->info("Starting tests...");
