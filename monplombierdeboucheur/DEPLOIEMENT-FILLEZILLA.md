# 🚀 Guide de Déploiement FileZilla - Étape par Étape

## 📋 Préalable : Vérification de nettoyage

✅ **Déjà fait** :
- Tous les fichiers `.bak` ont été supprimés
- Le code est prêt pour la production

---

## 📂 Structure des fichiers à transférer

### ✅ FICHIERS À TRANSFÉRER (OBLIGATOIRES)

```
monplombierdeboucheur/
├── api/                              ← DOSSIER COMPLET
│   ├── process.php                   ← Traitement sécurisé des formulaires
│   └── .htaccess                     ← Protection du dossier API
│
├── logs/                             ← DOSSIER VIDE (avec .htaccess)
│   └── .htaccess                     ← Protection du dossier logs
│
├── .htaccess                         ← Sécurité serveur (HTTPS, headers)
│
├── main.js                           ← JavaScript mis à jour
│
├── index.html                        ← Page d'accueil (2 formulaires)
├── contact.html                      ← Page contact (formulaire complet)
│
├── plombier-deboucheur-morbihan.html         ← Page département 56
├── plombier-deboucheur-finistere.html         ← Page département 29
├── plombier-deboucheur-ille-et-vilaine.html   ← Page département 35
├── plombier-deboucheur-loire-atlantique.html  ← Page département 44
├── plombier-deboucheur-mayenne.html           ← Page département 53
├── plombier-deboucheur-cotes-darmor.html      ← Page département 22
│
└── test-formulaire.html              ← Optionnel : page de test
```

### ❌ FICHIERS À NE PAS TRANSFÉRER

```
❌ README-DEPLOIEMENT.md             ← Documentation pour toi
❌ ROTATION-LOGS.md                  ← Documentation technique
❌ AVANT-APRES-LOGS.md               ← Documentation comparaison
❌ test-rotation-logs.php            ← Script de test (pas en prod)
❌ plan-implémentation...            ← Tous les fichiers .md
```

---

## 🔧 ÉTAPE 1 : Connexion FileZilla à o2switch

### 1.1 Récupérer les identifiants o2switch

Via le panel o2switch :
1. Connecte-toi à https://panel.o2switch.net
2. Menu **Hébergement** → **Ton domaine**
3. Onglet **FTP**
4. Note les infos :
   - **Hôte** :通常是 `ton-domaine.fr` ou `ftp.ton-domaine.fr`
   - **Utilisateur** :通常是 `ton-domaine` ou `ftp-ton-domaine`
   - **Mot de passe** : Le mot de passe FTP (pas celui du panel !)
   - **Port** : 21

### 1.2 Configurer FileZilla

1. Ouvrir **FileZilla**
2. Dans **Fichier** → **Gestionnaire de sites** (ou Cmd+S)
3. Cliquer sur **Nouveau site**
4. Remplir :
   - **Protocole** : FTP
   - **Hôte** : `monplombierdeboucheur.fr` (ou ton domaine exact)
   - **Port** : 21
   - **Type d'authentification** : Normale
   - **Utilisateur** : `[ton utilisateur FTP]`
   - **Mot de passe** : `[ton mot de passe FTP]`

5. Cliquer **Connexion**

### 1.3 Vérifier la connexion

✅ Tu dois voir dans la colonne **Site distant** (droite) :
```
/                                   (racine du serveur)
├── www/                             ← C'EST LE DOSSIER PUBLIC
├── mail/
└── ...
```

---

## 📤 ÉTAPE 2 : Transfert des fichiers

### 2.1 Naviguer vers le dossier `www/`

