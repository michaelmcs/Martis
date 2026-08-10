<?php

namespace App\Support;

use Anthropic\Client;

/**
 * Asistente de revisión con inteligencia artificial.
 *
 *  - calificarAlternativas(): revisión por reglas simples, sin conexión y sin IA.
 *    Compara la clave de respuestas con las del estudiante y sugiere una nota.
 *  - generarPreguntas(): genera preguntas con un modelo de lenguaje.
 *    Todas las llamadas a la API se hacen desde el servidor.
 */
class AsistenteIA
{
    /** ¿Está configurada la clave para las funciones con IA? */
    public function iaDisponible(): bool
    {
        return filled(config('services.ia.key'));
    }

    /**
     * Califica un examen de alternativas comparando la clave con las respuestas
     * del estudiante. Devuelve el detalle por pregunta y la nota sugerida (0-20).
     */
    public function calificarAlternativas(string $clave, string $respuestas): array
    {
        $clav = $this->parsearRespuestas($clave);
        $resp = $this->parsearRespuestas($respuestas);

        $total = count($clav);
        $detalle = [];
        $correctas = 0;

        foreach ($clav as $i => $correcta) {
            $marcada = $resp[$i] ?? null;
            $acierto = $marcada !== null && $marcada === $correcta;
            if ($acierto) {
                $correctas++;
            }
            $detalle[] = [
                'numero' => $i + 1,
                'correcta' => strtoupper($correcta),
                'marcada' => $marcada ? strtoupper($marcada) : '—',
                'acierto' => $acierto,
            ];
        }

        $notaSugerida = $total > 0 ? round(($correctas / $total) * 20, 2) : 0;

        return [
            'total' => $total,
            'correctas' => $correctas,
            'incorrectas' => $total - $correctas,
            'nota_sugerida' => $notaSugerida,
            'detalle' => $detalle,
        ];
    }

    /**
     * Extrae de un texto la secuencia ordenada de alternativas (a-e).
     * Acepta "1) A  2) C", "a,c,b", "acb", "1: a 2: b", etc.
     */
    private function parsearRespuestas(string $texto): array
    {
        $respuestas = [];
        // separar en tokens por espacios, comas, punto y coma o saltos de línea
        $tokens = preg_split('/[\s,;]+/', trim($texto)) ?: [];

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }
            // el último carácter alfabético a-e del token es la alternativa
            if (preg_match_all('/[a-eA-E]/', $token, $m)) {
                $respuestas[] = strtolower(end($m[0]));
            }
        }

        // caso "acbde" pegado sin separadores
        if (count($respuestas) <= 1 && preg_match('/^[a-eA-E]{2,}$/', trim($texto))) {
            $respuestas = str_split(strtolower(trim($texto)));
        }

        return $respuestas;
    }

    /**
     * Genera preguntas de examen con un modelo de lenguaje a partir de un tema o sílabo.
     * Devuelve el texto generado (Markdown). Lanza una excepción si falla.
     */
    public function generarPreguntas(string $tema, int $cantidad, string $tipo): string
    {
        $client = new Client(apiKey: config('services.ia.key'));

        $instrucciones = match ($tipo) {
            'alternativas' => 'preguntas de opción múltiple con 4 alternativas (a, b, c, d) e indica la respuesta correcta al final de cada una.',
            'desarrollo' => 'preguntas de desarrollo (respuesta abierta) con una breve pauta de lo que debería incluir una buena respuesta.',
            'vf' => 'afirmaciones de verdadero o falso, indicando la respuesta correcta.',
            default => 'preguntas variadas.',
        };

        $prompt = "Eres un asistente para docentes universitarios. Genera {$cantidad} {$instrucciones}\n\n"
            ."Tema o contenido del sílabo:\n{$tema}\n\n"
            .'Responde en español, numerando las preguntas. No agregues introducción ni despedida.';

        $message = $client->messages->create(
            maxTokens: 2048,
            messages: [
                ['role' => 'user', 'content' => $prompt],
            ],
            model: config('services.ia.model'),
            thinking: ['type' => 'adaptive'],
        );

        $texto = '';
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $texto .= $block->text;
            }
        }

        return trim($texto) ?: 'No se recibió contenido del modelo.';
    }
}
