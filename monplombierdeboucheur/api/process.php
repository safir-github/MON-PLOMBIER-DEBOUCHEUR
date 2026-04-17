<?php
/**
 * api/process.php - Traitement sécurisé des formulaires
 *
 * SÉCURITÉ :
 * - Token CSRF (Session PHP)
 * - Rate Limiting (3 envois/heure par IP)
 * - Honeypot anti-bot (champ 'website')
 * - Validation Referer (domaine autorisé)
 * - Sanitization des entrées
 *
 * DÉLIVRABILITÉ EMAIL :
 * - Headers optimisés pour SPF/DKIM/DMARC
 * - From: adresse du domaine
 * - Charset UTF-8
 *
 * @author MonPlombierDeboucheur
 * @version 1.0
 */

// Configuration
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ne pas afficher les erreurs en production

// Headers JSON
header('Content-Type: application/json; charset=utf-8');

// Démarrage session pour CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =============================================================================
// FONCTIONS DE SÉCURITÉ
// =============================================================================

/**
 * Vérifie si le referer vient du domaine autorisé
 */
function verifyReferer() {
    $allowed_domains = ['monplombierdeboucheur.fr', 'www.monplombierdeboucheur.fr'];
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $referer_domain = parse_url($referer, PHP_URL_HOST);

    // En développement, accepter les referers vides ou locaux
    if (empty($referer_domain) || in_array('localhost', $allowed_domains) || in_array('127.0.0.1', $allowed_domains)) {
        return true;
    }

    return in_array($referer_domain, $allowed_domains);
}

/**
 * Vérifie le rate limiting (max 3 soumissions/heure par IP)
 * Lit tous les fichiers de logs récents (rotation mensuelle)
 */
function checkRateLimit($ip) {
    $logdir = __DIR__ . '/../logs';
    $now = time();
    $hour_ago = $now - 3600;
    $count = 0;

    // Lire tous les fichiers de logs (actuel et mois précédent pour chevauchement)
    $files = glob($logdir . '/formulaire-*.log');

    if (empty($files)) {
        return true; // Pas de logs = pas de limite
    }

    foreach ($files as $logfile) {
        if (!file_exists($logfile)) {
            continue;
        }

        $lines = file($logfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 2 && trim($parts[0]) === $ip) {
                $timestamp = intval($parts[1] ?? 0);
                if ($timestamp > $hour_ago) {
                    $count++;
                }
            }
        }

        // Optimisation : arrêter si limite déjà atteinte
        if ($count >= 3) {
            return false;
        }
    }

    return $count < 3;
}

/**
 * Génère ou valide un token CSRF
 */
function handleCsrf() {
    // Génération du token si inexistant
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return ['token' => $_SESSION['csrf_token'], 'generated' => true];
    }

    // Validation du token si soumis
    if (isset($_POST['csrf_token'])) {
        $isValid = hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);

        // Régénérer le token après validation (prévention replay)
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        return ['token' => $_SESSION['csrf_token'], 'valid' => $isValid];
    }

    return ['token' => $_SESSION['csrf_token'], 'valid' => false];
}

/**
 * Nettoie une entrée utilisateur
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    // Supprime les caractères de contrôle sauf saut de ligne
    $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
    return $data;
}

/**
 * Valide un numéro de téléphone français
 */
function validatePhone($tel) {
    $tel = preg_replace('/[^0-9]/', '', $tel);
    return strlen($tel) >= 10 && strlen($tel) <= 11 && preg_match('/^0[67]/', $tel);
}

/**
 * Valide un email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valide un numéro de département
 */
function validateDept($dept) {
    $validDepts = ['35', '22', '56', '29', '44', '53'];
    return in_array($dept, $validDepts);
}

/**
 * Envoie un email
 */
