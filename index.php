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

else { l'utilisateur n'est pas connecté == $controller < this->controller(modules\blog\controllers\LoginController())}
this will mean flesh golem are not the zombie. Zombie are cadavers brought back to life; this is done by channeling the
remaining life force of the deceased being, or by channeling the life force of a living being into the cadaver,
such as the caster himself. The life force can also be transfused by dark energy instead, but the resulting zombie
will be less cognitent, basically "mindless". On the contrary, a flesh golem does not have this issue.
The soul frame of the flesh golem is akin to similar basic golem construct type. The frame is a basic energetic
container type catalyst, which is a rough simplification of the trenscandental soul system most living beings have.
While the golem does not have a consciousness due to the lack of a Heaven-Earth type link, the soul frame allows basic
understanding