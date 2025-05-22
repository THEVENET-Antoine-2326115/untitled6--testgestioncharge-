<?php
namespace modules\blog\models;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;

/**
 * Classe ExcelToBdModel
 *
 * Cette classe gère l'importation des données d'un fichier Excel de planification
 * vers la base de données. Elle traite les données de tâches et d'affectations
 * pour les stocker dans la table Donnees.
 */
class ExcelToBdModel {
    /**
     * @var \PDO $db Connexion à la base de données
     */
    private $db;

    /**
     * Constructeur de ExcelToBdModel
     */
    public function __construct() {
        $this->db = SingletonModel::getInstance()->getConnection();
    }

    /**
     * Importe les données d'un fichier XLSX de planification dans la base de données
     *
     * @param string $filePath Chemin vers le fichier XLSX
     * @return array Résultat de l'importation
     */
    public function importExcelToDatabase($filePath) {
        try {
            // Vérifier que le fichier existe
            if (!file_exists($filePath)) {
                return [
                    'success' => false,
                    'message' => "Le fichier n'existe pas : " . $filePath,
                    'file' => basename($filePath)
                ];
            }

            // Lire le fichier Excel
            $excelData = $this->readExcelFile($filePath);

            if (!$excelData) {
                return [
                    'success' => false,
                    'message' => "Impossible de lire le fichier Excel",
                    'file' => basename($filePath)
                ];
            }

            // Extraire les données des feuilles nécessaires
            $taches = $this->extractTasksData($excelData);
            $affectations = $this->extractAssignmentsData($excelData);

            if (empty($taches)) {
                return [
                    'success' => false,
                    'message' => "Aucune tâche trouvée dans le fichier",
                    'file' => basename($filePath)
                ];
            }

            // Croiser les données et calculer les attributions par jour
            $donnees = $this->calculateDailyAssignments($taches, $affectations);

            // Importer les données dans la base
            $importResult = $this->insertDataToDatabase($donnees);

            return [
                'success' => true,
                'message' => $importResult['message'],
                'file' => basename($filePath),
                'importCount' => $importResult['importCount'],
                'errorCount' => $importResult['errorCount']
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Erreur lors de l'importation : " . $e->getMessage(),
                'file' => basename($filePath)
            ];
        }
    }

