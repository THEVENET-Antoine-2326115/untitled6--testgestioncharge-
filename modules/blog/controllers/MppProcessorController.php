<?php
namespace modules\blog\controllers;

use modules\blog\models\DashboardModel;

/**
 * Classe MppProcessorController
 *
 * Cette classe gère les opérations liées à la conversion d'un fichier MPP spécifique en XLSX.
 */
class MppProcessorController {
    private $dashboardModel;

    // Chemin vers le script de conversion simplifié
    private $convertScript = 'convertMppFile.php';

    /**
     * Constructeur du MppProcessorController
     */
    public function __construct() {
        $this->dashboardModel = new DashboardModel();
    }

    /**
     * Gère la requête de conversion et redirige vers le tableau de bord
     */
    public function handleRequest() {
        try {
            // Vérifier si le script de conversion existe
            if (!file_exists($this->convertScript)) {
                $_SESSION['conversion_message'] = "Le script de conversion n'existe pas: " . $this->convertScript;
                $_SESSION['conversion_status'] = 'error';
                header('Location: index.php?action=dashboard');
                exit;
            }

            // Capturer la sortie du script de conversion
            ob_start();
            include $this->convertScript;
            $output = ob_get_clean();

            // Déterminer si la conversion a réussi
            $success = (strpos($output, 'Conversion réussie') !== false);

            // Stocker le message dans la session
            $_SESSION['conversion_message'] = $output;
            $_SESSION['conversion_status'] = $success ? 'success' : 'error';

            // Rediriger vers le tableau de bord
            header('Location: index.php?action=dashboard');
            exit;

        } catch (\Exception $e) {
            // En cas d'erreur, stocker le message d'erreur et rediriger
            $_SESSION['conversion_message'] = "Erreur lors de la conversion: " . $e->getMessage();
            $_SESSION['conversion_status'] = 'error';

            header('Location: index.php?action=dashboard');
            exit;
        }
    }
}