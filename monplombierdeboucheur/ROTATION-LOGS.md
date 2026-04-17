# 🔄 Rotation des Logs - Documentation Technique

## 📋 Résumé Exécutif

**Problème** : Le fichier de logs `formulaire.log` croissait indéfiniment, risquant de saturer l'espace disque.

**Solution implémentée** : Rotation mensuelle automatique avec nettoyage après 90 jours.

**Impact** : Zéro maintenance manuelle, risque de saturation éliminé.

---

## 🔧 Modifications du Code

### Avant (api/process.php - lignes 218-228)

```php
function logSubmission($ip, $formType, $nom) {
    $logfile = __DIR__ . '/../logs/formulaire.log';  // ❌ Fichier unique
    $line = sprintf("%s|%d|%s|%s|%s\n", ...);
    file_put_contents($logfile, $line, FILE_APPEND | LOCK_EX);
}
```

**Problèmes** :
- ❌ Fichier unique qui grossit indéfiniment
- ❌ Performance dégradée (file() charge tout en mémoire)
- ❌ Risque de saturation disque
- ❌ Pas de nettoyage automatique

### Après (api/process.php - lignes 218-252)

```php
function logSubmission($ip, $formType, $nom) {
    $logdir = __DIR__ . '/../logs';
    $currentMonth = date('Y-m');
    $logfile = $logdir . '/formulaire-' . $currentMonth . '.log';  // ✅ Mensuel

    // Écrire la ligne de log
    $line = sprintf("%s|%d|%s|%s|%s\n", ...);
    file_put_contents($logfile, $line, FILE_APPEND | LOCK_EX);

    // Nettoyage automatique (1 chance sur 20)
    if (rand(1, 20) === 1) {
        cleanOldLogs($logdir);
    }
}

function cleanOldLogs($logdir) {
    $files = glob($logdir . '/formulaire-*.log');
    $cutoff = time() - (90 * 86400);  // 90 jours

    foreach ($files as $file) {
        if (filemtime($file) < $cutoff) {
            @unlink($file);  // Suppression silencieuse
        }
    }
}
```

**Améliorations** :
- ✅ Fichiers mensuels : `formulaire-2026-04.log`
- ✅ Nettoyage automatique après 90 jours
- ✅ Impact performance minimisé (exécution aléatoire 1/20)
- ✅ Aucune maintenance manuelle requise

### Adaptation de checkRateLimit() (api/process.php - lignes 56-88)

**Avant** : Lecture d'un seul fichier

```php
function checkRateLimit($ip) {
    $logfile = __DIR__ . '/../logs/formulaire.log';  // ❌ Fichier unique
    $lines = file($logfile, ...);
    // ...
}
```

**Après** : Lecture de tous les fichiers récents

```php
function checkRateLimit($ip) {
    $logdir = __DIR__ . '/../logs';
    $files = glob($logdir . '/formulaire-*.log');  // ✅ Multi-fichiers

    foreach ($files as $logfile) {
        $lines = file($logfile, ...);
        // ...
        if ($count >= 3) return false;  // Optimisation : arrêt anticipé
    }
}
```

**Améliorations** :
- ✅ Gère le chevauchement mensuel (31 mars → 1er avril)
- ✅ Optimisation : arrête de compter dès que limite atteinte
- ✅ Compatible avec la rotation mensuelle

---

## 📊 Calcul de l'Espace Disque

### Scénario Normal (trafic standard)

**Hypothèses** :
- 100 soumissions/mois (3-4 par jour)
- 200 octets par ligne de log

**Calculs** :
- 1 mois = 100 × 200 octets = **20 Ko**
- 3 mois de rétention = 20 Ko × 3 = **60 Ko maximum**

**Conclusion** : Négligeable ✅

### Scénario Attaque Spam (trafic malveillant)

**Hypothèses** :
- 10 000 soumissions/mois (333 par jour)
- Rate limiting = 3/heure par IP → nécessite 1 000 IP différentes

**Calculs** :
- 1 mois = 10 000 × 200 octets = **2 Mo**
- 3 mois de rétention = 2 Mo × 3 = **6 Mo maximum**

**Conclusion** : Gérable ✅ (suppression auto après 90 jours)

### Scénario Catastrophe (attaque massive)

**Hypothèses** :
- 100 000 soumissions/mois (3 333 par jour)
- Rate limiting = 3/heure par IP → nécessite 10 000 IP différentes (botnet)

**Calculs** :
- 1 mois = 100 000 × 200 octets = **20 Mo**
- 3 mois de rétention = 20 Mo × 3 = **60 Mo maximum**

**Conclusion** :
- o2switch = stockage SSD illimité → pas de problème
- Suppression auto après 90 jours
- Rate limiting fonctionne mais nécessite botnet massif

---

## 🧪 Tests et Validation

### Script de Test Automatisé

**Fichier** : `test-rotation-logs.php`

**Fonctionnalités** :
1. Crée des fichiers de logs simulés pour 3 mois
2. Teste la fonction `checkRateLimit()`
3. Teste la fonction `cleanOldLogs()`
4. Vérifie l'état final

**Exécution** :
```bash
php test-rotation-logs.php
```

