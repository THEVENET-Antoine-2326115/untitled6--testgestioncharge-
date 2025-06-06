<?php
namespace modules\blog\controllers;

use modules\blog\models\DashboardModel;
use modules\blog\models\AjoutChargeModel;
use modules\blog\views\DashboardView;

/**
 * Classe DashboardController
 *
 * Cette classe sert de pont entre DashboardModel, AjoutChargeModel et DashboardView.
 * Elle s'adapte aux méthodes disponibles dans les modèles et gère l'ajout manuel de charges.
 */
class DashboardController {
    private $model;
    private $ajoutChargeModel;
    private $view;

    /**
     * Constructeur du DashboardController
     */
    public function __construct() {
        $this->model = new DashboardModel();
        $this->ajoutChargeModel = new AjoutChargeModel();
        $this->view = new DashboardView();
    }

    /**
     * Gère les actions liées au tableau de bord
     *
     * @param string $action Action à exécuter
     */
    public function handleRequest($action = '') {
        // Récupérer l'ID utilisateur de la session
        $userId = $_SESSION['user_id'] ?? 'Utilisateur';

        // Récupérer les informations de l'utilisateur
        $userInfo = $this->model->getUserInfo($userId);

        // VÉRIFIER SI C'EST UN AJOUT OU SUPPRESSION DE CHARGE (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postAction = $_POST['action'] ?? '';

            if ($postAction === 'add_charge') {
                $this->handleAddCharge($userInfo);
                return;
            } elseif ($postAction === 'delete_charge') {
                $this->handleDeleteCharge($userInfo);
                return;
            }
        }

        // Vérifier si une action spécifique est demandée
        $subAction = $_GET['subaction'] ?? '';

