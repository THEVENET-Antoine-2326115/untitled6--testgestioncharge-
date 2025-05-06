<?php
// Utiliser le chemin relatif pour l'autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Client Id et Secret pour l'API GroupDocs
$myClientId = "e77d5a47-1328-475f-a39a-037d2f258bdd";
$myClientSecret = "0adba4bdd2f4bb5ac80fa4fb4ccf8e33";

// Function pour la journalisation avec timestamp
function log_message($message) {
    $timestamp = date('[Y-m-d H:i:s]');
    echo $timestamp . " " . $message . "\n";
}

// Chemins de fichiers
$inputFilePath = __DIR__ . '/uploads/TestConversion.mpp';
$outputFolder = __DIR__ . '/uploads';
$outputFileName = "TestConversion.xlsx";
$outputFilePath = $outputFolder . "/" . $outputFileName;

// Vérifier que le fichier source existe
if (!file_exists($inputFilePath)) {
    log_message("ERREUR: Le fichier source n'existe pas: " . $inputFilePath);
    exit;
}

log_message("Démarrage du processus de conversion");
log_message("Fichier source: " . $inputFilePath);
log_message("Destination après conversion: " . $outputFilePath);

// Create instance of the API
log_message("Initialisation de l'API GroupDocs");
$configuration = new GroupDocs\Conversion\Configuration();
$configuration->setAppSid($myClientId);
$configuration->setAppKey($myClientSecret);
$convertApi = new GroupDocs\Conversion\ConvertApi($configuration);
$fileApi = new GroupDocs\Conversion\FileApi($configuration);

try {
    // 1. Définir le nom du stockage et du fichier
    $storageName = "Chargeconversion";
    $cloudFileName = "TestConversion.mpp";
    $cloudOutputPath = "TestConversion.xlsx";

    log_message("Stockage cible: " . $storageName);
    log_message("Nom du fichier dans le cloud: " . $cloudFileName);
    log_message("Nom du fichier de sortie: " . $cloudOutputPath);

    // 2. Télécharger le fichier vers le cloud
    log_message("Téléchargement du fichier vers le cloud...");
    $uploadRequest = new GroupDocs\Conversion\Model\Requests\UploadFileRequest(
        $cloudFileName,
        $inputFilePath,
        $storageName
    );

    $uploadResult = $fileApi->uploadFile($uploadRequest);
    log_message("Fichier téléchargé avec succès vers le cloud");

    // 3. Configurer les paramètres de conversion
    log_message("Configuration des paramètres de conversion...");

    // Configurer les paramètres de conversion
    $settings = new GroupDocs\Conversion\Model\ConvertSettings();
    $settings->setStorageName($storageName);
    $settings->setFilePath($cloudFileName);
    $settings->setFormat("xlsx");
    $settings->setOutputPath(""); // Dossier racine

    log_message("Paramètres de conversion configurés");

    // 4. Lancer la conversion
    log_message("Démarrage de la conversion...");
    $result = $convertApi->convertDocument(
        new GroupDocs\Conversion\Model\Requests\ConvertDocumentRequest($settings)
    );

    if ($result) {
        log_message("Conversion demandée avec succès");
        // Tenter d'afficher des informations sur le résultat
        if (method_exists($result, 'getUrl')) {
            log_message("URL du fichier converti: " . $result->getUrl());
        }
    } else {
        log_message("AVERTISSEMENT: Aucun résultat de conversion n'a été retourné");
    }

    // 5. Attendre que la conversion soit terminée
    log_message("Attente de 20 secondes pour s'assurer que la conversion est terminée...");
    sleep(20);

    // 6. Tenter de télécharger le fichier converti
    log_message("Tentative de téléchargement du fichier converti...");

    try {
        $downloadRequest = new GroupDocs\Conversion\Model\Requests\DownloadFileRequest(
            $cloudOutputPath,
            $storageName,
            null
        );

        $response = $fileApi->downloadFile($downloadRequest);
        log_message("Fichier converti téléchargé avec succès depuis le cloud");

        // Enregistrer le fichier localement
        copy($response->getPathName(), $outputFilePath);
        log_message("Fichier converti enregistré dans: " . $outputFilePath);

        // Vérifier si le fichier existe localement
        if (file_exists($outputFilePath)) {
            $fileSize = filesize($outputFilePath);
            log_message("Taille du fichier converti: " . $fileSize . " octets");
            log_message("CONVERSION RÉUSSIE!");
        } else {
            log_message("ERREUR: Le fichier converti n'a pas été correctement enregistré localement");
        }
    } catch (Exception $downloadError) {
        log_message("ERREUR lors du téléchargement du fichier converti: " . $downloadError->getMessage());

        // Essayer de récupérer plus d'informations sur l'erreur
        if ($downloadError instanceof \GuzzleHttp\Exception\RequestException && $downloadError->hasResponse()) {
            log_message("Détails de la réponse: " . $downloadError->getResponse()->getBody()->getContents());
        }
    }
} catch (Exception $e) {
    log_message("ERREUR GÉNÉRALE: " . $e->getMessage());

    // Afficher des détails supplémentaires sur l'erreur
    if (method_exists($e, 'getCode')) {
        log_message("Code d'erreur: " . $e->getCode());
    }

    if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
        log_message("Détails de la réponse: " . $e->getResponse()->getBody()->getContents());
    }
}

log_message("Fin du processus de conversion");
?>