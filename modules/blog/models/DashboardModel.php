<?php
namespace modules\blog\models;

/**
 * Classe DashboardModel
 *
 * Cette classe gère les données nécessaires pour le tableau de bord
 * en déléguant les tâches aux modèles spécialisés.
 */
class DashboardModel {
    /**
     * @var ImportModel $importModel Instance pour récupérer les données
     */
    private $importModel;

    /**
     * @var LectureDossierModel $lectureDossierModel Instance pour la conversion
     */
    private $lectureDossierModel;

    /**
     * Constructeur du DashboardModel
     */
    public function __construct() {
        $this->importModel = new ImportModel();
        $this->lectureDossierModel = new LectureDossierModel();
    }

    /**
     * Récupère les informations de l'utilisateur
     *
     * @param string $userId ID de l'utilisateur
     * @return array Informations de l'utilisateur
     */
    public function getUserInfo($userId) {
        // Informations basiques de l'utilisateur
        return [
            'id' => $userId,
            'nom' => $userId // Utiliser l'ID comme nom par défaut
        ];
    }

    /**
     * Récupère toutes les données depuis la base de données
     *
     * @return array Données de la table Donnees
     */
    public function getAllData() {
        return $this->importModel->getAllData();
    }

    /**
     * Vérifie si des données sont présentes
     *
     * @return bool True si des données existent
     */
    public function hasData() {
        return $this->importModel->hasData();
    }

    /**
     * Récupère les statistiques des données pour le dashboard
     *
     * @return array Statistiques basiques
     */
    public function getDataSummary() {
        $donnees = $this->importModel->getAllData();

        if (empty($donnees)) {
            return [
                'total_entries' => 0,
                'date_debut' => null,
                'date_fin' => null,
                'processus_uniques' => 0
            ];
        }

        $dates = array_column($donnees, 'Date');
        $processus = array_unique(array_column($donnees, 'Processus'));

        return [
            'total_entries' => count($donnees),
            'date_debut' => min($dates),
            'date_fin' => max($dates),
            'processus_uniques' => count($processus)
        ];
    }

    /**
     * Lance le processus de conversion des fichiers MPP vers XLSX et importation en BD
     *
     * @return array Résultat du processus complet
     */
    public function processConversion() {
        $result = $this->lectureDossierModel->processAllFiles();

        // Forcer le rechargement des données après la conversion
        $this->importModel->refreshData();

        return $result;
    }

    /**
     * Vide la table de données
     *
     * @return array Résultat de l'opération
     */
    public function clearData() {
        $success = $this->importModel->clearTable();

        return [
            'success' => $success,
            'message' => $success ?
                "La table de données a été vidée avec succès." :
                "Erreur lors de la suppression des données."
        ];
    }

    /**
     * Force le rechargement des données depuis la base
     *
     * @return bool Succès du rechargement
     */
    public function refreshData() {
        return $this->importModel->refreshData();
    }

    /**
     * Récupère des données limitées pour l'affichage (compatibilité)
     *
     * @param int $limit Nombre d'entrées à retourner
     * @return array Données limitées
     */
    public function getRecentData($limit = 50) {
        $donnees = $this->importModel->getAllData();

        // Trier par date décroissante et limiter
        usort($donnees, function($a, $b) {
            return strcmp($b['Date'], $a['Date']);
        });

        return array_slice($donnees, 0, $limit);
    }

    /**
     * Obtenir la liste des fichiers MPP disponibles (pour info)
     *
     * @return array Liste des fichiers MPP
     */
    public function getMppFilesList() {
        return $this->lectureDossierModel->getMppFiles();
    }

    /**
     * Obtenir la liste des fichiers XLSX convertis (pour info)
     *
     * @return array Liste des fichiers XLSX
     */
    public function getXlsxFilesList() {
        return $this->lectureDossierModel->getXlsxFiles();
    }
}