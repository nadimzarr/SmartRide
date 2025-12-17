#!/usr/bin/env php
<?php

/**
 * Script de diagnostic de configuration email pour Symfony
 * Usage: php test_email_config.php
 */

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNOSTIC DE CONFIGURATION EMAIL - SYMFONY 6.4               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$timestamp = date('Y-m-d H:i:s');
$logFile = __DIR__ . '/var/log/email_diagnostic.log';
$logContent = "=== DIAGNOSTIC EMAIL - $timestamp ===\n\n";

// Créer le dossier de logs si nécessaire
if (!is_dir(__DIR__ . '/var/log')) {
    mkdir(__DIR__ . '/var/log', 0777, true);
}

// Test 1 : Vérifier l'existence du fichier .env
echo "📋 Test 1 : Vérification du fichier .env\n";
echo str_repeat("-", 60) . "\n";

if (!file_exists(__DIR__ . '/.env')) {
    echo "❌ ERREUR : Le fichier .env n'existe pas !\n\n";
    $logContent .= "❌ Test 1 FAILED : .env file not found\n";
    file_put_contents($logFile, $logContent, FILE_APPEND);
    exit(1);
}

echo "✅ Fichier .env trouvé\n\n";
$logContent .= "✅ Test 1 PASSED : .env file exists\n";

// Test 2 : Lire et analyser le MAILER_DSN
echo "📋 Test 2 : Analyse du MAILER_DSN\n";
echo str_repeat("-", 60) . "\n";

$envContent = file_get_contents(__DIR__ . '/.env');
preg_match('/MAILER_DSN="?([^"\n]+)"?/', $envContent, $matches);

if (empty($matches[1])) {
    echo "❌ ERREUR : MAILER_DSN non trouvé dans .env !\n\n";
    $logContent .= "❌ Test 2 FAILED : MAILER_DSN not found\n";
    file_put_contents($logFile, $logContent, FILE_APPEND);
    exit(1);
}

$mailerDsn = $matches[1];
echo "✅ MAILER_DSN trouvé\n";
echo "   DSN : " . substr($mailerDsn, 0, 30) . "...\n\n";
$logContent .= "✅ Test 2 PASSED : MAILER_DSN found\n";

// Test 3 : Parser le DSN
echo "📋 Test 3 : Parsing du DSN\n";
echo str_repeat("-", 60) . "\n";

$dsnPattern = '/^smtp:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\?(.+)$/';
if (!preg_match($dsnPattern, $mailerDsn, $dsnParts)) {
    echo "❌ ERREUR : Format du DSN invalide !\n";
    echo "   Format attendu : smtp://user:password@host:port?options\n\n";
    $logContent .= "❌ Test 3 FAILED : Invalid DSN format\n";
    file_put_contents($logFile, $logContent, FILE_APPEND);
    exit(1);
}

$email = $dsnParts[1];
$password = $dsnParts[2];
$host = $dsnParts[3];
$port = $dsnParts[4];
parse_str($dsnParts[5], $options);

echo "✅ DSN parsé avec succès\n";
echo "   Email    : $email\n";
echo "   Host     : $host\n";
echo "   Port     : $port\n";
echo "   Password : " . str_repeat('*', strlen($password)) . " (" . strlen($password) . " caractères)\n";
echo "   Options  : " . json_encode($options) . "\n\n";

$logContent .= "✅ Test 3 PASSED : DSN parsed successfully\n";
$logContent .= "   Email: $email\n";
$logContent .= "   Host: $host\n";
$logContent .= "   Port: $port\n";
$logContent .= "   Password length: " . strlen($password) . "\n";

// Test 4 : Vérifier le format de l'email
echo "📋 Test 4 : Validation de l'adresse email\n";
echo str_repeat("-", 60) . "\n";

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "❌ ERREUR : L'adresse email '$email' est invalide !\n\n";
    $logContent .= "❌ Test 4 FAILED : Invalid email format\n";
    file_put_contents($logFile, $logContent, FILE_APPEND);
    exit(1);
}

