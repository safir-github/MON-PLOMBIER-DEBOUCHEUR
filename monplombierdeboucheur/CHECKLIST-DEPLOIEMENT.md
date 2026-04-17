# ✅ Checklist Avant Déploiement FileZilla

## 🔍 Vérification locale (avant ouverture FileZilla)

- [x] **Fichiers .bak supprimés** ✅ (déjà fait !)
- [ ] **Vérifier qu'il ne reste PAS de fichiers .bak** :
  ```bash
  ls -la *.bak
  # Doit afficher : No such file or directory
  ```

- [ ] **Vérifier les fichiers modifiés** :
  ```bash
  ls -la api/process.php main.js .htaccess
  # Doit afficher les 3 fichiers avec date récente
  ```

---

## 📤 Fichiers à transférer (liste exacte)

### Structure cible sur le serveur :

```
www/                              (racine du site)
│
├── .htaccess                     ← Sécurité serveur
│
├── api/                          ← DOSSIER À CRÉER
│   ├── .htaccess                 ← Protection API
│   └── process.php               ← Traitement formulaires
│
├── logs/                         ← DOSSIER À CRÉER (vide)
│   └── .htaccess                 ← Protection logs
│
├── main.js                       ← JavaScript mis à jour
│
├── index.html                    ← Accueil (2 formulaires)
├── contact.html                  ← Page contact
│
├── plombier-deboucheur-morbihan.html         ← Dépt 56
├── plombier-deboucheur-finistere.html        ← Dépt 29
├── plombier-deboucheur-ille-et-vilaine.html  ← Dépt 35
├── plombier-deboucheur-loire-atlantique.html ← Dépt 44
├── plombier-deboucheur-mayenne.html          ← Dépt 53
├── plombier-deboucheur-cotes-darmor.html     ← Dépt 22
│
└── test-formulaire.html          ← Optionnel (tests)
```

---

## ⚠️ FICHIRES À NE PAS TRANSFÉRER

❌ **Fichiers de documentation** (garder en local) :
- `README-DEPLOIEMENT.md`
- `ROTATION-LOGS.md`
- `AVANT-APRES-LOGS.md`
- `DEPLOIEMENT-FILLEZILLA.md`
- `CHECKLIST-DEPLOIEMENT.md` (ce fichier)

❌ **Scripts de test** (ne pas mettre en prod) :
- `test-rotation-logs.php`

---

## 🔧 Ordre de transfert RECOMMANDÉ

### 1. Créer les dossiers (côté distant)
- [ ] Créer `www/api/`
- [ ] Créer `www/logs/`

### 2. Transférer les fichiers de protection
- [ ] `.htaccess` → `www/`
- [ ] `api/.htaccess` → `www/api/`
- [ ] `logs/.htaccess` → `www/logs/`

### 3. Transférer le backend
- [ ] `api/process.php` → `www/api/`

### 4. Transférer le frontend
- [ ] `main.js` → `www/`
- [ ] `index.html` → `www/`
- [ ] `contact.html` → `www/`
- [ ] `plombier-deboucheur-morbihan.html` → `www/`
- [ ] `plombier-deboucheur-finistere.html` → `www/`
- [ ] `plombier-deboucheur-ille-et-vilaine.html` → `www/`
- [ ] `plombier-deboucheur-loire-atlantique.html` → `www/`
- [ ] `plombier-deboucheur-mayenne.html` → `www/`
- [ ] `plombier-deboucheur-cotes-darmor.html` → `www/`

### 5. Transférer le fichier de test (optionnel)
- [ ] `test-formulaire.html` → `www/`

---

## 🔐 Permissions à définir APRÈS transfert

### Dossiers (clic droit → Permissions)
- [ ] `www/api/` → **755**
- [ ] `www/logs/` → **755**

### Fichiers PHP
- [ ] `www/api/process.php` → **644**

### Fichiers de configuration
- [ ] `www/.htaccess` → **644**
- [ ] `www/api/.htaccess` → **644**
- [ ] `www/logs/.htaccess` → **644**

### Fichiers web
- [ ] `www/main.js` → **644**
- [ ] `www/*.html` → **644**

---

## ✅ Tests APRÈS déploiement

### Accès aux pages
- [ ] `https://monplombierdeboucheur.fr/` fonctionne
- [ ] `https://monplombierdeboucheur.fr/contact.html` fonctionne
- [ ] `https://monplombierdeboucheur.fr/plombier-deboucheur-morbihan.html` fonctionne

### Protection des dossiers
- [ ] `https://monplombierdeboucheur.fr/api/` → **403 Forbidden** ✅
- [ ] `https://monplombierdeboucheur.fr/logs/` → **403 Forbidden** ✅
- [ ] `https://monplombierdeboucheur.fr/api/process.php` → **403/405** ✅

### Test des formulaires
- [ ] Formulaire hero (index.html) envoie un email
- [ ] Formulaire contact (contact.html) envoie un email
- [ ] Email reçu sur `contact@monplombierdeboucheur.fr`
- [ ] Logs créés dans `www/logs/formulaire-YYYY-MM.log`

---

## 🌐 Configuration DNS (IMPORTANT !)

- [ ] **SPF** ajouté chez le registre :
  ```
  v=spf1 a mx include:mx.o2switch.com ~all
  ```

- [ ] **DKIM** configuré dans panel o2switch

- [ ] **DMARC** ajouté chez le registre :
  ```
  v=DMARC1; p=none; rua=mailto:contact@monplombierdeboucheur.fr
  ```

- [ ] Propagation DNS vérifiée (attendre 5-30 min)

---

## 🎉 Tu es prêt !

**Prochaine étape** : Ouvrir **DEPLOIEMENT-FILLEZILLA.md** et suivre le guide étape par étape.

Bonne chance ! 🚀
