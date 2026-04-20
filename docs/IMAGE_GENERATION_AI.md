# Système de Génération d'Image avec IA (Loopi Events)

## 📋 Vue d'ensemble

Le système de génération d'image est un mécanisme intelligent **3-niveaux** qui garantit une image d'événement même sans connexion API externe.

### Architecture

```
User Click "Générer IA"
          ↓
Frontend Extraction Titre/Description
          ↓
Backend API /admin/events/generate-image
          ↓
  ┌─────────────────────────────────────┐
  │ Niveau 1: OpenAI DALL-E            │
  │ (Si OPENAI_API_KEY configurée)     │
  │ ✓ Très haute qualité               │
  │ ✓ Images uniques et personnalisées │
  │ ✗ Coût: ~$0.04 par image          │
  └─────────────────────────────────────┘
          │ Si échoue ↓
  ┌─────────────────────────────────────┐
  │ Niveau 2: GD Library PHP            │
  │ (Génération locale)                 │
  │ ✓ Gratuit                          │
  │ ✓ Rapide                           │
  │ ✓ Gradient + texte                 │
  └─────────────────────────────────────┘
          │ Si échoue ↓
  ┌─────────────────────────────────────┐
  │ Niveau 3: SVG Fallback              │
  │ (Ultime secours)                    │
  │ ✓ Toujours fonctionnel             │
  │ ✓ Ultra-léger (~1KB)               │
  │ ✓ Vectoriel                        │
  └─────────────────────────────────────┘
          ↓
  Base64 Retourné au Frontend
          ↓
  Affichage en Data-URI
          ↓
  Conversion Blob et Upload
          ↓
  Stockage /public/uploads/events/
```

---

## 🎨 NIVEAU 1: OpenAI DALL-E

### Configuration

1. **Créer un compte OpenAI**
   - Aller sur https://platform.openai.com
   - Créer un compte ou se connecter
   - Activer le paiement (nécessaire pour les images)

2. **Générer une clé API**
   - Dans Settings → API keys
   - Créer nouvelle clé (ex: `sk-proj-abc123...`)
   - **⚠️ Garder secrète !**

3. **Configurer dans le projet**

   **Créer `.env.local` :**
   ```bash
   # .env.local
   OPENAI_API_KEY="sk-proj-votre-clé-ici"
   ```

   **Dans `config/services.yaml` :**
   ```yaml
   parameters:
     env(OPENAI_API_KEY): ""
   ```

4. **Code Backend (ApiController.php)**
   ```php
   private function generateImageWithOpenAI(string $apiKey, string $titre, string $description): array
   {
       $prompt = sprintf('Créer une image d\'événement pour "%s". Description : %s', $titre, $description);
       $payload = json_encode([
           'prompt' => $prompt,
           'n' => 1,
           'size' => '512x512'
       ]);

       $ch = curl_init('https://api.openai.com/v1/images/generations');
       curl_setopt_array($ch, [
           CURLOPT_POST => true,
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_HTTPHEADER => [
               'Content-Type: application/json',
               'Authorization: Bearer ' . $apiKey,
           ],
           CURLOPT_POSTFIELDS => $payload,
           CURLOPT_TIMEOUT => 30,
       ]);

       $response = curl_exec($ch);
       $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
       curl_close($ch);

       $decoded = json_decode($response, true);
       
       return [
           'success' => true,
           'imageData' => $decoded['data'][0]['b64_json'],
           'mimeType' => 'image/png'
       ];
   }
   ```

### Avantages
- ✅ Qualité très haute
- ✅ Images uniques et créatives
- ✅ Personnalisé au titre/description
- ✅ 512x512 pixels PNG

### Inconvénients
- ❌ Coût: $0.04-0.08 par image (2024)
- ❌ Nécessite connexion API
- ❌ Latence: 10-30 secondes

---

## 🖼️ NIVEAU 2: GD Library PHP

### Comment ça marche

1. **Création image PNG**
   ```php
   $image = imagecreatetruecolor(800, 450);
   ```

2. **Génération couleur basée sur titre**
   ```php
   $hash = md5($titre);
   $r = hexdec(substr($hash, 0, 2));  // Rouge
   $g = hexdec(substr($hash, 2, 2));  // Vert
   $b = hexdec(substr($hash, 4, 2));  // Bleu
   ```

