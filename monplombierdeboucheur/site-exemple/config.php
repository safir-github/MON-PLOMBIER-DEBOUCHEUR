<?php
/**
 * config.php — Configuration partagée (non accessible depuis le web)
 * Protégé via .htaccess : deny from all
 */

// Clé secrète CSRF — NE PAS DIVULGUER — changer si compromis
define('CSRF_SECRET', 'w5Kp9mXrLnQ2jTvZeYbAsF7hD4gJcUa3');

// Limites anti-spam
define('RATE_MAX',    5);    // soumissions max par IP
define('RATE_WINDOW', 600);  // fenêtre en secondes (10 min)
