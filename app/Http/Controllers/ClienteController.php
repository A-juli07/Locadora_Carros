<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\ClienteRepository;

class ClienteController extends Controller
{
    public function __construct(Cliente $cliente)
    {
        $this->cliente = $cliente;
    }

    /**
     * @OA\Get(
     *   path="/api/v1/cliente",
     *   summary="Clientes cadastrados",
     *   tags={"Cliente"},
     *   security={{"bearerAuth":{}}},
     * 
     *   @OA\Parameter(
     *     name="filtro",
     *     in="query",
     *     required=false,
     *     description="Filtros dinâmicos. Exemplos de operadores comuns: =, >, >=, <, <=, like, in, between. Use % caso queira pesquisa só o inicio do nome",
     *     example="nome:like:J%",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *   @OA\Parameter(
     *     name="atributos",
     *     in="query",
     *     required=false,
     *     description="Quais colunas do recurso 'cliente' retornar. Use vírgula para separar.",
     *     example="nome,id",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *   @OA\Response(response=200, description="Retorna os dados do cliente"),
     *   @OA\Response(response=401, description="Não autenticado")
     * )
     */

    public function index(Request $request)
    {
        $clienteRepository = new ClienteRepository($this->cliente);

        if ($request->has('filtro')) {
            $clienteRepository->filtro($request->filtro);
        }

        if ($request->has('atributos')) {
            $clienteRepository->atributos($request->atributos);
        }


        return response()->json($clienteRepository->getResultado(), 200);
    }


     /**
     * @OA\Post(
     *   path="/api/v1/cliente",
     *   summary="Cadastro de clientes",
     *   tags={"Cliente"},
     *   security={{"bearerAuth":{}}},
     * 
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"nome"},
     *       @OA\Property(property="nome", type="string", example="João Teste"),
     *     )
     *   ),
     *   @OA\Response(response=201, description="Cliente criado com sucesso"),
     *   @OA\Response(response=422, description="Dados inválidos")
     * )
     */


    public function store(Request $request)
    {
        $request->validate($this->cliente->rules());

        $cliente = $this->cliente->create(
            [
                'nome' => $request->nome,
            ]
        );

        return response()->json($cliente, 201);
    }


    /**
     * @OA\Get(
     *   path="/api/v1/cliente/{id}",
     *   summary="Detalhes de um cliente",
     *   tags={"Cliente"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do cliente a ser pesquisado",
     *     @OA\Schema(type="integer"),
     *     example=2
     *   ),
     * 
     *   @OA\Response(response=404, description="Cliente não encontrado"),
     *   @OA\Response(response=422, description="Dados inválidos")
     * )
     */


    public function show($id)
    {
        $cliente = $this->cliente->find($id);
        if ($cliente === null) {
            return response()->json(['erro' => 'Recurso pesquisado não existe'], 404);
        }

        return response()->json($cliente, 200);
    }



    /**
     * @OA\Put(
     *   path="/api/v1/cliente/{id}",
     *   summary="Atualização completa de um cliente",
     *   description="Substitui todos os campos do recurso cliente (estilo PUT).",
     *   tags={"Cliente"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do cliente a ser atualizado",
     *     @OA\Schema(type="integer"),
     *     example=1
     *   ),
     *
     *   @OA\RequestBody(
     *     required=true,
     *     description="Payload completo para atualização (PUT requer todos os campos obrigatórios do model).",
     *     @OA\JsonContent(
     *       required={"nome"},
     *       @OA\Property(property="nome", type="string")
     *     )
     *   ),
     *
     *   @OA\Response(response=200, description="Cliente atualizado com sucesso"),
     *   @OA\Response(response=404, description="Recurso não existe"),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
     *
     */


    public function update(Request $request, $id)
    {
        $cliente = $this->cliente->find($id);

        if ($cliente === null) {
            return response()->json(['erro' => 'Impossível realizar a atualização. O recurso solicitado não existe'], 404);
        }

        if ($request->method() === 'PATCH') {

            $regrasDinamicas = array();


            foreach ($cliente->rules() as $input => $regra) {

                if (array_key_exists($input, $request->all())) {
                    $regrasDinamicas[$input] = $regra;
                }
            }

            $request->validate($regrasDinamicas);
        } else {
            $request->validate($cliente->rules());
        }



        $cliente->fill($request->all());
        $cliente->save();

        return response()->json($cliente, 200);
    }


    /**
     * @OA\Delete(
     *   path="/api/v1/cliente/{id}",
     *   summary="Remover um cliente",
     *   tags={"Cliente"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do cliente a ser deletado",
     *     @OA\Schema(type="integer"),
     *     example=2
     *   ),
     * 
     *   @OA\Response(response=404, description="Cliente não encontrado"),
     *   @OA\Response(response=422, description="Dados inválidos")
     * )
     */


    public function destroy($id)
    {
        $cliente = $this->cliente->find($id);

        if ($cliente === null) {
            return response()->json(['erro' => 'Impossível realizar a exclusão. O recurso solicitado não existe'], 404);
        }

        $cliente->delete();
        return response()->json(['msg' => 'Cliente removido com sucesso!'], 200);
    }
}
