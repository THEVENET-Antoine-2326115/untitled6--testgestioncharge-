<?php
namespace modules\blog\views;

/**
 * Classe MppProcessorView
 *
 * Cette classe gère l'affichage des pages liées au traitement automatique des fichiers MPP.
 */
class MppProcessorView {
    /**
     * Affiche un message d'erreur
     *
     * @param string $message Message d'erreur à afficher
     * @return string Le contenu HTML généré
     */
    public function showErrorMessage($message) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erreur - Traitement MPP</title>
            <link rel="stylesheet" href="_assets/css/dashboard.css">
            <link rel="stylesheet" href="_assets/css/excel.css">
        </head>
        <body>
        <div class="navbar">
            <a href="index.php?action=dashboard">Tableau de bord</a>
            <a href="index.php?action=analyse-charge">Analyse de Charge</a>
            <a href="index.php?action=process-mpp-files">Traitement MPP</a>
            <a href="index.php?action=logout">Déconnexion</a>
        </div>

        <div class="container">
            <h1>Erreur</h1>
            <div class="error-message">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <a href="index.php?action=process-mpp-files" class="back-link">Retour à la page de traitement MPP</a>
        </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Affiche la page principale de traitement automatique des fichiers MPP
     *
     * @param array $userInfo Informations de l'utilisateur
     * @param array|null $result Résultat du traitement (facultatif)
     * @return string Le contenu HTML généré
     */
    public function showMppProcessorPage($userInfo, $result = null) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Conversion MPP vers XLSX - Gestion de Charge</title>
            <link rel="stylesheet" href="_assets/css/dashboard.css">
            <link rel="stylesheet" href="_assets/css/excel.css">
            <style>
                .action-section {
                    background-color: #f9f9f9;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    padding: 20px;
                    margin-bottom: 20px;
                }
                .section-title {
                    font-size: 18px;
                    font-weight: bold;
                    margin-bottom: 15px;
                }
                .process-btn {
                    background-color: #4CAF50;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 16px;
                }
                .process-btn:hover {
                    background-color: #45a049;
                }
                .info-box {
                    background-color: #e7f3fe;
                    border: 1px solid #b6d4fe;
                    color: #0c63e4;
                    padding: 15px;
                    margin-bottom: 20px;
                    border-radius: 4px;
                }
                .result-box {
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
                .result-success {
                    background-color: #d4edda;
                    border-color: #c3e6cb;
                    color: #155724;
                }
                .result-error {
                    background-color: #f8d7da;
                    border-color: #f5c2c7;
                    color: #721c24;
                }
                .details-box {
                    margin-top: 15px;
                    padding: 10px;
                    background-color: #f8f9fa;
                    border: 1px solid #e9ecef;
                    border-radius: 4px;
                    max-height: 400px;
                    overflow-y: auto;
                }
                .detail-item {
                    margin-bottom: 10px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #e9ecef;
                }
                .detail-item:last-child {
                    border-bottom: none;
                }
                .path-info {
                    font-family: monospace;
                    background-color: #f8f9fa;
                    padding: 5px;
                    border: 1px solid #e9ecef;
                    border-radius: 3px;
                    margin: 5px 0;
                }
            </style>
        </head>
        <body>
        <div class="navbar">
            <a href="index.php?action=dashboard">Tableau de bord</a>
            <a href="index.php?action=analyse-charge">Analyse de Charge</a>
            <a href="index.php?action=process-mpp-files">Traitement MPP</a>
            <a href="index.php?action=logout">Déconnexion</a>
        </div>

        <div class="container">
            <div class="card">
                <h1>Conversion des fichiers MPP vers XLSX</h1>
                <p>Bienvenue <?php echo htmlspecialchars($userInfo['nom']); ?></p>

                <div class="info-box">
                    <p><strong>Comment ça fonctionne :</strong> Ce module analyse automatiquement le dossier de plannings pour convertir tous les fichiers MPP (Microsoft Project) en format XLSX (Excel).</p>
                    <p><strong>Dossier source :</strong> <span class="path-info">C:\Users\a.thevenet\Documents\testplanning</span></p>
                    <p><strong>Dossier de destination :</strong> <span class="path-info">C:\Users\a.thevenet\Documents\PROCESSFILETEMP</span></p>
                </div>

                <!-- Affichage des résultats du traitement si disponibles -->
                <?php if ($result): ?>
                    <div class="result-box <?php echo $result['success'] ? 'result-success' : 'result-error'; ?>">
                        <h3><?php echo $result['success'] ? 'Traitement réussi' : 'Erreur lors du traitement'; ?></h3>
                        <p><?php echo htmlspecialchars($result['message']); ?></p>

                        <?php if (isset($result['details']) && is_array($result['details']) && !empty($result['details'])): ?>
                            <div class="details-box">
                                <h4>Détails du traitement:</h4>
                                <?php foreach ($result['details'] as $file => $fileResult): ?>
                                    <div class="detail-item">
                                        <p><strong>Fichier:</strong> <?php echo htmlspecialchars($file); ?></p>
                                        <p><strong>Statut:</strong> <?php echo isset($fileResult['success']) && $fileResult['success'] ? 'Converti avec succès' : 'Erreur'; ?></p>
                                        <p><strong>Message:</strong> <?php echo htmlspecialchars($fileResult['message'] ?? 'Non disponible'); ?></p>
                                        <?php if (isset($fileResult['outputPath'])): ?>
                                            <p><strong>Fichier de sortie:</strong> <span class="path-info"><?php echo htmlspecialchars($fileResult['outputPath']); ?></span></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="action-section">
                    <div class="section-title">Lancer la conversion des fichiers MPP en XLSX</div>
                    <p>Cliquez sur le bouton ci-dessous pour analyser le dossier de planning et convertir tous les fichiers MPP en format XLSX (Excel).</p>
                    <a href="index.php?action=process-mpp-files&subaction=process">
                        <button class="process-btn">Convertir les fichiers MPP en XLSX</button>
                    </a>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}