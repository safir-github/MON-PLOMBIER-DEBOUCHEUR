<?php
/**
 * token.php — Génère un token CSRF (HMAC, sans session)
 * Valide pendant ~20 min (fenêtre courante + précédente)
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$w     = (int) floor(time() / 600);
$token = hash_hmac('sha256', (string) $w, CSRF_SECRET);

echo json_encode(['token' => $token]);
