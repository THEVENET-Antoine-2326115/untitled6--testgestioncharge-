<?php
namespace modules\blog\models;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;

/**
 * Classe DashboardModel
 *
 * Cette classe gère les données nécessaires pour le tableau de bord.
 */
class DashboardModel {
    /**
     * @var \PDO $db Connexion à la base de données
     */
    private $db;

    /**
     * Constructeur du DashboardModel
     */
    public function __construct() {
        // Connexion à la base de données via SingletonModel
        $this->db = SingletonModel::getInstance()->getConnection();
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
     * Obtenir le chemin du fichier Excel par défaut
     *
     * @return string|null Chemin du fichier par défaut ou null si aucun fichier disponible
     */
    public function getDefaultExcelFile() {
        $defaultFile = 'uploads/planning stage information Antoine Thévenet test 20250418.xlsx';

        if (file_exists($defaultFile)) {
            return $defaultFile;
        }

        return null;
    }

    /**
     * Obtenir le nom du fichier Excel par défaut
     *
     * @return string|null Nom du fichier par défaut ou null si aucun fichier disponible
     */
    public function getDefaultExcelFileName() {
        return 'planning stage information Antoine Thévenet test 20250418.xlsx';
    }

    /**
     * Lire un fichier Excel et retourner son contenu sous forme de tableau
     *
     * @param string $filePath Chemin vers le fichier Excel
     * @return array Données du fichier Excel
     * @throws IOException Si le fichier ne peut pas être ouvert
     * @throws ReaderNotOpenedException Si le lecteur n'est pas ouvert
     */
    public function readExcelFile($filePath) {
        // Vérifier si le fichier existe
        if (!file_exists($filePath)) {
            throw new IOException("Le fichier $filePath n'existe pas.");
        }

        // Créer le lecteur en fonction de l'extension du fichier
        $reader = ReaderEntityFactory::createReaderFromFile($filePath);
        $reader->open($filePath);

        $data = [];

        // Lire chaque feuille du fichier
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheetData = [];
            $headers = [];
            $isFirstRow = true;

            // Lire chaque ligne de la feuille
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $rowData = $row->toArray();

                // Si c'est la première ligne, on considère que ce sont les en-têtes
                if ($isFirstRow) {
                    $headers = $rowData;
                    $isFirstRow = false;
                } else {
                    // Associer les valeurs aux en-têtes
                    $formattedRow = [];
                    foreach ($rowData as $cellIndex => $cellValue) {
                        if (isset($headers[$cellIndex])) {
                            $formattedRow[$headers[$cellIndex]] = $cellValue;
                        } else {
                            $formattedRow["Colonne_$cellIndex"] = $cellValue;
                        }
                    }
                    $sheetData[] = $formattedRow;
                }
            }

            $data[$sheet->getName()] = [
                'headers' => $headers,
                'rows' => $sheetData
            ];
        }

        $reader->close();
        return $data;
    }
}