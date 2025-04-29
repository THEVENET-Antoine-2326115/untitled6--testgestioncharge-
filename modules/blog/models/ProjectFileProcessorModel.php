<?php
namespace modules\blog\models;

use Exception;
use GroupDocs\Conversion\Configuration;
use GroupDocs\Conversion\ConversionApi;
//use GroupDocs\Conversion\ConvertApi; il a probablement crée conversionApi
//efffectivement il a probablement fait bordel
//$conversionApi = new GroupDocs\Conversion\ConversionApi($configuration); = c'est dans le machin officiel, objectif le reprendre exactement et voir si ca marche
//$settings = new GroupDocs\Conversion\Model\ConvertSettings();            =
use GroupDocs\Conversion\Model\ConvertSettings;
use GroupDocs\Conversion\Model\Requests\ConvertDocumentRequest;
use GroupDocs\Conversion\StorageApi;
use GroupDocs\Conversion\Model\Requests\UploadFileRequest;
use GroupDocs\Conversion\Model\Requests\DownloadFileRequest;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

/**
 * Classe ProjectFileProcessorModel
 *
 * Cette classe gère le traitement automatique des fichiers MPP uniquement
 */
class ProjectFileProcessorModel
{
    /**
     * @var string $scanDirectory Dossier à scanner pour les fichiers MPP
     */
    private $scanDirectory;

    /**
     * @var string $tempDirectory Dossier temporaire pour les fichiers convertis
     */
    private $tempDirectory;

    /**
     * @var DashboardModel $dashboardModel Instance du modèle Dashboard
     */
    private $dashboardModel;

    /**
     * @var ChargeModel $chargeModel Instance du modèle Charge
     */
    private $chargeModel;

    /**
     * @var ImportModel $importModel Instance du modèle Import
     */
    private $importModel;

    /**
     * @var Configuration $gdConfiguration Configuration de l'API GroupDocs
     */
    private $gdConfiguration;

    /**
     * @var array $processedFiles Liste des fichiers traités
     */
    private $processedFiles = [];

    /**
     * @var array $errors Liste des erreurs rencontrées
     */
    private $errors = [];

    /**
     * Constructeur de ProjectFileProcessorModel
     *
     * @param string $scanDirectory Dossier à scanner
     * @param string $tempDirectory Dossier temporaire pour les fichiers convertis
     */
    public function __construct($scanDirectory = 'C:\\Users\\a.thevenet\\Documents\\testplanning', $tempDirectory = 'C:\\Users\\a.thevenet\\Documents\\PROCESSFILETEMP')
    {
        $this->scanDirectory = $scanDirectory;
        $this->tempDirectory = $tempDirectory;

        // Pour le dossier de scan et le dossier temporaire, on vérifie seulement s'ils existent
        // sans essayer de les créer car ce sont des chemins spécifiques du système
        if (!file_exists($scanDirectory)) {
            throw new Exception("Le dossier de scan '$scanDirectory' n'existe pas ou n'est pas accessible.");
        }

        if (!file_exists($tempDirectory)) {
            throw new Exception("Le dossier temporaire '$tempDirectory' n'existe pas ou n'est pas accessible.");
        }

        // Initialiser les modèles
        $this->dashboardModel = new DashboardModel();
        $this->chargeModel = new ChargeModel();
        $this->importModel = new ImportModel();

        // Configurer l'API GroupDocs
        $this->gdConfiguration = new Configuration();
        $this->gdConfiguration->setAppSid("e77d5a47-1328-475f-a39a-037d2f258bdd");
        $this->gdConfiguration->setAppKey("0adba4bdd2f4bb5ac80fa4fb4ccf8e33");
        $this->gdConfiguration->setApiBaseUrl("https://api.groupdocs.cloud");
    }

    /**
     * Assure qu'un répertoire existe, le crée si nécessaire
     *
     * @param string $directory Chemin du répertoire
     * @return bool Succès de l'opération
     */
    private function ensureDirectoryExists($directory)
    {
        if (!file_exists($directory)) {
            return mkdir($directory, 0755, true);
        }
        return true;
    }