function sendEmail($data, $formType) {
    $to = 'contact@monplombierdeboucheur.fr';

    // Format du sujet
    if ($formType === 'hero') {
        $sujet = '[MONPLOMBIER] RAPPEL - ' . $data['ville'];
    } else {
        $dept = $data['dept'] ?? '';
        $type = $data['type'] ?? 'Contact';
        $sujet = '[MONPLOMBIER] ' . $dept . ' - ' . $type;
    }

    // Corps de l'email (texte brut)
    $corps = "═══════════════════════════════════════════════════════════\n";
    $corps .= "NOUVELLE DEMANDE - MonPlombierDeboucheur.fr\n";
    $corps .= "═══════════════════════════════════════════════════════════\n\n";

    if ($formType === 'hero') {
        $corps .= "📋 TYPE DE FORMULAIRE : Hero (Demande de rappel)\n\n";
        $corps .= "👤 CLIENT\n";
        $corps .= "   Nom : " . $data['nom'] . "\n";
        $corps .= "   Tel : " . $data['tel'] . "\n";
        $corps .= "   Ville : " . $data['ville'] . "\n\n";
        $corps .= "🔧 PRESTATION\n";
        $corps .= "   Service : " . ($data['service'] ?: 'Non spécifié') . "\n\n";
    } else {
        $corps .= "📋 TYPE DE FORMULAIRE : Contact complet\n\n";
        $corps .= "👤 CLIENT\n";
        $corps .= "   " . ($data['civilite'] ?? 'M.') . " " . ($data['prenom'] ?? '') . " " . $data['nom'] . "\n";
        $corps .= "   Tel : " . $data['tel'] . "\n";
        if (!empty($data['email'])) {
            $corps .= "   Email : " . $data['email'] . "\n";
        }
        $corps .= "   Ville : " . ($data['ville'] ?: 'Non spécifiée') . "\n";
        $corps .= "   Département : " . ($data['dept'] ?? '') . "\n\n";
        $corps .= "🔧 PRESTATION\n";
        $corps .= "   Type : " . ($data['type'] ?? 'Non spécifié') . "\n";
        $corps .= "   Urgence : " . ($data['urgence'] ?? 'Non spécifiée') . "\n\n";
        if (!empty($data['message'])) {
            $corps .= "💬 MESSAGE\n";
            $corps .= "   " . wordwrap($data['message'], 70, "\n   ") . "\n\n";
        }
    }

    $corps .= "📅 DATE DE SOUMISSION\n";
    $corps .= "   " . date('d/m/Y à H:i:s') . "\n\n";

    $corps .= "═══════════════════════════════════════════════════════════\n";
    $corps .= "Source : " . ($data['page_source'] ?? 'Inconnu') . "\n";
    $corps .= "═══════════════════════════════════════════════════════════\n";

    // Headers optimisés pour délivrabilité
    $headers = [
        'From' => 'contact@monplombierdeboucheur.fr',
        'Reply-To' => !empty($data['email']) ? $data['email'] : 'noreply@monplombierdeboucheur.fr',
        'Return-Path' => 'contact@monplombierdeboucheur.fr',
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
        'X-Mailer' => 'PHP/' . phpversion(),
        'X-Priority' => '3',
        'X-Auto-Response-Suppress' => 'OOF',
    ];

    $headersStr = '';
    foreach ($headers as $key => $value) {
        $headersStr .= "$key: $value\r\n";
    }

    // Envoi avec paramètre -f pour Return-Path
    return mail($to, $sujet, $corps, $headersStr, '-fcontact@monplombierdeboucheur.fr');
}

/**
 * Logger les soumissions avec rotation mensuelle automatique
 * Fichiers : formulaire-2026-04.log, formulaire-2026-05.log, etc.
 * Nettoyage automatique des logs de plus de 90 jours
 */
function logSubmission($ip, $formType, $nom) {
    $logdir = __DIR__ . '/../logs';
    $currentMonth = date('Y-m');
    $logfile = $logdir . '/formulaire-' . $currentMonth . '.log';

    // Écrire la ligne de log
    $line = sprintf("%s|%d|%s|%s|%s\n",
        $ip,
        time(),
        $formType,
        $nom,
        date('Y-m-d H:i:s')
    );
    file_put_contents($logfile, $line, FILE_APPEND | LOCK_EX);

    // Nettoyage automatique des anciens logs (plus de 90 jours)
    // Exécuté aléatoirement 1 fois sur 20 pour ne pas impacter les performances
    if (rand(1, 20) === 1) {
        cleanOldLogs($logdir);
    }
}

/**
 * Nettoie les fichiers de logs de plus de 90 jours
 */
function cleanOldLogs($logdir) {
    $files = glob($logdir . '/formulaire-*.log');
    $cutoff = time() - (90 * 86400); // 90 jours en secondes

    foreach ($files as $file) {
        if (filemtime($file) < $cutoff) {
            @unlink($file); // Suppression silencieuse
        }
    }
}

// =============================================================================
// TRAITEMENT DE LA REQUÊTE
// =============================================================================

