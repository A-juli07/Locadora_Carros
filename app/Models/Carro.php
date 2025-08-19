<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carro extends Model
{
    use HasFactory;

    protected $fillable = ['placa', 'modelo_id', 'disponivel', 'km'];

    public function rules()
    {
        return [
            'placa' => 'required|unique:carros,placa',
            'modelo_id' => 'exists:modelos,id',
            'disponivel' => 'required|boolean',
            'km' => 'required|integer|min:0'
        ];
    }

    public function modelo()
    {
        return $this->belongsTo('App\Models\Modelo');
    }
}
