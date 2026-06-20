<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://www.audioask.ai');
header('X-Content-Type-Options: nosniff');

function respond(bool $success, string $error = ''): never
{
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $error]);
    }
    exit;
}

// 1. POST uniquement
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Méthode non autorisée.');
}

// 2. Honeypot anti-spam : le champ "website" doit rester vide
if (!empty($_POST['website'])) {
    respond(true); // Silencieux : on fait croire au bot que ça a fonctionné
}

// 3. Récupération et sanitisation
$nom     = trim(strip_tags(htmlspecialchars($_POST['nom']     ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));
$email   = trim(strip_tags(htmlspecialchars($_POST['email']   ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));
$sujet   = trim(strip_tags(htmlspecialchars($_POST['sujet']   ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));
$message = trim(strip_tags(htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));

// 4. Validation
$sujets_autorises = ['Question produit', 'Problème technique', 'Partenariat', 'Autre'];

if ($nom === '') respond(false, 'Le nom est requis.');
if (mb_strlen($nom, 'UTF-8') > 100) respond(false, 'Le nom est trop long (100 caractères max).');

if ($email === '') respond(false, "L'adresse email est requise.");
if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) respond(false, "L'adresse email n'est pas valide.");
if (mb_strlen($email, 'UTF-8') > 254) respond(false, "L'adresse email est trop longue.");

if (!in_array($sujet, $sujets_autorises, true)) respond(false, 'Sujet invalide.');

if ($message === '') respond(false, 'Le message est requis.');
if (mb_strlen($message, 'UTF-8') < 10) respond(false, 'Le message est trop court (10 caractères minimum).');
if (mb_strlen($message, 'UTF-8') > 5000) respond(false, 'Le message est trop long (5 000 caractères max).');

// 5. Envoi email
$to      = 'hello@audioask.ai';
$subject = '=?UTF-8?B?' . base64_encode('Contact Audioask : ' . $sujet) . '?=';

$body  = "Nouveau message via le formulaire de contact Audioask\n";
$body .= str_repeat('-', 50) . "\n\n";
$body .= "Nom     : {$nom}\n";
$body .= "Email   : {$email}\n";
$body .= "Sujet   : {$sujet}\n\n";
$body .= "Message :\n{$message}\n";

$headers  = "From: noreply@audioask.ai\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";

$sent = mail($to, $subject, $body, $headers);

if (!$sent) {
    respond(false, "L'envoi a échoué côté serveur. Réessaie dans un instant.");
}

respond(true);
