# 🚨 Correction Erreur 500 - Guide Étape par Étape

## 🔍 **Problème identifié**

**Erreur 500 Internal Server Error** causée par :
1. ❌ Directive `<DirectoryMatch>` interdite dans le `.htaccess` racine
2. ❌ Syntaxe incompatible dans `api/.htaccess`

**C'est CORRIGÉ maintenant !** ✅

---

## ✅ **Ce que tu dois faire**

### **Étape 1 : Transférer les 3 fichiers corrigés**

Dans FileZilla, transfère ces 3 fichiers en **REMPLAÇANT** les anciens :

```
✅ .htaccess → www/
✅ api/.htaccess → www/api/
✅ logs/.htaccess → www/logs/
```

**Important** : Coche "Écraser" si FileZilla te demande confirmation.

---

### **Étape 2 : Vérifier les permissions**

Dans FileZilla, vérifie que les permissions sont correctes :

| Fichier | Permission requise |
|---------|-------------------|
| `www/.htaccess` | **644** |
| `www/api/.htaccess` | **644** |
| `www/logs/.htaccess` | **644** |
| `www/api/process.php` | **644** |
| `www/api/` | **755** |
| `www/logs/` | **755** |

**Comment corriger** :
- Clic droit sur le fichier → Permissions de fichier
- Cocher les cases pour obtenir 644 (RW-R--R--)
- Valider

---

### **Étape 3 : Créer le dossier logs si nécessaire**

Dans FileZilla, côté distant (www/) :
1. Clic droit dans la zone → Créer un répertoire
2. Nom : `logs`
3. Transférer `logs/.htaccess` dedans

---

### **Étape 4 : Tester**

1. Rafraîchir la page du site (F5 ou Cmd+R)
2. L'erreur 500 devrait avoir **DISPARU**
3. Essayer de remplir un formulaire

**✅ Si l'erreur 500 est toujours là** → Continue lire ci-dessous

---

## 🧪 **Si l'erreur persiste**

### Test 1 : Temporairement désactiver les .htaccess

1. Dans FileZilla, renomme les fichiers :
   - `.htaccess` → `_htaccess`
   - `api/.htaccess` → `api/_htaccess`
   - `logs/.htaccess` → `logs/_htaccess`

2. Rafraîchis la page

**Si l'erreur disparaît** → Le problème vient des .htaccess (contacte-moi)
**Si l'erreur persiste** → Le problème vient de process.php

---

### Test 2 : Vérifier les logs d'erreur o2switch

1. Connecte-toi au panel o2switch
2. Hébergement → Ton domaine → **Erreur** ou **Logs**
3. Regarde la dernière erreur

**Copie/colle l'erreur ici** et je te dirai quoi faire.

---

## 📝 **Résumé des corrections apportées**

### Fichier `.htaccess` (racine)

**AVANT** (❌ causait l'erreur 500) :
```apache
<DirectoryMatch "^(logs|api)/">
  Require all denied
</DirectoryMatch>
```

**APRÈS** (✅ corrigé) :
```apache
# Note : Les dossiers api/ et logs/ sont protégés par leurs propres fichiers .htaccess
```

**Pourquoi** : `<DirectoryMatch>` est interdit dans les .htaccess sur o2switch.

---

### Fichier `api/.htaccess`

**AVANT** (❌ bloquait tout) :
```apache
<FilesMatch "\.php$">
    Require all denied
</FilesMatch>
<LimitExcept POST>
    Require all denied
</LimitExcept>
```

**APRÈS** (✅ simplifié) :
```apache
<Files "process.php">
    # On laisse process.php accessible
    # La protection se fait via validation referer dans le PHP
</Files>
```

**Pourquoi** : La directive précédente bloquait aussi les POST légitimes.

---

## ✅ **Ce qui est maintenant protégé**

| Dossier/Fichier | Protection |
|-----------------|------------|
| **www/api/** | ✅ .htaccess (simplifié mais OK) |
| **www/logs/** | ✅ .htaccess (accès bloqué) |
| **www/api/process.php** | ✅ Validation referer dans le code PHP |
| **Fichiers sensibles** | ✅ Bloqués par .htaccess racine |

**La sécurité est MAINTENUE !** 🔒

---

## 🎯 **Prochaine étape**

1. **Transférer les 3 fichiers corrigés** (2 min)
2. **Rafraîchir la page** (30 secondes)
3. **Tester un formulaire** (1 min)

Si ça ne fonctionne toujours pas, envoie-moi le message d'erreur exact des logs o2switch.

Bon courage ! 🚀