3. **Création gradient**
   ```php
   for ($i = 0; $i < $height; $i++) {
       $lineColor = imagecolorallocate(
           $image,
           min($r + ($i / $height) * 40, 255),
           min($g + ($i / $height) * 40, 255),
           min($b + ($i / $height) * 40, 255)
       );
       imageline($image, 0, $i, $width, $i, $lineColor);
   }
   ```

4. **Ajout texte**
   ```php
   $textColor = imagecolorallocate($image, 255, 255, 255); // Blanc
   imagestring($image, 5, $x, 180, $titre, $textColor);
   imagestring($image, 5, $x, 220, $descShort, $textColor);
   ```

5. **Conversion base64**
   ```php
   ob_start();
   imagepng($image);
   $imageContent = ob_get_clean();
   imagedestroy($image);
   return base64_encode($imageContent);
   ```

### Avantages
- ✅ Gratuit
- ✅ Super rapide (< 100ms)
- ✅ 100% local, pas de dépendance externe
- ✅ Fonctionne même offline

### Inconvénients
- ❌ Besoin de l'extension GD PHP activée
- ❌ Moins personnalisé que DALL-E
- ❌ Couleur basée sur hash du titre seulement

---

## 📊 NIVEAU 3: SVG Fallback

### Code

```php
private function generateFallbackImage(string $titre, string $description): string
{
    $hash = md5($titre);
    $r = hexdec(substr($hash, 0, 2));
    $g = hexdec(substr($hash, 2, 2));
    $b = hexdec(substr($hash, 4, 2));

    $color = sprintf('#%02x%02x%02x', $r, $g, $b);
    $textColor = ($r + $g + $b > 382) ? '#000000' : '#FFFFFF';

    $svg = <<<SVG
<svg width="800" height="450" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:$color;stop-opacity:1" />
      <stop offset="100%" style="stop-color:rgb(100, 100, 100);stop-opacity:1" />
    </linearGradient>
  </defs>
  <rect width="800" height="450" fill="url(#grad)"/>
  <text x="400" y="180" font-size="48" font-weight="bold" text-anchor="middle" fill="$textColor">
    $titre
  </text>
  <text x="400" y="280" font-size="20" text-anchor="middle" fill="$textColor" opacity="0.8">
    $description
  </text>
</svg>
SVG;

    return base64_encode($svg);
}
```

### Avantages
- ✅ Toujours fonctionnel
- ✅ Ultra-léger (~1KB)
- ✅ Vectoriel, scalable
- ✅ Gratuit

### Inconvénients
- ❌ Moins attractif visuellement
- ❌ Pas d'images réelles

---

## 🔄 Flux Frontend Complet

### JavaScript : Génération d'image

```javascript
// 1. Utilisateur clique "Générer IA"
async function generateImageWithAI() {
    // 2. Récupère titre/description du formulaire
    const titre = document.getElementById('eventTitre')?.value.trim();
    const description = document.getElementById('eventDescription')?.value.trim();

    // 3. Envoie au backend
    const response = await fetch('/api/admin/events/generate-image', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            titre: titre,
            description: description.substring(0, 200)
        })
    });

    const result = await response.json();

    // 4. Affiche l'image
    if (result.success && result.imageData) {
        const mimeType = result.mimeType || 'image/png';
        const preview = document.getElementById('eventImagePreview');
        preview.innerHTML = `<img src="data:${mimeType};base64,${result.imageData}" 
                                   alt="Image générée" 
                                   style="width: 100%; height: 100%; object-fit: cover;">`;
        
        // 5. Stocke pour la sauvegarde
        generatedImageData = result.imageData;
    }
}
```

### JavaScript : Conversion et Upload

```javascript
async function saveEvent() {
    // ... validation ...
    
    const formData = new FormData();
    
    // Si image générée par IA
    if (generatedImageData) {
        // Convertit base64 → Blob
        const base64String = generatedImageData.split(',')[1]; // Enlève "data:..." si présent
        const byteCharacters = atob(base64String);
        const byteArray = new Uint8Array(byteCharacters.length);
        for (let i = 0; i < byteCharacters.length; i++) {
            byteArray[i] = byteCharacters.charCodeAt(i);
        }
        
        // Crée Blob et l'ajoute au FormData
        const blob = new Blob([byteArray], { type: 'image/png' });
        formData.append('image_evenement', blob, 'generated-image.png');
    }
    
    // ... reste du traitement ...
}
```

