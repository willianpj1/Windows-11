<?php

declare(strict_types=1);

use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

const TEST_CPF   = '123.123.123-12';
const TEST_EMAIL = 'pereira@pereira.com';
const TEST_PHONE = '69999999999';

test('Register com dados validos retorna 201 status true', function () {
    $request = (new RequestFactory())
        ->createRequest('POST', '/cadastro')
        ->withHeader('Content-type', 'application/x-www-form-urlencoded')
        ->withParsedBody([
            'nome'      => 'willian',
            'sobrenome' => 'pereira',
            'cpf'       => TEST_CPF,
            'rg'        => '123456',
            'senha'     => '1234',
            'email'     => TEST_EMAIL,
            'telefone'  => TEST_PHONE,
        ]);

    $response = (new ResponseFactory())->createResponse();

    $result = (new \app\controller\Register())->preRegister($request, $response);

    $result->getBody()->rewind();
    $json = json_decode($result->getBody()->getContents(), true);

    expect($result->getStatusCode())->toBe(201);
    expect($json['status'])->toBeTrue();
    expect($json['msg'])->toContain('Pré-cadastro realizado com sucesso!');
    expect($json['id'])->toBeInt()->toBeGreaterThan(0);
});
