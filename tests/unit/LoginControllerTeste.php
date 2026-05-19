<?php

declare(strict_types=1);

use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

test('Register com dados validos retorna 200 status true', function () {
    $request = (new RequestFactory())
        ->createRequest('POST', '/preRegister')
        ->withHeader('Content-type', 'application/x-www-form-urlencoded')
        ->withParsedBody([
            'nome' => 'willian',
            'sobrenome' => 'pereira',
            'cpf' => '123.123.123-12',
            'rg' => '123456',
            'senha' => '1234',
            'email' => 'pereira@pereira.com',
            'telefone' => '69999999999'
        ]);

    $response = (new ResponseFactory())->createResponse();

    $result = (new app\controller\Register() )->preRegister($request, $response);

    $result->getBody()->rewind();

    $json = json_decode($result->getbody()->getContents(), true);
    #Capturamos o codigo de resposta caso seja 201 significa que foi criado
    expect($result->getStatusCode())->toBe(201);

    expect($json['status'])->toBeTrue();
    
    expect($json['msg'])->toContain('Pré-cadastro realizado com sucesso!');
    
    expect($json['status'])->toBeTrue();
});
