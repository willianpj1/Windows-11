<?php

declare(strict_types=1);

namespace app\middleware;

class Middleware
{
    #metodo de autenticação via token de rotas post
    public static function api(){
        $middleware = function ($resquest, $handle){

        };
        return $middleware;

    }
    #metodo de autenticação das rotas get
    public static function web(){
        $middleware = function ($resquest, $handle){

        };
        return $middleware;

    }

}