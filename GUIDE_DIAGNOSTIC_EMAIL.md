# 📧 GUIDE DE DIAGNOSTIC - ENVOI D'EMAILS SYMFONY

## 🔍 PROBLÈMES IDENTIFIÉS ET CORRIGÉS

### ✅ Corrections appliquées automatiquement :

1. **`.env`** : Ajout d'un commentaire pour rappeler la cohérence des emails
2. **`ResetPasswordController.php`** : Changement de `nadim.zarrouk1@gmail.com` → `mohamedoueslati788@gmail.com`
3. **Nouveau contrôleur de test** : `EmailTestController.php` avec logging détaillé

---

## 📋 ÉTAPES DE DIAGNOSTIC À SUIVRE

### ÉTAPE 1 : Vérifier votre App Password Gmail

#### ⚠️ IMPORTANT : Votre mot de passe actuel semble suspect

Le mot de passe `hhtjkrrgllectnxg` dans votre `.env` doit être un **App Password Gmail valide**.

**Comment vérifier/créer un nouveau App Password :**

1. Allez sur : https://myaccount.google.com/security
2. Vérifiez que la **validation en 2 étapes** est activée
3. Allez dans **Mots de passe des applications** (App Passwords)
4. Créez un nouveau mot de passe pour "Mail" ou "Autre (nom personnalisé)"
5. Gmail vous donnera un code de 16 caractères (ex: `abcd efgh ijkl mnop`)
6. **IMPORTANT** : Retirez les espaces → `abcdefghijklmnop`
7. Remplacez dans `.env` :

```env
MAILER_DSN="smtp://mohamedoueslati788@gmail.com:VOTRE_NOUVEAU_APP_PASSWORD@smtp.gmail.com:587?encryption=tls&auth_mode=login"
```

---

### ÉTAPE 2 : Tester l'envoi d'email

#### Option A : Via le navigateur (recommandé)

1. Démarrez votre serveur Symfony :
   ```bash
   symfony server:start
   ```
   ou
   ```bash
   php -S localhost:8000 -t public
   ```

2. Ouvrez votre navigateur : http://localhost:8000/email-test

3. Résultats possibles :
   - ✅ **Succès** : "Email envoyé avec succès !"
   - ❌ **Erreur** : Message d'erreur détaillé affiché

#### Option B : Via la console Symfony

```bash
php bin/console debug:container mailer
```

---

### ÉTAPE 3 : Consulter les logs

Les logs sont automatiquement enregistrés dans :
```
var/log/email_test.log
```

**Format des logs :**
```
[2025-12-11 14:30:45] ✅ SUCCESS - Email envoyé avec succès de mohamedoueslati788@gmail.com vers mohamedoueslati788@gmail.com
```

ou en cas d'erreur :
```
[2025-12-11 14:30:45] ❌ ERROR - Échec de l'envoi
   Type d'erreur: Symfony\Component\Mailer\Exception\TransportException
   Message: Expected response code "250" but got code "535", with message "535-5.7.8 Username and Password not accepted..."
   Fichier: /path/to/file.php:123
   Trace: ...
```

---

## 🔧 DIAGNOSTIC DES ERREURS COURANTES

### Erreur 1 : "535 Username and Password not accepted"

**Cause** : App Password invalide ou 2FA non activée

**Solution** :
1. Activez la validation en 2 étapes sur Gmail
2. Générez un nouveau App Password (voir ÉTAPE 1)
3. Mettez à jour `.env`

---

### Erreur 2 : "Connection could not be established with host smtp.gmail.com"

**Cause** : Problème de connexion réseau ou port bloqué

**Solution** :
1. Vérifiez votre connexion Internet
2. Testez si le port 587 est ouvert :
   ```bash
   telnet smtp.gmail.com 587
   ```
3. Essayez le port 465 avec SSL :
   ```env
   MAILER_DSN="smtp://mohamedoueslati788@gmail.com:PASSWORD@smtp.gmail.com:465?encryption=ssl&auth_mode=login"
   ```

---

### Erreur 3 : "530 5.7.0 Must issue a STARTTLS command first"

**Cause** : Problème de configuration TLS

**Solution** :
Vérifiez que `encryption=tls` est bien présent dans votre DSN

---

### Erreur 4 : "454 4.7.0 Too many login attempts, please try again later"

**Cause** : Gmail a temporairement bloqué votre compte

**Solution** :
1. Attendez 15-30 minutes
2. Allez sur : https://accounts.google.com/DisplayUnlockCaptcha
3. Cliquez sur "Continuer"

---

## 🧪 TESTS À EFFECTUER DANS L'ORDRE

### Test 1 : Configuration de base
```bash
# Vérifier que le DSN est bien chargé
php bin/console debug:container --env-vars | grep MAILER
```

### Test 2 : Envoi via le contrôleur de test
```
http://localhost:8000/email-test
```

### Test 3 : Envoi via reset password
1. Allez sur : http://localhost:8000/reset-password
2. Entrez un email d'utilisateur existant
3. Vérifiez les logs

---

## 📊 CHECKLIST DE VÉRIFICATION

- [ ] La validation en 2 étapes est activée sur Gmail
- [ ] Un App Password valide a été généré
- [ ] Le `.env` contient le bon App Password (sans espaces)
- [ ] L'email "from" dans les contrôleurs = email dans MAILER_DSN
- [ ] Le serveur Symfony est démarré
- [ ] Le port 587 est accessible
- [ ] Les logs `var/log/email_test.log` sont consultés

---

## 🚨 SI RIEN NE FONCTIONNE

### Option 1 : Créer un nouveau compte Gmail dédié

**Pourquoi ?**
- Votre compte actuel peut avoir des restrictions
- Un compte dédié pour l'application est plus sécurisé

**Comment ?**
1. Créez un nouveau compte Gmail : `smartride.noreply@gmail.com`
2. Activez la validation en 2 étapes
3. Générez un App Password
4. Mettez à jour `.env` et tous les contrôleurs

### Option 2 : Utiliser un autre service SMTP

**Alternatives à Gmail :**
- **Mailtrap** (pour le développement) : https://mailtrap.io
- **SendGrid** : https://sendgrid.com
- **Mailgun** : https://www.mailgun.com
- **Amazon SES** : https://aws.amazon.com/ses/

---

## 📞 SUPPORT

Si le problème persiste après avoir suivi toutes ces étapes :

1. Consultez les logs Symfony : `var/log/dev.log`
2. Vérifiez les logs du serveur web
3. Partagez le contenu de `var/log/email_test.log`

---

**Date de création** : 2025-12-11
**Version Symfony** : 6.4
**Composant** : symfony/mailer

