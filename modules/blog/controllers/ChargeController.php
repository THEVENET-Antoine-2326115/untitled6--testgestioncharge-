<?php
namespace modules\blog\controllers;

use modules\blog\models\DashboardModel;
use modules\blog\models\ChargeModel;
use modules\blog\views\ChargeView;

/**
 * Classe ChargeController
 *
 * Cette classe gère les opérations liées à l'analyse de charge.
 */
class ChargeController {
    private $dashboardModel;
    private $chargeModel;
    private $chargeView;

    /**
     * Constructeur du ChargeController
     */
    public function __construct() {
        $this->dashboardModel = new DashboardModel();
        $this->chargeModel = new ChargeModel();
        $this->chargeView = new ChargeView();
    }

    /**
     * Gère les actions liées à l'analyse de charge
     *
     * @param string $action Action à exécuter
     */
    public function handleRequest($action = '') {
        // Récupérer l'ID utilisateur de la session
        $userId = $_SESSION['user_id'] ?? 'Utilisateur';

        // Récupérer les informations de l'utilisateur
        $userInfo = $this->dashboardModel->getUserInfo($userId);

        try {
            // Obtenir le chemin du fichier Excel par défaut
            $filePath = $this->dashboardModel->getDefaultExcelFile();
            $fileName = $this->dashboardModel->getDefaultExcelFileName();

            if (!$filePath) {
                echo $this->chargeView->showErrorMessage("Aucun fichier Excel disponible.");
                return;
            }

            // Lire les données du fichier Excel
            $excelData = $this->dashboardModel->readExcelFile($filePath);

            // Analyser les données pour obtenir la charge par période
            $resultatAnalyse = $this->chargeModel->analyserChargeParPeriode($excelData);

            if (isset($resultatAnalyse['error'])) {
                echo $this->chargeView->showErrorMessage($resultatAnalyse['error']);
                return;
            }

            // Formater les résultats pour l'affichage
            $resultatsFormattés = $this->chargeModel->formaterResultats($resultatAnalyse);

            // Afficher les résultats de l'analyse de charge
            echo $this->chargeView->showChargeAnalysis($userInfo, $fileName, $resultatsFormattés);

        } catch (\Exception $e) {
            echo $this->chargeView->showErrorMessage("Une erreur est survenue : " . $e->getMessage());
        }
    }
}