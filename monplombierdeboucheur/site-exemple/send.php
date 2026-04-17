<?php
/**
 * send.php — Gestionnaire de formulaires pour Les Serruriers de l'Ouest
 * Compatible o2switch (PHP mail() natif)
 */
require_once __DIR__ . '/config.php';

// ---- Configuration ----
define('DEST_EMAIL',  'lesserruriersdelouest@gmail.com');
define('CC_EMAIL',    'artilead.ads@gmail.com');
define('FROM_EMAIL',  'noreply@serruriers-bretagne.fr');
define('SITE_NAME',   'Les Serruriers de Bretagne');
define('HONEYPOT_CB', 'website_callback');
define('HONEYPOT_CT', 'website_field');

// ---- Headers JSON ----
header('Content-Type: application/json; charset=utf-8');

// ---- Méthode POST uniquement ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// ---- Helpers ----

// Nettoyage général (XSS)
function clean(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

// Nettoyage pour les champs utilisés dans les headers email
// Supprime CR, LF et null-byte pour éviter l'injection de headers
function clean_header(string $val): string {
    return preg_replace('/[\r\n\0]/', '', clean($val));
}

function is_valid_phone(string $tel): bool {
    $tel = preg_replace('/[\s\.\-\(\)\/]/', '', $tel);
    return (bool) preg_match('/^(\+33|0033|0)[1-9][0-9]{8}$/', $tel);
}

function is_valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ---- CSRF — HMAC sans session, sans IP (compatible CDN/proxy) ----
// Token valide pendant ~20 min (fenêtre courante + précédente de 10 min)
function verify_csrf(string $token): bool {
    if (empty($token)) return false;
    $w       = (int) floor(time() / 600);
    $valid_a = hash_hmac('sha256', (string) $w,       CSRF_SECRET);
    $valid_b = hash_hmac('sha256', (string) ($w - 1), CSRF_SECRET);
    return hash_equals($valid_a, $token) || hash_equals($valid_b, $token);
}

// ---- Rate limiting — par IP, fichier temporaire avec verrou ----
function check_rate_limit(): bool {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $file = sys_get_temp_dir() . '/rl_' . md5($ip) . '.json';
    $now  = time();

    $fp = @fopen($file, 'c+');
    if (!$fp) return true; // Impossible d'ouvrir le fichier → on laisse passer

    $allowed = true;

    if (flock($fp, LOCK_EX)) {
        $raw  = stream_get_contents($fp);
        $hits = ($raw !== false && $raw !== '') ? json_decode($raw, true) : [];
        if (!is_array($hits)) $hits = [];

        // Supprimer les entrées hors fenêtre
        $hits = array_values(array_filter($hits, function ($t) use ($now) {
            return ($now - $t) < RATE_WINDOW;
        }));

        if (count($hits) >= RATE_MAX) {
            $allowed = false;
        } else {
            $hits[] = $now;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($hits));
        }

        flock($fp, LOCK_UN);
    }

    fclose($fp);
    return $allowed;
}

function send_mail(string $subject, string $body): bool {
    $to      = DEST_EMAIL;
    $headers = implode("\r\n", [
        'From: ' . SITE_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . FROM_EMAIL,
        'Cc: ' . CC_EMAIL,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: PHP/' . PHP_VERSION,
    ]);
    return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

// ======================================================
// Vérifications communes (CSRF + rate limit)
// ======================================================

// 1. CSRF
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Requête invalide. Rechargez la page et réessayez.']);
    exit;
}

// 2. Rate limit
if (!check_rate_limit()) {
    http_response_code(429);
    header('Retry-After: 600');
    echo json_encode(['ok' => false, 'error' => 'Trop de tentatives. Réessayez dans 10 minutes.']);
    exit;
}

// ---- Action ----
$action = isset($_POST['action']) ? clean($_POST['action']) : '';

// == CALLBACK (mini formulaire hero) ==
if ($action === 'callback') {
    if (!empty($_POST[HONEYPOT_CB])) {
        echo json_encode(['ok' => true]); // silently ignore bot
        exit;
    }

    $tel = clean($_POST['telephone'] ?? '');

    if (empty($tel)) {
        echo json_encode(['ok' => false, 'error' => 'Numéro manquant']);
        exit;
    }
    if (!is_valid_phone($tel)) {
        echo json_encode(['ok' => false, 'error' => 'Numéro invalide']);
        exit;
    }

    $date    = date('d/m/Y à H:i');
    $subject = '[Rappel Urgent] Demande de rappel – ' . $tel;
    $body    = "Nouvelle demande de rappel reçue le {$date}.\n\n"
             . "Téléphone : {$tel}\n\n"
             . "---\n"
             . SITE_NAME . "\n";

    $sent = send_mail($subject, $body);
    echo json_encode(['ok' => $sent]);
    exit;
}

// == CONTACT (formulaire complet) ==
if ($action === 'contact') {
    if (!empty($_POST[HONEYPOT_CT])) {
        echo json_encode(['ok' => true]);
        exit;
    }

    // clean_header() sur les champs qui passent dans le sujet de l'email
    $nom     = clean_header($_POST['nom']       ?? '');
    $tel     = clean($_POST['telephone']         ?? '');
    $email   = clean($_POST['email']             ?? '');
    $sujet   = clean_header($_POST['sujet']      ?? '');
    $message = clean($_POST['message']           ?? '');

    if (empty($nom) || empty($tel)) {
        echo json_encode(['ok' => false, 'error' => 'Champs obligatoires manquants']);
        exit;
    }
    if (!is_valid_phone($tel)) {
        echo json_encode(['ok' => false, 'error' => 'Numéro de téléphone invalide']);
        exit;
    }
    if (!empty($email) && !is_valid_email($email)) {
        echo json_encode(['ok' => false, 'error' => 'Adresse email invalide']);
        exit;
    }

    $date     = date('d/m/Y à H:i');
    $subj_str = '[Contact] ' . ($sujet ?: 'Demande') . ' – ' . $nom;
    $body     = "Nouveau message reçu le {$date}.\n\n"
              . "Nom    : {$nom}\n"
              . "Tél.   : {$tel}\n"
              . (!empty($email)   ? "Email  : {$email}\n"   : '')
              . (!empty($sujet)   ? "Sujet  : {$sujet}\n"   : '')
              . (!empty($message) ? "\nMessage :\n{$message}\n" : '')
              . "\n---\n"
              . SITE_NAME . "\n";

    $sent = send_mail($subj_str, $body);
    echo json_encode(['ok' => $sent]);
    exit;
}

// Aucune action reconnue
http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Action inconnue']);
