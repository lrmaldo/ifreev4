<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campana;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CampanaController extends Controller
{
    /**
     * Obtiene las campañas activas para mostrar en el carrusel
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getCampanasActivas(Request $request)
    {
        // Obtener cliente_id si está presente en la petición
        $clienteId = $request->input('cliente_id');

        // Base query con campañas activas
        $query = Campana::activas();

        // Filtrar por cliente_id si se especifica
        if ($clienteId) {
            $query->where(function($q) use ($clienteId) {
                $q->where('cliente_id', $clienteId)
                  ->orWhereNull('cliente_id'); // Incluir también las globales
            });
        }

        // Obtener las campañas
        $campanas = $query->latest()->get();

        // Preparar la respuesta con URLs completas
        $campanas->transform(function ($campana) {
            $campana->archivo_url = Storage::url($campana->archivo_path);
            return $campana;
        });

        return response()->json([
            'success' => true,
            'data' => $campanas
        ]);
    }
}
