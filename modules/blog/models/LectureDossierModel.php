<?php
namespace modules\blog\models;

/**
 * Classe LectureDossierModel
 *
 * Cette classe gère la lecture automatique des dossiers de planification,
 * la conversion des fichiers MPP en XLSX, et l'importation des données
 * dans la base de données.
 */
class LectureDossierModel {

    /**
     * Dossier source contenant les fichiers MPP (à la racine du projet)
     */
    const MPP_SOURCE_FOLDER = __DIR__ . '/../../../uploads';

    /**
     * Dossier de destination pour les fichiers XLSX convertis
     */
    const XLSX_OUTPUT_FOLDER = __DIR__ . '/../../../converted';

    /**
     * Instance du converteur MPP
     */
    private $mppConverter;

    /**
     * Instance du transporteur XLSX vers BD
     */
    private $excelToBdModel;

    /**
     * Constructeur
     */
    public function __construct() {
        $this->console_log("=== INITIALISATION DE LectureDossierModel ===");
        $this->console_log("Dossier source MPP: " . realpath(self::MPP_SOURCE_FOLDER));
        $this->console_log("Dossier destination XLSX: " . realpath(self::XLSX_OUTPUT_FOLDER));

        // Initialiser le converteur MPP avec le dossier de destination
        $this->mppConverter = new MppConverterModel(self::XLSX_OUTPUT_FOLDER);

        // Initialiser le transporteur XLSX vers BD
        $this->excelToBdModel = new ExcelToBdModel();

        // S'assurer que les dossiers existent
        $this->ensureDirectoriesExist();

        $this->console_log("=== INITIALISATION TERMINÉE ===");
    }

    /**
     * Lance le processus complet de lecture, conversion et importation
     *
     * @return array Résultat détaillé du processus
     */
    public function processAllFiles() {
        $this->console_log("=== DÉBUT DU PROCESSUS COMPLET ===");

        $results = [
            'conversion' => [],
            'importation' => [],
            'summary' => [
                'mpp_found' => 0,
                'mpp_converted' => 0,
                'mpp_errors' => 0,
                'xlsx_found' => 0,
                'xlsx_imported' => 0,
                'xlsx_errors' => 0
            ]
        ];

        $this->log_message("=== DÉBUT DU PROCESSUS COMPLET ===");

        // Étape 1 : Parcourir le dossier source et convertir les fichiers MPP
        $this->console_log("=== ÉTAPE 1: CONVERSION DES FICHIERS MPP ===");
        $this->log_message("Étape 1 : Conversion des fichiers MPP");
        $conversionResults = $this->convertMppFiles();
        $results['conversion'] = $conversionResults;

        // Étape 2 : Parcourir le dossier de destination et importer les fichiers XLSX
        $this->console_log("=== ÉTAPE 2: IMPORTATION DES FICHIERS XLSX ===");
        $this->log_message("Étape 2 : Importation des fichiers XLSX");
        $importationResults = $this->importXlsxFiles();
        $results['importation'] = $importationResults;

        // Calculer le résumé
        $results['summary'] = $this->calculateSummary($conversionResults, $importationResults);

        $this->console_log("=== FIN DU PROCESSUS COMPLET ===");
        $this->log_message("=== FIN DU PROCESSUS COMPLET ===");
        $this->displaySummary($results['summary']);

        return $results;
    }

