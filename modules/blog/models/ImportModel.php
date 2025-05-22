<?php
namespace modules\blog\models;

/**
 * Classe ImportModel
 *
 * Cette classe gère uniquement la récupération des données depuis la table Donnees
 * et les stocke en mémoire pour être réutilisées par d'autres classes.
 */
class ImportModel {
    /**
     * @var \PDO $db Connexion à la base de données
     */
    private $db;

    /**
     * @var array $donnees Données stockées en mémoire
     */
    private $donnees = [];

    /**
     * @var bool $dataLoaded Indique si les données ont été chargées
     */
    private $dataLoaded = false;

    /**
     * Constructeur de ImportModel
     */
    public function __construct() {
        // Connexion à la base de données via SingletonModel
        $this->db = SingletonModel::getInstance()->getConnection();
    }

    /**
     * Charge toutes les données de la table Donnees en mémoire
     *
     * @param bool $forceReload Force le rechargement même si déjà chargé
     * @return bool Succès du chargement
     */
    public function loadAllData($forceReload = false) {
        // Si déjà chargé et pas de force reload, ne pas recharger
        if ($this->dataLoaded && !$forceReload) {
            return true;
        }

        try {
            $query = "SELECT Processus, Tache, Charge, Date FROM Donnees ORDER BY Date ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            $this->donnees = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->dataLoaded = true;

            return true;
        } catch (\PDOException $e) {
            error_log("Erreur chargement données: " . $e->getMessage());
            $this->donnees = [];
            $this->dataLoaded = false;
            return false;
        }
    }

    /**
     * Retourne toutes les données chargées
     *
     * @return array Toutes les données de la table Donnees
     */
    public function getAllData() {
        // Charger les données si pas encore fait
        if (!$this->dataLoaded) {
            $this->loadAllData();
        }

        return $this->donnees;
    }

    /**
     * Vide la table Donnees
     *
     * @return bool Succès de l'opération
     */
    public function clearTable() {
        try {
            $this->db->exec("TRUNCATE TABLE Donnees");

            // Vider aussi les données en mémoire
            $this->donnees = [];
            $this->dataLoaded = true; // Marquer comme chargé car maintenant vide

            return true;
        } catch (\PDOException $e) {
            error_log("Erreur vidage table: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Indique si des données sont chargées en mémoire
     *
     * @return bool True si des données sont chargées
     */
    public function hasData() {
        return $this->dataLoaded && !empty($this->donnees);
    }

    /**
     * Force le rechargement des données depuis la base
     *
     * @return bool Succès du rechargement
     */
    public function refreshData() {
        return $this->loadAllData(true);
    }
}