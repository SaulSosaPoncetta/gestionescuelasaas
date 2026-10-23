<?php

namespace App\Services;

use App\Models\Usuario;

class PersonaService
{
    public function buscarPorDni(?string $dni): ?Usuario
    {
        if (!$dni) {
            return null;
        }

        return Usuario::whereHas('dnis', fn ($q) => $q->where('dni', $dni))->first();
    }
}