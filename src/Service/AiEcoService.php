<?php

namespace App\Service;

class AiEcoService
{
    /**
     * Calls Mistral AI (The European AI) - A sophisticated alternative to Gemini.
     */
    public function generateImpactMessage(float $amount, string $material): string
    {
        // Checks both GEMINI_API_KEY and MISTRAL_API_KEY for maximum flexibility
        $apiKey = $_ENV['MISTRAL_API_KEY'] ?? ($_ENV['GEMINI_API_KEY'] ?? ($_SERVER['MISTRAL_API_KEY'] ?? ($_SERVER['GEMINI_API_KEY'] ?? getenv('MISTRAL_API_KEY'))));
        
        $fallback = "⚠️ [DEBUG] L'IA Mistral n'a pas pu générer de message (Clé manquante ou erreur réseau).";
        
        if (empty($apiKey)) return $fallback;

        // Using Mistral-Small or Mistral-Tiny (Very fast and Free)
        $url = 'https://api.mistral.ai/v1/chat/completions';
        
        $prompt = "A user donated $amount kg of $material.

Write ONE short sentence describing the impact of this donation.

Rules:
- Max 12 words
- No numbers, no statistics, no comparisons
- Do not invent facts or quantities
- Focus on general environmental benefit
- Clear, simple, and realistic tone

Style:
- Professional and suitable for an academic project
- Positive but not exaggerated

Output only the sentence in French.";
        
        $payload = json_encode([
            'model' => 'mistral-small-latest',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 50,
            'temperature' => 0.8 // Increases randomness/creativity
        ]);

        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\nAuthorization: Bearer $apiKey\r\n",
                'method'  => 'POST',
                'content' => $payload,
                'ignore_errors' => true,
                'timeout' => 8
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
        ];
        
        try {
            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);
            if (!$response) return $fallback;

            $data = json_decode($response, true);
            $text = $data['choices'][0]['message']['content'] ?? null;
            
            return $text ? trim($text) : $fallback;
        } catch (\Exception $e) {
            return $fallback;
        }
    }
}
