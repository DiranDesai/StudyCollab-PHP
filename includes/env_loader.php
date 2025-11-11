<?php
use Dotenv\Dotenv;

// Load .env file from project root
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();