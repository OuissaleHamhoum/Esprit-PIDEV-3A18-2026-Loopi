<?php

namespace App\Service;

class BadWordFilterService
{
    private array $badWords = [
        'merde', 'putain', 'connard', 'salope', 'enculé', 'fdp', 'con', 'connasse', 'pute', 'bordel',
        'fuck', 'shit', 'asshole', 'bitch', 'bastard', 'dick', 'pussy', 'crap', 'damn'
    ];

    /**
     * Detects if the text contains any bad words.
     * Advanced: uses regex to catch variations and leet speak.
     */
    public function isInappropriate(string $text): bool
    {
        if (empty($text)) return false;
        
        $text = mb_strtolower($text, 'UTF-8');
        
        // Basic normalization: replace common leet speak characters
        $leetMap = [
            '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '8' => 'b', '@' => 'a', '$' => 's', '!' => 'i'
        ];
        $normalizedText = strtr($text, $leetMap);

        foreach ($this->badWords as $word) {
            $letters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
            $pattern = '/\b' . implode('[^a-z0-9]*', array_map(fn($l) => preg_quote($l, '/') . '+', $letters)) . '\b/iu';
            
            if (preg_match($pattern, $text) || preg_match($pattern, $normalizedText)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Masks bad words in the text with asterisks.
     */
    public function cleanText(string $text): string
    {
        if (empty($text)) return $text;
        
        $cleaned = $text;
        foreach ($this->badWords as $word) {
            $letters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
            $pattern = '/\b' . implode('[^a-z0-9]*', array_map(fn($l) => preg_quote($l, '/') . '+', $letters)) . '\b/iu';
            
            $cleaned = preg_replace_callback($pattern, function($matches) {
                return str_repeat('*', mb_strlen($matches[0], 'UTF-8'));
            }, $cleaned);
        }
        return $cleaned;
    }

    /**
     * Highlights bad words in the text with orange color.
     */
    public function highlightBadWords(string $text): string
    {
        if (empty($text)) return $text;
        
        $highlighted = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        foreach ($this->badWords as $word) {
            $letters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
            $pattern = '/\b' . implode('[^a-z0-9]*', array_map(fn($l) => preg_quote($l, '/') . '+', $letters)) . '\b/iu';
            
            $highlighted = preg_replace_callback($pattern, function($matches) {
                return '<span style="color:#FF8C00; font-weight:bold; text-decoration:underline">' . $matches[0] . '</span>';
            }, $highlighted);
        }
        return $highlighted;
    }
}