    /**
     * Lance le traitement automatique des fichiers MPP
     *
     * @return array Résultats du traitement
     */
    public function processProjectFiles()
    {
        // Réinitialiser les tableaux de résultats
        $this->processedFiles = [];
        $this->errors = [];

        // Vérifier si le dossier existe
        if (!file_exists($this->scanDirectory)) {
            return [
                'success' => false,
                'message' => "Le dossier de scan {$this->scanDirectory} n'existe pas"
            ];
        }

        // Scanner le dossier
        $files = scandir($this->scanDirectory);

        foreach ($files as $file) {
            // Ignorer les entrées de dossier spéciales
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $this->scanDirectory . '/' . $file;

            // Ignorer les dossiers
            if (is_dir($filePath)) {
                continue;
            }

            // Vérifier si c'est un fichier MPP
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            if ($fileExtension === 'mpp') {
                try {
                    // Traiter le fichier MPP
                    $this->processMppFile($filePath);
                } catch (Exception $e) {
                    $this->errors[$file] = $e->getMessage();
                }
            }
            // Ignorer tous les autres types de fichiers
        }

        // Préparer le rapport de résultats
        return [
            'success' => empty($this->errors),
            'processed_files' => $this->processedFiles,
            'errors' => $this->errors,
            'total_processed' => count($this->processedFiles),
            'total_errors' => count($this->errors)
        ];
    }

    /**
     * Traite un fichier MPP
     *
     * @param string $filePath Chemin vers le fichier MPP
     * @return bool Succès de l'opération
     * @throws Exception En cas d'erreur lors du traitement
     */
    private function processMppFile($filePath)
    {
        $fileName = basename($filePath);
        $fileBaseName = pathinfo($fileName, PATHINFO_FILENAME);

        // 1. Convertir MPP en MPX
        $mpxFilePath = $this->convertMppToMpx($filePath);

        // 2. Convertir MPX en XLSX
        $xlsxFilePath = $this->convertMpxToXlsx($mpxFilePath, $fileBaseName);

        // 3. Traiter le fichier XLSX pour l'analyse et l'importation
        $this->processXlsxFile($xlsxFilePath);

        // Enregistrer le succès
        $this->processedFiles[$fileName] = [
            'original' => $filePath,
            'mpx_conversion' => $mpxFilePath,
            'xlsx_conversion' => $xlsxFilePath,
            'status' => 'processed'
        ];

        return true;
    }

    /**
     * Convertit un fichier MPP en MPX
     *
     * @param string $mppFilePath Chemin vers le fichier MPP
     * @return string Chemin vers le fichier MPX généré
     * @throws Exception En cas d'erreur lors de la conversion
     */
    private function convertMppToMpx($mppFilePath)
    {
        $fileName = basename($mppFilePath);
        $mpxFileName = pathinfo($fileName, PATHINFO_FILENAME) . '.mpx';
        $mpxFilePath = $this->tempDirectory . '/' . $mpxFileName;

        try {
            // Créer les API nécessaires
            $storageApi = new StorageApi($this->gdConfiguration);
            $conversionApi = new ConversionApi($this->gdConfiguration);

            // Télécharger le fichier vers le stockage cloud
            $fileStream = fopen($mppFilePath, 'r');
            $uploadRequest = new UploadFileRequest($fileName, $fileStream);
            $uploadResult = $storageApi->uploadFile($uploadRequest);

            // Configurer la conversion
            $settings = new ConvertSettings();
            $settings->setFilePath($fileName);
            $settings->setFormat("mpx");
            $settings->setOutputPath("converted");

            // Lancer la conversion
            $convertRequest = new ConvertDocumentRequest($settings);
            $convertResponse = $conversionApi->convertDocument($convertRequest);

            // Télécharger le fichier converti
            $convertedFileName = $convertResponse[0]->getName();
            $downloadRequest = new DownloadFileRequest("converted/" . $convertedFileName);
            $downloadResponse = $storageApi->downloadFile($downloadRequest);

            // Enregistrer le contenu téléchargé dans le fichier MPX local
            file_put_contents($mpxFilePath, $downloadResponse->getStream());

            return $mpxFilePath;

        } catch (Exception $e) {
            throw new Exception("Erreur lors de la conversion MPP vers MPX: " . $e->getMessage());
        }
    }