### Backend : Récupération et stockage

```php
public function updateEvent(int $id, Request $request, ManagerRegistry $doctrine): JsonResponse
{
    // ... validation ...
    
    $uploadedImage = $request->files->get('image_evenement');
    
    if ($uploadedImage instanceof UploadedFile && $uploadedImage->isValid()) {
        // Valide l'extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        $extension = strtolower($uploadedImage->guessExtension() ?: '');
        
        if (!in_array($extension, $allowedExtensions, true)) {
            return new JsonResponse(['success' => false, 'message' => 'Format non supporté']);
        }
        
        // Valide la taille (max 5MB)
        if ($uploadedImage->getSize() > 5 * 1024 * 1024) {
            return new JsonResponse(['success' => false, 'message' => 'Fichier trop volumineux']);
        }
        
        // Déplace vers le dossier de stockage
        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/events';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        $filename = uniqid('event_', true) . '.' . $extension;
        $uploadedImage->move($uploadsDir, $filename);
        $event->setImageEvenement($filename);
    }
    
    $entityManager = $doctrine->getManager();
    $entityManager->flush();
    
    return new JsonResponse(['success' => true, 'message' => 'Événement mis à jour']);
}
```

---

## 📱 Amélioration de l'Affichage des Images

### CSS Adaptable

```css
/* Conteneur préview - s'adapte à toute taille d'image */
#eventImagePreview {
    width: 100%;
    height: auto;
    min-height: 250px;      /* Hauteur minimale */
    max-height: 400px;      /* Hauteur maximale */
    overflow: auto;         /* Scrollbar si nécessaire */
    border: 2px dashed var(--border);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg);
}

/* Image intérieure */
#eventImagePreview img {
    width: 100%;            /* Prend toute la largeur */
    height: 100%;           /* Prend toute la hauteur */
    object-fit: cover;      /* Remplit le conteneur sans distorsion */
    border-radius: 8px;     /* Coins arrondis */
}
```

### Résultat

- ✅ Image s'affiche quelle que soit sa taille
- ✅ Pas de distorsion ou déformation
- ✅ Responsive et adaptatif
- ✅ Scrollbar si image trop grande

---

## 🧪 Tests

### Test DALL-E
```bash
curl -X POST http://localhost:8000/api/admin/events/generate-image \
  -H "Content-Type: application/json" \
  -d '{
    "titre": "Nettoyage de forêt",
    "description": "Événement de nettoyage écologique"
  }'
```

### Réponse attendue
```json
{
  "success": true,
  "message": "Image générée avec succès",
  "imageData": "iVBORw0KGgoAAAANSUhEUgAAA...",
  "mimeType": "image/png"
}
```

---

## 🐛 Troubleshooting

### DALL-E ne fonctionne pas
- [ ] Vérifier `OPENAI_API_KEY` dans `.env.local`
- [ ] Vérifier que le compte OpenAI a du crédit
- [ ] Vérifier les logs : `var/log/dev.log`
- [ ] Fallback GD devrait activer automatiquement

### GD Library non disponible
- [ ] Vérifier avec `php -i | grep GD`
- [ ] Installer : `sudo apt-get install php-gd` (Linux) ou `php-gd` dans XAMPP
- [ ] Fallback SVG devrait fonctionner

### Image ne s'affiche pas dans le preview
- [ ] Vérifier la base64 n'est pas tronquée
- [ ] Vérifier le mimeType retourné
- [ ] Vérifier la console navigateur pour erreurs

---

## 💡 Recommandations

### Pour production
1. **Utiliser DALL-E** si budget permet (meilleure qualité)
2. **Configurer fallback GD** pour robustesse
3. **Limiter les appels** : cache les résultats 1h
4. **Monitorer les coûts** OpenAI

### Pour développement
1. Commencer sans clé API (GD + SVG suffisent)
2. Ajouter API key quand ready
3. Tester chaque niveau séparément

---

## 📞 Support

- OpenAI Docs: https://platform.openai.com/docs/guides/images
- GD PHP: https://www.php.net/manual/en/book.image.php
- SVG Spec: https://www.w3.org/TR/SVG2/
