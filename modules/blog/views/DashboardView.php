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

                    <!-- 🆕 NOUVELLE SECTION : Conversion par numéro d'affaire -->
                    <div class="convert-by-number-section">
                        <h3>🎯 Conversion sélective par numéro d'affaire</h3>
                        <div class="convert-form-container">
                            <p class="convert-description">
                                Convertissez un fichier MPP spécifique en saisissant son numéro d'affaire.<br>
                                <small>Format attendu : <code>24-09_0009</code> (pour un fichier nommé "AFF24-09_0009 planning en cours.mpp")</small>
                            </p>

                            <form action="index.php" method="POST" class="convert-form" id="convertForm">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="numero_affaire">Numéro d'affaire :</label>
                                        <input type="text"
                                               id="numero_affaire"
                                               name="numero_affaire"
                                               placeholder="Ex: 24-09_0009"
                                               pattern="[0-9]{2}-[0-9]{2}_[0-9]{4}"
                                               title="Format attendu : XX-XX_XXXX (ex: 24-09_0009)"
                                               required>
                                    </div>
                                    <div class="form-group buttons-group">
                                        <button type="submit" class="btn-convert-selective" onclick="setConvertAction('convert_by_number')">
                                            🔄 Convertir ce fichier
                                        </button>
                                        <button type="submit" class="btn-delete-selective" onclick="setConvertAction('delete_by_number')">
                                            🗑️ Supprimer le fichier converti
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="action" id="convertActionField" value="convert_by_number">
                            </form>
                        </div>
                    </div>

                    <!-- Boutons d'action généraux -->
                    <div class="action-buttons">
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

                            // 🆕 Validation du formulaire de conversion
                            const convertForm = document.getElementById('convertForm');
                            if (convertForm) {
                                convertForm.addEventListener('submit', function(e) {
                                    const numeroAffaire = document.getElementById('numero_affaire').value.trim();
                                    const action = document.getElementById('convertActionField').value;

                                    if (!numeroAffaire) {
                                        alert('Veuillez saisir un numéro d\'affaire.');
                                        e.preventDefault();
                                        return;
                                    }

                                    // Validation du format
                                    const formatPattern = /^[0-9]{2}-[0-9]{2}_[0-9]{4}$/;
                                    if (!formatPattern.test(numeroAffaire)) {
                                        alert('Format invalide. Utilisez le format XX-XX_XXXX (ex: 24-09_0009)');
                                        e.preventDefault();
                                        return;
                                    }

                                    let confirmMsg = '';
                                    if (action === 'delete_by_number') {
                                        confirmMsg = `Supprimer le fichier XLSX converti contenant le numéro d'affaire "${numeroAffaire}" ?\n\n` +
                                            `Le système recherchera dans le dossier 'converted' et supprimera le fichier correspondant.`;
                                    } else {
                                        confirmMsg = `Convertir le fichier avec le numéro d'affaire "${numeroAffaire}" ?\n\n` +
                                            `Le système recherchera un fichier contenant ce numéro dans le dossier uploads.`;
                                    }

                                    if (!confirm(confirmMsg)) {
                                        e.preventDefault();
                                    }
                                });
                            }

                            // 🆕 Fonction pour définir l'action de conversion/suppression
                            window.setConvertAction = function(action) {
                                document.getElementById('convertActionField').value = action;
                                console.log('Action sélectionnée:', action);
                            };
                        });
                    </script>
                </div>
                <!-- SECTION AJOUT MANUEL DE CHARGE -->
                <div class="add-charge-section">
                    <h2>Ajouter ou supprimer une charge manuellement</h2>

                    <form action="index.php" method="POST" class="add-charge-form" id="chargeForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="processus">Compétence :</label>
                                <input type="text"
                                       id="processus"
                                       name="processus"
                                       placeholder="Ex: CHAUDNQ, SOUDNQ, CT..."
                                       list="processus-suggestions"
                                       required>
                                <datalist id="processus-suggestions">
                                    <!-- Les suggestions seront ajoutées ici par le contrôleur -->
                                    <?php if (isset($dashboardData['processus_suggestions'])): ?>
                                    <?php foreach ($dashboardData['processus_suggestions'] as $suggestion): ?>
                                    <option value="<?php echo htmlspecialchars($suggestion); ?>">
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                </datalist>
                            </div>

                            <div class="form-group">
                                <label for="tache">Projet :</label>
                                <input type="text"
                                       id="tache"
                                       name="tache"
                                       placeholder="Description du projet"
                                       required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="charge">Charge :</label>
                                <input type="number"
                                       id="charge"
                                       name="charge"
                                       step="1"
                                       min="0"
                                       placeholder="Ex: 1"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="date">Date :</label>
                                <input type="date"
                                       id="date"
                                       name="date"
                                       value="<?php echo date('Y-m-d'); ?>"
                                       required>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-add-charge" onclick="setAction('add_charge')">
                                ➕ Ajouter la charge
                            </button>
                            <button type="submit" class="btn-delete-charge" onclick="setAction('delete_charge')">
                                🗑️ Supprimer la charge
                            </button>
                        </div>

                        <!-- Champ hidden pour l'action -->
                        <input type="hidden" name="action" id="actionField" value="add_charge">
                    </form>

                    <script>
                        // Fonction pour définir l'action du formulaire
                        function setAction(action) {
                            document.getElementById('actionField').value = action;

                            // Changer la couleur du bouton temporairement pour feedback visuel
                            if (action === 'delete_charge') {
                                console.log('🗑️ Action: Suppression de charge');
                            } else {
                                console.log('➕ Action: Ajout de charge');
                            }
                        }

                        // Confirmation pour la suppression
                        document.addEventListener('DOMContentLoaded', function() {
                            const deleteBtn = document.querySelector('.btn-delete-charge');
                            if (deleteBtn) {
                                deleteBtn.addEventListener('click', function(e) {
                                    const processus = document.getElementById('processus').value;
                                    const tache = document.getElementById('tache').value;
                                    const charge = document.getElementById('charge').value;
                                    const date = document.getElementById('date').value;

                                    if (!processus || !tache || !charge || !date) {
                                        alert('Veuillez remplir tous les champs avant de supprimer.');
                                        e.preventDefault();
                                        return;
                                    }

                                    const confirmMsg = `Êtes-vous sûr de vouloir supprimer cette charge ?\n\n` +
                                        `Processus: ${processus}\n` +
                                        `Tâche: ${tache}\n` +
                                        `Charge: ${charge}\n` +
                                        `Date: ${date}`;

                                    if (!confirm(confirmMsg)) {
                                        e.preventDefault();
                                    }
                                });
                            }
                        });
                    </script>
                </div>
            </div>

        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}