        switch ($subAction) {
            case 'import':
                $this->handleImport($userInfo);
                break;
            case 'clear_data':
                $this->handleClearData($userInfo);
                break;
            case 'convert_files':
                $this->handleConvertFiles($userInfo);
                break;
            case 'show_all_files':
                $this->handleShowAllFiles($userInfo);
                break;
            default:
                $this->handleDashboard($userInfo);
                break;
        }
    }

    /**
     * Gère la suppression d'une charge
     *
     * @param array $userInfo Informations de l'utilisateur
     */
    private function handleDeleteCharge($userInfo) {
        try {
            echo "<script>console.log('=== TRAITEMENT SUPPRESSION CHARGE ===');</script>";

            // Récupérer les données du formulaire
            $donnees = [
                'processus' => trim($_POST['processus'] ?? ''),
                'tache' => trim($_POST['tache'] ?? ''),
                'charge' => trim($_POST['charge'] ?? ''),
                'date' => trim($_POST['date'] ?? '')
            ];

            echo "<script>console.log('Données reçues pour suppression: " . addslashes(json_encode($donnees)) . "');</script>";

            // Supprimer la charge via le modèle
            $result = $this->ajoutChargeModel->supprimerCharge($donnees);

            if ($result['success']) {
                echo "<script>console.log('✅ Suppression réussie');</script>";

                // Forcer le rechargement des données dans ImportModel
                $this->model->refreshData();

                // Préparer les données et afficher avec le résultat de succès
                $dashboardData = $this->prepareDashboardData();
                echo $this->view->showDashboardWithResult($userInfo, $dashboardData, $result);
            } else {
                echo "<script>console.log('❌ Erreur suppression: " . addslashes($result['message']) . "');</script>";

                // Préparer les données et afficher avec le résultat d'erreur
                $dashboardData = $this->prepareDashboardData();
                echo $this->view->showDashboardWithResult($userInfo, $dashboardData, $result);
            }

        } catch (\Exception $e) {
            echo "<script>console.log('💥 Exception suppression charge: " . addslashes($e->getMessage()) . "');</script>";

            $result = [
                'success' => false,
                'message' => "Erreur lors de la suppression : " . $e->getMessage()
            ];

            $dashboardData = $this->prepareDashboardData();
            echo $this->view->showDashboardWithResult($userInfo, $dashboardData, $result);
        }
    }

    /**
     * 🆕 Gère l'ajout manuel d'une charge
     *
     * @param array $userInfo Informations de l'utilisateur
     */
    private function handleAddCharge($userInfo) {
        try {
            echo "<script>console.log('=== TRAITEMENT AJOUT CHARGE ===');</script>";

            // Récupérer les données du formulaire
            $donnees = [
                'processus' => trim($_POST['processus'] ?? ''),
                'tache' => trim($_POST['tache'] ?? ''),
                'charge' => trim($_POST['charge'] ?? ''),
                'date' => trim($_POST['date'] ?? '')
            ];

            echo "<script>console.log('Données reçues: " . addslashes(json_encode($donnees)) . "');</script>";

            // Ajouter la charge via le modèle
            $result = $this->ajoutChargeModel->ajouterCharge($donnees);

            if ($result['success']) {
                echo "<script>console.log('✅ Ajout réussi');</script>";

                // Forcer le rechargement des données dans ImportModel
                $this->model->refreshData();

                // Préparer les données et afficher avec le résultat de succès
                $dashboardData = $this->prepareDashboardData();
                echo $this->view->showDashboardWithResult($userInfo, $dashboardData, $result);
            } else {
                echo "<script>console.log('❌ Erreur ajout: " . addslashes($result['message']) . "');</script>";

                // Préparer les données et afficher avec le résultat d'erreur
                $dashboardData = $this->prepareDashboardData();
                echo $this->view->showDashboardWithResult($userInfo, $dashboardData, $result);
            }

        } catch (\Exception $e) {
            echo "<script>console.log('💥 Exception ajout charge: " . addslashes($e->getMessage()) . "');</script>";

            $result = [
                'success' => false,
                'message' => "Erreur lors de l'ajout : " . $e->getMessage()
            ];

            $dashboardData = $this->prepareDashboardData();
            echo $this->view->showDashboardWithResult($userInfo, $dashboardData, $result);
        }
    }

    /**
     * Affiche le tableau de bord principal
     *
     * @param array $userInfo Informations de l'utilisateur
     */
    private function handleDashboard($userInfo) {
        try {
            // Préparer les données pour la vue refactorisée
            $dashboardData = $this->prepareDashboardData();

            // Utiliser la méthode showDashboard de la vue refactorisée
            echo $this->view->showDashboard($userInfo, $dashboardData);

        } catch (\Exception $e) {
            echo $this->view->showErrorMessage("Une erreur est survenue : " . $e->getMessage());
        }
    }

    /**
     * Gère l'importation des données depuis la base vers la mémoire
     *
     * @param array $userInfo Informations de l'utilisateur
     */
    private function handleImport($userInfo) {
        try {
            // Utiliser la méthode refreshData() du modèle
            $success = $this->model->refreshData();

            $result = [
                'success' => $success,
                'message' => $success ?
                    "Les données ont été importées avec succès depuis la base de données." :
                    "Erreur lors de l'importation des données."
            ];

            // Préparer les données et afficher avec le résultat
            $dashboardData = $this->prepareDashboardData();
            echo $this->view->showDashboardWithResult($userInfo, $dashboardData, $result);

        } catch (\Exception $e) {
            echo $this->view->showErrorMessage("Erreur lors de l'importation : " . $e->getMessage());
        }
    }

    /**
     * Gère la conversion des fichiers MPP et leur importation en base
     *
     * @param array $userInfo Informations de l'utilisateur
     */
    private function handleConvertFiles($userInfo) {
        try {
            // Utiliser la méthode processConversion() du modèle
            $conversionResult = $this->model->processConversion();

            // Formater le message de résultat
            $summary = $conversionResult['summary'] ?? [];
            $message = "Conversion terminée:\n";
            $message .= "• Fichiers MPP trouvés: " . ($summary['mpp_found'] ?? 0) . "\n";
            $message .= "• Fichiers MPP convertis: " . ($summary['mpp_converted'] ?? 0) . "\n";
            $message .= "• Erreurs de conversion: " . ($summary['mpp_errors'] ?? 0) . "\n";
            $message .= "• Fichiers XLSX trouvés: " . ($summary['xlsx_found'] ?? 0) . "\n";
            $message .= "• Fichiers XLSX importés: " . ($summary['xlsx_imported'] ?? 0) . "\n";
            $message .= "• Erreurs d'importation: " . ($summary['xlsx_errors'] ?? 0);

            $result = [
                'success' => ($summary['mpp_converted'] ?? 0) > 0 || ($summary['xlsx_imported'] ?? 0) > 0,
                'message' => $message
            ];

            // Préparer les données et afficher avec le résultat
            $dashboardData = $this->prepareDashboardData();
            echo $this->view->showDashboardWithResult($userInfo, $dashboardData, $result);

        } catch (\Exception $e) {
            echo $this->view->showErrorMessage("Erreur lors de la conversion : " . $e->getMessage());
        }
    }

    /**
     * Gère la suppression des données
     *
     * @param array $userInfo Informations de l'utilisateur
     */
    private function handleClearData($userInfo) {
        try {
            // Utiliser la méthode clearData() du modèle
            $result = $this->model->clearData();

            // Préparer les données et afficher avec le résultat
            $dashboardData = $this->prepareDashboardData();
            echo $this->view->showDashboardWithResult($userInfo, $dashboardData, $result);

        } catch (\Exception $e) {
            echo $this->view->showErrorMessage("Erreur lors de la suppression des données : " . $e->getMessage());
        }
    }

    /**
     * Gère l'affichage de toutes les données
     *
     * @param array $userInfo Informations de l'utilisateur
     */
    private function handleShowAllFiles($userInfo) {
        try {
            // Préparer toutes les données pour l'affichage
            $allData = $this->prepareAllDataForDisplay();

            // Utiliser showAllData de la vue refactorisée
            echo $this->view->showAllData($userInfo, $allData);

        } catch (\Exception $e) {
            echo $this->view->showErrorMessage("Erreur lors de la récupération des données : " . $e->getMessage());
        }
    }

    /**
     * Prépare les données du dashboard au format attendu par la vue refactorisée
     *
     * @return array Données formatées pour DashboardView
     */
    private function prepareDashboardData() {
        $hasData = $this->model->hasData();
        $dataSummary = $this->model->getDataSummary();

        // Préparer les données d'affichage
        $displayData = [];
        $displayTitle = "Aucune donnée disponible";

        if ($hasData) {
            $recentData = $this->model->getRecentData(10);
            $displayData = [
                'Donnees' => [
                    'headers' => ['Processus', 'Tache', 'Charge', 'Date'],
                    'rows' => $recentData
                ]
            ];
            $displayTitle = "Données récentes (" . count($recentData) . " entrées)";
        }

        // Informations sur les fichiers
        $mppFiles = $this->model->getMppFilesList();
        $xlsxFiles = $this->model->getXlsxFilesList();

        // 🆕 AJOUTER LES SUGGESTIONS DE PROCESSUS
        $processusSuggestions = $this->ajoutChargeModel->getProcessusSuggestions();

        return [
            'summary' => $dataSummary,
            'files_info' => [
                'mpp_count' => count($mppFiles),
                'xlsx_count' => count($xlsxFiles)
            ],
            'display_data' => $displayData,
            'display_title' => $displayTitle,
            'processus_suggestions' => $processusSuggestions // 🆕 NOUVEAU
        ];
    }

    /**
     * Prépare toutes les données pour l'affichage complet
     *
     * @return array Données formatées pour l'affichage de toutes les données
     */
    private function prepareAllDataForDisplay() {
        $allData = $this->model->getAllData();
        $dataSummary = $this->model->getDataSummary();

        $displayData = [];
        if (!empty($allData)) {
            $displayData = [
                'Donnees' => [
                    'headers' => ['Processus', 'Tache', 'Charge', 'Date'],
                    'rows' => $allData
                ]
            ];
        }

        // Informations sur les fichiers
        $mppFiles = $this->model->getMppFilesList();
        $xlsxFiles = $this->model->getXlsxFilesList();

        // 🆕 AJOUTER LES SUGGESTIONS DE PROCESSUS
        $processusSuggestions = $this->ajoutChargeModel->getProcessusSuggestions();

        return [
            'summary' => $dataSummary,
            'files_info' => [
                'mpp_count' => count($mppFiles),
                'xlsx_count' => count($xlsxFiles)
            ],
            'display_data' => $displayData,
            'display_title' => "Toutes les données (" . count($allData) . " entrées)",
            'processus_suggestions' => $processusSuggestions // 🆕 NOUVEAU
        ];
    }
}