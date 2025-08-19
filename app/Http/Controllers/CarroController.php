<?php

namespace App\Http\Controllers;

use App\Models\Carro;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\CarroRepository;

class CarroController extends Controller
{

    public function __construct(Carro $carro)
    {
        $this->carro = $carro;
    }

    /**
     * @OA\Get(
     *   path="/api/v1/carro",
     *   summary="Carros cadastrados",
     *   tags={"Carro"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="atributos_modelo",
     *     in="query",
     *     required=false,
     *     description="Quais colunas do relacionamento 'modelo' retornar. Use vírgula para separar.",
     *     example="nome,numero_portas",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *   @OA\Parameter(
     *     name="filtro",
     *     in="query",
     *     required=false,
     *     description="Filtros dinâmicos. Exemplos de operadores comuns: =, >, >=, <, <=, like, in, between. ",
     *     example="km:>:1000",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *   @OA\Parameter(
     *     name="atributos",
     *     in="query",
     *     required=false,
     *     description="Quais colunas do recurso 'carro' retornar. Use vírgula para separar.",
     *     example="modelo_id,placa,km,disponivel",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *   @OA\Response(response=200, description="Retorna os dados do carro"),
     *   @OA\Response(response=401, description="Não autenticado")
     * )
     */

    public function index(Request $request)
    {
        $carroRepository = new CarroRepository($this->carro);

        if ($request->has('atributos_modelo')) {
            $atributos_modelo = 'modelo:id,' . $request->atributos_modelo;
            $carroRepository->selectAtributosRegistrosRelacionamentos($atributos_modelo);
        } else {
            $carroRepository->selectAtributosRegistrosRelacionamentos('modelo');
        }

        if ($request->has('filtro')) {
            $carroRepository->filtro($request->filtro);
        }

        if ($request->has('atributos')) {
            $carroRepository->atributos($request->atributos);
        }


        return response()->json($carroRepository->getResultado(), 200);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/carro",
     *   summary="Cadastro de carros",
     *   tags={"Carro"},
     *   security={{"bearerAuth":{}}},
     * 
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"modelo_id","placa","disponivel","km"},
     *       @OA\Property(property="modelo_id", type="integer", example="1"),
     *       @OA\Property(property="placa", type="integer", example="JG23P4"),
     *       @OA\Property(property="disponivel", type="boolean", example="1"),
     *       @OA\Property(property="km", type="integer", example="1000")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Carro criado com sucesso"),
     *   @OA\Response(response=422, description="Dados inválidos")
     * )
     */

    public function store(Request $request)
    {
        $request->validate($this->carro->rules());

        $carro = $this->carro->create(
            [
                'modelo_id' => $request->modelo_id,
                'placa' => $request->placa,
                'disponivel' => $request->disponivel,
                'km' => $request->km,
            ]
        );

        return response()->json($carro, 201);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/carro/{id}",
     *   summary="Detalhes de um carro",
     *   tags={"Carro"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do carro a ser pesquisado",
     *     @OA\Schema(type="integer"),
     *     example=2
     *   ),
     * 
     *   @OA\Response(response=404, description="Carro não encontrado"),
     *   @OA\Response(response=422, description="Dados inválidos")
     * )
     */

    public function show($id)
    {
        $carro = $this->carro->with('modelo')->find($id);
        if ($carro === null) {
            return response()->json(['erro' => 'Recurso pesquisado não existe'], 404);
        }

        return response()->json($carro, 200);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/carro/{id}",
     *   summary="Atualização completa de um carro",
     *   description="Substitui todos os campos do recurso carro (estilo PUT).",
     *   tags={"Carro"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do carro a ser atualizado",
     *     @OA\Schema(type="integer"),
     *     example=1
     *   ),
     *
     *   @OA\RequestBody(
     *     required=true,
     *     description="Payload completo para atualização (PUT requer todos os campos obrigatórios do model).",
     *     @OA\JsonContent(
     *       required={"modelo_id","placa","km","disponivel"},
     *       @OA\Property(property="modelo_id", type="integer", example=3),
     *       @OA\Property(property="placa", type="string", example="ABC1D23"),
     *       @OA\Property(property="km", type="integer", example=15000),
     *       @OA\Property(property="disponivel", type="boolean", example=true)
     *     )
     *   ),
     *
     *   @OA\Response(response=200, description="Carro atualizado com sucesso"),
     *   @OA\Response(response=404, description="Recurso não existe"),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
     *
     * @OA\Patch(
     *   path="/api/v1/carro/{id}",
     *   summary="Atualização parcial de um carro",
     *   description="Atualiza apenas os campos enviados (estilo PATCH).",
     *   tags={"Carro"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do carro a ser atualizado parcialmente",
     *     @OA\Schema(type="integer"),
     *     example=1
     *   ),
     *
     *   @OA\RequestBody(
     *     required=true,
     *     description="Qualquer subconjunto de campos do carro.",
     *     @OA\JsonContent(
     *       @OA\Property(property="modelo_id", type="integer", example=1),
     *       @OA\Property(property="placa", type="string", example="XYZ9Z99"),
     *       @OA\Property(property="km", type="integer", example=22000),
     *       @OA\Property(property="disponivel", type="boolean", example=false),
     *       example={"km":22000,"disponivel":false}
     *     )
     *   ),
     *
     *   @OA\Response(response=200, description="Carro atualizado parcialmente com sucesso"),
     *   @OA\Response(response=404, description="Recurso não existe"),
     *   @OA\Response(response=422, description="Erro de validação (apenas para campos enviados)")
     * )
     */


    public function update(Request $request, $id)
    {

        $carro = $this->carro->find($id);

        if ($carro === null) {
            return response()->json(['erro' => 'Impossível realizar a atualização. O recurso solicitado não existe'], 404);
        }

        if ($request->method() === 'PATCH') {

            $regrasDinamicas = array();

            //percorrendo todas as regras definidas no Model
            foreach ($carro->rules() as $input => $regra) {

                //coletar apenas as regras aplicáveis aos parâmetros parciais da requisição PATCH
                if (array_key_exists($input, $request->all())) {
                    $regrasDinamicas[$input] = $regra;
                }
            }

            $request->validate($regrasDinamicas);
        } else {

            $request->validate($carro->rules());
        }


        $carro->fill($request->all());
        $carro->save();

        return response()->json($carro, 200);
    }


    /**
     * @OA\Delete(
     *   path="/api/v1/carro/{id}",
     *   summary="Remover um carro",
     *   tags={"Carro"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do carro a ser deletado",
     *     @OA\Schema(type="integer"),
     *     example=2
     *   ),
     * 
     *   @OA\Response(response=404, description="Carro não encontrado"),
     *   @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    

    public function destroy($id)
    {
        $carro = $this->carro->find($id);

        if ($carro === null) {
            return response()->json(['erro' => 'Impossível realizar a exclusão. O recurso solicitado não existe'], 404);
        }

        $carro->delete();
        return response()->json(['msg' => 'Carro foi removido com sucesso!'], 200);
    }
}
