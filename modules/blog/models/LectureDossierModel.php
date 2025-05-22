<?php
namespace modules\blog\models;

/**
 * Classe LectureDossierModel - Version refactorisée
 *
 * Cette classe gère la lecture automatique des dossiers de planification,
 * la conversion des fichiers MPP en XLSX, et l'importation des données
 * dans la base de données.
 *
 * Utilise une logique de parcours de dossier robuste et testée.
 */
class LectureDossierModel {

    /**
     * Dossier source contenant les fichiers MPP
     */
    private $mppSourceFolder = 'C:\\Users\\a.thevenet\\PLANNINGTEST';

    /**
     * Dossier de destination pour les fichiers XLSX convertis
     */
    private $xlsxOutputFolder = 'C:\\Users\\a.thevenet\\PROCESSFILETEMP';

    /**
     * Instance du converteur MPP
     */
    private $mppConverter;

    /**
     * Instance du transporteur XLSX vers BD
     */
    private $excelToBdModel;

    /**
     * Logs des opérations
     */
    private $logs = [];

    /**
     * Constructeur
     */
    public function __construct() {
        $this->log_message("=== INITIALISATION DE LectureDossierModel ===");

        // Initialiser le converteur MPP avec le dossier de destination
        $this->mppConverter = new MppConverterModel($this->xlsxOutputFolder);
        $this->log_message("Converteur MPP initialisé");

        // Initialiser le transporteur XLSX vers BD
        $this->excelToBdModel = new ExcelToBdModel();
        $this->log_message("Transporteur XLSX vers BD initialisé");

        // S'assurer que les dossiers existent
        $this->ensureDirectoriesExist();

        $this->log_message("=== INITIALISATION TERMINÉE ===");
    }

    /**
     * Lance le processus complet de lecture, conversion et importation
     *
     * @return array Résultat détaillé du processus
     */
    public function processAllFiles() {
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
        $this->log_message("Étape 1 : Conversion des fichiers MPP");
        $conversionResults = $this->convertMppFiles();
        $results['conversion'] = $conversionResults;

        // Étape 2 : Parcourir le dossier de destination et importer les fichiers XLSX
        $this->log_message("Étape 2 : Importation des fichiers XLSX");
        $importationResults = $this->importXlsxFiles();
        $results['importation'] = $importationResults;

        // Calculer le résumé
        $results['summary'] = $this->calculateSummary($conversionResults, $importationResults);

        $this->log_message("=== FIN DU PROCESSUS COMPLET ===");
        $this->displaySummary($results['summary']);

        return $results;
    }

    /**
     * Parcourt le dossier source et convertit tous les fichiers MPP
     * Utilise la logique de parcours robuste
     *
     * @return array Résultats de la conversion
     */
    private function convertMppFiles() {
        $results = [];

        $this->log_message("=== DÉBUT CONVERSION FICHIERS MPP ===");
        $this->log_message("Dossier source: " . $this->mppSourceFolder);

        // Vérifier que le dossier existe et est accessible
        if (!$this->dossierExiste($this->mppSourceFolder)) {
            $this->log_message("ERREUR: Le dossier source n'existe pas ou n'est pas accessible");
            return $results;
        }

        // Récupérer tous les fichiers MPP du dossier
        $fichiersMpp = $this->obtenirFichiersMpp();

        $this->log_message("Nombre de fichiers MPP trouvés: " . count($fichiersMpp));

        if (empty($fichiersMpp)) {
            $this->log_message("Aucun fichier MPP trouvé dans le dossier source");
            return $results;
        }

        // Traiter chaque fichier MPP
        foreach ($fichiersMpp as $fichier) {
            $this->log_message("Traitement du fichier: " . $fichier['nom']);
            $this->log_message("  -> Chemin: " . $fichier['chemin_complet']);
            $this->log_message("  -> Taille: " . $this->formatTaille($fichier['taille']));

            // Lancer la conversion avec MppConverterModel
            $conversionResult = $this->mppConverter->convertMppToXlsx($fichier['chemin_complet']);

            $results[$fichier['nom']] = [
                'type' => 'mpp',
                'path' => $fichier['chemin_complet'],
                'size' => $fichier['taille'],
                'modified' => $fichier['date_modification'],
                'conversion' => $conversionResult
            ];

            if ($conversionResult['success']) {
                $this->log_message("  ✓ Conversion réussie -> " . $conversionResult['outputFile']);
            } else {
                $this->log_message("  ✗ Erreur conversion: " . $conversionResult['message']);
            }
        }

        $this->log_message("=== FIN CONVERSION FICHIERS MPP ===");
        return $results;
    }

