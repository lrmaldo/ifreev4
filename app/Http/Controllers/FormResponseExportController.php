<?php

namespace App\Http\Controllers;

use App\Models\FormResponse;
use App\Models\Zona;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class FormResponseExportController extends Controller
{
    public function export(Request $request, Zona $zona)
    {
        // Construir la consulta base
        $query = FormResponse::where('zona_id', $zona->id)
            ->with(['zona', 'zona.campos', 'zona.campos.opciones'])
            ->orderBy('created_at', 'desc');

        // Aplicar filtros si existen
        if ($request->has('mac_address') && !empty($request->mac_address)) {
            $query->where('mac_address', 'like', '%' . $request->mac_address . '%');
        }

        if ($request->has('fecha_inicio') && !empty($request->fecha_inicio)) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }

        if ($request->has('fecha_fin') && !empty($request->fecha_fin)) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }

        // Obtener todas las respuestas
        $respuestas = $query->get();

        // Crear nuevo spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Definir encabezados
        $sheet->setCellValue('A1', 'Fecha');
        $sheet->setCellValue('B1', 'MAC Address');
        $sheet->setCellValue('C1', 'Dispositivo');
        $sheet->setCellValue('D1', 'Tiempo Activo');
        $sheet->setCellValue('E1', 'Estado');
        $sheet->setCellValue('F1', 'Nombre');
        $sheet->setCellValue('G1', 'Teléfono');

        // Obtener campos dinámicos del formulario
        $campos = $zona->campos;
        $columnaActual = 'H';

        foreach ($campos as $campo) {
            $sheet->setCellValue($columnaActual . '1', $campo->etiqueta);
            $columnaActual++;
        }

        // Llenar datos
        $row = 2;
        foreach ($respuestas as $respuesta) {
            $sheet->setCellValue('A' . $row, $respuesta->created_at->format('d/m/Y H:i:s'));
            $sheet->setCellValue('B' . $row, $respuesta->mac_address);

            // Intentar obtener el tipo de dispositivo
            $dispositivo = 'No detectado';
            if (!empty($respuesta->dispositivo)) {
                if (strlen($respuesta->dispositivo) > 30) {
                    // Si es una cadena larga (user agent), extraer información relevante
                    if (strpos(strtolower($respuesta->dispositivo), 'iphone') !== false) {
                        $dispositivo = 'iPhone';
                    } elseif (strpos(strtolower($respuesta->dispositivo), 'android') !== false) {
                        $dispositivo = 'Android';
                    } elseif (strpos(strtolower($respuesta->dispositivo), 'windows') !== false) {
                        $dispositivo = 'Windows';
                    } elseif (strpos(strtolower($respuesta->dispositivo), 'mac') !== false) {
                        $dispositivo = 'Mac';
                    } else {
                        // Tomar solo los primeros 30 caracteres
                        $dispositivo = substr($respuesta->dispositivo, 0, 30) . '...';
                    }
                } else {
                    $dispositivo = $respuesta->dispositivo;
                }
            }
            $sheet->setCellValue('C' . $row, $dispositivo);

            // Formatear tiempo activo
            $tiempoActivo = $this->formatearTiempo($respuesta->active_time);
            $sheet->setCellValue('D' . $row, $tiempoActivo);

            // Estado
            $estado = $respuesta->disconnected_at ? 'Desconectado' : 'Activo';
            $sheet->setCellValue('E' . $row, $estado);

            // Asegurarnos de que respuestas sea un array
            $detalles = [];
            if (is_string($respuesta->respuestas)) {
                // Si es una cadena JSON, decodificamos
                $detalles = json_decode($respuesta->respuestas, true) ?: [];
            } elseif (is_array($respuesta->respuestas)) {
                // Si ya es un array, lo usamos directamente
                $detalles = $respuesta->respuestas;
            }

            // Nombre y teléfono (columnas fijas)
            $nombre = $detalles['nombre'] ?? '-';
            $telefono = $detalles['Telefono'] ?? $detalles['telefono'] ?? '-';

            $sheet->setCellValue('F' . $row, $nombre);
            $sheet->setCellValue('G' . $row, $telefono);

            // Respuestas dinámicas
            // Asegurarnos de que respuestas sea un array
            $detalles = [];
            if (is_string($respuesta->respuestas)) {
                // Si es una cadena JSON, decodificamos
                $detalles = json_decode($respuesta->respuestas, true) ?: [];
            } elseif (is_array($respuesta->respuestas)) {
                // Si ya es un array, lo usamos directamente
                $detalles = $respuesta->respuestas;
            }

            $columnaActual = 'F';
            $columnasDisponibles = ['nombre', 'Telefono', 'telefono', 'email', 'correo'];

            foreach ($campos as $campo) {
                // Intentar con el ID del campo
                $valor = $detalles[$campo->id] ?? null;

                // Si no se encontró por ID, intentar por el nombre del campo
                if ($valor === null) {
                    $campoNombre = strtolower($campo->campo);
                    foreach ($detalles as $clave => $val) {
                        if (strtolower($clave) == $campoNombre) {
                            $valor = $val;
                            break;
                        }
                    }
                }

                // Si aún es null, intentar con nombres comunes
                if ($valor === null) {
                    foreach ($columnasDisponibles as $columnaComun) {
                        if (isset($detalles[$columnaComun]) && strtolower($campo->etiqueta) == strtolower($columnaComun)) {
                            $valor = $detalles[$columnaComun];
                            break;
                        }
                    }
                }

                // Si sigue siendo null, usar un valor predeterminado
                if ($valor === null) {
                    $valor = '-';
                }

                // Manejar valores de arrays (checkboxes)
                if (is_array($valor)) {
                    $valor = implode(', ', $valor);
                }

                $sheet->setCellValue($columnaActual . $row, $valor);
                $columnaActual++;
            }

            $row++;
        }

        // Ajustar anchos de columna automáticamente
        foreach (range('A', $columnaActual) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Formatear cabeceras
        $styleCabecera = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E9ECEF',
                ],
            ],
        ];

        $sheet->getStyle('A1:' . $columnaActual . '1')->applyFromArray($styleCabecera);

        // Generar el archivo
        $fileName = 'Respuestas_Formulario_' . $zona->nombre . '_' . Carbon::now()->format('dmY_His') . '.xlsx';

        // Crear archivo temporal
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        // Devolver el archivo
        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Formatear tiempo activo en formato legible
     */
    private function formatearTiempo($segundos)
    {
        if ($segundos < 60) {
            return $segundos . 's';
        } elseif ($segundos < 3600) {
            return floor($segundos / 60) . 'm ' . ($segundos % 60) . 's';
        } else {
            $horas = floor($segundos / 3600);
            $minutos = floor(($segundos % 3600) / 60);
            $segs = $segundos % 60;
            return $horas . 'h ' . $minutos . 'm ' . $segs . 's';
        }
    }
}
