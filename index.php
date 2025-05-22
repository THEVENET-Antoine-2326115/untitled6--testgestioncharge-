<?php

// Inclure l'autoloader
require_once 'vendor/autoload.php';

// Démarrer la session
session_start();

// Désactiver l'affichage des messages d'erreur de type deprecated
error_reporting(E_ALL & ~E_DEPRECATED);

// Pour ignorer la partie connexion, on simule un utilisateur connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'test_user';
}

// Récupérer l'action demandée
$action = $_GET['action'] ?? '';

// Router vers le contrôleur approprié en fonction de l'action
switch ($action) {
    case 'analyse-charge':
        // Afficher l'analyse de charge
        $controller = new modules\blog\controllers\ChargeController();
        $controller->handleRequest();
        break;
    case 'logout':
        // Déconnexion
        session_destroy();
        header('Location: index.php');
        exit;
    default:
        // Afficher le tableau de bord par défaut
        $controller = new modules\blog\controllers\DashboardController();
        $controller->handleRequest();
        break;
}