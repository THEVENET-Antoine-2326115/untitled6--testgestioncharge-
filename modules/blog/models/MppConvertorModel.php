<?php
namespace modules\blog\models;

/**
 * Classe MppConvertorModel
 *
 * Cette classe gère la conversion des fichiers MPP en format XLSX
 * en utilisant le script convertMppFile.php pour la conversion.
 */
class MppConvertorModel {
    /**
     * Chemin vers le script de conversion de fichiers individuels
     */
    private $convertScript = 'convertMppFile.php';

    /**
     * Dossier source contenant les fichiers MPP
     */
    private $sourceFolder = 'C:\Users\a.thevenet\Documents\testplanning';

    /**
     * Dossier de destination pour les fichiers XLSX
     */
    private $outputFolder = 'C:\Users\a.thevenet\Documents\PROCESSFILETEMP';

    /**
     * Constructeur de MppConvertorModel
     */
    public function __construct() {
        // S'assurer que le dossier de destination existe
        if (!is_dir($this->outputFolder)) {
            mkdir($this->outputFolder, 0777, true);
        }
    }

    /**
     * Vérifie si le script de conversion existe
     *
     * @return bool True si le script existe, false sinon
     */
    public function isConversionScriptAvailable() {
        return file_exists($this->convertScript);
    }

    /**
     * Convertit un fichier MPP en XLSX en utilisant le script convertMppFile.php
     *
     * @param string $inputFilePath Chemin du fichier MPP à convertir
     * @return array Résultat de la conversion
     */
    public function convertMppToXlsx($inputFilePath) {
        if (!$this->isConversionScriptAvailable()) {
            return [
                'success' => false,
                'message' => "Le script de conversion n'existe pas: {$this->convertScript}"
            ];
        }

        if (!file_exists($inputFilePath)) {
            return [
                'success' => false,
                'message' => "Le fichier n'existe pas: {$inputFilePath}"
            ];
        }

        // Vérifier l'extension du fichier
        $fileExtension = strtolower(pathinfo($inputFilePath, PATHINFO_EXTENSION));
        if ($fileExtension !== 'mpp') {
            return [
                'success' => false,
                'message' => "Le fichier n'est pas au format MPP: {$inputFilePath}"
            ];
        }

        try {
            // Générer un script temporaire qui inclut le chemin du fichier à convertir
            $tempScript = $this->generateTempConversionScript($inputFilePath);

            // Exécuter le script temporaire et capturer la sortie
            ob_start();
            include $tempScript;
            $output = ob_get_clean();

            // Supprimer le script temporaire
            @unlink($tempScript);

            // Vérifier si la conversion a réussi
            $outputFileName = pathinfo(basename($inputFilePath), PATHINFO_FILENAME) . ".xlsx";
            $outputFilePath = $this->outputFolder . DIRECTORY_SEPARATOR . $outputFileName;

            if (file_exists($outputFilePath)) {
                return [
                    'success' => true,
                    'message' => "Conversion réussie: " . basename($inputFilePath) . " a été converti en " . $outputFileName,
                    'outputPath' => $outputFilePath
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "La conversion a échoué: {$output}"
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Erreur lors de la conversion: " . $e->getMessage()
            ];
        }
    }

    /**
     * Génère un script temporaire basé sur convertMppFile.php avec un chemin de fichier spécifique
     *
     * @param string $inputFilePath Chemin du fichier à convertir
     * @return string Chemin du script temporaire généré
     */
    private function generateTempConversionScript($inputFilePath) {
        // Lire le contenu du script original
        $originalScript = file_get_contents($this->convertScript);

        // Modifier le script pour utiliser le chemin de fichier spécifié
        $modifiedScript = preg_replace(
            '/\$inputFilePath = "[^"]+";/',
            '$inputFilePath = "' . addslashes($inputFilePath) . '";',
            $originalScript
        );

        // Créer un script temporaire
        $tempFile = tempnam(sys_get_temp_dir(), 'mpp_convert_');
        file_put_contents($tempFile, $modifiedScript);

        return $tempFile;
    }

    /**
     * Parcourt un dossier et convertit tous les fichiers MPP trouvés en XLSX
     *
     * @return array Résultat de la conversion pour chaque fichier et un résumé
     */
    public function processDirectory() {
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        // Vérifier si le dossier source existe
        if (!is_dir($this->sourceFolder)) {
            return [
                'success' => false,
                'message' => "Le dossier source n'existe pas: {$this->sourceFolder}"
            ];
        }

        // Parcourir tous les fichiers du dossier
        $files = scandir($this->sourceFolder);
        $mppFound = false;

        foreach ($files as $file) {
            // Ignorer les dossiers spéciaux
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $this->sourceFolder . DIRECTORY_SEPARATOR . $file;

            // Vérifier si c'est un fichier et s'il a l'extension .mpp
            if (is_file($filePath) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'mpp') {
                $mppFound = true;

                // Convertir le fichier
                $result = $this->convertMppToXlsx($filePath);
                $results[$file] = $result;

                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }
        }

        // Si aucun fichier MPP n'a été trouvé
        if (!$mppFound) {
            return [
                'success' => false,
                'message' => "Aucun fichier MPP trouvé dans le dossier: {$this->sourceFolder}"
            ];
        }

        // Ajouter un résumé aux résultats
        $results['summary'] = [
            'success' => $successCount > 0,
            'message' => "Résumé: {$successCount} fichier(s) converti(s) avec succès, {$errorCount} erreur(s)",
            'successCount' => $successCount,
            'errorCount' => $errorCount
        ];

        return $results;
    }
}