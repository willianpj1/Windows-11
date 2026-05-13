<?php

declare(strict_types=1);

namespace app\controller;

final class Register extends Base
{
    // ─── Página ───────────────────────────────────────────────────────────────

    public function register($request, $response)
    {
        return $this->getTwig()
            ->render($response, $this->setView('register'), [
                'titulo' => 'Cadastro',
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }

    // ─── Inserção ─────────────────────────────────────────────────────────────

    /*public function store($request, $response)
    {
        $form = $request->getParsedBody();

        $nome      = $form['nome']      ?? null;
        $sobrenome = $form['sobrenome'] ?? null;
        $cpf       = $form['cpf']       ?? null;
        $rg        = $form['rg']        ?? null;
        $senha     = $form['senha']     ?? null;
        $email     = $form['email']     ?? null;
        $telefone  = $form['telefone']     ?? null;


        if (is_null($nome) || is_null($sobrenome) || ) {
            return $this->json($response, ['status' => false, 'msg' => 'Por favor informe os dados corretamente!', 'id' => 0], 403);
        }

        $DataUser = [
            'nome'=>$nome,
            'sobrenome'=>$sobrenome,
            'cpf'=>$cpf,
            'rg'=>$rg,
            'senha'=>password_hash($senha,PASSWORD_DEFAULT);        
        ];


        #Inserir o usuário na tabela users e receber o seu código
        $id = \app\database\DB::insert('nome da tabela do banco', []);


        $DataEmail = [
                'id_usuario'=>$id,
                'tipo'=>$tipo,
                'contato'=>$contato
        ];

        // ── 2. Unicidade de e-mail e CPF ──────────────────────────────────────
        try {
            $existeEmail = \app\database\DB::select('id')
                ->from('users')
                ->where('email = ' . \app\database\DB::connection()->quote($email))
                ->fetchOne();

            if ($existeEmail) {
                return $this->json($response, [
                    'status'  => false,
                    'message' => 'Este e-mail já está cadastrado.',
                ], 409);
            }

            $existeCpf = \app\database\DB::select('id')
                ->from('users')
                ->where('cpf = ' . \app\database\DB::connection()->quote($cpf))
                ->fetchOne();

            if ($existeCpf) {
                return $this->json($response, [
                    'status'  => false,
                    'message' => 'Este CPF já está cadastrado.',
                ], 409);
            }
        } catch (\Throwable $e) {
            return $this->json($response, [
                'status'  => false,
                'message' => 'Erro ao verificar dados. Tente novamente.',
            ], 500);
        }

        // ── 3. Inserção ───────────────────────────────────────────────────────
        try {

            \app\database\DB::connection()->insert('users', [
                'nome'          => $nome,
                'sobrenome'     => $sobrenome,
                'cpf'           => $cpf,
                'rg'            => $rg,
                'senha'         => password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]),
                'google_id'     => null,
                'salario'       => 0,
            ]);

            return $this->json($response, [
                'status'  => true,
                'message' => 'Conta criada com sucesso!',
            ], 201);
        } catch (\Throwable $e) {
            return $this->json($response, [
                'status' => false,
                'msg' => 'Restrição: ' . $e->getMessage(),
                'id' => 0
            ], 500);
        }
    }*/

    public function preRegister($request, $response)
    {
        $form = $request->getParsedBody();
        #Captura os dados informado pelo usuário no formulário de pré-cadastro
        $nome      = $form['nome'] ?? null;
        $sobrenome = $form['sobrenome'] ?? null;
        $cpf       = $form['cpf'] ?? null;
        $rg        = $form['rg'] ?? null;
        $senha     = $form['senha'] ?? null;
        #Dados de contato.
        $email     = $form['email'] ?? null;
        $telefone  = $form['telefone'] ?? null;
        #Criamos o array associativo com os dados do usuário, onde a 
        #chave é o nome da coluna no banco de dados e o valor é o dado 
        #informado pelo usuário.
        $DataUser = [
            'nome'      => $nome,
            'sobrenome' => $sobrenome,
            'cpf'       => $cpf,
            'rg'        => $rg,
            'senha'     => password_hash($senha, PASSWORD_DEFAULT)
        ];
        $id_usuario = 0;
        #Insere os dados no data base com o Docrine e recebe o ID do usuário criado.
        $id_usuario = \app\database\DB::connection()->insert('users', $DataUser);
        #Insere os dados do email do usuário na base.
        $DataEmail = [
            'id_usuario' => $id_usuario,
            'tipo' => 'EMAIL',
            'contato' => $email
        ];
        \app\database\DB::connection()->insert('contact', $DataEmail);
        #Insere os dados do telefone do usuário na base.
        $DataTel = [
            'id_usuario' => $id_usuario,
            'tipo' => 'TELEFONE',
            'contato' => $telefone
        ];
        \app\database\DB::connection()->insert('contact', $DataTel);
        #Retorna a resposta de sucesso ao cliente
        return $this->json($response, [
            'status' => true,
            'msg' => 'Usuário cadastrado com sucesso!'
        ], 200);
    }

    // ─── Validação interna ────────────────────────────────────────────────────

    /*private function validar(array $form): array
    {
        $erros = [];

        if (empty(trim($form['nome'] ?? ''))) {
            $erros[] = 'Nome é obrigatório.';
        }

        if (empty(trim($form['sobrenome'] ?? ''))) {
            $erros[] = 'Sobrenome é obrigatório.';
        }

        $email = trim($form['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'E-mail inválido.';
        }

        $cpf = preg_replace('/\D/', '', $form['cpf'] ?? '');
        if (!$this->validarCpf($cpf)) {
            $erros[] = 'CPF inválido.';
        }

        $rg = preg_replace('/\D/', '', $form['rg'] ?? '');
        if (strlen($rg) < 5) {
            $erros[] = 'RG inválido.';
        }

        $senha = $form['senha'] ?? '';
        if (strlen($senha) < 8) {
            $erros[] = 'A senha deve ter pelo menos 8 caracteres.';
        }

        $confirmar = $form['confirmarSenha'] ?? '';
        if ($senha !== $confirmar) {
            $erros[] = 'As senhas não coincidem.';
        }

        return $erros;
    }

    private function validarCpf(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        $calc = function (int $limit) use ($cpf): int {
            $soma = 0;
            for ($i = 0; $i < $limit; $i++) {
                $soma += (int) $cpf[$i] * ($limit + 1 - $i);
            }
            $resto = ($soma * 10) % 11;
            return $resto > 9 ? 0 : $resto;
        };

        return $calc(9) === (int) $cpf[9] && $calc(10) === (int) $cpf[10];
    }*/
}
