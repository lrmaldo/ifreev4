<?php

namespace App\Http\Controllers;

use App\Models\Campana;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortalCautivoController extends Controller
{
    /**
     * Muestra las campañas activas para el carrusel del portal cautivo
     *
     * @param Request $request
     * @param string $zonaId ID o ID personalizado de la zona
     * @return \Illuminate\Http\Response
     */
    public function obtenerCampanas(Request $request, $zonaId)
    {
        // Buscar la zona primero por id_personalizado, luego por id
        $zona = Zona::where('id_personalizado', $zonaId)->first();

        if (!$zona) {
            $zona = Zona::find($zonaId);
        }

        if (!$zona) {
            return response()->json([
                'success' => false,
                'message' => 'Zona no encontrada'
            ], 404);
        }

        // Obtener campañas activas para el cliente de la zona o las globales
        $campanas = Campana::activas()
            ->where(function($query) use ($zona) {
                $query->where('cliente_id', $zona->cliente_id)
                      ->orWhereNull('cliente_id'); // Incluir también campañas globales
            })
            ->latest()
            ->get();

        // Preparar la respuesta incluyendo las URLs completas
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