**Sortie attendue** :
```
=== Test Rotation des Logs ===

Test 1 : Création de fichiers de logs simulés...
  ✅ Créé : formulaire-2026-01.log
  ✅ Créé : formulaire-2026-02.log
  ✅ Créé : formulaire-2026-03.log
  ✅ Créé : formulaire-2026-04.log

Test 2 : Liste des fichiers de logs...
  4 fichier(s) trouvé(s)
  - formulaire-2026-01.log (450 octets, modifié: 2026-04-17 16:45:32)
  - formulaire-2026-02.log (450 octets, modifié: 2026-04-17 16:45:32)
  ...

Test 3 : Test de checkRateLimit()...
  IP 93.184.216.37 avec 2 soumissions récentes : AUTORISÉ ✅

Test 4 : Test de cleanOldLogs()...
  🗑️  Supprimé : formulaire-2026-01.log
  🗑️  Supprimé : formulaire-2026-02.log
  Résultat : 2 fichier(s) supprimé(s)

Test 5 : État final des logs...
  2 fichier(s) restant(s)
  - formulaire-2026-03.log (450 octets)
  - formulaire-2026-04.log (450 octets)

=== Test terminé ===
✅ Tous les tests sont passés avec succès !
```

### Tests Manuels

**1. Vérifier la création mensuelle**
```bash
# Fin avril 2026
ls -lh logs/
# Doit afficher : formulaire-2026-04.log

# 1er mai 2026 (après minuit)
ls -lh logs/
# Doit afficher :
# formulaire-2026-04.log (mois précédent)
# formulaire-2026-05.log (nouveau fichier créé)
```

**2. Vérifier le nettoyage automatique**
```bash
# Créer un vieux fichier (simulé)
touch -t 202601010000 logs/formulaire-2026-01.log

# Attendre une soumission (ou forcer avec le script de test)

# Vérifier qu'il a été supprimé
ls logs/formulaire-2026-01.log
# Doit afficher : No such file or directory
```

**3. Vérifier le rate limiting multi-fichiers**
```bash
# Créer 3 soumissions le 31 mars à 23h
# (via formulaire ou script)

# Le 1er avril à 01h, essayer une 4ème soumission
# Doit être bloquée : "Trop de demandes"
```

---

## 🎛️ Configuration et Personnalisation

### Modifier la durée de rétention (90 jours par défaut)

**Fichier** : `api/process.php`
**Fonction** : `cleanOldLogs()`
**Ligne** : `237`

```php
$cutoff = time() - (90 * 86400);  // 90 jours
```

**Exemples** :
```php
// 30 jours (1 mois)
$cutoff = time() - (30 * 86400);

// 180 jours (6 mois)
$cutoff = time() - (180 * 86400);

// 365 jours (1 an)
$cutoff = time() - (365 * 86400);
```

### Modifier la fréquence du nettoyage (1/20 par défaut)

**Fichier** : `api/process.php`
**Fonction** : `logSubmission()`
**Ligne** : `234`

```php
if (rand(1, 20) === 1) {  // 1 chance sur 20
    cleanOldLogs($logdir);
}
```

**Exemples** :
```php
// Plus fréquent (1/10 = 10% des requêtes)
if (rand(1, 10) === 1) {
    cleanOldLogs($logdir);
}

// Moins fréquent (1/100 = 1% des requêtes)
if (rand(1, 100) === 1) {
    cleanOldLogs($logdir);
}

// À chaque requête (déconseillé, impact performance)
cleanOldLogs($logdir);
```

---

## 🔍 Surveillance et Maintenance

### Commandes de surveillance

**Voir l'espace utilisé par les logs**
```bash
du -sh logs/
# Sortie : 120K    logs/

du -h logs/*.log
# Sortie :
# 20K     logs/formulaire-2026-02.log
# 40K     logs/formulaire-2026-03.log
# 60K     logs/formulaire-2026-04.log
```

**Voir les fichiers avec leur âge**
```bash
ls -lht logs/
# Sortie :
# -rw-r--r--  1 user  group   60K Apr 17 16:45 formulaire-2026-04.log
# -rw-r--r--  1 user  group   40K Mar 31 23:59 formulaire-2026-03.log
# -rw-r--r--  1 user  group   20K Feb 28 23:59 formulaire-2026-02.log
```

**Compter le nombre de soumissions par mois**
```bash
wc -l logs/formulaire-*.log
# Sortie :
#      100 logs/formulaire-2026-02.log
#      250 logs/formulaire-2026-03.log
#      450 logs/formulaire-2026-04.log
#      800 total
```

### Alertes recommandées

**Alerte espace disque** (via monitoring o2switch) :
- Seuil : 90% d'utilisation
- Action : Vérifier les logs, réduire la rétention si nécessaire

**Alerte taux de soumission** (via monitoring) :
- Seuil : > 1000 soumissions/jour
- Action : Vérifier attaque spam, ajuster rate limiting

---

## ✅ Checklist Déploiement

- [ ] Transférer `api/process.php` modifié
- [ ] Exécuter `test-rotation-logs.php` sur le serveur
- [ ] Vérifier que les fichiers mensuels sont créés
- [ ] Confirmer le nettoyage automatique après 90 jours
- [ ] Surveiller l'espace disque pendant 1 semaine

---

## 📞 Support

En cas de problème :

1. **Vérifier les permissions** :
   ```bash
   ls -la logs/
   # Doit être : drwxr-xr-x  ... logs/
   ```

2. **Vérifier .htaccess** :
   ```bash
   cat logs/.htaccess
   # Doit contenir : Require all denied
   ```

3. **Tester manuellement** :
   ```bash
   php -r "require 'api/process.php'; echo 'OK';"
   ```

---

## 🎓 Conclusion

La rotation des logs est maintenant **entièrement automatisée** :

✅ **Fichiers mensuels** : `formulaire-YYYY-MM.log`
✅ **Nettoyage automatique** : Suppression après 90 jours
✅ **Performance optimisée** : Nettoyage aléatoire (1/20)
✅ **Aucune maintenance** : Tout est automatique
✅ **Risque éliminé** : Plus de saturation disque possible

**Code production-ready** 👍
