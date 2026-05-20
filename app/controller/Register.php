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
    public function preRegister($request, $response)
    {
        $form = $request->getParsedBody();

        // Captura os dados informados pelo usuário no formulário de pré-cadastro
        $nome      = $form['nome']      ?? null;
        $sobrenome = $form['sobrenome'] ?? null;
        $cpf       = $form['cpf']       ?? null;
        $rg        = $form['rg']        ?? null;
        $senha     = $form['senha']     ?? null;
        $email     = $form['email']     ?? null;
        $telefone  = $form['telefone']  ?? null;

        // Criamos o array associativo com os dados do usuário, onde a
        // chave é o nome da coluna no banco de dados e o valor é o dado
        // informado pelo usuário.
        $DataUser = [
            'nome'         => $nome,
            'sobrenome'    => $sobrenome,
            'cpf'          => $cpf,
            'rg'           => $rg,
            'senha'        => password_hash($senha, PASSWORD_DEFAULT),
            'criado_em'    => date('Y-m-d H:i:s'),
            'atualizado_em' => date('Y-m-d H:i:s'),
        ];

        try {
            $conn = \app\database\DB::connection();
            $conn->beginTransaction();

            // Insere os dados no database com o Doctrine e recebe o ID do usuário criado.
            $conn->insert('users', $DataUser);
            $id_usuario = (int) $conn->lastInsertId();

            // Insere os dados do email do usuário na base.
            if (!empty($email)) {
                $DataEmail = [
                    'id_usuario'   => $id_usuario,
                    'tipo'         => 'EMAIL',
                    'contato'      => $email,
                    'criado_em'    => date('Y-m-d H:i:s'),
                    'atualizado_em' => date('Y-m-d H:i:s'),
                ];
                $conn->insert('contact', $DataEmail);
            }

            // Insere os dados do telefone do usuário na base.
            if (!empty($telefone)) {
                $DataTel = [
                    'id_usuario'   => $id_usuario,
                    'tipo'         => 'TELEFONE',
                    'contato'      => $telefone,
                    'criado_em'    => date('Y-m-d H:i:s'),
                    'atualizado_em' => date('Y-m-d H:i:s'),
                ];
                $conn->insert('contact', $DataTel);
            }

            $conn->commit();

            return $this->json($response, [
                'status' => true,
                'msg'    => 'Pré-cadastro realizado com sucesso!',
                'id'     => $id_usuario,
            ], 201);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            $conn->rollBack();
            return $this->json($response, [
                'status' => false,
                'msg'    => 'CPF ou contato já cadastrado.',
                'id'     => 0,
            ], 409);
        } catch (\Throwable $e) {
            $conn->rollBack();
            error_log('[preRegister] ' . $e->getMessage());
            return $this->json($response, [
                'status' => false,
                'msg'    => 'Erro ao realizar pré-cadastro. Tente novamente.',
                'id'     => 0,
            ], 500);
        }
    }    
}