    /**
     * Parcourt le dossier source et convertit tous les fichiers MPP
     *
     * @return array Résultats de la conversion
     */
    private function convertMppFiles() {
        $results = [];

        $this->console_log("=== DÉBUT convertMppFiles() ===");
        $this->console_log("Parcours du dossier source : " . self::MPP_SOURCE_FOLDER);
        $this->log_message("Parcours du dossier source : " . self::MPP_SOURCE_FOLDER);

        // Vérifier que le dossier source existe
        if (!is_dir(self::MPP_SOURCE_FOLDER)) {
            $error = "ERREUR : Le dossier source n'existe pas : " . self::MPP_SOURCE_FOLDER;
            $this->console_log($error);
            $this->log_message($error);
            return $results;
        }

        $this->console_log("Dossier source trouvé et accessible");

        // Lire tous les fichiers du dossier
        $files = scandir(self::MPP_SOURCE_FOLDER);
        $this->console_log("Nombre de fichiers/dossiers trouvés: " . count($files));

        foreach ($files as $file) {
            // Ignorer les dossiers spéciaux
            if ($file === '.' || $file === '..') {
                continue;
            }

            $this->console_log("Examen du fichier: " . $file);

            $filePath = self::MPP_SOURCE_FOLDER . DIRECTORY_SEPARATOR . $file;

            // Vérifier si c'est un fichier
            if (!is_file($filePath)) {
                $this->console_log("Ignoré (pas un fichier) : " . $file);
                $this->log_message("Ignoré (pas un fichier) : " . $file);
                continue;
            }

            // Déterminer le type de fichier
            $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $this->console_log("Extension détectée pour " . $file . ": " . $fileExtension);

            if ($fileExtension === 'mpp') {
                $this->console_log("✓ Fichier MPP trouvé : " . $file);
                $this->log_message("Fichier MPP trouvé : " . $file);

                // Lancer la conversion avec MppConverterModel
                $this->console_log("Lancement de la conversion pour: " . $file);
                $conversionResult = $this->mppConverter->convertMppToXlsx($filePath);

                $results[$file] = [
                    'type' => 'mpp',
                    'path' => $filePath,
                    'conversion' => $conversionResult
                ];

                if ($conversionResult['success']) {
                    $this->console_log("✓ Conversion réussie : " . $file . " -> " . $conversionResult['outputFile']);
                    $this->log_message("✓ Conversion réussie : " . $file . " -> " . $conversionResult['outputFile']);
                } else {
                    $this->console_log("✗ Erreur conversion : " . $file . " - " . $conversionResult['message']);
                    $this->log_message("✗ Erreur conversion : " . $file . " - " . $conversionResult['message']);
                }
            } else {
                $this->console_log("Fichier ignoré (pas MPP) : " . $file . " (extension: " . $fileExtension . ")");
                $this->log_message("Fichier ignoré (pas MPP) : " . $file . " (extension: " . $fileExtension . ")");
                $results[$file] = [
                    'type' => $fileExtension,
                    'path' => $filePath,
                    'ignored' => true,
                    'reason' => 'Not an MPP file'
                ];
            }
        }

        $this->console_log("=== FIN convertMppFiles() ===");
        $this->console_log("Nombre de résultats de conversion: " . count($results));

        return $results;
    }

