<?php
namespace modules\blog\controllers;

use modules\blog\models\DashboardModel;
use modules\blog\models\ChargeModel;
use modules\blog\models\GraphGeneratorModel;
use modules\blog\views\ChargeView;

/**
 * Classe ChargeController
 *
 * Cette classe gère les opérations liées à l'analyse de charge.
 */
class ChargeController {
    private $dashboardModel;
    private $chargeModel;
    private $graphGenerator;
    private $chargeView;

    /**
     * Constructeur du ChargeController
     */
    public function __construct() {
        $this->dashboardModel = new DashboardModel();
        $this->chargeModel = new ChargeModel();
        $this->graphGenerator = new GraphGeneratorModel(); // AJOUT
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
            // Vérifier si des données sont disponibles
            if (!$this->dashboardModel->hasData()) {
                echo $this->chargeView->showErrorMessage("Aucune donnée disponible. Veuillez d'abord convertir et importer des fichiers depuis le tableau de bord.");
                return;
            }

            // Analyser les données pour obtenir la charge par période
            $resultatAnalyse = $this->chargeModel->analyserChargeParPeriode();

            if (isset($resultatAnalyse['error'])) {
                echo $this->chargeView->showErrorMessage($resultatAnalyse['error']);
                return;
            }

            // Formater les résultats pour l'affichage
            $resultatsFormattés = $this->chargeModel->formaterResultats($resultatAnalyse);

            // GÉNÉRATION DES GRAPHIQUES - CORRECTION
            $chartPaths = [];
            if (isset($resultatsFormattés['graphiquesData'])) {
                $chartPaths = $this->graphGenerator->generateAllCharts($resultatsFormattés['graphiquesData']);
            }

            // Obtenir un résumé des données pour l'affichage
            $dataSummary = $this->dashboardModel->getDataSummary();
            $fileName = "Données de la base (" . $dataSummary['total_entries'] . " entrées)";

            // Afficher les résultats de l'analyse de charge
            echo $this->chargeView->showChargeAnalysis($userInfo, $fileName, $resultatsFormattés, $chartPaths);

        } catch (\Exception $e) {
            echo $this->chargeView->showErrorMessage("Une erreur est survenue : " . $e->getMessage());
        }
    }
}