    /**
     * Lit un fichier Excel et retourne son contenu
     *
     * @param string $filePath Chemin vers le fichier Excel
     * @return array|false Données du fichier Excel ou false en cas d'erreur
     */
    private function readExcelFile($filePath) {
        try {
            $reader = ReaderEntityFactory::createReaderFromFile($filePath);
            $reader->open($filePath);

            $data = [];
            $sheetIndex = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                $sheetData = [];
                $headers = [];
                $isFirstRow = true;

                foreach ($sheet->getRowIterator() as $row) {
                    $rowData = $row->toArray();

                    if ($isFirstRow) {
                        $headers = $rowData;
                        $isFirstRow = false;
                    } else {
                        $formattedRow = [];
                        foreach ($rowData as $cellIndex => $cellValue) {
                            if (isset($headers[$cellIndex])) {
                                $formattedRow[$headers[$cellIndex]] = $cellValue;
                            }
                        }
                        $sheetData[] = $formattedRow;
                    }
                }

                $data[$sheetIndex] = [
                    'name' => $sheet->getName(),
                    'headers' => $headers,
                    'rows' => $sheetData
                ];
                $sheetIndex++;
            }

            $reader->close();
            return $data;

        } catch (IOException | ReaderNotOpenedException $e) {
            error_log("Erreur lecture Excel : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Extrait les données des tâches de la première feuille
     *
     * @param array $excelData Données du fichier Excel
     * @return array Données des tâches avec dates
     */
    private function extractTasksData($excelData) {
        // La première feuille (index 0) contient les tâches
        if (!isset($excelData[0]) || !isset($excelData[0]['rows'])) {
            return [];
        }

        $taches = [];
        foreach ($excelData[0]['rows'] as $row) {
            // Vérifier que les colonnes nécessaires existent
            if (!isset($row['Nom']) || !isset($row['Début']) || !isset($row['Fin'])) {
                continue;
            }

            // Ignorer les lignes vides ou invalides
            if (empty($row['Nom']) || empty($row['Début']) || empty($row['Fin'])) {
                continue;
            }

            $taches[] = [
                'nom' => trim($row['Nom']),
                'debut' => $this->parseDate($row['Début']),
                'fin' => $this->parseDate($row['Fin'])
            ];
        }

        return $taches;
    }

    /**
     * Extrait les données d'affectation de la troisième feuille
     *
     * @param array $excelData Données du fichier Excel
     * @return array Données des affectations
     */
    private function extractAssignmentsData($excelData) {
        // La troisième feuille (index 2) contient les affectations
        if (!isset($excelData[2]) || !isset($excelData[2]['rows'])) {
            return [];
        }

        $affectations = [];
        foreach ($excelData[2]['rows'] as $row) {
            // Vérifier que les colonnes nécessaires existent
            if (!isset($row['Nom de la tâche']) || !isset($row['Nom de la ressource']) || !isset($row['Capacité'])) {
                continue;
            }

            // Ignorer les lignes vides
            if (empty($row['Nom de la tâche']) || empty($row['Nom de la ressource'])) {
                continue;
            }

            $capacite = $this->parseCapacity($row['Capacité']);

            $affectations[] = [
                'tache' => trim($row['Nom de la tâche']),
                'ressource' => trim($row['Nom de la ressource']),
                'capacite' => $capacite
            ];
        }

        return $affectations;
    }

    /**
     * Calcule les attributions quotidiennes en croisant tâches et affectations
     *
     * @param array $taches Liste des tâches avec dates
     * @param array $affectations Liste des affectations avec capacités
     * @return array Données formatées pour insertion en base
     */
    private function calculateDailyAssignments($taches, $affectations) {
        $donnees = [];

        // Pour chaque tâche, calculer les jours d'attribution
        foreach ($taches as $tache) {
            // Trouver les affectations correspondantes à cette tâche
            $affectationsTache = array_filter($affectations, function($affectation) use ($tache) {
                return $affectation['tache'] === $tache['nom'];
            });

            // Si aucune affectation trouvée, créer une entrée par défaut
            if (empty($affectationsTache)) {
                $affectationsTache = [[
                    'tache' => $tache['nom'],
                    'ressource' => 'Non assigné',
                    'capacite' => 0
                ]];
            }

            // Pour chaque affectation de cette tâche
            foreach ($affectationsTache as $affectation) {
                // Calculer tous les jours ouvrés entre début et fin
                $joursOuvres = $this->getWorkingDaysBetween($tache['debut'], $tache['fin']);

                // Créer une entrée pour chaque jour ouvré avec la charge complète
                foreach ($joursOuvres as $jour) {
                    $donnees[] = [
                        'processus' => substr($affectation['ressource'], 0, 40), // Limité à 40 caractères
                        'tache' => substr($tache['nom'], 0, 200), // Limité à 200 caractères
                        'charge' => $affectation['capacite'], // Charge complète pour chaque jour
                        'date' => $jour->format('Y-m-d')
                    ];
                }
            }
        }

        return $donnees;
    }

    /**
     * Insère les données dans la base de données
     *
     * @param array $donnees Données à insérer
     * @return array Résultat de l'insertion
     */
    private function insertDataToDatabase($donnees) {
        $stmt = $this->db->prepare("INSERT INTO Donnees (Processus, Tache, Charge, Date) VALUES (:processus, :tache, :charge, :date)");

        $importCount = 0;
        $errorCount = 0;

        foreach ($donnees as $donnee) {
            try {
                $stmt->bindParam(':processus', $donnee['processus']);
                $stmt->bindParam(':tache', $donnee['tache']);
                $stmt->bindParam(':charge', $donnee['charge']);
                $stmt->bindParam(':date', $donnee['date']);
                $stmt->execute();
                $importCount++;
            } catch (\PDOException $e) {
                $errorCount++;
                error_log("Erreur insertion BD: " . $e->getMessage() . " - Données: " . json_encode($donnee));
            }
        }

        return [
            'importCount' => $importCount,
            'errorCount' => $errorCount,
            'message' => "Importation terminée. $importCount entrées importées, $errorCount erreurs."
        ];
    }

    /**
     * Parse une date depuis le format du fichier Excel
     *
     * @param string $dateStr Date sous forme de chaîne
     * @return \DateTime|null Objet DateTime ou null si parsing échoue
     */
    private function parseDate($dateStr) {
        try {
            // Format attendu: "07 Avril 2025 09:00"
            $moisFr = [
                'Janvier' => 'January', 'Février' => 'February', 'Mars' => 'March',
                'Avril' => 'April', 'Mai' => 'May', 'Juin' => 'June',
                'Juillet' => 'July', 'Août' => 'August', 'Septembre' => 'September',
                'Octobre' => 'October', 'Novembre' => 'November', 'Décembre' => 'December'
            ];

            // Remplacer le mois français par l'anglais
            $dateEn = $dateStr;
            foreach ($moisFr as $fr => $en) {
                $dateEn = str_replace($fr, $en, $dateEn);
            }

            return new \DateTime($dateEn);

        } catch (\Exception $e) {
            error_log("Erreur parsing date '$dateStr': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse la capacité depuis le format du fichier Excel
     *
     * @param string $capacityStr Capacité sous forme de chaîne (ex: "20%")
     * @return float Capacité en décimal (ex: 0.2)
     */
    private function parseCapacity($capacityStr) {
        // Enlever le symbole % et convertir en décimal
        $capacity = str_replace('%', '', trim($capacityStr));
        return floatval($capacity) / 100;
    }

    /**
     * Obtient tous les jours ouvrés entre deux dates (exclut weekends)
     *
     * @param \DateTime $debut Date de début
     * @param \DateTime $fin Date de fin
     * @return array Liste des jours ouvrés
     */
    private function getWorkingDaysBetween($debut, $fin) {
        if (!$debut || !$fin) {
            return [];
        }

        $joursOuvres = [];
        $current = clone $debut;

        while ($current <= $fin) {
            // Exclure samedi (6) et dimanche (7)
            if ($current->format('N') < 6) {
                $joursOuvres[] = clone $current;
            }
            $current->add(new \DateInterval('P1D'));
        }

        return $joursOuvres;
    }
}