**CÔTÉ DISTANT (droite)** :
- Double-cliquer sur **www/**

**⚠️ IMPORTANT** : Le dossier `www/` est la racine de ton site web !
- `www/index.html` = `https://monplombierdeboucheur.fr/index.html`
- `www/api/process.php` = `https://monplombierdeboucheur.fr/api/process.php`

### 2.2 Organiser les transferts

**MÉTHODE RECOMMANDÉE : Transfert par groupe**

#### Groupe 1 : Dossiers système (créer d'abord)

**CÔTÉ DISTANT (droite)** - Clic droit → **Créer un répertoire** :
- `api/`
- `logs/`

#### Groupe 2 : Fichiers de configuration

**CÔTÉ LOCAL (gauche)** :
Naviguer vers : `/Users/safirlemoudaa/LOCAL MAC/ARTILEAD/MON PLOMBIER DEBOUCHEUR/monplombierdeboucheur/`

Transférer dans l'ordre :

1. **.htaccess** (racine)
   - Clic droit → **Télécharger**

2. **api/.htaccess**
   - Entrer dans `api/` (côté distant)
   - Transférer `api/.htaccess` depuis le local

3. **api/process.php**
   - Transférer `api/process.php` dans le dossier `api/` distant

4. **logs/.htaccess**
   - Entrer dans `logs/` (côté distant)
   - Transférer `logs/.htaccess` depuis le local

#### Groupe 3 : Fichiers JavaScript et HTML

Toujours depuis **CÔTÉ LOCAL** vers **CÔTÉ DISTANT** (dans `www/`) :

1. **main.js** → Transférer
2. **index.html** → Transférer
3. **contact.html** → Transférer
4. **plombier-deboucheur-*.html** (7 fichiers) → Transférer

#### Groupe 4 : Fichier de test (optionnel)

5. **test-formulaire.html** → Transférer (pour tester sur le serveur)

### 2.3 Vérifier que tout est transféré

**CÔTÉ DISTANT (droite)** dans `www/` :

```
www/
├── .htaccess                    ✅
├── api/
│   ├── .htaccess               ✅
│   └── process.php             ✅
├── logs/
│   └── .htaccess               ✅
├── main.js                      ✅
├── index.html                   ✅
├── contact.html                 ✅
├── plombier-deboucheur-morbihan.html         ✅
├── plombier-deboucheur-finistere.html         ✅
├── plombier-deboucheur-ille-et-vilaine.html   ✅
├── plombier-deboucheur-loire-atlantique.html  ✅
├── plombier-deboucheur-mayenne.html           ✅
├── plombier-deboucheur-cotes-darmor.html      ✅
└── test-formulaire.html        ✅ (optionnel)
```

---

## 🔐 ÉTAPE 3 : Définir les permissions (CHMOD)

### 3.1 Permissions des dossiers

**CÔTÉ DISTANT (droite)** - Clic droit sur chaque dossier → **Permissions de fichier** :

| Dossier | Permission à définir | Case à cocher |
|---------|---------------------|---------------|
| `www/api/` | **755** | ✅ Lire + ✅ Exécuter (propriétaire), ✅ Lire + ✅ Exécuter (groupe), ✅ Lire + ✅ Exécuter (public) |
| `www/logs/` | **755** | ✅ Lire + ✅ Exécuter (propriétaire), ✅ Lire + ✅ Exécuter (groupe), ✅ Lire + ✅ Exécuter (public) |

**Raccourci** : Cocher **"Récursif"** pour appliquer aux sous-dossiers.

### 3.2 Permissions des fichiers

| Type de fichier | Permission |
|-----------------|------------|
| `api/process.php` | **644** (RW-R--R--) |
| `api/.htaccess` | **644** |
| `logs/.htaccess` | **644** |
| `.htaccess` (racine) | **644** |
| `main.js` | **644** |
| `*.html` | **644** |

**⚠️ IMPORTANT** : Les fichiers PHP ne doivent JAMAIS être en 777 !

### 3.3 Vérifier via FileZilla

Dans la colonne **Droits** du site distant :
- Dossiers : `drwxr-xr-x` (755)
- Fichiers : `-rw-r--r--` (644)

---

## ✅ ÉTAPE 4 : Vérifier le déploiement

### 4.1 Tester l'accès aux pages

Ouvrir ton navigateur et tester :

1. **Page d'accueil** :
   ```
   https://monplombierdeboucheur.fr/index.html
   ```
   ✅ La page doit s'afficher

2. **Page contact** :
   ```
   https://monplombierdeboucheur.fr/contact.html
   ```
   ✅ La page doit s'afficher

3. **Une page département** :
   ```
   https://monplombierdeboucheur.fr/plombier-deboucheur-morbihan.html
   ```
   ✅ La page doit s'afficher

4. **Fichier de test** :
   ```
   https://monplombierdeboucheur.fr/test-formulaire.html
   ```
   ✅ La page de test doit s'afficher

### 4.2 Vérifier la protection des dossiers

Tester que les dossiers sensibles sont bien protégés :

1. **Dossier api** :
   ```
   https://monplombierdeboucheur.fr/api/
   ```
   ❌ DOIT afficher : **"403 Forbidden"** ou **"Access Denied"**

2. **Dossier logs** :
   ```
   https://monplombierdeboucheur.fr/logs/
   ```
   ❌ DOIT afficher : **"403 Forbidden"** ou **"Access Denied"**

3. **Fichier process.php** (accès direct) :
   ```
   https://monplombierdeboucheur.fr/api/process.php
   ```
   ❌ DOIT afficher : **"403 Forbidden"** ou **"405 Method Not Allowed"**

---

## 🧪 ÉTAPE 5 : Tests de formulaires

### 5.1 Formulaire HERO (page d'accueil)

1. Aller sur : `https://monplombierdeboucheur.fr/index.html`
2. Remplir le formulaire hero (en haut) :
   - Nom : `Test Déploiement`
   - Téléphone : `06 12 34 56 78`
   - Ville : `Rennes`
3. Cliquer sur **"Être rappelé"**
4. Vérifier :
   - ✅ Le bouton affiche "Envoi en cours..."
   - ✅ Le bouton devient vert "Envoyé ! On vous rappelle."
   - ✅ Email reçu sur `contact@monplombierdeboucheur.fr`

### 5.2 Formulaire CONTACT complet

1. Aller sur : `https://monplombierdeboucheur.fr/contact.html`
2. Remplir :
   - Nom : `Test`
   - Prénom : `Déploiement`
   - Email : `ton@email.com`
   - Téléphone : `06 12 34 56 78`
   - Département : `35`
   - Message : `Test de déploiement du formulaire`
3. Cliquer sur **"Envoyer la demande"**
4. Vérifier :
   - ✅ Le bouton affiche "Envoi en cours..."
   - ✅ Le bouton devient vert "Demande envoyée — réponse sous 5 min"
   - ✅ Email reçu sur `contact@monplombierdeboucheur.fr`

### 5.3 Vérifier les logs (optionnel)

Via SSH ou panel o2switch :
```bash
# Lister les fichiers de logs
ls -lh www/logs/

# Doit afficher (après au moins 1 soumission) :
# formulaire-2026-04.log

# Voir le contenu
tail www/logs/formulaire-2026-04.log

# Doit afficher :
# 93.184.216.34|1744896752|hero|Test Déploiement|2026-04-17 14:32:15
```

---

## ⚠️ ÉTAPE 6 : Configuration DNS (IMPORTANT !)

Les emails risquent d'aller en spam sans cette configuration !

### 6.1 Enregistrement SPF

1. Aller chez ton registre (o2switch, Gandi, OVH, etc.)
2. Ajouter un enregistrement **TXT** :

```
Type : TXT
Nom : @ (ou monplombierdeboucheur.fr)
Valeur : v=spf1 a mx include:mx.o2switch.com ~all
TTL : 3600
```

### 6.2 Enregistrement DKIM

1. Aller dans le panel o2switch
2. Menu **Hébergement** → **Ton domaine**
3. Onglet **Emails** → **DKIM**
4. Activer DKIM
5. Copier l'enregistrement TXT fourni
6. Ajouter cet enregistrement chez ton registre

### 6.3 Enregistrement DMARC

1. Ajouter un enregistrement **TXT** chez ton registre :

```
Type : TXT
Nom : _dmarc
Valeur : v=DMARC1; p=none; rua=mailto:contact@monplombierdeboucheur.fr
TTL : 3600
```

**Note** : Commencer avec `p=none` (surveillance), puis passer à `p=quarantine` après 2 semaines.

### 6.4 Vérifier la propagation

Attendre 5-30 minutes, puis tester :
```
https://mxtoolbox.com/SPFTool.aspx
https://mxtoolbox.com/DKIMTool.aspx
https://mxtoolbox.com/DMARCTool.aspx
```

---

## 🎉 ÉTAPE 7 : Finalisation

### 7.1 Supprimer le fichier de test (optionnel)

Une fois tous les tests réussis :

**CÔTÉ DISTANT (droite)** :
- Clic droit sur `test-formulaire.html`
- **Supprimer**

### 7.2 Surveiller pendant 24h

- Vérifier que tous les formulaires fonctionnent
- Surveiller les logs : `tail www/logs/formulaire-YYYY-MM.log`
- Vérifier que les emails arrivent dans la boîte de réception (pas spam)

### 7.3 Ajuster si nécessaire

Si trop de spam :
- Réduire la rétention des logs à 30 jours
- Augmenter le rate limiting à 1/heure

Si trop peu de soumissions :
- Vérifier que les formulaires sont bien visibles
- Vérifier les messages d'erreur

---

## 📞 En cas de problème

### Problème : Emails ne partent pas

1. Vérifier les logs PHP o2switch
2. Vérifier que `contact@monplombierdeboucheur.fr` existe
3. Tester avec un script simple :
   ```php
   <?php
   mail('test@example.com', 'Test', 'Corps');
   ?>
   ```

### Problème : "403 Forbidden" partout

1. Vérifier les permissions des dossiers (755)
2. Vérifier les permissions des fichiers (644)
3. Vérifier que `.htaccess` ne contient pas d'erreurs

### Problème : "Accès non autorisé" (403 sur formulaire)

1. Vérifier que le formulaire vient bien de `monplombierdeboucheur.fr`
2. Peut être dû à un proxy/firewall

---

## ✅ Checklist Finale

- [ ] Fichiers `.bak` supprimés ✅
- [ ] Connexion FileZilla établie
- [ ] Tous les fichiers transférés
- [ ] Permissions définies (dossiers 755, fichiers 644)
- [ ] Pages accessibles (index.html, contact.html, etc.)
- [ ] Dossiers protégés (api/ et logs/ retournent 403)
- [ ] Formulaire hero testé et fonctionne
- [ ] Formulaire contact testé et fonctionne
- [ ] Emails reçus sur `contact@monplombierdeboucheur.fr`
- [ ] Logs créés dans `www/logs/`
- [ ] DNS configurés (SPF, DKIM, DMARC)
- [ ] Propagation DNS vérifiée
- [ ] Surveillance pendant 24h

---

**Tu es prêt à déployer ! 🚀**

Une question avant de commencer ?
