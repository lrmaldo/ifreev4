#!/usr/bin/env php
<?php

/**
 * Script para probar las correcciones de Telegram
 * Verifica que los comandos con mención se procesen correctamente
 */

// Archivo de test para verificar que el método cleanCommandText funciona

class TelegramControllerTest {
    /**
     * Limpia el texto del comando eliminando la mención del bot
     * Convierte "/comando@nombre_bot" en "/comando"
     */
    protected function cleanCommandText(string $text): string
    {
        // Dividir la primera palabra (comando) del resto
        $parts = explode(' ', $text, 2);
        $command = $parts[0];
        $rest = isset($parts[1]) ? ' '.$parts[1] : '';

        // Remover la mención del bot si existe (@nombre_bot)
        if (strpos($command, '@') !== false) {
            $command = explode('@', $command)[0];
        }

        return $command.$rest;
    }

    public function runTests()
    {
        echo "================== PRUEBAS DE LIMPIEZA DE COMANDOS ==================\n\n";

        $testCases = [
            [
                'input' => '/zonas@iFreeBotv3_bot',
                'expected' => '/zonas',
                'description' => 'Comando con mención en grupo',
            ],
            [
                'input' => '/start@iFreeBotv3_bot',
                'expected' => '/start',
                'description' => 'Comando /start con mención',
            ],
            [
                'input' => '/registrar@iFreeBotv3_bot 1',
                'expected' => '/registrar 1',
                'description' => 'Comando con parámetros y mención',
            ],
            [
                'input' => '/zonas',
                'expected' => '/zonas',
                'description' => 'Comando sin mención (DM)',
            ],
            [
                'input' => '/ayuda',
                'expected' => '/ayuda',
                'description' => 'Comando simple sin mención',
            ],
            [
                'input' => '/registrar 5',
                'expected' => '/registrar 5',
                'description' => 'Comando con parámetro sin mención',
            ],
        ];

        $passed = 0;
        $failed = 0;

        foreach ($testCases as $test) {
            $result = $this->cleanCommandText($test['input']);
            $success = $result === $test['expected'];

            $status = $success ? '✅ PASS' : '❌ FAIL';
            echo "{$status} - {$test['description']}\n";
            echo "   Input:    {$test['input']}\n";
            echo "   Expected: {$test['expected']}\n";
            echo "   Got:      {$result}\n";
            echo "\n";

            if ($success) {
                $passed++;
            } else {
                $failed++;
            }
        }

        echo "================== RESULTADOS ==================\n";
        echo "Pruebas Pasadas: {$passed}\n";
        echo "Pruebas Fallidas: {$failed}\n";
        echo "Total: ".count($testCases)."\n";

        return $failed === 0;
    }
}

$tester = new TelegramControllerTest();
$success = $tester->runTests();

exit($success ? 0 : 1);
