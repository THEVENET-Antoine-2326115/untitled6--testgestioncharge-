<?php

// Inclure l'autoloader
require_once 'vendor/autoload.php';

// Démarrer la session
session_start();

// Récupérer l'action demandée
$action = $_GET['action'] ?? '';

// Router vers le contrôleur approprié en fonction de l'action
if (isset($_SESSION['user_id'])) {
    // L'utilisateur est connecté
    switch ($action) {
        case 'logout':
            $controller = new modules\blog\controllers\LoginController();
            $controller->handleRequest('logout');
            break;
        case 'dashboard':
            // Ici vous pouvez ajouter d'autres contrôleurs selon vos besoins
            echo "Tableau de bord - Bienvenue " . $_SESSION['user_id'] . " (Type: " . $_SESSION['user_type'] . ")";
            break;
        default:
            // Rediriger vers le tableau de bord par défaut si l'utilisateur est déjà connecté
            header('Location: index.php?action=dashboard');
            exit;
    }
} else {
    // L'utilisateur n'est pas connecté
    $controller = new modules\blog\controllers\LoginController();
    $controller->handleRequest($action);
}