    /**
     * Parcourt le dossier de destination et importe tous les fichiers XLSX
     *
     * @return array Résultats de l'importation
     */
    private function importXlsxFiles() {
        $results = [];

        $this->log_message("=== DÉBUT IMPORTATION FICHIERS XLSX ===");
        $this->log_message("Dossier destination: " . $this->xlsxOutputFolder);

        // Vérifier que le dossier existe et est accessible
        if (!$this->dossierExiste($this->xlsxOutputFolder)) {
            $this->log_message("ERREUR: Le dossier de destination n'existe pas ou n'est pas accessible");
            return $results;
        }

        // Récupérer tous les fichiers XLSX du dossier
        $fichiersXlsx = $this->obtenirFichiersXlsx();

        $this->log_message("Nombre de fichiers XLSX trouvés: " . count($fichiersXlsx));

        if (empty($fichiersXlsx)) {
            $this->log_message("Aucun fichier XLSX trouvé dans le dossier de destination");
            return $results;
        }

        // Traiter chaque fichier XLSX
        foreach ($fichiersXlsx as $fichier) {
            $this->log_message("Importation du fichier: " . $fichier['nom']);
            $this->log_message("  -> Chemin: " . $fichier['chemin_complet']);
            $this->log_message("  -> Taille: " . $this->formatTaille($fichier['taille']));

            // Lancer l'importation avec ExcelToBdModel
            $importResult = $this->excelToBdModel->importExcelToDatabase($fichier['chemin_complet']);

            $results[$fichier['nom']] = [
                'type' => 'xlsx',
                'path' => $fichier['chemin_complet'],
                'size' => $fichier['taille'],
                'modified' => $fichier['date_modification'],
                'importation' => $importResult
            ];

            if ($importResult['success']) {
                $this->log_message("  ✓ Importation réussie: " . $importResult['importCount'] . " entrées");
            } else {
                $this->log_message("  ✗ Erreur importation: " . $importResult['message']);
            }
        }

        $this->log_message("=== FIN IMPORTATION FICHIERS XLSX ===");
        return $results;
    }

    /**
     * Vérifie si un dossier existe et est accessible
     *
     * @param string $chemin
     * @return bool
     */
    private function dossierExiste($chemin) {
        return is_dir($chemin) && is_readable($chemin);
    }

    /**
     * Récupère tous les fichiers MPP du dossier source
     *
     * @return array Liste des fichiers MPP avec leurs informations
     */
    private function obtenirFichiersMpp() {
        return $this->obtenirFichiersParExtension($this->mppSourceFolder, 'mpp');
    }

    /**
     * Récupère tous les fichiers XLSX du dossier de destination
     *
     * @return array Liste des fichiers XLSX avec leurs informations
     */
    private function obtenirFichiersXlsx() {
        return $this->obtenirFichiersParExtension($this->xlsxOutputFolder, 'xlsx');
    }

    /**
     * Récupère tous les fichiers d'un dossier avec une extension spécifique
     * Logique de parcours robuste basée sur notre ParcoursClasse
     *
     * @param string $cheminDossier
     * @param string $extension
     * @return array
     */
    private function obtenirFichiersParExtension($cheminDossier, $extension) {
        $fichiersTrouves = [];

        if (!$this->dossierExiste($cheminDossier)) {
            $this->log_message("ERREUR: Le dossier '$cheminDossier' n'existe pas ou n'est pas accessible");
            return $fichiersTrouves;
        }

        // Parcourir le dossier
        $contenuDossier = scandir($cheminDossier);

        $this->log_message("Parcours du dossier: $cheminDossier");
        $this->log_message("Éléments trouvés: " . count($contenuDossier));

        foreach ($contenuDossier as $element) {
            // Ignorer les dossiers spéciaux
            if ($element === '.' || $element === '..') {
                continue;
            }

            $cheminComplet = $cheminDossier . DIRECTORY_SEPARATOR . $element;

            // Vérifier que c'est un fichier
            if (!is_file($cheminComplet)) {
                $this->log_message("Ignoré (pas un fichier): $element");
                continue;
            }

            // Vérifier l'extension
            $extensionFichier = strtolower(pathinfo($element, PATHINFO_EXTENSION));
            if ($extensionFichier !== strtolower($extension)) {
                $this->log_message("Ignoré (extension $extensionFichier ≠ $extension): $element");
                continue;
            }

            // Fichier valide trouvé
            $this->log_message("Fichier .$extension trouvé: $element");

            $fichiersTrouves[] = [
                'nom' => $element,
                'chemin_complet' => $cheminComplet,
                'taille' => filesize($cheminComplet),
                'date_modification' => filemtime($cheminComplet),
                'extension' => $extensionFichier
            ];
        }

        $this->log_message("Total fichiers .$extension trouvés: " . count($fichiersTrouves));
        return $fichiersTrouves;
    }

