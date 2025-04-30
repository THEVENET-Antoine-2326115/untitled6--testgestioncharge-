<?php
namespace modules\blog\controllers;

use modules\blog\models\DashboardModel;
use modules\blog\models\ImportModel;
use modules\blog\views\DashboardView;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;

/**
 * Classe DashboardController
 *
 * Cette classe gère les opérations liées au tableau de bord.
 */
class DashboardController {
    private $model;
    private $view;
    private $importModel;

    /**
     * Constructeur du DashboardController
     */
    public function __construct() {
        $this->model = new DashboardModel();
        $this->view = new DashboardView();
        $this->importModel = new ImportModel();
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

        // Vérifier si une action spécifique est demandée
        $subAction = $_GET['subaction'] ?? '';

        if ($subAction === 'import') {
            $this->handleImport($userInfo);
            return;
        } elseif ($subAction === 'clear_data') {
            $this->handleClearData($userInfo);
            return;
        } elseif ($subAction === 'show_all_files') {
            $this->handleShowAllFiles($userInfo);
            return;
        }

        // Récupérer et afficher les données du fichier Excel par défaut
        try {
            // Obtenir le chemin du fichier Excel par défaut
            $filePath = $this->model->getDefaultExcelFile();
            $fileName = $this->model->getDefaultExcelFileName();

            if (!$filePath) {
                echo $this->view->showErrorMessage("Aucun fichier Excel disponible.");
                return;
            }

            // Lire les données du fichier Excel
            $excelData = $this->model->readExcelFile($filePath);

            // Récupérer la liste des fichiers convertis disponibles
            $convertedFiles = $this->model->getConvertedExcelFiles();

            // Récupérer les dernières données importées (s'il y en a)
            $importedData = $this->importModel->getImportedData(10);

            // Afficher le tableau de bord avec les données Excel
            echo $this->view->showDashboardWithExcel($userInfo, $fileName, $excelData, $importedData, null, $convertedFiles);

        } catch (IOException | ReaderNotOpenedException $e) {
            echo $this->view->showErrorMessage("Erreur lors de la lecture du fichier Excel : " . $e->getMessage());
        } catch (\Exception $e) {
            echo $this->view->showErrorMessage("Une erreur est survenue : " . $e->getMessage());
        }
    }

    /**
     * Gère l'affichage de tous les fichiers convertis
     *
     * @param array $userInfo Informations de l'utilisateur
     */
    private function handleShowAllFiles($userInfo) {
        try {
            // Lire tous les fichiers Excel convertis
            $allExcelData = $this->model->readAllConvertedExcelFiles();

            if (empty($allExcelData)) {
                echo $this->view->showErrorMessage("Aucun fichier Excel converti n'est disponible.");
                return;
            }

            // Récupérer la liste des fichiers convertis disponibles
            $convertedFiles = $this->model->getConvertedExcelFiles();

            // Afficher le tableau de bord avec tous les fichiers
            echo $this->view->showDashboardWithAllExcelFiles($userInfo, $allExcelData, $convertedFiles);

        } catch (\Exception $e) {
            echo $this->view->showErrorMessage("Erreur lors de la lecture des fichiers Excel : " . $e->getMessage());
        }
    }

    /**
     * Gère l'importation des données
     *
     * @param array $userInfo Informations de l'utilisateur
     */
    private function handleImport($userInfo) {
        try {
            // Obtenir le chemin du fichier Excel à importer (via GET ou par défaut)
            $filePath = $_GET['file'] ?? $this->model->getDefaultExcelFile();
            $fileName = basename($filePath);

            if (!$filePath || !file_exists($filePath)) {
                echo $this->view->showErrorMessage("Le fichier spécifié n'existe pas.");
                return;
            }

            // Lire les données du fichier Excel
            $excelData = $this->model->readExcelFile($filePath);

            // Importer les données
            $result = $this->importModel->importDataToDatabase($excelData);

            // Récupérer les dernières données importées
            $importedData = $this->importModel->getImportedData(10);

            // Récupérer la liste des fichiers convertis disponibles
            $convertedFiles = $this->model->getConvertedExcelFiles();

            // Afficher le tableau de bord avec le message de résultat
            echo $this->view->showDashboardWithExcel($userInfo, $fileName, $excelData, $importedData, $result, $convertedFiles);

        } catch (\Exception $e) {
            echo $this->view->showErrorMessage("Erreur lors de l'importation : " . $e->getMessage());
        }
    }

    /**
     * Gère la suppression des données
     *
     * @param array $userInfo Informations de l'utilisateur
     */
    private function handleClearData($userInfo) {
        try {
            // Vider la table
            $success = $this->importModel->clearTable();

            $result = [
                'success' => $success,
                'message' => $success ? "La table de données a été vidée avec succès." : "Erreur lors de la suppression des données."
            ];

            // Obtenir le chemin du fichier Excel par défaut
            $filePath = $this->model->getDefaultExcelFile();
            $fileName = $this->model->getDefaultExcelFileName();

            // Lire les données du fichier Excel
            $excelData = $this->model->readExcelFile($filePath);

            // Récupérer les dernières données importées (devraient être vides maintenant)
            $importedData = $this->importModel->getImportedData(10);

            // Récupérer la liste des fichiers convertis disponibles
            $convertedFiles = $this->model->getConvertedExcelFiles();

            // Afficher le tableau de bord avec le message de résultat
            echo $this->view->showDashboardWithExcel($userInfo, $fileName, $excelData, $importedData, $result, $convertedFiles);

        } catch (\Exception $e) {
            echo $this->view->showErrorMessage("Erreur lors de la suppression des données : " . $e->getMessage());
        }
    }
}