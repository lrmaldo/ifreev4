<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormResponse;
use App\Models\Zona;
use Illuminate\Support\Facades\Auth;

class FormResponseController extends Controller
{
    /**
     * Guardar respuesta del formulario
     */
    public function store(Request $request)
    {
        $request->validate([
            'zona_id' => 'required|exists:zonas,id',
            'mac_address' => 'required|string',
            'respuestas' => 'required|array',
            'tiempo_activo' => 'integer|min:0',
            'dispositivo' => 'string|nullable',
            'navegador' => 'string|nullable',
            'formulario_completado' => 'boolean'
        ]);

        try {
            $response = FormResponse::create([
                'zona_id' => $request->zona_id,
                'mac_address' => $request->mac_address,
                'dispositivo' => $request->dispositivo ?: $request->header('User-Agent'),
                'navegador' => $request->navegador,
                'tiempo_activo' => $request->tiempo_activo ?? 0,
                'formulario_completado' => $request->formulario_completado ?? false,
                'respuestas' => $request->respuestas
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Respuesta guardada correctamente',
                'data' => $response
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la respuesta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar respuestas de una zona
     */
    public function index($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);

        // Verificar permisos
        if (Auth::user()->hasRole('admin')) {
            // Admin puede ver todas las zonas
        } else {
            // Cliente/técnico solo puede ver sus propias zonas
            if ($zona->user_id !== Auth::id()) {
                abort(403, 'No tienes permisos para ver estas respuestas');
            }
        }

        return view('form-responses.index', compact('zona'));
    }
}
