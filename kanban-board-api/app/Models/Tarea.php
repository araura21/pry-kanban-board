<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarea extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'titulo'];

    protected $attributes = ['estado' => 'creado', 'version' => 1];

    protected function casts(): array
    {
        return ['version' => 'integer', 'comprometida_en' => 'datetime'];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function movidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'movida_por');
    }

    public function datosTablero(): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'estado' => $this->estado,
            'version' => $this->version,
            'creador' => $this->creador->name,
            'movidaPor' => $this->movidaPor?->name,
            'creadaEn' => $this->created_at->toISOString(),
            'comprometidaEn' => $this->comprometida_en?->toISOString(),
            'actualizadaEn' => $this->updated_at->toISOString(),
        ];
    }
}
