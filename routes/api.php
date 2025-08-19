<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\JwtMiddleware;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('v1')->middleware('jwt')->group(function () {
    Route::post('me', 'App\Http\Controllers\AuthController@me');
    Route::post('logout', 'App\Http\Controllers\AuthController@logout');
    Route::post('refresh', 'App\Http\Controllers\AuthController@refresh');
    Route::apiresource('cliente', 'App\Http\Controllers\ClienteController'); //Seleciona todos os metodos criados ao criar o ClienteController com -a, ou -mcr 
    Route::apiresource('carro', 'App\Http\Controllers\CarroController');
    Route::apiresource('locacao', 'App\Http\Controllers\LocacaoController');
    Route::apiresource('marca', 'App\Http\Controllers\MarcaController');
    Route::apiresource('modelo', 'App\Http\Controllers\ModeloController');
});

Route::post('login', 'App\Http\Controllers\AuthController@login');
Route::post('cadastro', 'App\Http\Controllers\AuthController@cadastro');