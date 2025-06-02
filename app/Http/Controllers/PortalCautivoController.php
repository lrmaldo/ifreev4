<?php

namespace App\Http\Controllers;

use App\Models\Campana;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortalCautivoController extends Controller
{
    /**
     * Muestra la campaña seleccionada para el carrusel del portal cautivo
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

        // Obtener campañas activas filtradas por tipo
        $tipo = $request->input('tipo', 'imagen');
        $campanasActivas = $zona->getCampanasActivas()->where('tipo', $tipo);

        if ($campanasActivas->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay campañas activas para esta zona',
                'data' => []
            ]);
        }

        // Seleccionar campaña según método configurado en la zona
        $modoSeleccion = $zona->seleccion_campanas ?? 'prioridad';
        if ($modoSeleccion === 'aleatorio') {
            $campanaSeleccionada = $campanasActivas->random();
        } else {
            $campanaSeleccionada = $campanasActivas->sortBy('prioridad')->first();
        }

        // Preparar la respuesta incluyendo las URL completas
        $campanaSeleccionada->archivo_url = Storage::url($campanaSeleccionada->archivo_path);
        $tiempoVisualizacion = $zona->tiempo_visualizacion ?? 15;

        return response()->json([
            'success' => true,
            'data' => [
                'campana' => $campanaSeleccionada,
                'tiempo_visualizacion' => $tiempoVisualizacion
            ]
        ]);
    }

    /**
     * Muestra todas las campañas activas, sin aplicar selección
     *
     * @param Request $request
     * @param string $zonaId ID o ID personalizado de la zona
     * @return \Illuminate\Http\Response
     */
    public function obtenerTodasCampanas(Request $request, $zonaId)
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

    /**
     * Registra la reproducción completa de un video y autoriza el acceso
     *
     * @param Request $request
     * @param string $zonaId ID o ID personalizado de la zona
     * @return \Illuminate\Http\Response
     */
    public function videoCompletado(Request $request, $zonaId)
    {
        // Validar datos recibidos
        $request->validate([
            'campana_id' => 'required|exists:campanas,id',
            'mac' => 'required|string',
            'ip' => 'required|string'
        ]);

        // Buscar la zona
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

        // Aquí iría la lógica de autenticación en el router Mikrotik
        // Por ahora simplemente devolvemos éxito

        // Se podría registrar en una tabla de visualizaciones para estadísticas

        return response()->json([
            'success' => true,
            'message' => 'Acceso concedido',
            'redirect_url' => $request->input('link-orig', 'http://example.com')
        ]);
    }
}
