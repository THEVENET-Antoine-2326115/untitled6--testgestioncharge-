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
        echo "<script>console.log('=== DÉBUT handleRequest ===');</script>";
        echo "<script>console.log('Action reçue: " . addslashes($action) . "');</script>";
        echo "<script>console.log('URL complète: " . addslashes($_SERVER['REQUEST_URI'] ?? 'N/A') . "');</script>";
        echo "<script>console.log('Paramètres GET: " . addslashes(json_encode($_GET)) . "');</script>";

        // Récupérer l'ID utilisateur de la session
        $userId = $_SESSION['user_id'] ?? 'Utilisateur';

        // Récupérer les informations de l'utilisateur
        $userInfo = $this->dashboardModel->getUserInfo($userId);

        echo "<script>console.log('📊 FLUX STANDARD - Nettoyage puis génération des graphiques');</script>";

        try {
            // Vérifier si des données sont disponibles
            if (!$this->dashboardModel->hasData()) {
                echo "<script>console.log('❌ ERREUR: Aucune donnée disponible');</script>";
                echo $this->chargeView->showErrorMessage("Aucune donnée disponible. Veuillez d'abord convertir et importer des fichiers depuis le tableau de bord.");
                return;
            }

            echo "<script>console.log('✓ Données disponibles, début du processus...');</script>";

            // 🆕 ÉTAPE 1 : NETTOYER AUTOMATIQUEMENT LES IMAGES
            echo "<script>console.log('🧹 ÉTAPE 1: Nettoyage automatique des images...');</script>";
            $nettoyageResult = $this->chargeModel->nettoyerGraphiquesPng();

            if ($nettoyageResult['success']) {
                echo "<script>console.log('✅ Nettoyage réussi: " . addslashes($nettoyageResult['message']) . "');</script>";
            } else {
                echo "<script>console.log('⚠️ Problème nettoyage: " . addslashes($nettoyageResult['message']) . "');</script>";
            }

            // ÉTAPE 2 : Analyser les données pour obtenir la charge par période
            echo "<script>console.log('📊 ÉTAPE 2: Analyse des données...');</script>";
            $resultatAnalyse = $this->chargeModel->analyserChargeParPeriode();

            if (isset($resultatAnalyse['error'])) {
                echo "<script>console.log('❌ ERREUR analyse: " . addslashes($resultatAnalyse['error']) . "');</script>";
                echo $this->chargeView->showErrorMessage($resultatAnalyse['error']);
                return;
            }

            echo "<script>console.log('✓ Analyse réussie');</script>";

            // ÉTAPE 3 : Formater les résultats pour l'affichage
            echo "<script>console.log('🔧 ÉTAPE 3: Formatage des résultats...');</script>";
            $resultatsFormattés = $this->chargeModel->formaterResultats($resultatAnalyse);
            echo "<script>console.log('✓ Formatage réussi');</script>";

            // ÉTAPE 4 : GÉNÉRATION DES NOUVEAUX GRAPHIQUES (après nettoyage)
            $chartPaths = [];
            if (isset($resultatsFormattés['graphiquesData'])) {
                echo "<script>console.log('🎨 ÉTAPE 4: Génération des nouveaux graphiques...');</script>";
                $chartPaths = $this->graphGenerator->generateAllCharts($resultatsFormattés['graphiquesData']);
                echo "<script>console.log('✓ Graphiques générés: " . count($chartPaths) . " fichiers');</script>";
            } else {
                echo "<script>console.log('⚠️ Pas de données graphiques disponibles');</script>";
            }

            // ÉTAPE 5 : Obtenir un résumé des données pour l'affichage
            $dataSummary = $this->dashboardModel->getDataSummary();
            $fileName = "Données de la base (" . $dataSummary['total_entries'] . " entrées)";

            echo "<script>console.log('🖼️ ÉTAPE 5: Affichage de la page avec " . count($chartPaths) . " graphiques');</script>";

            // Afficher les résultats de l'analyse de charge
            echo $this->chargeView->showChargeAnalysis($userInfo, $fileName, $resultatsFormattés, $chartPaths);

        } catch (\Exception $e) {
            echo "<script>console.log('💥 EXCEPTION: " . addslashes($e->getMessage()) . "');</script>";
            echo $this->chargeView->showErrorMessage("Une erreur est survenue : " . $e->getMessage());
        }

        echo "<script>console.log('=== FIN handleRequest ===');</script>";
    }

}