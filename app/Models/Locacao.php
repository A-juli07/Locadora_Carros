<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Locacao extends Model
{
    use HasFactory;
    protected $table = 'locacoes';
    protected $fillable = ['cliente_id', 'carro_id', 'data_inicio_periodo', 'data_final_previsto_periodo', 'data_final_realizado_periodo', 'valor_diaria', 'km_inicial', 'km_final'];

    public function rules()
    {
        return [
            'cliente_id' => 'exists:clientes,id',
            'carro_id' => 'exists:carros,id',
            'data_inicio_periodo' => 'required|date_format:d-m-y',
            'data_final_previsto_periodo' => 'required|date_format:d-m-y|after:data_inicio_periodo',
            'data_final_realizado_periodo' => 'date_format:d-m-y',
            'valor_diaria' => 'required|integer',
            'km_inicial' => 'nullable|integer', // será preenchido automaticamente
            'km_final' => 'nullable|integer',
        ];
    }

    public function carro()
    {
        return $this->belongsTo('App\Models\Carro');
    }

    public function cliente()
    {
        return $this->belongsTo('App\Models\Cliente');
    }

}
