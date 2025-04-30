<?php
namespace modules\blog\views;

/**
 * Classe DashboardView
 *
 * Cette classe gère l'affichage du tableau de bord.
 */
class DashboardView {
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
            <title>Erreur</title>
            <link rel="stylesheet" href="_assets/css/dashboard.css">
            <link rel="stylesheet" href="_assets/css/dashboard-files.css">
        </head>
        <body>
        <div class="navbar">
            <a href="index.php?action=dashboard">Tableau de bord</a>
            <a href="index.php?action=analyse-charge">Analyse de Charge</a>
            <a href="index.php?action=logout">Déconnexion</a>
        </div>

        <div class="container">
            <h1>Erreur</h1>
            <div class="message error">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <a href="index.php?action=dashboard" class="btn back-link">Retour au tableau de bord</a>
        </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Affiche le tableau de bord avec les données Excel intégrées
     *
     * @param array $userInfo Informations de l'utilisateur
     * @param string $fileName Nom du fichier Excel
     * @param array $excelData Données du fichier Excel
     * @param array $importedData Données déjà importées dans la base de données
     * @param array|null $importResult Résultat de l'importation (facultatif)
     * @param array|null $convertedFiles Liste des fichiers convertis disponibles (facultatif)
     * @return string Le contenu HTML généré
     */
    public function showDashboardWithExcel($userInfo, $fileName, $excelData, $importedData = [], $importResult = null, $convertedFiles = []) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Tableau de bord - Gestion de Charge</title>
            <link rel="stylesheet" href="_assets/css/dashboard.css">
            <link rel="stylesheet" href="_assets/css/excel.css">
            <link rel="stylesheet" href="_assets/css/dashboard-files.css">
        </head>
        <body>
        <div class="navbar">
            <a href="index.php?action=dashboard">Tableau de bord</a>
            <a href="index.php?action=analyse-charge">Analyse de Charge</a>
            <a href="index.php?action=logout">Déconnexion</a>
        </div>

        <div class="container">
            <div class="card">
                <h1>Tableau de bord - Gestion de Charge</h1>
                <p>Bienvenue <?php echo htmlspecialchars($userInfo['nom']); ?></p>

                <div class="menu-items">
                    <div class="menu-item">
                        <a href="index.php?action=dashboard">
                            <div class="icon">📊</div>
                            <h3>Visualiser les données</h3>
                            <p>Consulter les données brutes du fichier Excel</p>
                        </a>
                    </div>
                    <div class="menu-item">
                        <a href="index.php?action=analyse-charge">
                            <div class="icon">📈</div>
                            <h3>Analyse de charge</h3>
                            <p>Analyser la répartition de charge par période</p>
                        </a>
                    </div>
                </div>

                <!-- Section d'importation -->
                <div class="import-section">
                    <h2>Importation des données</h2>

                    <?php if ($importResult): ?>
                        <div class="message <?php echo $importResult['success'] ? 'success' : 'error'; ?>">
                            <?php echo htmlspecialchars($importResult['message']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="import-actions">
                        <a href="index.php?action=dashboard&subaction=import" class="btn-import" onclick="return confirm('Êtes-vous sûr de vouloir importer les données?')">
                            <button>Importer les données vers la base</button>
                        </a>
                        <a href="index.php?action=dashboard&subaction=clear_data" class="btn-clear" onclick="return confirm('Attention! Cette action supprimera toutes les données importées. Continuer?')">
                            <button>Vider la table de données</button>
                        </a>
                        <a href="index.php?action=process-mpp-files" class="btn-convert">
                            <button>Convertir les fichiers MPP en XLSX</button>
                        </a>
                    </div>

                    <div class="card">
                        <?php
                        // Afficher le message de conversion si disponible
                        if (isset($_SESSION['conversion_message'])) {
                            $messageClass = ($_SESSION['conversion_status'] === 'success') ? 'success' : 'error';
                            echo '<div class="message ' . $messageClass . '">' .
                                htmlspecialchars($_SESSION['conversion_message']) .
                                '</div>';

                            // Une fois affiché, supprimer le message de la session
                            unset($_SESSION['conversion_message']);
                            unset($_SESSION['conversion_status']);
                        }
                        ?>
                        <h1>Tableau de bord - Gestion de Charge</h1>
                </div>

                <div class="excel-container">
                    <h2>Fichier: <?php echo htmlspecialchars($fileName); ?></h2>
                    <button class="toggle-button" id="toggleData">Afficher les données brutes</button>

                    <div id="rawDataContainer" class="hidden">
                        <?php if (empty($excelData)): ?>
                            <p>Aucune donnée trouvée dans ce fichier.</p>
                        <?php else: ?>
                            <?php foreach ($excelData as $sheetName => $sheetData): ?>
                                <div class="sheet">
                                    <h3>Feuille: <?php echo htmlspecialchars($sheetName); ?></h3>

                                    <?php if (empty($sheetData['rows'])): ?>
                                        <p>Aucune donnée dans cette feuille.</p>
                                    <?php else: ?>
                                        <div class="table-container">
                                            <table>
                                                <thead>
                                                <tr>
                                                    <?php foreach ($sheetData['headers'] as $header): ?>
                                                        <th><?php echo htmlspecialchars($header); ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($sheetData['rows'] as $row): ?>
                                                    <tr>
                                                        <?php foreach ($sheetData['headers'] as $header): ?>
                                                            <td>
                                                                <?php echo isset($row[$header]) ? (is_string($row[$header]) ? htmlspecialchars($row[$header]) : $row[$header]) : ''; ?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const toggleButton = document.getElementById('toggleData');
                            const dataContainer = document.getElementById('rawDataContainer');

                            toggleButton.addEventListener('click', function() {
                                if (dataContainer.classList.contains('hidden')) {
                                    dataContainer.classList.remove('hidden');
                                    toggleButton.textContent = 'Masquer les données brutes';
                                } else {
                                    dataContainer.classList.add('hidden');
                                    toggleButton.textContent = 'Afficher les données brutes';
                                }
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}