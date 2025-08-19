<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = ['nome'];

    public function rules()
    {
        return [
            'nome' => 'required|unique:clientes,nome,'.$this->id.'|min:3',
        ];
    }

    public function carro()
    {
        return $this->belongsTo('App\Models\Carro');
    }

    public function locacao(){
        return $this->belongsTo('App\Models\Locacao');
    }
}
