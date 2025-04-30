<?php

// Utiliser le chemin relatif pour l'autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Client Id et Secret pour l'API GroupDocs
$myClientId = "e77d5a47-1328-475f-a39a-037d2f258bdd";
$myClientSecret = "0adba4bdd2f4bb5ac80fa4fb4ccf8e33";

// Chemins de fichiers
$inputFilePath = __DIR__ . '/uploads/TestConversion.mpp';
$outputFolder = __DIR__ . '/uploads';
$outputFileName = "TestConversion.xlsx";
$outputFilePath = $outputFolder . "/" . $outputFileName;

// Vérifier que le fichier existe
if (!file_exists($inputFilePath)) {
    echo "Le fichier source n'existe pas: " . $inputFilePath;
    exit;
}

// Create instance of the API
$configuration = new GroupDocs\Conversion\Configuration();
$configuration->setAppSid($myClientId);
$configuration->setAppKey($myClientSecret);
$convertApi = new GroupDocs\Conversion\ConvertApi($configuration);
$fileApi = new GroupDocs\Conversion\FileApi($configuration);

try {
    // 1. D'abord, télécharger le fichier vers le cloud
    $cloudFileName = "TestConversion.mpp"; // Nom du fichier dans le cloud
    $uploadRequest = new GroupDocs\Conversion\Model\Requests\UploadFileRequest(
        $cloudFileName,
        $inputFilePath,
        "Chargeconversion" // Nom du stockage
    );
    $uploadResult = $fileApi->uploadFile($uploadRequest);

    // 2. Préparer les paramètres pour la conversion d'un fichier du cloud
    $settings = new GroupDocs\Conversion\Model\ConvertSettings();
    $settings->setStorageName("Chargeconversion");
    $settings->setFilePath($cloudFileName); // Utiliser uniquement le nom du fichier cloud, pas le chemin local
    $settings->setFormat("xlsx");
    $settings->setOutputPath(""); // Dossier racine du cloud

    // 3. Convertir
    $result = $convertApi->convertDocument(new GroupDocs\Conversion\Model\Requests\ConvertDocumentRequest($settings));

    // 4. Télécharger le fichier converti
    $cloudOutputPath = pathinfo($cloudFileName, PATHINFO_FILENAME) . ".xlsx";
    $request = new GroupDocs\Conversion\Model\Requests\DownloadFileRequest(
        $cloudOutputPath,
        "Chargeconversion",
        null
    );
    $response = $fileApi->downloadFile($request);

    // 5. Enregistrer le fichier téléchargé localement
    copy($response->getPathName(), $outputFilePath);

    echo "Conversion réussie! Le fichier a été enregistré dans: " . $outputFilePath;

} catch (Exception $e) {
    echo "Something went wrong: ", $e->getMessage(), "\n", PHP_EOL;
}
?>