<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    /**
     * Generate text with the Gemini API using the given (user-supplied) key.
     *
     * @throws RuntimeException on an auth/quota/network error, with a message
     *                          suitable for showing to the user.
     */
    public function generate(string $apiKey, string $prompt): string
    {
        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        try {
            $response = Http::timeout(45)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 1200,
                    ],
                ]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Nepodarilo sa spojiť s Gemini. Skús to znova neskôr.');
        }

        if ($response->status() === 400 || $response->status() === 403) {
            throw new RuntimeException('Gemini API kľúč je neplatný alebo nemá prístup. Skontroluj ho v nastaveniach.');
        }
        if ($response->status() === 429) {
            throw new RuntimeException('Prekročený limit Gemini API. Skús to o chvíľu.');
        }
        if (! $response->successful()) {
            throw new RuntimeException('Gemini vrátil chybu ('.$response->status().').');
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini nevrátil žiadny súhrn.');
        }

        return trim($text);
    }
}
