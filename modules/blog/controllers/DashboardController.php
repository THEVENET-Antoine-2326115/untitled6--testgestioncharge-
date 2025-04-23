<?php
namespace modules\blog\controllers;

use modules\blog\models\DashboardModel;
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

    /**
     * Constructeur du DashboardController
     */
    public function __construct() {
        $this->model = new DashboardModel();
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

        // Récupérer et afficher les données du fichier Excel
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

            // Afficher le tableau de bord avec les données Excel
            echo $this->view->showDashboardWithExcel($userInfo, $fileName, $excelData);

        } catch (IOException | ReaderNotOpenedException $e) {
            echo $this->view->showErrorMessage("Erreur lors de la lecture du fichier Excel : " . $e->getMessage());
        } catch (\Exception $e) {
            echo $this->view->showErrorMessage("Une erreur est survenue : " . $e->getMessage());
        }
    }
}