<?php

namespace App\Controller;

use App\Entity\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CertificateController extends AbstractController
{
    /**
     * Uses EndroidQrCodeBundle (a real Symfony Bundle) to generate a QR Code
     * and display it on a certificate page.
     */
    #[Route('/participant/certificate/pdf', name: 'api_participant_certificate_pdf', methods: ['GET'])]
    public function generateCertificatePdf(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PARTICIPANT');

        /** @var User $user */
        $user = $this->getUser();
        $xp = method_exists($user, 'getXp') ? $user->getXp() : 0;

        $title = 'Eco-Novice 🌱';
        if ($xp >= 5000) $title = 'Gardien de la Terre 🌎';
        elseif ($xp >= 2000) $title = 'Héros de la Planète 🌳';
        elseif ($xp >= 500) $title = 'Recycleur Actif 🌿';

        // ── SYMFONY BUNDLE: endroid/qr-code-bundle ──────────────────────────
        // Using SvgWriter — no PHP GD extension required
        $builder = new Builder(
            writer: new SvgWriter(),
            data: 'Certificat Loopi — ' . $user->getNom() . ' ' . $user->getPrenom() . ' | Rang: ' . $title . ' | XP: ' . $xp,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 220,
            margin: 12,
            validateResult: false
        );

        $result   = $builder->build();
        $qrSvg    = $result->getString(); // Returns raw SVG markup
        // ────────────────────────────────────────────────────────────────────

        $fullName = htmlspecialchars($user->getNom() . ' ' . $user->getPrenom());

        $html = "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Certificat Loopi — {$fullName}</title>
    <link href='https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500&display=swap' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; background: #F4F1EB; }
        .cert-outer { border: 2px solid #C9A96E; border-radius: 24px; padding: 6px; background: linear-gradient(135deg, #C9A96E22, #1E3A2F11); max-width: 680px; width: 100%; }
        .cert { background: #fff; border-radius: 20px; padding: 60px 50px; text-align: center; box-shadow: 0 20px 60px rgba(30,58,47,0.12); }
        .badge { display: inline-block; background: rgba(201,169,110,0.12); color: #8B6914; padding: 6px 18px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 24px; }
        h1 { font-family: 'Playfair Display', serif; font-size: 2.2rem; font-weight: 900; color: #1E3A2F; margin-bottom: 6px; }
        .subtitle { color: #6B7468; margin-bottom: 32px; font-size: 1rem; }
        .name { font-family: 'Playfair Display', serif; font-size: 2.8rem; font-weight: 700; color: #C9A96E; margin-bottom: 12px; line-height: 1.1; }
        .desc { color: #6B7468; max-width: 420px; margin: 0 auto 32px; line-height: 1.6; }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 36px; }
        .stat-box { background: #F4F1EB; border-radius: 14px; padding: 18px; }
        .stat-label { font-size: 0.72rem; color: #6B7468; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .stat-value { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 700; color: #1E3A2F; }
        .qr-section { border-top: 1px solid rgba(30,58,47,0.08); padding-top: 28px; }
        .qr-section svg { width: 100%; height: auto; display: block; border-radius: 8px; }
        .qr-label { font-size: 0.75rem; color: #9B9E98; margin-top: 10px; }
        .footer { margin-top: 28px; font-size: 0.72rem; color: #9B9E98; }
        @media print { body { background: white; } .cert-outer { border: none; box-shadow: none; } }
    </style>
</head>
<body>
    <div class='cert-outer'>
        <div class='cert'>
            <div class='badge'>Certificat Officiel</div>
            <h1>Certificat d'Écologie</h1>
            <p class='subtitle'>Fièrement décerné à</p>
            <div class='name'>{$fullName}</div>
            <p class='desc'>Pour sa contribution exceptionnelle à la plateforme de recyclage Loopi et son engagement pour un avenir plus vert.</p>
            
            <div class='stats-grid'>
                <div class='stat-box'>
                    <div class='stat-label'>Rang Social</div>
                    <div class='stat-value'>{$title}</div>
                </div>
                <div class='stat-box'>
                    <div class='stat-label'>Expérience Totale</div>
                    <div class='stat-value'>{$xp} XP</div>
                </div>
            </div>

            <div class='qr-section'>
                <div style='display:inline-block; max-width:180px; background:#fff; padding:10px; border:1px solid #EAEAEA; border-radius:12px; box-shadow:0 8px 24px rgba(30,58,47,0.06);'>
                    {$qrSvg}
                </div>
                <div class='qr-label'>🔐 Scannez pour vérifier l'authenticité sur Loopi</div>
                <div style='margin-top:4px; font-size:0.65rem; color:#C9A96E; font-weight:bold;'>Généré via Symfony EndroidQrCodeBundle</div>
            </div>
            
            <div class='footer'>Certifié par Loopi Platform — " . date('d/Y') . "</div>
        </div>
    </div>
</body>
</html>";

        return new Response($html);
    }
}