    /**
     * Parcourt le dossier de destination et importe tous les fichiers XLSX
     *
     * @return array Résultats de l'importation
     */
    private function importXlsxFiles() {
        $results = [];

        $this->console_log("=== DÉBUT importXlsxFiles() ===");
        $this->console_log("Parcours du dossier de destination : " . self::XLSX_OUTPUT_FOLDER);
        $this->log_message("Parcours du dossier de destination : " . self::XLSX_OUTPUT_FOLDER);

        // Vérifier que le dossier de destination existe
        if (!is_dir(self::XLSX_OUTPUT_FOLDER)) {
            $error = "ERREUR : Le dossier de destination n'existe pas : " . self::XLSX_OUTPUT_FOLDER;
            $this->console_log($error);
            $this->log_message($error);
            return $results;
        }

        $this->console_log("Dossier destination trouvé et accessible");

        // Lire tous les fichiers du dossier
        $files = scandir(self::XLSX_OUTPUT_FOLDER);
        $this->console_log("Nombre de fichiers/dossiers trouvés: " . count($files));

        foreach ($files as $file) {
            // Ignorer les dossiers spéciaux
            if ($file === '.' || $file === '..') {
                continue;
            }

            $this->console_log("Examen du fichier: " . $file);

            $filePath = self::XLSX_OUTPUT_FOLDER . DIRECTORY_SEPARATOR . $file;

            // Vérifier si c'est un fichier
            if (!is_file($filePath)) {
                $this->console_log("Ignoré (pas un fichier) : " . $file);
                $this->log_message("Ignoré (pas un fichier) : " . $file);
                continue;
            }

            // Déterminer le type de fichier
            $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $this->console_log("Extension détectée pour " . $file . ": " . $fileExtension);

            if ($fileExtension === 'xlsx') {
                $this->console_log("✓ Fichier XLSX trouvé : " . $file);
                $this->log_message("Fichier XLSX trouvé : " . $file);

                // Lancer l'importation avec ExcelToBdModel
                $this->console_log("Lancement de l'importation pour: " . $file);
                $importResult = $this->excelToBdModel->importExcelToDatabase($filePath);

                $results[$file] = [
                    'type' => 'xlsx',
                    'path' => $filePath,
                    'importation' => $importResult
                ];

                if ($importResult['success']) {
                    $this->console_log("✓ Importation réussie : " . $file . " - " . $importResult['importCount'] . " entrées");
                    $this->log_message("✓ Importation réussie : " . $file . " - " . $importResult['importCount'] . " entrées");
                } else {
                    $this->console_log("✗ Erreur importation : " . $file . " - " . $importResult['message']);
                    $this->log_message("✗ Erreur importation : " . $file . " - " . $importResult['message']);
                }
            } else {
                $this->console_log("Fichier ignoré (pas XLSX) : " . $file . " (extension: " . $fileExtension . ")");
                $this->log_message("Fichier ignoré (pas XLSX) : " . $file . " (extension: " . $fileExtension . ")");
                $results[$file] = [
                    'type' => $fileExtension,
                    'path' => $filePath,
                    'ignored' => true,
                    'reason' => 'Not an XLSX file'
                ];
            }
        }

        $this->console_log("=== FIN importXlsxFiles() ===");
        $this->console_log("Nombre de résultats d'importation: " . count($results));

        return $results;
    }

    /**
     * Calcule le résumé des opérations
     *
     * @param array $conversionResults Résultats de conversion
     * @param array $importationResults Résultats d'importation
     * @return array Résumé calculé
     */
    private function calculateSummary($conversionResults, $importationResults) {
        $this->console_log("=== CALCUL DU RÉSUMÉ ===");

        $summary = [
            'mpp_found' => 0,
            'mpp_converted' => 0,
            'mpp_errors' => 0,
            'xlsx_found' => 0,
            'xlsx_imported' => 0,
            'xlsx_errors' => 0
        ];

        // Analyser les résultats de conversion
        foreach ($conversionResults as $file => $result) {
            if ($result['type'] === 'mpp') {
                $summary['mpp_found']++;
                if (isset($result['conversion']['success']) && $result['conversion']['success']) {
                    $summary['mpp_converted']++;
                } else {
                    $summary['mpp_errors']++;
                }
            }
        }

        // Analyser les résultats d'importation
        foreach ($importationResults as $file => $result) {
            if ($result['type'] === 'xlsx') {
                $summary['xlsx_found']++;
                if (isset($result['importation']['success']) && $result['importation']['success']) {
                    $summary['xlsx_imported']++;
                } else {
                    $summary['xlsx_errors']++;
                }
            }
        }

        $this->console_log("Résumé calculé: " . json_encode($summary));

        return $summary;
    }

    /**
     * Affiche le résumé des opérations
     *
     * @param array $summary Résumé à afficher
     */
    private function displaySummary($summary) {
        $this->console_log("=== AFFICHAGE DU RÉSUMÉ ===");
        $this->log_message("=== RÉSUMÉ DES OPÉRATIONS ===");
        $this->log_message("Fichiers MPP trouvés : " . $summary['mpp_found']);
        $this->log_message("Fichiers MPP convertis : " . $summary['mpp_converted']);
        $this->log_message("Erreurs de conversion : " . $summary['mpp_errors']);
        $this->log_message("Fichiers XLSX trouvés : " . $summary['xlsx_found']);
        $this->log_message("Fichiers XLSX importés : " . $summary['xlsx_imported']);
        $this->log_message("Erreurs d'importation : " . $summary['xlsx_errors']);
        $this->log_message("=============================");
    }

