<?php
namespace modules\blog\views;

/**
 * Classe DashboardView
 *
 * Cette classe gère l'affichage du tableau de bord.
 * Adaptée pour les nouvelles méthodes du contrôleur refactorisé.
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
     * Affiche le tableau de bord principal
     *
     * @param array $userInfo Informations de l'utilisateur
     * @param array $dashboardData Données du dashboard
     * @return string Le contenu HTML généré
     */
    public function showDashboard($userInfo, $dashboardData) {
        return $this->renderDashboard($userInfo, $dashboardData);
    }

    /**
     * Affiche le tableau de bord avec un résultat d'opération
     *
     * @param array $userInfo Informations de l'utilisateur
     * @param array $dashboardData Données du dashboard
     * @param array $result Résultat de l'opération
     * @return string Le contenu HTML généré
     */
    public function showDashboardWithResult($userInfo, $dashboardData, $result) {
        return $this->renderDashboard($userInfo, $dashboardData, $result);
    }

    /**
     * Affiche toutes les données
     *
     * @param array $userInfo Informations de l'utilisateur
     * @param array $allData Toutes les données
     * @return string Le contenu HTML généré
     */
    public function showAllData($userInfo, $allData) {
        return $this->renderDashboard($userInfo, $allData, null, true);
    }

    /**
     * Génère le HTML du tableau de bord (méthode commune)
     *
     * @param array $userInfo Informations de l'utilisateur
     * @param array $dashboardData Données du dashboard
     * @param array|null $result Résultat d'opération (optionnel)
     * @param bool $showAll Afficher toutes les données
     * @return string Le contenu HTML généré
     */
    private function renderDashboard($userInfo, $dashboardData, $result = null, $showAll = false) {
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
            <a href="index.php">Tableau de bord</a>
            <a href="index.php?action=analyse-charge">Analyse de Charge</a>
            <a href="index.php?action=logout">Déconnexion</a>
        </div>

        <div class="container">
            <div class="card">
                <h1>Tableau de bord - Gestion de Charge</h1>
                <p>Bienvenue <?php echo htmlspecialchars($userInfo['nom']); ?></p>

                <div class="menu-items">
                    <div class="menu-item">
                        <a href="index.php">
                            <div class="icon">📊</div>
                            <h3>Visualiser les données</h3>
                            <p>Consulter les données de la base de données</p>
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

                <!-- Section d'importation et conversion -->
                <div class="import-section">
                    <h2>Gestion des données</h2>

                    <!-- Affichage du résultat si présent -->
                    <?php if ($result): ?>
                        <div class="message <?php echo $result['success'] ? 'success' : 'error'; ?>">
                            <?php echo nl2br(htmlspecialchars($result['message'])); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Affichage du résumé des données -->
                    <?php if (isset($dashboardData['summary'])): ?>
                        <div class="summary-box">
                            <div class="summary-title">État des données</div>
                            <?php if ($dashboardData['summary']['total_entries'] > 0): ?>
                                <p><strong>Données disponibles :</strong> <?php echo $dashboardData['summary']['total_entries']; ?> entrées</p>
                                <p><strong>Période :</strong> <?php echo $dashboardData['summary']['date_debut']; ?> au <?php echo $dashboardData['summary']['date_fin']; ?></p>
                                <p><strong>Processus :</strong> <?php echo $dashboardData['summary']['processus_uniques']; ?> processus différents</p>
                            <?php else: ?>
                                <p><strong>Aucune donnée</strong> disponible dans la base de données.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Informations sur les fichiers -->
                    <?php if (isset($dashboardData['files_info'])): ?>
                        <div class="summary-box">
                            <div class="summary-title">Fichiers disponibles</div>
                            <p><strong>Fichiers MPP :</strong> <?php echo $dashboardData['files_info']['mpp_count']; ?> fichier(s)</p>
                            <p><strong>Fichiers XLSX :</strong> <?php echo $dashboardData['files_info']['xlsx_count']; ?> fichier(s)</p>
                        </div>
                    <?php endif; ?>

                    <!-- Boutons d'action -->
                    <div class="action-buttons">
                        <a href="index.php?subaction=convert_files" class="btn-convert" onclick="return confirm('Lancer la conversion des fichiers MPP et l\'importation en base ?')">
                            <button>Convertir les fichiers MPP</button>
                        </a>
                        <a href="index.php?subaction=import" class="btn-import" onclick="return confirm('Importer les données de la base vers la mémoire ?')">
                            <button>Importer les données</button>
                        </a>
                        <a href="index.php?subaction=clear_data" class="btn-clear" onclick="return confirm('Attention! Cette action supprimera toutes les données. Continuer?')">
                            <button>Vider la base de données</button>
                        </a>
                        <?php if (isset($dashboardData['summary']) && $dashboardData['summary']['total_entries'] > 0): ?>
                            <a href="index.php?subaction=show_all_files" class="btn view-all">
                                <button>Voir toutes les données</button>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Affichage des données -->
                <div class="excel-container">
                    <?php if (isset($dashboardData['display_data']) && !empty($dashboardData['display_data'])): ?>
                        <h2><?php echo htmlspecialchars($dashboardData['display_title'] ?? 'Données'); ?></h2>
                        <button class="toggle-button" id="toggleData">
                            <?php echo $showAll ? 'Masquer les données' : 'Afficher les données brutes'; ?>
                        </button>

                        <div id="rawDataContainer" class="<?php echo $showAll ? '' : 'hidden'; ?>">
                            <?php foreach ($dashboardData['display_data'] as $sheetName => $sheetData): ?>
                                <div class="sheet">
                                    <h3>Données: <?php echo htmlspecialchars($sheetName); ?></h3>

                                    <?php if (empty($sheetData['rows'])): ?>
                                        <p>Aucune donnée dans cette section.</p>
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
                                                                <?php
                                                                $value = $row[$header] ?? '';
                                                                echo is_string($value) ? htmlspecialchars($value) : $value;
                                                                ?>
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
                        </div>
                    <?php else: ?>
                        <h2>Aucune donnée à afficher</h2>
                        <p>Convertissez d'abord des fichiers MPP ou importez des données existantes.</p>
                    <?php endif; ?>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const toggleButton = document.getElementById('toggleData');
                            const dataContainer = document.getElementById('rawDataContainer');

                            if (toggleButton && dataContainer) {
                                toggleButton.addEventListener('click', function() {
                                    if (dataContainer.classList.contains('hidden')) {
                                        dataContainer.classList.remove('hidden');
                                        toggleButton.textContent = 'Masquer les données brutes';
                                    } else {
                                        dataContainer.classList.add('hidden');
                                        toggleButton.textContent = 'Afficher les données brutes';
                                    }
                                });
                            }
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