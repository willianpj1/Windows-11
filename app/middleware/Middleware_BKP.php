<?php

declare(strict_types=1);

namespace app\middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Slim\Psr7\Response;

class Middleware
{
    // Rotas que não precisam de autenticação (além do próprio /login)
    private const PUBLIC_PATHS = [
        '/login',
        '/cadastro',
        '/auth/google',
        '/auth/google/callback',
        '/logout',
    ];

    /**
     * Middleware para rotas de API (POST que retornam JSON).
     * Devolve 401 JSON se não autenticado.
     */
    public static function api()
    {
        return function ($request, $handler) {
            if (!self::isAuthenticated()) {
                $response = new Response();
                $response->getBody()->write(json_encode([
                    'status'  => false,
                    'message' => 'Sessão expirada ou não autenticada.',
                    'id'      => 0,
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
            }
            return $handler->handle($request);
        };
    }

    /**
     * Middleware para rotas web (GET que retornam HTML).
     * Redireciona para /login se não autenticado,
     * ou para /home se já autenticado e tentar acessar /login.
     */
    public static function web()
    {
        return function ($request, $handler) {
            $path    = $request->getUri()->getPath();
            $isLogin = $path === '/login';
            $isPublic = in_array($path, self::PUBLIC_PATHS, true);
            $auth    = self::isAuthenticated();

            // Autenticado tentando ver o login → vai para home
            if ($isLogin && $auth) {
                return (new Response())
                    ->withHeader('Location', '/home')
                    ->withStatus(302);
            }

            // Não autenticado em rota privada → vai para login
            if (!$isPublic && !$auth) {
                return (new Response())
                    ->withHeader('Location', '/login')
                    ->withStatus(302);
            }

            return $handler->handle($request);
        };
    }

    /**
     * Verifica se o usuário atual está autenticado:
     * cookie JWT válido + flag de sessão presentes.
     */
    public static function isAuthenticated(): bool
    {
        // Garante sessão iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_COOKIE['auth_token'] ?? null;

        if (!$token || empty($_SESSION['user']['logado'])) {
            return false;
        }

        try {
            JWT::decode($token, new Key(SECRET_KEY, 'HS256'));
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