    /**
     * S'assure que les dossiers nécessaires existent
     */
    private function ensureDirectoriesExist() {
        $this->console_log("=== VÉRIFICATION DES DOSSIERS ===");

        if (!is_dir(self::MPP_SOURCE_FOLDER)) {
            $this->console_log("Création du dossier source : " . self::MPP_SOURCE_FOLDER);
            $this->log_message("Création du dossier source : " . self::MPP_SOURCE_FOLDER);
            mkdir(self::MPP_SOURCE_FOLDER, 0777, true);
        } else {
            $this->console_log("Dossier source OK : " . self::MPP_SOURCE_FOLDER);
        }

        if (!is_dir(self::XLSX_OUTPUT_FOLDER)) {
            $this->console_log("Création du dossier de destination : " . self::XLSX_OUTPUT_FOLDER);
            $this->log_message("Création du dossier de destination : " . self::XLSX_OUTPUT_FOLDER);
            mkdir(self::XLSX_OUTPUT_FOLDER, 0777, true);
        } else {
            $this->console_log("Dossier destination OK : " . self::XLSX_OUTPUT_FOLDER);
        }
    }

    /**
     * Function pour la journalisation avec timestamp
     *
     * @param string $message Message à logger
     */
    private function log_message($message) {
        $timestamp = date('[Y-m-d H:i:s]');
        echo $timestamp . " " . $message . "\n";
    }

    /**
     * Function pour la journalisation dans la console du navigateur
     *
     * @param string $message Message à logger
     */
    private function console_log($message) {
        echo "<script>console.log('[LectureDossierModel] " . addslashes($message) . "');</script>";
    }

    /**
     * Retourne la liste des fichiers MPP dans le dossier source
     *
     * @return array Liste des fichiers MPP
     */
    public function getMppFiles() {
        $this->console_log("=== getMppFiles() ===");

        $mppFiles = [];

        if (!is_dir(self::MPP_SOURCE_FOLDER)) {
            $this->console_log("Dossier source introuvable: " . self::MPP_SOURCE_FOLDER);
            return $mppFiles;
        }

        $files = scandir(self::MPP_SOURCE_FOLDER);
        $this->console_log("Fichiers scannés: " . count($files));

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = self::MPP_SOURCE_FOLDER . DIRECTORY_SEPARATOR . $file;

            if (is_file($filePath) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'mpp') {
                $this->console_log("Fichier MPP trouvé: " . $file);
                $mppFiles[] = [
                    'name' => $file,
                    'path' => $filePath,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath)
                ];
            }
        }

        $this->console_log("Total fichiers MPP: " . count($mppFiles));
        return $mppFiles;
    }

    /**
     * Retourne la liste des fichiers XLSX dans le dossier de destination
     *
     * @return array Liste des fichiers XLSX
     */
    public function getXlsxFiles() {
        $this->console_log("=== getXlsxFiles() ===");

        $xlsxFiles = [];

        if (!is_dir(self::XLSX_OUTPUT_FOLDER)) {
            $this->console_log("Dossier destination introuvable: " . self::XLSX_OUTPUT_FOLDER);
            return $xlsxFiles;
        }

        $files = scandir(self::XLSX_OUTPUT_FOLDER);
        $this->console_log("Fichiers scannés: " . count($files));

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = self::XLSX_OUTPUT_FOLDER . DIRECTORY_SEPARATOR . $file;

            if (is_file($filePath) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'xlsx') {
                $this->console_log("Fichier XLSX trouvé: " . $file);
                $xlsxFiles[] = [
                    'name' => $file,
                    'path' => $filePath,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath)
                ];
            }
        }

        $this->console_log("Total fichiers XLSX: " . count($xlsxFiles));
        return $xlsxFiles;
    }
}