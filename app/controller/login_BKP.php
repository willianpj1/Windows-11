<?php

declare(strict_types=1);

namespace app\controller;

use Firebase\JWT\JWT;
use Google\Auth\OAuth2;

final class Login extends Base
{
    // ─── Constantes de configuração ───────────────────────────────────────────
    private const JWT_EXPIRY    = 3600 * 8;   // 8 horas
    private const COOKIE_NAME   = 'auth_token';
    private const GOOGLE_SCOPES = 'openid email profile';

    // ─── Páginas ───────────────────────────────────────────────────────────────

    public function login($request, $response)
    {
        return $this->getTwig()
            ->render($response, $this->setView('login'), [
                'titulo' => 'Login',
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }

    // ─── Autenticação com usuário e senha ──────────────────────────────────────

    public function authenticate($request, $response)
    {
        $form  = $request->getParsedBody();
        $login = trim($form['login'] ?? '');
        $senha = trim($form['senha'] ?? '');

        if ($login === '' || $senha === '') {
            return $this->json($response, [
                'status'  => false,
                'message' => 'Login e senha são obrigatórios.',
            ], 422);
        }

        try {
            // Busca por CPF, e-mail ou telefone
            $qb   = \app\database\DB::select('*')->from('users');
            $user = $qb->where(
                $qb->expr()->or(
                    $qb->expr()->eq('cpf',   $qb->createPositionalParameter($login)),
                    $qb->expr()->eq('email', $qb->createPositionalParameter($login))
                )
            )->fetchAssociative();

            if (!$user || !password_verify($senha, (string) $user['senha'])) {
                return $this->json($response, [
                    'status'  => false,
                    'message' => 'Credenciais inválidas.',
                ], 401);
            }

            if (!(bool) $user['ativo']) {
                return $this->json($response, [
                    'status'  => false,
                    'message' => 'Usuário inativo. Contate o administrador.',
                ], 403);
            }

            return $this->issueSession($response, $user);

        } catch (\Throwable $e) {
            return $this->json($response, [
                'status'  => false,
                'message' => 'Erro interno. Tente novamente.',
            ], 500);
        }
    }

    // ─── OAuth Google — redireciona para o consent screen ─────────────────────

    public function googleRedirect($request, $response)
    {
        $oauth2 = $this->buildGoogleOAuth2();

        // Gera state aleatório e armazena na sessão para validar no callback
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth2_state'] = $state;

        $authUrl = $oauth2->buildFullAuthorizationUri([
            'state'  => $state,
            'prompt' => 'select_account',
        ]);

        return $response
            ->withHeader('Location', (string) $authUrl)
            ->withStatus(302);
    }

    // ─── OAuth Google — callback após autorização ──────────────────────────────

    public function googleCallback($request, $response)
    {
        $params = $request->getQueryParams();

        // Valida state para prevenir CSRF
        $state        = $params['state']         ?? '';
        $sessionState = $_SESSION['oauth2_state'] ?? '';
        unset($_SESSION['oauth2_state']);

        if (!hash_equals($sessionState, $state) || $state === '') {
            return $response
                ->withHeader('Location', '/login?erro=state_invalido')
                ->withStatus(302);
        }

        $code = $params['code'] ?? '';
        if ($code === '') {
            return $response
                ->withHeader('Location', '/login?erro=acesso_negado')
                ->withStatus(302);
        }

        try {
            $oauth2 = $this->buildGoogleOAuth2();
            $oauth2->setCode($code);
            $oauth2->fetchAuthToken();

            $idToken = $oauth2->getIdToken();
            if (!$idToken) {
                throw new \RuntimeException('ID token não recebido.');
            }

            // Verifica e decodifica o ID token do Google
            $payload = $this->verifyGoogleIdToken($idToken);

            $googleId = $payload['sub'];
            $email    = $payload['email']      ?? '';
            $nome     = $payload['given_name'] ?? ($payload['name'] ?? 'Usuário');
            $sobrenome= $payload['family_name'] ?? '';

            // Busca ou cria o usuário
            $user = $this->findOrCreateGoogleUser($googleId, $email, $nome, $sobrenome);

            if (!(bool) $user['ativo']) {
                return $response
                    ->withHeader('Location', '/login?erro=usuario_inativo')
                    ->withStatus(302);
            }

            $this->issueSession($response, $user);

            // issueSession retorna uma PSR-7 Response com JSON — no fluxo Google
            // queremos redirecionar para a home em vez de retornar JSON.
            $this->buildCookie($user);
            $this->buildSession($user);

            return $response
                ->withHeader('Location', '/home')
                ->withStatus(302);

        } catch (\Throwable $e) {
            return $response
                ->withHeader('Location', '/login?erro=falha_google')
                ->withStatus(302);
        }
    }

    // ─── Logout ────────────────────────────────────────────────────────────────

    public function logout($request, $response)
    {
        // Destrói a sessão
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // Remove o cookie JWT definindo expiração no passado
        setcookie(self::COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => ($_ENV['APP_ENV'] ?? 'production') === 'production',
        ]);

        return $response
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }

    // ─── Helpers privados ──────────────────────────────────────────────────────

    /**
     * Gera JWT, grava cookie httponly e sessão, retorna JSON (fluxo form).
     */
    private function issueSession($response, array $user)
    {
        $this->buildCookie($user);
        $this->buildSession($user);

        return $this->json($response, [
            'status'  => true,
            'message' => 'Autenticado com sucesso.',
        ], 200);
    }

    /**
     * Monta e grava o cookie JWT httponly.
     */
    private function buildCookie(array $user): void
    {
        $now     = time();
        $payload = [
            'iss'   => $_ENV['APP_URL']      ?? 'localhost',
            'sub'   => (int) $user['id'],
            'name'  => $user['nome']         ?? '',
            'email' => $user['email']        ?? '',
            'admin' => (bool) ($user['administrador'] ?? false),
            'iat'   => $now,
            'exp'   => $now + self::JWT_EXPIRY,
        ];

        $token = JWT::encode($payload, SECRET_KEY, 'HS256');

        setcookie(self::COOKIE_NAME, $token, [
            'expires'  => $now + self::JWT_EXPIRY,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => ($_ENV['APP_ENV'] ?? 'production') === 'production',
        ]);
    }

    /**
     * Grava dados mínimos na sessão (usada pelo middleware para short-circuit).
     */
    private function buildSession(array $user): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'logado'        => true,
            'id'            => (int) $user['id'],
            'nome'          => $user['nome']          ?? '',
            'email'         => $user['email']         ?? '',
            'administrador' => (bool) ($user['administrador'] ?? false),
        ];
    }

    /**
     * Busca usuário pelo google_id ou e-mail; cria se não existir.
     */
    private function findOrCreateGoogleUser(
        string $googleId,
        string $email,
        string $nome,
        string $sobrenome
    ): array {
        $conn = \app\database\DB::connection();

        // 1. Tenta pelo google_id
        $user = \app\database\DB::select('*')
            ->from('users')
            ->where('google_id = ' . $conn->quote($googleId))
            ->fetchAssociative();

        if ($user) {
            return $user;
        }

        // 2. Tenta pelo e-mail (vincula conta existente)
        if ($email !== '') {
            $user = \app\database\DB::select('*')
                ->from('users')
                ->where('email = ' . $conn->quote($email))
                ->fetchAssociative();

            if ($user) {
                // Vincula google_id à conta existente
                $conn->update('users', ['google_id' => $googleId], ['id' => $user['id']]);
                $user['google_id'] = $googleId;
                return $user;
            }
        }

        // 3. Cria novo usuário
        $conn->insert('users', [
            'nome'          => $nome,
            'sobrenome'     => $sobrenome,
            'email'         => $email,
            'google_id'     => $googleId,
            'senha'         => password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
            'cpf'           => '',
            'rg'            => '',
            'ativo'         => true,
            'administrador' => false,
        ]);

        return \app\database\DB::select('*')
            ->from('users')
            ->where('google_id = ' . $conn->quote($googleId))
            ->fetchAssociative();
    }

    /**
     * Constrói a instância OAuth2 do Google com as configurações do .env.
     */
    private function buildGoogleOAuth2(): OAuth2
    {
        return new OAuth2([
            'clientId'                => $_ENV['GOOGLE_CLIENT_ID'],
            'clientSecret'            => $_ENV['GOOGLE_CLIENT_SECRET'],
            'authorizationUri'        => 'https://accounts.google.com/o/oauth2/v2/auth',
            'tokenCredentialUri'      => 'https://oauth2.googleapis.com/token',
            'redirectUri'             => $_ENV['GOOGLE_REDIRECT_URI'],
            'scope'                   => self::GOOGLE_SCOPES,
            'additionalRequestParams' => ['access_type' => 'online'],
        ]);
    }

    /**
     * Verifica o ID token do Google via endpoint de tokeninfo (sem chave pública local).
     * Para produção de alta escala, prefira validação local com as chaves JWKS do Google.
     */
    private function verifyGoogleIdToken(string $idToken): array
    {
        $url      = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
        $response = @file_get_contents($url);

        if ($response === false) {
            throw new \RuntimeException('Falha ao verificar token do Google.');
        }

        $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if (isset($payload['error_description'])) {
            throw new \RuntimeException('Token inválido: ' . $payload['error_description']);
        }

        // Garante que o token foi emitido para o nosso app
        if (($payload['aud'] ?? '') !== $_ENV['GOOGLE_CLIENT_ID']) {
            throw new \RuntimeException('Token não pertence a esta aplicação.');
        }

        return $payload;
    }
}