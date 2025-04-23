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
        </head>
        <body>
        <div class="navbar">
            <a href="index.php?action=dashboard">Tableau de bord</a>
            <a href="index.php?action=logout">Déconnexion</a>
        </div>

        <div class="container">
            <h1>Erreur</h1>
            <div class="error-message">
                <?php echo htmlspecialchars($message); ?>
            </div>
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
     * @return string Le contenu HTML généré
     */
    public function showDashboardWithExcel($userInfo, $fileName, $excelData) {
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
        </head>
        <body>
        <div class="navbar">
            <a href="index.php?action=dashboard">Tableau de bord</a>
            <a href="index.php?action=logout">Déconnexion</a>
        </div>

        <div class="container">
            <div class="card">
                <h1>Tableau de bord - Gestion de Charge</h1>
                <p>Bienvenue <?php echo htmlspecialchars($userInfo['nom']); ?></p>

                <div class="excel-container">
                    <h2>Fichier: <?php echo htmlspecialchars($fileName); ?></h2>

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
            </div>
        </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}