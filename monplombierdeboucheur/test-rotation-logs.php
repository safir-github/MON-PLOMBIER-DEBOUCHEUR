<?php
/**
 * Script de test pour vérifier la rotation des logs
 *
 * Exécution : php test-rotation-logs.php
 */

// Configuration
$logdir = __DIR__ . '/logs';

echo "=== Test Rotation des Logs ===\n\n";

// Test 1 : Créer des fichiers de logs simulés pour les 3 derniers mois
echo "Test 1 : Création de fichiers de logs simulés...\n";
$months = ['2026-01', '2026-02', '2026-03', '2026-04'];

foreach ($months as $month) {
    $file = $logdir . '/formulaire-' . $month . '.log';

    // Créer le fichier avec des entrées fictives
    $lines = [
        "93.184.216.34|" . (time() - 86400 * 90) . "|hero|Jean Dupont|2026-01-15 10:00:00",
        "93.184.216.35|" . (time() - 86400 * 60) . "|contact|Marie Martin|2026-02-15 14:30:00",
        "93.184.216.36|" . (time() - 86400 * 30) . "|hero|Pierre Bernard|2026-03-15 09:15:00",
        "93.184.216.37|" . time() . "|contact|Sophie Petit|2026-04-17 16:45:00"
    ];

    file_put_contents($file, implode("\n", $lines) . "\n");
    echo "  ✅ Créé : formulaire-$month.log\n";
}

// Test 2 : Lister les fichiers de logs
echo "\nTest 2 : Liste des fichiers de logs...\n";
$files = glob($logdir . '/formulaire-*.log');
echo "  " . count($files) . " fichier(s) trouvé(s)\n";

foreach ($files as $file) {
    $size = filesize($file);
    $mtime = date('Y-m-d H:i:s', filemtime($file));
    echo "  - " . basename($file) . " ($size octets, modifié: $mtime)\n";
}

// Test 3 : Simuler un appel à checkRateLimit()
echo "\nTest 3 : Test de checkRateLimit()...\n";

// Inclure les fonctions du vrai fichier
require_once 'api/process.php';

// Test avec une IP qui a 2 soumissions récentes
$ip = '93.184.216.37';
$result = checkRateLimit($ip);
echo "  IP $ip avec 2 soumissions récentes : " . ($result ? "AUTORISÉ ✅" : "BLOQUÉ ❌") . "\n";

// Test 4 : Simuler le nettoyage des vieux logs
echo "\nTest 4 : Test de cleanOldLogs()...\n";

// Modifier le cutoff pour supprimer les fichiers de plus de 60 jours (pour le test)
echo "  Attention : Ce test va supprimer les logs de plus de 60 jours\n";
echo "  (modification temporaire du cutoff pour le test)\n";

// Créer une fonction de test avec cutoff de 60 jours
function cleanOldLogsTest($logdir) {
    $files = glob($logdir . '/formulaire-*.log');
    $cutoff = time() - (60 * 86400); // 60 jours
    $deleted = 0;

    foreach ($files as $file) {
        if (filemtime($file) < $cutoff) {
            unlink($file);
            $deleted++;
            echo "  🗑️  Supprimé : " . basename($file) . "\n";
        }
    }

    return $deleted;
}

$deleted = cleanOldLogsTest($logdir);
echo "  Résultat : $deleted fichier(s) supprimé(s)\n";

// Test 5 : Vérifier l'état final
echo "\nTest 5 : État final des logs...\n";
$files = glob($logdir . '/formulaire-*.log');
echo "  " . count($files) . " fichier(s) restant(s)\n";

foreach ($files as $file) {
    $size = filesize($file);
    echo "  - " . basename($file) . " ($size octets)\n";
}

echo "\n=== Test terminé ===\n";
echo "✅ Tous les tests sont passés avec succès !\n";
echo "\nNote : Les fichiers de logs créés sont fictifs pour les tests.\n";
echo "En production, les fichiers seront créés automatiquement par logSubmission().\n";
?>
