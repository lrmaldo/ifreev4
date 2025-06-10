<?php

namespace App\Traits;

use App\Models\FormField;

trait RenderizaFormFields
{
    /**
     * Renderiza un campo del formulario según su tipo
     *
     * @param FormField $campo
     * @param array $valores Valores actuales del formulario
     * @param string $prefijo Prefijo para los nombres de los inputs
     * @return string HTML del campo
     */
    public function renderizarCampo(FormField $campo, array $valores = [], string $prefijo = 'form')
    {
        $nombre = $prefijo . '[' . $campo->campo . ']';
        $id = $prefijo . '_' . $campo->campo;
        $valor = $valores[$campo->campo] ?? '';
        $required = $campo->obligatorio ? 'required' : '';
        $html = '';

        // Abrimos el div contenedor del campo
        $html .= '<div class="mb-4">';

        // Label para todos los campos excepto checkbox
        if ($campo->tipo !== 'checkbox') {
            $html .= '<label for="' . $id . '" class="block text-sm font-medium text-gray-700">' . $campo->etiqueta;
            if ($campo->obligatorio) {
                $html .= ' <span class="text-red-500">*</span>';
            }
            $html .= '</label>';
        }

        // Según el tipo de campo
        switch ($campo->tipo) {
            case 'text':
            case 'email':
            case 'tel':
            case 'number':
                $html .= '<input type="' . $campo->tipo . '" id="' . $id . '" name="' . $nombre . '" value="' . $valor . '" ' . $required .
                        ' class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">';
                break;

            case 'select':
                $html .= '<select id="' . $id . '" name="' . $nombre . '" ' . $required .
                        ' class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">';
                $html .= '<option value="">Seleccione una opción</option>';

                foreach ($campo->opciones as $opcion) {
                    $selected = ($valor == $opcion->valor) ? 'selected' : '';
                    $html .= '<option value="' . $opcion->valor . '" ' . $selected . '>' . $opcion->etiqueta . '</option>';
                }

                $html .= '</select>';
                break;

            case 'radio':
                $html .= '<div class="radio-group">';

                foreach ($campo->opciones as $opcion) {
                    $radioId = $id . '_' . $opcion->id;
                    $checked = ($valor == $opcion->valor) ? 'checked' : '';

                    $html .= '<div class="radio-option">';
                    $html .= '<input type="radio" id="' . $radioId . '" name="' . $nombre . '" value="' . $opcion->valor . '" ' . $checked . ' ' . $required . '>';
                    $html .= '<label for="' . $radioId . '">' . $opcion->etiqueta . '</label>';
                    $html .= '</div>';
                }

                $html .= '</div>';
                break;

            case 'checkbox':
                if ($campo->opciones->count() > 0) {
                    // Múltiples checkbox con opciones
                    // Añadimos primero la etiqueta principal del grupo si no es un checkbox único
                    $html .= '<div class="mt-1 mb-2 font-medium text-sm">' . $campo->etiqueta;
                    if ($campo->obligatorio) {
                        $html .= ' <span class="text-red-500">*</span>';
                    }
                    $html .= '</div>';

                    $html .= '<div class="checkbox-group">';

                    foreach ($campo->opciones as $opcion) {
                        $checkId = $id . '_' . $opcion->id;
                        $checkName = $prefijo . '[' . $campo->campo . '][' . $opcion->valor . ']';
                        $checked = (isset($valores[$campo->campo][$opcion->valor])) ? 'checked' : '';

                        $html .= '<div class="checkbox-option">';
                        $html .= '<input type="checkbox" id="' . $checkId . '" name="' . $checkName . '" value="1" ' . $checked . '>';
                        $html .= '<label for="' . $checkId . '">' . $opcion->etiqueta . '</label>';
                        $html .= '</div>';
                    }

                    $html .= '</div>';
                } else {
                    // Checkbox único (como "Acepto los términos y condiciones")
                    $checked = ($valor) ? 'checked' : '';
                    $html .= '<div class="checkbox-option">';
                    $html .= '<input type="checkbox" id="' . $id . '" name="' . $nombre . '" value="1" ' . $checked . ' ' . $required . '>';
                    $html .= '<label for="' . $id . '">' . $campo->etiqueta;
                    if ($campo->obligatorio) {
                        $html .= ' <span class="text-red-500">*</span>';
                    }
                    $html .= '</label>';
                    $html .= '</div>';
                }
                break;
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza todos los campos de un formulario
     *
     * @param \Illuminate\Database\Eloquent\Collection $campos
     * @param array $valores Valores actuales del formulario
     * @param string $prefijo Prefijo para los nombres de los inputs
     * @return string HTML de todos los campos
     */
    public function renderizarFormulario($campos, array $valores = [], string $prefijo = 'form')
    {
        $html = '';

        foreach ($campos as $campo) {
            $html .= $this->renderizarCampo($campo, $valores, $prefijo);
        }

        return $html;
    }
}