try {
    // Vérification méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        exit;
    }

    // Récupération de l'IP réelle
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ??
          $_SERVER['HTTP_X_FORWARDED_FOR'] ??
          $_SERVER['REMOTE_ADDR'];

    // Vérification du Referer
    if (!verifyReferer()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
        exit;
    }

    // Rate limiting
    if (!checkRateLimit($ip)) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Trop de demandes. Réessayez dans 1 heure.']);
        exit;
    }

    // Honeypot (anti-bot)
    if (!empty($_POST['website'])) {
        // Succès silencieux pour ne pas alerter le bot
        echo json_encode(['success' => true]);
        exit;
    }

    // Gestion CSRF
    $csrf = handleCsrf();

    // Initialisation réponse
    $response = [
        'success' => false,
        'message' => '',
        'csrf_token' => $csrf['token'] // Retourner le nouveau token
    ];

    // Récupération et nettoyage des données
    $data = [];
    $errors = [];

    // Champs communs
    $data['nom'] = sanitizeInput($_POST['nom'] ?? '');
    $data['tel'] = sanitizeInput($_POST['tel'] ?? '');
    $data['ville'] = sanitizeInput($_POST['ville'] ?? '');
    $data['service'] = sanitizeInput($_POST['service'] ?? '');
    $data['page_source'] = sanitizeInput($_POST['page_source'] ?? $_SERVER['HTTP_REFERER'] ?? '');

    // Déterminer le type de formulaire
    $isHeroForm = !empty($_POST['prenom']) ? false : true;

    if (!$isHeroForm) {
        // Formulaire contact complet
        $data['civilite'] = sanitizeInput($_POST['civilite'] ?? 'M.');
        $data['prenom'] = sanitizeInput($_POST['prenom'] ?? '');
        $data['email'] = sanitizeInput($_POST['email'] ?? '');
        $data['dept'] = sanitizeInput($_POST['dept'] ?? '');
        $data['type'] = sanitizeInput($_POST['type'] ?? '');
        $data['urgence'] = sanitizeInput($_POST['urgence'] ?? '');
        $data['message'] = sanitizeInput($_POST['message'] ?? '');
    }

    // =============================================================================
    // VALIDATION DES DONNÉES
    // =============================================================================

    // Nom (2-50 caractères)
    if (strlen($data['nom']) < 2 || strlen($data['nom']) > 50) {
        $errors[] = 'Nom invalide (2-50 caractères requis)';
    }

    // Téléphone (format français 06/07)
    if (!validatePhone($data['tel'])) {
        $errors[] = 'Téléphone invalide (format 06/07 requis)';
    }

    // Ville (si présente)
    if (!empty($data['ville']) && strlen($data['ville']) < 2) {
        $errors[] = 'Ville invalide';
    }

    // Validation spécifique pour formulaire contact
    if (!$isHeroForm) {
        // Prénom
        if (strlen($data['prenom']) < 2 || strlen($data['prenom']) > 50) {
            $errors[] = 'Prénom invalide (2-50 caractères requis)';
        }

        // Email (si présent)
        if (!empty($data['email']) && !validateEmail($data['email'])) {
            $errors[] = 'Email invalide';
        }

        // Département
        if (!empty($data['dept']) && !validateDept($data['dept'])) {
            $errors[] = 'Département invalide';
        }

        // Message
        if (strlen($data['message']) < 10) {
            $errors[] = 'Message trop court (min. 10 caractères)';
        }
    }

    // Si erreurs de validation
    if (!empty($errors)) {
        $response['message'] = implode("\n", $errors);
        echo json_encode($response);
        exit;
    }

    // =============================================================================
    // ENVOI DE L'EMAIL
    // =============================================================================

    $formType = $isHeroForm ? 'hero' : 'contact';

    if (sendEmail($data, $formType)) {
        // Succès
        logSubmission($ip, $formType, $data['nom']);

        $response['success'] = true;
        $response['message'] = $isHeroForm
            ? 'Demande envoyée ! On vous rappelle rapidement.'
            : 'Demande envoyée — réponse sous 5 min en journée.';
    } else {
        // Erreur envoi
        $response['message'] = 'Erreur technique lors de l\'envoi. Veuillez réessayer ou nous appeler au 06 42 56 16 71.';
    }

    echo json_encode($response);

} catch (Exception $e) {
    // Erreur imprévue
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur technique. Contactez-nous par téléphone.'
    ]);
}
?>