    /**
     * Convertit un fichier MPX en XLSX
     *
     * @param string $mpxFilePath Chemin vers le fichier MPX
     * @param string $baseFileName Nom de base du fichier (sans extension)
     * @return string Chemin vers le fichier XLSX généré
     * @throws Exception En cas d'erreur lors de la conversion
     */
    private function convertMpxToXlsx($mpxFilePath, $baseFileName)
    {
        $xlsxFilePath = $this->tempDirectory . '/' . $baseFileName . '.xlsx';

        try {
            // Utiliser PHPOffice/PHPProject pour lire le fichier MPX
            $reader = new \PhpOffice\PhpProject\Reader\MPX();
            $project = $reader->canRead($mpxFilePath) ? $reader->load($mpxFilePath) : null;

            if ($project === null) {
                throw new Exception("Impossible de lire le fichier MPX: $mpxFilePath");
            }

            // Créer un writer pour fichier XLSX avec Box/Spout
            $writer = WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($xlsxFilePath);

            // Feuille des tâches
            $writer->setCurrentSheet($writer->getCurrentSheet());
            $writer->getCurrentSheet()->setName('Table_tâches');

            // Écrire l'en-tête des tâches
            $headers = ['Nom', 'Début', 'Fin'];
            $writer->addRow(WriterEntityFactory::createRowFromArray($headers));

            // Écrire les données des tâches
            foreach ($project->getAllTasks() as $task) {
                $taskData = [
                    $task->getTitle(),
                    $task->getStartDate()->format('d m Y H:i'),
                    $task->getEndDate()->format('d m Y H:i')
                ];
                $writer->addRow(WriterEntityFactory::createRowFromArray($taskData));
            }

            // Créer une nouvelle feuille pour les affectations
            $writer->addNewSheetAndMakeItCurrent();
            $writer->getCurrentSheet()->setName('Table_affectation');

            // Écrire l'en-tête des affectations
            $assignmentHeaders = ['Nom de la tâche', 'Nom de la ressource', 'Capacité'];
            $writer->addRow(WriterEntityFactory::createRowFromArray($assignmentHeaders));

            // Écrire les données des affectations
            foreach ($project->getAllResources() as $resource) {
                foreach ($project->getAssignmentsByResource($resource) as $assignment) {
                    $task = $assignment->getTask();
                    $assignmentData = [
                        $task->getTitle(),
                        $resource->getTitle(),
                        $assignment->getUnits() . '%'
                    ];
                    $writer->addRow(WriterEntityFactory::createRowFromArray($assignmentData));
                }
            }

            $writer->close();

            return $xlsxFilePath;

        } catch (Exception $e) {
            throw new Exception("Erreur lors de la conversion MPX vers XLSX: " . $e->getMessage());
        }
    }

    /**
     * Traite un fichier XLSX
     *
     * @param string $xlsxFilePath Chemin vers le fichier XLSX
     * @return bool Succès de l'opération
     * @throws Exception En cas d'erreur lors du traitement
     */
    private function processXlsxFile($xlsxFilePath)
    {
        try {
            // 1. Lire le fichier Excel
            $excelData = $this->dashboardModel->readExcelFile($xlsxFilePath);

            // 2. Analyser les données
            $analyseResult = $this->chargeModel->analyserChargeParPeriode($excelData);

            if (isset($analyseResult['error'])) {
                throw new Exception("Erreur d'analyse: " . $analyseResult['error']);
            }

            // 3. Importer les données en base
            $importResult = $this->importModel->importDataToDatabase($excelData);

            if (!$importResult['success']) {
                throw new Exception("Erreur d'importation: " . $importResult['message']);
            }

            return true;

        } catch (Exception $e) {
            throw new Exception("Erreur lors du traitement du fichier XLSX: " . $e->getMessage());
        }
    }
}