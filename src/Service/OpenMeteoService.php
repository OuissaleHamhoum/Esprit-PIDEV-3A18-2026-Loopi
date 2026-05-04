<?php

namespace App\Service;

class OpenMeteoService
{
    /**
     * API Externe (100% Gratuite, sans clé API)
     * Récupère la météo en temps réel de Tunis pour dire aux participants si c'est un bon jour pour aller déposer leurs dons.
     */
    public function getCurrentWeatherLive(): string
    {
        try {
            // Utilisation native PHP pour éviter les erreurs si http-client n'est pas installé
            $url = 'https://api.open-meteo.com/v1/forecast?latitude=36.8189&longitude=10.1657&current_weather=true';
            
            // Création d'un contexte avec timeout court pour ne pas bloquer l'appli
            $ctx = stream_context_create(['http' => ['timeout' => 3]]);
            $response = @file_get_contents($url, false, $ctx);
            
            if (!$response) return "Météo indisponible 🌤️";

            $data = json_decode($response, true);
            $temp = $data['current_weather']['temperature'] ?? 'Inconnue';
            $weatherCode = $data['current_weather']['weathercode'] ?? 0;

            // Traduction simple du code météo
            $condition = "Dégagé ☀️";
            if ($weatherCode >= 1 && $weatherCode <= 3) $condition = "Nuageux ⛅";
            if ($weatherCode >= 51 && $weatherCode <= 67) $condition = "Pluvieux 🌧️";

            return "Météo actuelle pour la collecte : $temp °C, $condition";
        } catch (\Exception $e) {
            return "Météo indisponible 🌤️";
        }
    }
}
