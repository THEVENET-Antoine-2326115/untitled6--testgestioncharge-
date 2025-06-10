<?php
namespace modules\blog\controllers;

use modules\blog\models\DashboardModel;
use modules\blog\models\ChargeModel;
use modules\blog\models\GraphGeneratorModel;
use modules\blog\views\ChargeView;

/**
 * Classe ChargeController
 *
 * Cette classe gère les opérations liées à l'analyse de charge avec sélecteur de semaine.
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
        $this->graphGenerator = new GraphGeneratorModel();
        $this->chargeView = new ChargeView();
    }

    /**
     * Gère les actions liées à l'analyse de charge avec sélection de semaine
     *
     * @param string $action Action à exécuter
     */
    public function handleRequest($action = '') {
        echo "<script>console.log('=== DÉBUT handleRequest AVEC SÉLECTION SEMAINE ===');</script>";
        echo "<script>console.log('Action reçue: " . addslashes($action) . "');</script>";
        echo "<script>console.log('URL complète: " . addslashes($_SERVER['REQUEST_URI'] ?? 'N/A') . "');</script>";
        echo "<script>console.log('Paramètres GET: " . addslashes(json_encode($_GET)) . "');</script>";

        // Récupérer l'ID utilisateur de la session
        $userId = $_SESSION['user_id'] ?? 'Utilisateur';

        // Récupérer les informations de l'utilisateur
        $userInfo = $this->dashboardModel->getUserInfo($userId);

        try {
            // Vérifier si des données sont disponibles
            if (!$this->dashboardModel->hasData()) {
                echo "<script>console.log('❌ ERREUR: Aucune donnée disponible');</script>";
                echo $this->chargeView->showErrorMessage("Aucune donnée disponible. Veuillez d'abord convertir et importer des fichiers depuis le tableau de bord.");
                return;
            }

            echo "<script>console.log('✓ Données disponibles, début du processus...');</script>";

            // ÉTAPE 1 : Analyser les données pour obtenir toutes les semaines disponibles
            echo "<script>console.log('📊 ÉTAPE 1: Récupération des semaines disponibles...');</script>";
            $availableWeeks = $this->chargeModel->getAvailableWeeks();

            if (empty($availableWeeks)) {
                echo "<script>console.log('❌ ERREUR: Aucune semaine disponible');</script>";
                echo $this->chargeView->showErrorMessage("Aucune semaine disponible dans les données.");
                return;
            }

            echo "<script>console.log('✓ Semaines disponibles: " . count($availableWeeks) . "');</script>";

            // ÉTAPE 2 : Déterminer la semaine sélectionnée
            $selectedWeek = $this->determineSelectedWeek($availableWeeks);
            echo "<script>console.log('📅 Semaine sélectionnée: " . addslashes($selectedWeek) . "');</script>";

            // ÉTAPE 3 : Récupérer les données pour la semaine sélectionnée
            echo "<script>console.log('🔍 ÉTAPE 3: Récupération données pour la semaine...');</script>";
            $weeklyData = $this->chargeModel->getDailyDataForWeek($selectedWeek);

            if (isset($weeklyData['error'])) {
                echo "<script>console.log('❌ ERREUR récupération données semaine: " . addslashes($weeklyData['error']) . "');</script>";
                echo $this->chargeView->showErrorMessage($weeklyData['error']);
                return;
            }

            echo "<script>console.log('✓ Données semaine récupérées');</script>";

            // ÉTAPE 4 : Génération des graphiques pour la semaine sélectionnée
            echo "<script>console.log('🎨 ÉTAPE 4: Génération des graphiques pour la semaine...');</script>";
            $chartPaths = $this->graphGenerator->generateWeeklyCharts($weeklyData['graphiquesData']);
            echo "<script>console.log('✓ Graphiques générés: " . count($chartPaths) . " fichiers');</script>";

            // ÉTAPE 5 : Préparer les résultats pour l'affichage
            echo "<script>console.log('🔧 ÉTAPE 5: Préparation pour affichage...');</script>";

            // Analyser toutes les données pour le récapitulatif général
            $resultatAnalyseComplete = $this->chargeModel->analyserChargeParPeriode();
            $resultatsFormattés = $this->chargeModel->formaterResultats($resultatAnalyseComplete);

            // Obtenir un résumé des données pour l'affichage
            $dataSummary = $this->dashboardModel->getDataSummary();
            $fileName = "Données de la base (" . $dataSummary['total_entries'] . " entrées)";

            echo "<script>console.log('🖼️ ÉTAPE 6: Affichage de la page avec sélecteur de semaine');</script>";

            // Afficher les résultats avec le sélecteur de semaine
            echo $this->chargeView->showChargeAnalysis(
                $userInfo,
                $fileName,
                $resultatsFormattés,
                $availableWeeks,
                $selectedWeek,
                $chartPaths
            );

        } catch (\Exception $e) {
            echo "<script>console.log('💥 EXCEPTION: " . addslashes($e->getMessage()) . "');</script>";
            echo $this->chargeView->showErrorMessage("Une erreur est survenue : " . $e->getMessage());
        }

        echo "<script>console.log('=== FIN handleRequest AVEC SÉLECTION SEMAINE ===');</script>";
    }

    /**
     * 🆕 Détermine la semaine sélectionnée en fonction des paramètres URL
     *
     * @param array $availableWeeks Liste des semaines disponibles
     * @return string Identifiant de la semaine sélectionnée
     */
    private function determineSelectedWeek($availableWeeks) {
        // Récupérer le paramètre de semaine depuis l'URL
        $requestedWeek = $_GET['selected_week'] ?? '';

        echo "<script>console.log('Semaine demandée via URL: " . addslashes($requestedWeek) . "');</script>";

        // Vérifier si la semaine demandée existe dans les semaines disponibles
        if (!empty($requestedWeek)) {
            foreach ($availableWeeks as $week) {
                if ($week['value'] === $requestedWeek) {
                    echo "<script>console.log('✓ Semaine demandée trouvée et valide');</script>";
                    return $requestedWeek;
                }
            }
            echo "<script>console.log('⚠️ Semaine demandée non trouvée, utilisation de la semaine par défaut');</script>";
        }

        // Par défaut, utiliser la première semaine disponible (semaine courante ou la plus proche)
        $defaultWeek = $availableWeeks[0]['value'];
        echo "<script>console.log('Semaine par défaut sélectionnée: " . addslashes($defaultWeek) . "');</script>";

        return $defaultWeek;
    }
}