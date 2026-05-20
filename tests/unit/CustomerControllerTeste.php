<?php

declare(strict_types=1);

use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

test('Register com dados validos retorna 200 status true', function () {
    $request = (new RequestFactory())
        ->createRequest('POST', '/cliente/insert')
        ->withHeader('Content-type', 'application/x-www-form-urlencoded')
        ->withParsedBody([
            'nome_fantasia' => 'willian',
            'sobrenome_razao' => 'pereira',
            'cpf_cnpj' => '123.123.123-12',
            'inscricao_estadual' => '123456',
            'nascimento_fundacao' => '12/12/2012',
            'ativo' => 'true',
        ]);

    $response = (new ResponseFactory())->createResponse();

    $result = (new app\controller\Customer())->insert($request, $response);

    $result->getBody()->rewind();

    $json = json_decode($result->getbody()->getContents(), true);
    #Capturamos o codigo de resposta caso seja 201 significa que foi criado
    expect($result->getStatusCode())->toBe(201);

    expect($json['status'])->toBeTrue();

    expect($json['msg'])->toContain('Salvo com sucesso!');
});