    /**
     * Calcule le résumé des opérations
     *
     * @param array $conversionResults Résultats de conversion
     * @param array $importationResults Résultats d'importation
     * @return array Résumé calculé
     */
    private function calculateSummary($conversionResults, $importationResults) {
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

        return $summary;
    }

    /**
     * Affiche le résumé des opérations
     *
     * @param array $summary Résumé à afficher
     */
    private function displaySummary($summary) {
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
        // Vérifier le dossier source
        if ($this->dossierExiste($this->mppSourceFolder)) {
            $this->log_message("✓ Dossier source accessible: " . $this->mppSourceFolder);
        } else {
            $this->log_message("✗ ATTENTION: Dossier source inaccessible: " . $this->mppSourceFolder);
        }

        // Vérifier le dossier de destination
        if ($this->dossierExiste($this->xlsxOutputFolder)) {
            $this->log_message("✓ Dossier destination accessible: " . $this->xlsxOutputFolder);
        } else {
            $this->log_message("✗ ATTENTION: Dossier destination inaccessible: " . $this->xlsxOutputFolder);
        }
    }

    /**
     * Formate la taille en octets de manière lisible
     *
     * @param int $octets
     * @return string
     */
    private function formatTaille($octets) {
        $unites = ['o', 'Ko', 'Mo', 'Go'];
        $puissance = 0;

        while ($octets >= 1024 && $puissance < count($unites) - 1) {
            $octets /= 1024;
            $puissance++;
        }

        return round($octets, 2) . ' ' . $unites[$puissance];
    }

    /**
     * Function pour la journalisation avec timestamp
     *
     * @param string $message Message à logger
     */
    private function log_message($message) {
        $timestamp = date('[Y-m-d H:i:s]');
        $logEntry = $timestamp . " " . $message;

        // Stocker dans l'historique des logs
        $this->logs[] = $logEntry;

        // Afficher dans la console
        echo $logEntry . "\n";

        // Ajouter du debug JavaScript pour le navigateur
        echo "<script>console.log('" . addslashes($logEntry) . "');</script>\n";
        flush(); // Forcer l'affichage immédiat
    }

    /**
     * Retourne tous les logs
     *
     * @return array
     */
    public function getLogs() {
        return $this->logs;
    }

    /**
     * Retourne la liste des fichiers MPP dans le dossier source
     * MÉTHODE PUBLIQUE pour compatibilité avec l'ancienne interface
     *
     * @return array Liste des fichiers MPP
     */
    public function getMppFiles() {
        $this->log_message("Récupération de la liste des fichiers MPP");
        return $this->obtenirFichiersMpp();
    }

    /**
     * Retourne la liste des fichiers XLSX dans le dossier de destination
     * MÉTHODE PUBLIQUE pour compatibilité avec l'ancienne interface
     *
     * @return array Liste des fichiers XLSX
     */
    public function getXlsxFiles() {
        $this->log_message("Récupération de la liste des fichiers XLSX");
        return $this->obtenirFichiersXlsx();
    }

    /**
     * Teste le parcours de dossier sans lancer les conversions
     * Utile pour déboguer
     *
     * @return array Informations sur les fichiers trouvés
     */
    public function testParcoursDossier() {
        $this->log_message("=== TEST DE PARCOURS DE DOSSIER ===");

        $result = [
            'source_accessible' => $this->dossierExiste($this->mppSourceFolder),
            'destination_accessible' => $this->dossierExiste($this->xlsxOutputFolder),
            'mpp_files' => $this->obtenirFichiersMpp(),
            'xlsx_files' => $this->obtenirFichiersXlsx()
        ];

        $this->log_message("Test terminé");
        $this->log_message("Dossier source accessible: " . ($result['source_accessible'] ? 'OUI' : 'NON'));
        $this->log_message("Dossier destination accessible: " . ($result['destination_accessible'] ? 'OUI' : 'NON'));
        $this->log_message("Fichiers MPP trouvés: " . count($result['mpp_files']));
        $this->log_message("Fichiers XLSX trouvés: " . count($result['xlsx_files']));

        return $result;
    }
}