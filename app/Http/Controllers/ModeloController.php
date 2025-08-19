<?php

namespace App\Http\Controllers;

use App\Models\Modelo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Repositories\ModeloRepository;

class ModeloController extends Controller
{
    public function __construct(Modelo $modelo)
    {
        $this->modelo = $modelo;
    }

    /**
     * @OA\Get(
     *   path="/api/v1/modelo",
     *   summary="Modelo cadastrados",
     *   tags={"Modelo"},
     *   security={{"bearerAuth":{}}},
     * 
     *  @OA\Parameter(
     *     name="atributos_marca",
     *     in="query",
     *     required=false,
     *     description="Quais colunas do relacionamento 'marca' retornar. Use vírgula para separar.",
     *     example="nome,imagem",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *   @OA\Parameter(
     *     name="filtro",
     *     in="query",
     *     required=false,
     *     description="Filtros dinâmicos. Exemplos de operadores comuns: =, >, >=, <, <=, like, in, between. ",
     *     example="nome:like:Fiat;numero_portas:<:4",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *   @OA\Parameter(
     *     name="atributos",
     *     in="query",
     *     required=false,
     *     description="Quais colunas do recurso 'modelo' retornar. Use vírgula para separar.",
     *     example="nome,imagem,numero_portas,lugares,marca_id",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *   @OA\Response(response=200, description="Retorna os dados do modelo"),
     *   @OA\Response(response=401, description="Não autenticado")
     * )
     */
    
    
    public function index(Request $request)
    {
        $modeloRepository = new ModeloRepository($this->modelo);

        if ($request->has('atributos_marca')) {
            $atributos_marca = 'marca:id,'.$request->atributos_marca;
            $modeloRepository->selectAtributosRegistrosRelacionamentos($atributos_marca);
        } else {
            $modeloRepository->selectAtributosRegistrosRelacionamentos('marca');
        }

        if($request->has('filtro')){ 
            $modeloRepository->filtro($request->filtro); // Colocando o nome:like:F% voce consegue ver os dados que começam com F
        }

        if ($request->has('atributos')) {
            $modeloRepository->atributos($request->atributos);
        }


        return response()->json($modeloRepository->getResultado(), 200);
    }


    /**
     * @OA\Post(
     *   path="/api/v1/modelo",
     *   summary="Cadastrar modelo",
     *   tags={"Modelo"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         type="object",
     *         required={"nome","imagem","marca_id","numero_portas","lugares","air_bag","abs"},
     *         @OA\Property(
     *           property="nome",
     *           type="string",
     *           minLength=3,
     *           description="Nome do modelo.",
     *           example="Tesla"
     *         ),
     *         @OA\Property(
     *           property="imagem",
     *           type="string",
     *           format="binary",
     *           description="Arquivo de imagem (formatos permitidos: png, jpg, svg, webp)."
     *         ),
     *         @OA\Property(property="marca_id", type="integer", example="1", description="ID do modelo."),
     *         @OA\Property(property="numero_portas", type="integer", example="4", description="Numero de portas do modelo"),
     *         @OA\Property(property="lugares", type="integer", example="5", description="Quantidade de lugares"),
     *         @OA\Property(property="air_bag", type="text", example="1", description="Air_bag disponivel?"),
     *         @OA\Property(property="abs", type="text", example="1", description="abs disponivel?")
     *       )
     *     )
     *   ),
     *
     *   @OA\Response(
     *     response=201,
     *     description="Marca criada com sucesso"),
     *
     *   @OA\Response(
     *     response=422,
     *     description="Erros de validação")
     * )
     */
       

    public function store(Request $request)
    {
        $request->validate($this->modelo->rules());
        $imagem = $request->file('imagem');
        $imagem_path = $imagem->store('imagens/modelos', 'public');


        $modelo = $this->modelo->create(
            [
                'marca_id' => $request->marca_id,
                'nome' => $request->nome,
                'imagem' => $imagem_path,
                'numero_portas' => $request->numero_portas,
                'lugares' => $request->lugares,
                'air_bag' => $request->air_bag,
                'abs' => $request->abs
            ]
        );

        return response()->json($modelo, 201);
    }


    /**
     * @OA\Get(
     *   path="/api/v1/modelo/{id}",
     *   summary="Detalhes de uma modelo",
     *   tags={"Modelo"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do modelo a ser pesquisado",
     *     @OA\Schema(type="integer"),
     *     example=1
     *   ),
     * 
     *   @OA\Response(response=404, description="Modelo não encontrado"),
     *   @OA\Response(response=422, description="Dados inválidos")
     * )
     */

    public function show($id)
    {
        $modelo = $this->modelo->with('marca')->find($id);
        if ($modelo === null) {
            return response()->json(['erro' => 'Recurso pesquisado não existe'], 404);
        }

        return response()->json($modelo, 200);
    }

    public function update(Request $request, $id)
    {

        $modelo = $this->modelo->find($id);

        if ($modelo === null) {
            return response()->json(['erro' => 'Impossível realizar a atualização. O recurso solicitado não existe'], 404);
        }

        if ($request->method() === 'PATCH') {

            $regrasDinamicas = array();

            //percorrendo todas as regras definidas no Model
            foreach ($modelo->rules() as $input => $regra) {

                //coletar apenas as regras aplicáveis aos parâmetros parciais da requisição PATCH
                if (array_key_exists($input, $request->all())) {
                    $regrasDinamicas[$input] = $regra;
                }
            }

            $request->validate($regrasDinamicas);
        } else {

            $request->validate($modelo->rules());
        }


        Storage::disk('public')->delete($modelo->imagem);

        $imagem = $request->file('imagem');
        $imagem_path = $imagem->store('imagens/modelos', 'public');

        $modelo->fill($request->all());
        $modelo->imagem = $imagem_path;
        $modelo->save();

        return response()->json($modelo, 200);
    }


    /**
     * @OA\Delete(
     *   path="/api/v1/modelo/{id}",
     *   summary="Remover um modelo",
     *   tags={"Modelo"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID do modelo a ser deletado",
     *     @OA\Schema(type="integer"),
     *     example=2
     *   ),
     * 
     *   @OA\Response(response=404, description="Modelo não encontrado"),
     *   @OA\Response(response=422, description="Dados inválidos")
     * )
     */


    public function destroy($id)
    {
        $modelo = $this->modelo->find($id);

        if ($modelo === null) {
            return response()->json(['erro' => 'Impossível realizar a exclusão. O recurso solicitado não existe'], 404);
        }

        Storage::disk('public')->delete($modelo->imagem);

        $modelo->delete();
        return response()->json(['msg' => 'O modelo foi removido com sucesso!'], 200);
    }
}
