<?php
namespace modules\blog\models;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;

/**
 * Classe DashboardModel test
 *
 * Cette classe gère les données nécessaires pour le tableau de bord.
 */
class DashboardModel {
    /**
     * @var \PDO $db Connexion à la base de données
     */
    private $db;

    /**
     * @var string Dossier contenant les fichiers XLSX convertis
     */
    private $convertedFilesFolder = 'C:\Users\a.thevenet\Documents\PROCESSFILETEMP';

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
     * Obtenir la liste de tous les fichiers XLSX dans le dossier des fichiers convertis
     *
     * @return array Liste des chemins des fichiers XLSX
     */
    public function getConvertedExcelFiles() {
        $excelFiles = [];

        // Vérifier si le dossier existe
        if (!is_dir($this->convertedFilesFolder)) {
            return $excelFiles;
        }

        // Parcourir le dossier pour trouver les fichiers XLSX
        $files = scandir($this->convertedFilesFolder);
        foreach ($files as $file) {
            // Ignorer les dossiers spéciaux
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $this->convertedFilesFolder . DIRECTORY_SEPARATOR . $file;

            // Vérifier si c'est un fichier et s'il a l'extension .xlsx
            if (is_file($filePath) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'xlsx') {
                $excelFiles[] = $filePath;
            }
        }

        return $excelFiles;
    }

    /**
     * Obtenir le chemin du fichier Excel par défaut
     *
     * Cette méthode retourne maintenant le premier fichier XLSX trouvé dans le dossier
     * des fichiers convertis, ou le fichier par défaut si aucun fichier converti n'est disponible.
     *
     * @return string|null Chemin du fichier par défaut ou null si aucun fichier disponible
     */
    public function getDefaultExcelFile() {
        // Essayer d'abord de trouver des fichiers XLSX convertis
        $convertedFiles = $this->getConvertedExcelFiles();

        if (!empty($convertedFiles)) {
            // Retourner le premier fichier XLSX trouvé
            return $convertedFiles[0];
        }

        // Si aucun fichier converti n'est trouvé, essayer le fichier par défaut
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
        $filePath = $this->getDefaultExcelFile();

        if ($filePath) {
            return basename($filePath);
        }

        return null;
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

    /**
     * Lire tous les fichiers Excel convertis et retourner leur contenu fusionné
     *
     * @return array Données fusionnées de tous les fichiers Excel
     */
    public function readAllConvertedExcelFiles() {
        $allData = [];
        $excelFiles = $this->getConvertedExcelFiles();

        foreach ($excelFiles as $filePath) {
            try {
                $fileName = basename($filePath);
                $fileData = $this->readExcelFile($filePath);

                // Ajouter les données de ce fichier à l'ensemble des données
                // en utilisant le nom du fichier comme clé pour distinguer les sources
                $allData[$fileName] = $fileData;
            } catch (\Exception $e) {
                // Logger l'erreur mais continuer avec les autres fichiers
                error_log("Erreur lors de la lecture du fichier $filePath: " . $e->getMessage());
            }
        }

        return $allData;
    }
}