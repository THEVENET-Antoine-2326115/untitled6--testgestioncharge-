<?php
namespace modules\blog\controllers;

use modules\blog\models\ProjectFileProcessorModel;

/**
 * Classe MppProcessorController
 *
 * Cette classe gère les opérations de traitement automatique des fichiers MPP.
 */
class MppProcessorController
{
    /**
     * @var ProjectFileProcessorModel $model Instance du modèle de traitement
     */
    private $model;

    /**
     * Constructeur du MppProcessorController
     */
    public function __construct()
    {
        $this->model = new ProjectFileProcessorModel();
    }

    /**
     * Gère les requêtes liées au traitement des fichiers MPP
     *
     * @return void
     */
    public function handleRequest()
    {
        // Lancer le traitement automatique
        $result = $this->model->processProjectFiles();

        // Pour l'interface backend, on peut simplement retourner un JSON
        header('Content-Type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Exécute le traitement à partir d'une tâche cron ou autre processus automatisé
     *
     * @return array Résultats du traitement
     */
    public function processBatch()
    {
        return $this->model->processProjectFiles();
    }
}