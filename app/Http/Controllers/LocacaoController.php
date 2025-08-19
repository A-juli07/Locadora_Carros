<?php

namespace App\Http\Controllers;

use App\Models\Locacao;
use App\Models\Carro;
use App\Http\Controllers\Controller;
use App\Repositories\LocacaoRepository;
use Illuminate\Http\Request;

class LocacaoController extends Controller
{
    public function __construct(Locacao $locacao)
    {
        $this->locacao = $locacao;
    }

    /**
     * @OA\Get(
     *   path="/api/v1/locacao",
     *   summary="Locacao cadastradas",
     *   tags={"Locacao"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="atributos_clientes",
     *     in="query",
     *     required=false,
     *     description="Quais colunas do relacionamento 'cliente' retornar. Use vírgula para separar.",
     *     example="nome",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *  @OA\Parameter(
     *     name="atributos_carros",
     *     in="query",
     *     required=false,
     *     description="Quais colunas do relacionamento 'carro' retornar. Use vírgula para separar.",
     *     example="placa,disponivel,km",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *   @OA\Parameter(
     *     name="filtro",
     *     in="query",
     *     required=false,
     *     description="Filtros dinâmicos. Exemplos de operadores comuns: =, >, >=, <, <=, like, in, between. ",
     *     example="km_final:>:1000",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *   @OA\Parameter(
     *     name="atributos",
     *     in="query",
     *     required=false,
     *     description="Quais colunas do recurso 'locacao' retornar. Use vírgula para separar.",
     *     example="cliente_id,carro_id,data_inicio_periodo,data_final_previsto_periodo,km_final",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *   @OA\Response(response=200, description="Retorna os dados da locação"),
     *   @OA\Response(response=401, description="Não autenticado")
     * )
     */

    public function index(Request $request)
    {

        $locacaoRepository = new LocacaoRepository($this->locacao);

        if ($request->has('atributos_clientes')) {
            $atributos_clientes = 'cliente:id,'.$request->atributos_clientes;
            $locacaoRepository->selectAtributosRegistrosRelacionamentos($atributos_clientes);
        } else {
            $locacaoRepository->selectAtributosRegistrosRelacionamentos('cliente');
        }

        if ($request->has('atributos_carros')) {
            $atributos_carros = 'carro:id,'.$request->atributos_carros;
            $locacaoRepository->selectAtributosRegistrosRelacionamentos($atributos_carros);
        } else {
            $locacaoRepository->selectAtributosRegistrosRelacionamentos('carro');
        }

        if($request->has('filtro')){ 
            $locacaoRepository->filtro($request->filtro);
        }

        if ($request->has('atributos')) {
            $locacaoRepository->atributos($request->atributos);
        }


        return response()->json($locacaoRepository->getResultado(), 200);
    }


    /**
     * @OA\Post(
     *   path="/api/v1/locacao",
     *   summary="Cadastro de locações",
     *   tags={"Locacao"},
     *   security={{"bearerAuth":{}}},
     * 
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"cliente_id","carro_id","data_inicio_periodo","data_final_previsto_periodo","data_final_realizado_periodo", "valor_diaria"},
     *       @OA\Property(property="cliente_id", type="integer", example="2"),
     *       @OA\Property(property="carro_id", type="integer", example="3"),
     *       @OA\Property(property="data_inicio_periodo", type="date_format:d-m-y", example="18-08-25"),
     *       @OA\Property(property="data_final_previsto_periodo", type="date_format:d-m-y", example="20-08-25"),
     *       @OA\Property(property="data_final_realizado_periodo", type="date_format:d-m-y", example="20-08-25"),
     *       @OA\Property(property="valor_diaria", type="integer", example="150")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Locação criada com sucesso"),
     *   @OA\Response(response=422, description="Dados inválidos")
     * )
     */

    
    public function store(Request $request)
    {
        $request->validate($this->locacao->rules());

        $carro = Carro::findOrFail($request->carro_id);

        if($carro->disponivel == 0){

            return response()->json(['msg'=> 'O veiculo não está disponivel, selecione outro'], 400);

        }else{

            $locacao = $this->locacao->create(
                [
                    'cliente_id' => $request->cliente_id,
                    'carro_id' => $carro->id,
                    'data_inicio_periodo' => $request->data_inicio_periodo,
                    'data_final_previsto_periodo' => $request->data_final_previsto_periodo,
                    'data_final_realizado_periodo' => $request->data_final_realizado_periodo,
                    'valor_diaria' => $request->valor_diaria,
                    'km_inicial' => $carro->km,
                    'km_final' => $request->km_final,
                ]
            );

            if($request->data_final_realizado_periodo == null || $request->km_final == null){
                $locacao->status = 'aberto';
            }else{
                $locacao->status = 'finalizado';
            }
           
            $carro->disponivel = 0;
            $carro->save();
        }


        return response()->json($locacao, 201);
    }



