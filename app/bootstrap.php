<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// ─── Variáveis de ambiente ────────────────────────────────────────────────────
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// ─── Sessão (antes de qualquer output) ───────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => ($_ENV['APP_ENV'] ?? 'production') === 'production',
    ]);
    session_start();
}

// ─── Constante global para o JWT ─────────────────────────────────────────────
// Coloque JWT_SECRET no seu .env — mínimo 32 caracteres aleatórios.
if (!defined('SECRET_KEY')) {
    define('SECRET_KEY', $_ENV['JWT_SECRET'] ?? throw new \RuntimeException(
        'JWT_SECRET não definida no .env'
    ));
}

// ─── Slim ─────────────────────────────────────────────────────────────────────
$app = AppFactory::create();

$app->addRoutingMiddleware();

$debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
$app->addErrorMiddleware($debug, $debug, $debug);

require __DIR__ . '/helpers/settings.php';
require __DIR__ . '/routes/routes.php';

return $app;