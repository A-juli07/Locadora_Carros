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

    public function destroy($id)
    {
        $modelo = $this->modelo->find($id);

        if ($modelo === null) {
            return response()->json(['erro' => 'Impossível realizar a exclusão. O recurso solicitado não existe'], 404);
        }

        Storage::disk('public')->delete($modelo->imagem);

        $modelo->delete();
        return response()->json(['msg' => 'O modelo foi removida com sucesso!'], 200);
    }
}
