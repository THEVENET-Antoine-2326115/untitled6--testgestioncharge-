<?php
namespace modules\blog\models;

/**
 * Classe ImportModel
 *
 * Cette classe gère l'importation des données analysées vers la base de données.
 */
class ImportModel {
    /**
     * @var \PDO $db Connexion à la base de données
     */
    private $db;

    /**
     * @var ChargeModel $chargeModel Instance de ChargeModel pour l'analyse des données
     */
    private $chargeModel;

    /**
     * Constructeur de ImportModel
     */
    public function __construct() {
        // Connexion à la base de données via SingletonModel
        $this->db = SingletonModel::getInstance()->getConnection();
        $this->chargeModel = new ChargeModel();
    }

    /**
     * Importe les données d'un fichier Excel analysé vers la base de données
     *
     * @param array $excelData Données du fichier Excel
     * @return array Résultat de l'importation
     */
    public function importDataToDatabase($excelData) {
        // Analyser les données avec ChargeModel
        $analyseResult = $this->chargeModel->analyserChargeParPeriode($excelData);

        if (isset($analyseResult['error'])) {
            return [
                'success' => false,
                'message' => $analyseResult['error']
            ];
        }

        // Formater les résultats - cela nous donne exactement le format que nous voulons
        $resultatsFormattés = $this->chargeModel->formaterResultats($analyseResult);

        // Compter les entrées importées
        $importCount = 0;
        $errorCount = 0;

        // Préparer la requête d'insertion
        $stmt = $this->db->prepare("INSERT INTO Donnees (Date, Processus, Tache, Charge) VALUES (:date, :processus, :tache, :charge)");

        // Parcourir les données mensuelles formatées
        foreach ($resultatsFormattés['donneesMensuelles'] as $mois => $jours) {
            foreach ($jours as $jour) {
                // Ne pas importer les week-ends
                if (isset($jour['estWeekend']) && $jour['estWeekend']) {
                    continue;
                }

                // Convertir la date du format "dd/mm/yyyy" en "yyyy-mm-dd" pour la base de données
                $dateParts = explode('/', $jour['date']);
                if (count($dateParts) !== 3) {
                    continue; // Format de date invalide
                }

                $date = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];

                // Récupérer les processus et les tâches
                $processus = $jour['processus'] ?? '';
                $taches = $jour['taches'] ?? '';
                $charge = $jour['charge'] ?? 0;

                // Vérifier que la charge est un nombre
                if (!is_numeric($charge)) {
                    $charge = floatval(str_replace(',', '.', $charge));
                }

                try {
                    $stmt->bindParam(':date', $date);
                    $stmt->bindParam(':processus', $processus);
                    $stmt->bindParam(':tache', $taches);
                    $stmt->bindParam(':charge', $charge);
                    $stmt->execute();
                    $importCount++;
                } catch (\PDOException $e) {
                    $errorCount++;
                }
            }
        }

        return [
            'success' => true,
            'importCount' => $importCount,
            'errorCount' => $errorCount,
            'message' => "Importation terminée. $importCount entrées importées, $errorCount erreurs."
        ];
    }

    /**
     * Vide la table Donnees
     *
     * @return bool Succès de l'opération
     */
    public function clearTable() {
        try {
            $this->db->exec("TRUNCATE TABLE Donnees");
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Récupère les données importées
     *
     * @param int $limit Limite du nombre de résultats
     * @return array Données importées
     */
    public function getImportedData($limit = 50) {
        $query = "SELECT * FROM Donnees ORDER BY Date DESC LIMIT :limit";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}