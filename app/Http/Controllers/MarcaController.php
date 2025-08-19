<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use App\Repositories\MarcaRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MarcaController extends Controller
{
    //Construtor para instanciar a marca 

    public function __construct(Marca $marca)
    {
        $this->marca = $marca;
    }


    /**
     * @OA\Get(
     *   path="/api/v1/marca",
     *   summary="Marca cadastradas",
     *   tags={"Marca"},
     *   security={{"bearerAuth":{}}},
     * 
     *  @OA\Parameter(
     *     name="atributos_modelos",
     *     in="query",
     *     required=false,
     *     description="Quais colunas do relacionamento 'modelo' retornar. Use vírgula para separar.",
     *     example="nome,imagem,marca_id",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *   @OA\Parameter(
     *     name="filtro",
     *     in="query",
     *     required=false,
     *     description="Filtros dinâmicos. Exemplos de operadores comuns: =, >, >=, <, <=, like, in, between. ",
     *     example="nome:like:Fiat",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *   @OA\Parameter(
     *     name="atributos",
     *     in="query",
     *     required=false,
     *     description="Quais colunas do recurso 'locacao' retornar. Use vírgula para separar.",
     *     example="nome,imagem,id",
     *     @OA\Schema(type="string")
     *   ),
     * 
     *   @OA\Response(response=200, description="Retorna os dados da marca"),
     *   @OA\Response(response=401, description="Não autenticado")
     * )
     */


    public function index(Request $request)
    {

        $marcaRepository = new MarcaRepository($this->marca);

        if ($request->has('atributos_modelos')) {
            $atributos_modelos = 'modelos:id,'.$request->atributos_modelos;
            $marcaRepository->selectAtributosRegistrosRelacionamentos($atributos_modelos);
        } else {
            $marcaRepository->selectAtributosRegistrosRelacionamentos('modelos');
        }

        if($request->has('filtro')){ 
            $marcaRepository->filtro($request->filtro); 
        }

        if ($request->has('atributos')) {
            $marcaRepository->atributos($request->atributos);
        }


        return response()->json($marcaRepository->getResultado(), 200);
    }

    

     /**
     * @OA\Post(
     *   path="/api/v1/marca",
     *   summary="Cadastrar marca",
     *   tags={"Marca"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         type="object",
     *         required={"nome","imagem"},
     *         @OA\Property(
     *           property="nome",
     *           type="string",
     *           minLength=3,
     *           description="Nome da marca (único).",
     *           example="Tesla"
     *         ),
     *         @OA\Property(
     *           property="imagem",
     *           type="string",
     *           format="binary",
     *           description="Arquivo de imagem (formatos permitidos: png, jpg, svg, webp)."
     *         )
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
        //$marca = Marca::create($request->all());
        //nome
        //imagem
        $request->validate($this->marca->rules(), $this->marca->feedback());
        $imagem = $request->file('imagem');
        $imagem_path = $imagem->store('imagens', 'public');


        $marca = $this->marca->create(
            [
                'nome' => $request->nome,
                'imagem' => $imagem_path
            ]
        );

        /* Outra forma de fazer 
        $marca->nome = $request->nome;
        $marca->imagem = $imagem_path;
        $marca->save();
        */

        return response()->json($marca, 201);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/marca/{id}",
     *   summary="Detalhes de uma marca",
     *   tags={"Marca"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID da marca a ser pesquisado",
     *     @OA\Schema(type="integer"),
     *     example=2
     *   ),
     * 
     *   @OA\Response(response=404, description="Marca não encontrado"),
     *   @OA\Response(response=422, description="Dados inválidos")
     * )
     */

    public function show($id)
    {
        $marca = $this->marca->with('modelos')->find($id);
        if ($marca === null) {
            return response()->json(['erro' => 'Recurso pesquisado não existe'], 404);
        }

        return response()->json($marca, 200);
    }

    //Implementar Swagger na função update

    public function update(Request $request, $id)
    {
        $marca = $this->marca->find($id);

        if ($marca === null) {
            return response()->json(['erro' => 'Impossível realizar a atualização. O recurso solicitado não existe'], 404);
        }

        if ($request->method() === 'PATCH') {

            $regrasDinamicas = array();

            //percorrendo todas as regras definidas no Model
            foreach ($marca->rules() as $input => $regra) {

                //coletar apenas as regras aplicáveis aos parâmetros parciais da requisição PATCH
                if (array_key_exists($input, $request->all())) {
                    $regrasDinamicas[$input] = $regra;
                }
            }

            $request->validate($regrasDinamicas, $marca->feedback());
        } else {
            $request->validate($marca->rules(), $marca->feedback());
        }

        $marca->fill($request->all());


        if ($request->file('imagem')) {
            Storage::disk('public')->delete($marca->imagem);

            $imagem = $request->file('imagem');
            $imagem_path= $imagem->store('imagem', 'public');

            $marca->imagem = $imagem_path;
        }

        $marca->save();
        return response()->json($marca, 200);
        
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/marca/{id}",
     *   summary="Remover uma marca",
     *   tags={"Marca"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID da marca a ser deletada ",
     *     @OA\Schema(type="integer"),
     *     example=2
     *   ),
     * 
     *   @OA\Response(response=404, description="Marca não encontrado"),
     *   @OA\Response(response=422, description="Dados inválidos")
     * )
     */

    public function destroy($id)
    {
        $marca = $this->marca->find($id);

        if ($marca === null) {
            return response()->json(['erro' => 'Impossível realizar a exclusão. O recurso solicitado não existe'], 404);
        }

        Storage::disk('public')->delete($marca->imagem);

        $marca->delete();
        return response()->json(['msg' => 'A marca foi removida com sucesso!'], 200);
    }
}