echo "✅ Format d'email valide\n\n";
$logContent .= "✅ Test 4 PASSED : Valid email format\n";

// Test 5 : Vérifier la longueur du mot de passe
echo "📋 Test 5 : Validation du mot de passe\n";
echo str_repeat("-", 60) . "\n";

if (strlen($password) < 10) {
    echo "⚠️  AVERTISSEMENT : Le mot de passe semble court (" . strlen($password) . " caractères)\n";
    echo "   Un App Password Gmail fait normalement 16 caractères\n\n";
    $logContent .= "⚠️  Test 5 WARNING : Password seems short (" . strlen($password) . " chars)\n";
} else {
    echo "✅ Longueur du mot de passe acceptable (" . strlen($password) . " caractères)\n\n";
    $logContent .= "✅ Test 5 PASSED : Password length OK\n";
}

// Test 6 : Vérifier la connexion au serveur SMTP
echo "📋 Test 6 : Test de connexion au serveur SMTP\n";
echo str_repeat("-", 60) . "\n";

$connection = @fsockopen($host, $port, $errno, $errstr, 10);
if (!$connection) {
    echo "❌ ERREUR : Impossible de se connecter à $host:$port\n";
    echo "   Erreur : $errstr ($errno)\n";
    echo "   Vérifiez votre connexion Internet et votre pare-feu\n\n";
    $logContent .= "❌ Test 6 FAILED : Cannot connect to SMTP server\n";
    $logContent .= "   Error: $errstr ($errno)\n";
} else {
    echo "✅ Connexion au serveur SMTP réussie\n";
    $response = fgets($connection, 1024);
    echo "   Réponse du serveur : " . trim($response) . "\n\n";
    fclose($connection);
    $logContent .= "✅ Test 6 PASSED : SMTP connection successful\n";
}

// Test 7 : Vérifier les contrôleurs
echo "📋 Test 7 : Vérification des contrôleurs\n";
echo str_repeat("-", 60) . "\n";

$controllers = [
    'src/Controller/ResetPasswordController.php',
    'src/Controller/EmailTestController.php'
];

foreach ($controllers as $controller) {
    if (file_exists(__DIR__ . '/' . $controller)) {
        $content = file_get_contents(__DIR__ . '/' . $controller);
        preg_match('/->from\([^)]*[\'"]([^\'"\)]+)[\'"]/', $content, $fromMatches);
        
        if (!empty($fromMatches[1])) {
            $fromEmail = $fromMatches[1];
            if ($fromEmail === $email) {
                echo "✅ $controller : Email 'from' cohérent ($fromEmail)\n";
                $logContent .= "✅ $controller : Email matches\n";
            } else {
                echo "⚠️  $controller : Email 'from' différent !\n";
                echo "   DSN : $email\n";
                echo "   Controller : $fromEmail\n";
                $logContent .= "⚠️  $controller : Email mismatch (DSN: $email, Controller: $fromEmail)\n";
            }
        }
    }
}

echo "\n";

// Résumé final
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  RÉSUMÉ DU DIAGNOSTIC                                          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "📊 Configuration actuelle :\n";
echo "   • Email SMTP : $email\n";
echo "   • Serveur    : $host:$port\n";
echo "   • Encryption : " . ($options['encryption'] ?? 'none') . "\n";
echo "   • Auth mode  : " . ($options['auth_mode'] ?? 'default') . "\n\n";

echo "📝 Log enregistré dans : $logFile\n\n";

echo "🔍 Prochaines étapes :\n";
echo "   1. Testez l'envoi via : http://localhost:8000/email-test\n";
echo "   2. Consultez le guide : GUIDE_DIAGNOSTIC_EMAIL.md\n";
echo "   3. Vérifiez les logs : var/log/email_test.log\n\n";

$logContent .= "\n=== FIN DU DIAGNOSTIC ===\n\n";
file_put_contents($logFile, $logContent, FILE_APPEND);

echo "✅ Diagnostic terminé !\n";

