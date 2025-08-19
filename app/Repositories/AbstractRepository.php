<?php 

namespace App\Repositories;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractRepository{

    public function __construct(Model $model){
            $this->model = $model;
        }

        public function selectAtributosRegistrosRelacionamentos($atributos){
            $this->model = $this->model->with($atributos);

        }

        public function filtro($filtros){   
            $filtros = explode(';', $filtros);

            foreach($filtros as $key => $filtro){

                $f = explode(':', $filtro);
                $this->model = $this->model->where($f[0], $f[1], $f[2]);
                
            }
        }

        public function atributos($atributos){
            $this->model = $this->model->selectRaw($atributos);
        }

        public function getResultado(){
            return $this->model->get();
        }
}

?>