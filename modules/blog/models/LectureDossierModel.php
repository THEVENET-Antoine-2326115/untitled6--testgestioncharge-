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
     * 🆕 Supprime un fichier XLSX converti par numéro d'affaire (fichier uniquement)
     * Respecte le MVC : ne touche que aux fichiers
     *
     * @param string $numeroAffaire Numéro d'affaire du fichier à supprimer
     * @return array Résultat de la suppression de fichier
     */
    public function deleteConvertedFileByNumber($numeroAffaire) {
        $this->console_log("=== DÉBUT SUPPRESSION FICHIER XLSX ===");
        $this->console_log("Numéro d'affaire recherché: " . $numeroAffaire);
        $this->log_message("=== SUPPRESSION FICHIER XLSX PAR NUMÉRO D'AFFAIRE ===");
        $this->log_message("Recherche du fichier XLSX pour: " . $numeroAffaire);

        $result = [
            'success' => false,
            'message' => '',
            'numero_affaire' => $numeroAffaire,
            'file_found' => null,
            'deleted_file' => null
        ];

        try {
            // Rechercher le fichier XLSX correspondant dans le dossier converted
            $this->console_log("=== RECHERCHE DANS LE DOSSIER CONVERTED ===");
            $foundFile = $this->findXlsxFileByNumber($numeroAffaire);

            if (!$foundFile) {
                $this->console_log("❌ Aucun fichier XLSX trouvé pour le numéro: " . $numeroAffaire);
                $this->log_message("❌ Aucun fichier XLSX trouvé pour le numéro: " . $numeroAffaire);

                $result['message'] = "Aucun fichier XLSX converti trouvé contenant le numéro d'affaire \"$numeroAffaire\" dans le dossier converted.";
                return $result;
            }

            $this->console_log("✅ Fichier XLSX trouvé: " . $foundFile['name']);
            $this->log_message("✅ Fichier XLSX trouvé: " . $foundFile['name']);
            $result['file_found'] = $foundFile;

            // Supprimer le fichier XLSX
            $this->console_log("=== SUPPRESSION DU FICHIER XLSX ===");
            $this->log_message("Suppression du fichier: " . $foundFile['name']);

            if (!unlink($foundFile['path'])) {
                $this->console_log("❌ Erreur lors de la suppression du fichier: " . $foundFile['name']);
                $this->log_message("❌ Erreur lors de la suppression du fichier: " . $foundFile['name']);

                $result['message'] = "Erreur lors de la suppression du fichier \"" . $foundFile['name'] . "\". Vérifiez les permissions.";
                return $result;
            }

            $this->console_log("✅ Fichier supprimé avec succès: " . $foundFile['name']);
            $this->log_message("✅ Fichier supprimé avec succès: " . $foundFile['name']);

            $result['success'] = true;
            $result['deleted_file'] = $foundFile;
            $result['message'] = "Fichier \"" . $foundFile['name'] . "\" supprimé avec succès.";

        } catch (\Exception $e) {
            $this->console_log("💥 EXCEPTION: " . $e->getMessage());
            $this->log_message("💥 EXCEPTION: " . $e->getMessage());

            $result['message'] = "Erreur inattendue lors de la suppression : " . $e->getMessage();
        }

        $this->console_log("=== FIN SUPPRESSION FICHIER XLSX ===");
        $this->log_message("=== FIN SUPPRESSION FICHIER XLSX ===");

        return $result;
    }

    /**
     * 🆕 Récupère tous les fichiers XLSX dans le dossier converted
     * Respecte le MVC : ne fait que lire les fichiers
     *
     * @return array Liste des fichiers XLSX avec leurs informations
     */
    public function getAllConvertedFiles() {
        $this->console_log("=== RÉCUPÉRATION FICHIERS CONVERTED ===");

        $convertedFiles = [];

        try {
            // Vérifier que le dossier converted existe
            if (!is_dir(self::XLSX_OUTPUT_FOLDER)) {
                $this->console_log("❌ Dossier converted introuvable: " . self::XLSX_OUTPUT_FOLDER);
                return $convertedFiles;
            }

            // Lire tous les fichiers du dossier converted
            $files = scandir(self::XLSX_OUTPUT_FOLDER);
            $this->console_log("Fichiers scannés: " . count($files));

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = self::XLSX_OUTPUT_FOLDER . DIRECTORY_SEPARATOR . $file;

                // Ne récupérer que les fichiers XLSX
                if (is_file($filePath) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'xlsx') {
                    $this->console_log("Fichier XLSX trouvé: " . $file);
                    $convertedFiles[] = [
                        'name' => $file,
                        'path' => $filePath,
                        'size' => filesize($filePath),
                        'modified' => filemtime($filePath)
                    ];
                }
            }

            $this->console_log("Total fichiers XLSX converted: " . count($convertedFiles));

        } catch (\Exception $e) {
            $this->console_log("💥 EXCEPTION récupération fichiers: " . $e->getMessage());
        }

        return $convertedFiles;
    }

    /**
     * 🆕 Recherche un fichier XLSX dans le dossier converted par numéro d'affaire
     *
     * @param string $numeroAffaire Numéro d'affaire à rechercher
     * @return array|null Informations du fichier trouvé ou null
     */
    private function findXlsxFileByNumber($numeroAffaire) {
        $this->console_log("=== RECHERCHE FICHIER XLSX PAR NUMÉRO ===");
        $this->console_log("Recherche de: " . $numeroAffaire);

        // Vérifier que le dossier converted existe
        if (!is_dir(self::XLSX_OUTPUT_FOLDER)) {
            $this->console_log("❌ Dossier converted introuvable: " . self::XLSX_OUTPUT_FOLDER);
            return null;
        }

        // Lire tous les fichiers du dossier converted
        $files = scandir(self::XLSX_OUTPUT_FOLDER);
        $this->console_log("Fichiers dans le dossier converted: " . count($files));

        foreach ($files as $file) {
            // Ignorer les dossiers spéciaux
            if ($file === '.' || $file === '..') {
                continue;
            }

            $this->console_log("Examen du fichier: " . $file);

            $filePath = self::XLSX_OUTPUT_FOLDER . DIRECTORY_SEPARATOR . $file;

            // Vérifier si c'est un fichier XLSX
            if (!is_file($filePath) || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'xlsx') {
                $this->console_log("Ignoré (pas un fichier XLSX): " . $file);
                continue;
            }

            // Vérifier si le nom du fichier contient le numéro d'affaire
            if ($this->fileContainsNumber($file, $numeroAffaire)) {
                $this->console_log("🎯 TROUVÉ! Fichier XLSX correspondant: " . $file);

                return [
                    'name' => $file,
                    'path' => $filePath,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath)
                ];
            } else {
                $this->console_log("Numéro non trouvé dans: " . $file);
            }
        }

        $this->console_log("❌ Aucun fichier XLSX trouvé contenant le numéro: " . $numeroAffaire);
        return null;
    }

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
     * 🆕 Lance la conversion d'un fichier spécifique par numéro d'affaire
     *
     * @param string $numeroAffaire Numéro d'affaire à rechercher (ex: "24-09_0009")
     * @return array Résultat détaillé du processus
     */
    public function processFileByNumber($numeroAffaire) {
        $this->console_log("=== DÉBUT PROCESSUS PAR NUMÉRO D'AFFAIRE ===");
        $this->console_log("Numéro d'affaire recherché: " . $numeroAffaire);
        $this->log_message("=== CONVERSION CIBLÉE PAR NUMÉRO D'AFFAIRE ===");
        $this->log_message("Recherche du fichier pour: " . $numeroAffaire);

        $result = [
            'success' => false,
            'message' => '',
            'numero_affaire' => $numeroAffaire,
            'file_found' => null,
            'conversion' => null,
            'importation' => null
        ];

        try {
            // Étape 1 : Rechercher le fichier MPP correspondant
            $this->console_log("=== ÉTAPE 1: RECHERCHE DU FICHIER MPP ===");
            $foundFile = $this->findMppFileByNumber($numeroAffaire);

            if (!$foundFile) {
                $this->console_log("❌ Aucun fichier trouvé pour le numéro: " . $numeroAffaire);
                $this->log_message("❌ Aucun fichier trouvé pour le numéro: " . $numeroAffaire);

                $result['message'] = "Aucun fichier MPP trouvé contenant le numéro d'affaire \"$numeroAffaire\" dans le dossier uploads.";
                return $result;
            }

            $this->console_log("✅ Fichier trouvé: " . $foundFile['name']);
            $this->log_message("✅ Fichier trouvé: " . $foundFile['name']);
            $result['file_found'] = $foundFile;

            // Étape 2 : Convertir le fichier MPP vers XLSX
            $this->console_log("=== ÉTAPE 2: CONVERSION MPP → XLSX ===");
            $this->log_message("Étape 2 : Conversion du fichier " . $foundFile['name']);

            $conversionResult = $this->mppConverter->convertMppToXlsx($foundFile['path']);
            $result['conversion'] = $conversionResult;

            if (!$conversionResult['success']) {
                $this->console_log("❌ Erreur de conversion: " . $conversionResult['message']);
                $this->log_message("❌ Erreur de conversion: " . $conversionResult['message']);

                $result['message'] = "Erreur lors de la conversion du fichier \"" . $foundFile['name'] . "\" : " . $conversionResult['message'];
                return $result;
            }

            $this->console_log("✅ Conversion réussie: " . $conversionResult['outputFile']);
            $this->log_message("✅ Conversion réussie: " . $conversionResult['outputFile']);

            // Étape 3 : Importer le fichier XLSX en base de données
            $this->console_log("=== ÉTAPE 3: IMPORTATION XLSX → BASE ===");
            $this->log_message("Étape 3 : Importation du fichier " . $conversionResult['outputFile']);

            $importationResult = $this->excelToBdModel->importExcelToDatabase($conversionResult['outputPath']);
            $result['importation'] = $importationResult;

            if (!$importationResult['success']) {
                $this->console_log("❌ Erreur d'importation: " . $importationResult['message']);
                $this->log_message("❌ Erreur d'importation: " . $importationResult['message']);

                $result['message'] = "Conversion réussie mais erreur lors de l'importation : " . $importationResult['message'];
                return $result;
            }

            $this->console_log("✅ Importation réussie: " . $importationResult['importCount'] . " entrées");
            $this->log_message("✅ Importation réussie: " . $importationResult['importCount'] . " entrées");

            // Succès complet
            $result['success'] = true;
            $result['message'] = sprintf(
                "Conversion réussie !\n" .
                "• Fichier trouvé : %s\n" .
                "• Fichier converti : %s\n" .
                "• Entrées importées : %d\n" .
                "• Erreurs : %d",
                $foundFile['name'],
                $conversionResult['outputFile'],
                $importationResult['importCount'],
                $importationResult['errorCount']
            );

            $this->console_log("🎉 PROCESSUS TERMINÉ AVEC SUCCÈS");
            $this->log_message("🎉 PROCESSUS TERMINÉ AVEC SUCCÈS");

        } catch (\Exception $e) {
            $this->console_log("💥 EXCEPTION: " . $e->getMessage());
            $this->log_message("💥 EXCEPTION: " . $e->getMessage());

            $result['message'] = "Erreur inattendue lors du processus : " . $e->getMessage();
        }

        $this->console_log("=== FIN PROCESSUS PAR NUMÉRO D'AFFAIRE ===");
        $this->log_message("=== FIN PROCESSUS PAR NUMÉRO D'AFFAIRE ===");

        return $result;
    }

    /**
     * 🆕 Recherche un fichier MPP par numéro d'affaire
     *
     * @param string $numeroAffaire Numéro d'affaire à rechercher
     * @return array|null Informations du fichier trouvé ou null
     */
    private function findMppFileByNumber($numeroAffaire) {
        $this->console_log("=== RECHERCHE FICHIER PAR NUMÉRO ===");
        $this->console_log("Recherche de: " . $numeroAffaire);

        // Vérifier que le dossier source existe
        if (!is_dir(self::MPP_SOURCE_FOLDER)) {
            $this->console_log("❌ Dossier source introuvable: " . self::MPP_SOURCE_FOLDER);
            return null;
        }

        // Lire tous les fichiers du dossier
        $files = scandir(self::MPP_SOURCE_FOLDER);
        $this->console_log("Fichiers dans le dossier: " . count($files));

        foreach ($files as $file) {
            // Ignorer les dossiers spéciaux
            if ($file === '.' || $file === '..') {
                continue;
            }

            $this->console_log("Examen du fichier: " . $file);

            $filePath = self::MPP_SOURCE_FOLDER . DIRECTORY_SEPARATOR . $file;

            // Vérifier si c'est un fichier MPP
            if (!is_file($filePath) || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'mpp') {
                $this->console_log("Ignoré (pas un fichier MPP): " . $file);
                continue;
            }

            // Vérifier si le nom du fichier contient le numéro d'affaire
            if ($this->fileContainsNumber($file, $numeroAffaire)) {
                $this->console_log("🎯 TROUVÉ! Fichier correspondant: " . $file);

                return [
                    'name' => $file,
                    'path' => $filePath,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath)
                ];
            } else {
                $this->console_log("Numéro non trouvé dans: " . $file);
            }
        }

        $this->console_log("❌ Aucun fichier MPP trouvé contenant le numéro: " . $numeroAffaire);
        return null;
    }

    /**
     * 🆕 Vérifie si un nom de fichier contient le numéro d'affaire
     *
     * @param string $fileName Nom du fichier
     * @param string $numeroAffaire Numéro d'affaire à chercher
     * @return bool True si le numéro est trouvé
     */
    private function fileContainsNumber($fileName, $numeroAffaire) {
        $this->console_log("Vérification: '" . $numeroAffaire . "' dans '" . $fileName . "'");

        // Recherche exacte du numéro d'affaire dans le nom du fichier
        $found = (strpos($fileName, $numeroAffaire) !== false);

        $this->console_log("Résultat: " . ($found ? "TROUVÉ" : "PAS TROUVÉ"));

        return $found;
    }

    /**
     * Lance le processus complet de lecture, conversion et importation
     * 🗑️ OBSOLÈTE - Remplacé par la conversion ciblée par numéro d'affaire
     *
     * @deprecated Utiliser processFileByNumber() à la place
     * @return array Résultat détaillé du processus
     */
    public function processAllFiles() {
        // Méthode conservée pour compatibilité mais vidée
        return [
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

    /**
     * 🆕 Retourne la liste détaillée des fichiers XLSX avec numéro d'affaire et nom extrait
     *
     * @return array Liste des fichiers XLSX avec détails (numéro d'affaire, nom propre, etc.)
     */
    public function getXlsxFilesDetailed() {
        $this->console_log("=== getXlsxFilesDetailed() ===");

        $xlsxFilesDetailed = [];

        if (!is_dir(self::XLSX_OUTPUT_FOLDER)) {
            $this->console_log("Dossier destination introuvable: " . self::XLSX_OUTPUT_FOLDER);
            return $xlsxFilesDetailed;
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

                // Extraire le numéro d'affaire et le nom propre
                $fileDetails = $this->extractFileDetails($file);

                $fileSize = filesize($filePath);
                $fileModified = filemtime($filePath);

                $xlsxFilesDetailed[] = [
                    'name' => $file,
                    'path' => $filePath,
                    'size' => $fileSize,
                    'size_formatted' => $this->formatFileSize($fileSize),
                    'modified' => $fileModified,
                    'modified_formatted' => date('d/m/Y H:i', $fileModified),
                    'numero_affaire' => $fileDetails['numero_affaire'],
                    'nom_propre' => $fileDetails['nom_propre'],
                    'has_numero' => $fileDetails['has_numero']
                ];
            }
        }

        $this->console_log("Total fichiers XLSX détaillés: " . count($xlsxFilesDetailed));
        return $xlsxFilesDetailed;
    }

    /**
     * 🆕 Extrait le numéro d'affaire et le nom propre d'un nom de fichier
     *
     * @param string $filename Nom du fichier (ex: "AFF24-09_0009 planning en cours.xlsx")
     * @return array Détails extraits
     */
    private function extractFileDetails($filename) {
        $this->console_log("=== extractFileDetails pour: " . $filename . " ===");

        // Supprimer l'extension
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

        // Regex pour trouver le pattern XX-XX_XXXX (numéro d'affaire)
        $pattern = '/(\d{2}-\d{2}_\d{4})/';

        $details = [
            'numero_affaire' => null,
            'nom_propre' => $nameWithoutExt,
            'has_numero' => false
        ];

        if (preg_match($pattern, $nameWithoutExt, $matches)) {
            $numeroAffaire = $matches[1];
            $details['numero_affaire'] = $numeroAffaire;
            $details['has_numero'] = true;

            // Extraire le nom propre en supprimant le préfixe et le numéro d'affaire
            $nomPropre = $nameWithoutExt;

            // Supprimer le préfixe "AFF" s'il existe
            $nomPropre = preg_replace('/^AFF/', '', $nomPropre);

            // Supprimer le numéro d'affaire
            $nomPropre = str_replace($numeroAffaire, '', $nomPropre);

            // Nettoyer les espaces multiples et trim
            $nomPropre = trim(preg_replace('/\s+/', ' ', $nomPropre));

            // Si le nom propre est vide après nettoyage, utiliser un nom par défaut
            if (empty($nomPropre)) {
                $nomPropre = "planning";
            }

            $details['nom_propre'] = $nomPropre;

            $this->console_log("Numéro d'affaire trouvé: " . $numeroAffaire);
            $this->console_log("Nom propre extrait: " . $nomPropre);
        } else {
            $this->console_log("Aucun numéro d'affaire trouvé dans: " . $filename);
            // Le nom propre reste le nom complet du fichier sans extension
        }

        return $details;
    }

    /**
     *  Formate la taille d'un fichier en octets de manière lisible
     *
     * @param int $bytes Taille en octets
     * @return string Taille formatée (ex: "1.2 MB")
     */
    private function formatFileSize($bytes) {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = 0;

        while ($bytes >= 1024 && $power < count($units) - 1) {
            $bytes /= 1024;
            $power++;
        }

        return round($bytes, 1) . ' ' . $units[$power];
    }
}