    /**
     * @OA\Get(
     *   path="/api/v1/locacao/{id}",
     *   summary="Detalhes de uma locacao",
     *   tags={"Locacao"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID da locacao a ser pesquisado",
     *     @OA\Schema(type="integer"),
     *     example=2
     *   ),
     * 
     *   @OA\Response(response=404, description="Locacao não encontrado"),
     *   @OA\Response(response=422, description="Dados inválidos")
     * )
     */



    public function show($id)
    {
        $locacao = $this->locacao->with('carro')->with('cliente')->find($id);
        if ($locacao === null) {
            return response()->json(['erro' => 'Recurso pesquisado não existe'], 404);
        }

        return response()->json($locacao, 200);
    }


    /**
     * @OA\Put(
     *   path="/api/v1/locacao/{id}",
     *   summary="Atualização completa de uma locacao",
     *   description="Substitui todos os campos do recurso locacao (estilo PUT).",
     *   tags={"Locacao"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID da locacao a ser atualizado",
     *     @OA\Schema(type="integer"),
     *     example=1
     *   ),
     *
     *   @OA\RequestBody(
     *     required=true,
     *     description="Payload completo para atualização (PUT requer todos os campos obrigatórios do model).",
     *     @OA\JsonContent(
     *       required={"cliente_id","carro_id","data_inicio_periodo","data_final_previsto_periodo", "valor_diaria"},
     *       @OA\Property(property="cliente_id", type="integer", example="2"),
     *       @OA\Property(property="carro_id", type="integer", example="3"),
     *       @OA\Property(property="data_inicio_periodo", type="date_format:d-m-y", example="18-08-25"),
     *       @OA\Property(property="data_final_previsto_periodo", type="date_format:d-m-y", example="20-08-25"),
     *       @OA\Property(property="valor_diaria", type="integer", example="150")
     *     )
     *   ),
     *
     *   @OA\Response(response=200, description="Carro atualizado com sucesso"),
     *   @OA\Response(response=404, description="Recurso não existe"),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
     *
     * @OA\Patch(
     *   path="/api/v1/locacao/{id}",
     *   summary="Finaliza uma locacao",
     *   description="Atualiza apenas os campos enviados (estilo PATCH).",
     *   tags={"Locacao"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID da locacao a ser atualizado parcialmente",
     *     @OA\Schema(type="integer"),
     *     example=1
     *   ),
     *
     *   @OA\RequestBody(
     *     required=true,
     *     description="Qualquer subconjunto de campos de locacao.",
     *     @OA\JsonContent(
     *       @OA\Property(property="km_final", type="integer", example=1798),
     *       @OA\Property(property="data_final_realizado_periodo", type="date_format:d-m-y", example="20-08-25"),
     *     )
     *   ),
     *
     *   @OA\Response(response=200, description="Locacao atualizado parcialmente com sucesso"),
     *   @OA\Response(response=404, description="Recurso não existe"),
     *   @OA\Response(response=422, description="Erro de validação (apenas para campos enviados)")
     * )
     */


    
    public function update(Request $request, $id)
    {
        $locacao = $this->locacao->find($id);

        if ($locacao === null) {
            return response()->json(['erro' => 'Impossível realizar a atualização. O recurso solicitado não existe'], 404);
        }

        if ($request->method() === 'PATCH') {

            $regrasDinamicas = array();

            //percorrendo todas as regras definidas no Model
            foreach ($locacao->rules() as $input => $regra) {

                //coleta apenas as regras aplicáveis aos parâmetros parciais da requisição PATCH
                if (array_key_exists($input, $request->all())) {
                    $regrasDinamicas[$input] = $regra;
                }
            }

            $request->validate($regrasDinamicas);
        } else {
            $request->validate($locacao->rules());
        }

        $kmInicialEfetivo = $request->has('km_inicial') ? $request->km_inicial : $locacao->km_inicial;

        if ($request->has('km_final')) {
            if (!is_numeric($request->km_final) || !is_numeric($kmInicialEfetivo) || $request->km_final <= $kmInicialEfetivo) {
                return response()->json(['errors' => ['km_final' => ['O km_final deve ser maior que o km_inicial.']]], 422);
            }
        }


        $locacao->fill($request->all());
        $locacao->status = 'finalizado';
        $locacao->save();


        if ($request->has('km_final')) {
            
            $carro = Carro::find($locacao->carro_id);
            if ($carro) {
                $carro->km = $locacao->km_final;
                $carro->disponivel = 1;
                $carro->save();
            }

        }

        
        return response()->json($locacao, 200);
    }

    
    /**
     * @OA\Delete(
     *   path="/api/v1/locacao/{id}",
     *   summary="Remover uma locacao",
     *   tags={"Locacao"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID da locacao a ser deletado",
     *     @OA\Schema(type="integer"),
     *     example=2
     *   ),
     * 
     *   @OA\Response(response=404, description="Locacao não encontrado"),
     *   @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    

    public function destroy($id)
    {
        $locacao = $this->locacao->find($id);

        if ($locacao === null) {
            return response()->json(['erro' => 'Impossível realizar a exclusão. O recurso solicitado não existe'], 404);
        }

        $locacao->delete();
        return response()->json(['msg' => 'A locacao foi removida com sucesso!'], 200);
    }
}
