<?php

namespace App\Http\Controllers;

use App\Models\FormField;
use App\Models\SolicitudDemo;
use App\Models\Zona;
use App\Services\AlertasEventoService;
use App\Services\ReporteEventoService;
use App\Services\TelegramMensajero;
use App\Services\ValidacionPortal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Página pública con los resultados de un evento y el formulario para pedir una demo.
 * Los eventos se dan de alta en config('ifree.casos').
 */
class CasoEventoController extends Controller
{
    public function show(string $slug)
    {
        $caso = $this->caso($slug);

        $cifras = Cache::remember("caso:{$slug}", 600, fn () => app(ReporteEventoService::class)->generar(
            Zona::findOrFail($caso['zona_id']),
            Carbon::parse($caso['desde']),
            Carbon::parse($caso['hasta']),
        ));

        return view('casos.show', [
            'slug' => $slug,
            'caso' => $caso,
            'c' => $cifras,
            'intereses' => SolicitudDemo::INTERESES,
            'whatsapp' => config('ifree.whatsapp'),
        ]);
    }

    public function solicitarDemo(Request $request, string $slug)
    {
        $this->caso($slug);

        // Campo trampa: los bots lo llenan, las personas no lo ven
        if (filled($request->input('sitio_web'))) {
            return redirect()->route('casos.show', $slug)->with('demo_enviada', true);
        }

        $errores = [];
        try {
            $request->validate([
                'interes' => ['required', Rule::in(array_keys(SolicitudDemo::INTERESES))],
                'empresa' => ['required', 'string', 'max:150'],
                'mensaje' => ['nullable', 'string', 'max:1000'],
            ], ['interes.required' => 'Dinos qué te interesa.', 'empresa.required' => 'Falta tu empresa.']);
        } catch (ValidationException $e) {
            $errores = $e->errors();
        }

        // Mismas reglas que el portal: nombre y apellido, teléfono a 10 dígitos, correo real
        $campos = collect([
            new FormField(['campo' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'obligatorio' => true]),
            new FormField(['campo' => 'telefono', 'etiqueta' => 'Teléfono', 'tipo' => 'tel', 'obligatorio' => true]),
            new FormField(['campo' => 'correo', 'etiqueta' => 'Correo', 'tipo' => 'email', 'obligatorio' => true]),
        ])->each(fn ($c) => $c->setRelation('opciones', collect()));

        try {
            $datos = app(ValidacionPortal::class)->validar($campos, $request->only(['nombre', 'telefono', 'correo']));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $clave => $mensajes) {
                $errores[str_replace('respuestas.', '', $clave)] = $mensajes;
            }
        }

        if ($errores) {
            throw ValidationException::withMessages($errores)->redirectTo(route('casos.show', $slug) . '#demo');
        }

        $solicitud = SolicitudDemo::create([
            'nombre' => $datos['nombre'],
            'telefono' => $datos['telefono'],
            'correo' => $datos['correo'],
            'empresa' => trim($request->empresa),
            'interes' => $request->interes,
            'mensaje' => $request->mensaje,
            'origen' => $slug,
        ]);

        \Illuminate\Support\defer(fn () => $this->avisar($solicitud));

        return redirect()->to(route('casos.show', $slug) . '#demo')->with('demo_enviada', true);
    }

    private function caso(string $slug): array
    {
        $caso = config("ifree.casos.{$slug}");
        abort_unless(is_array($caso), 404);

        return $caso;
    }

    private function avisar(SolicitudDemo $s): void
    {
        $mensaje = "🚀 <b>Solicitud de demo</b>\n\n"
            . '• <b>Nombre:</b> ' . e($s->nombre) . "\n"
            . '• <b>Empresa:</b> ' . e($s->empresa) . "\n"
            . '• <b>Teléfono:</b> ' . e($s->telefono) . "\n"
            . '• <b>Correo:</b> ' . e($s->correo) . "\n"
            . '• <b>Interés:</b> ' . e(SolicitudDemo::INTERESES[$s->interes] ?? $s->interes) . "\n"
            . ($s->mensaje ? '• <b>Mensaje:</b> ' . e($s->mensaje) . "\n" : '')
            . "\nLlegó desde " . e(route('casos.show', $s->origen));

        foreach (app(AlertasEventoService::class)->destinatarios() as $chatId) {
            app(TelegramMensajero::class)->enviar((string) $chatId, $mensaje);
        }
    }
}
