<?php
namespace modules\blog\views;

/**
 * Classe ChargeView
 *
 * Cette classe gère l'affichage de l'analyse de charge avec sélecteur de semaine.
 */
class ChargeView {
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
            <title>Erreur - Analyse de Charge</title>
            <link rel="stylesheet" href="_assets/css/dashboard.css">
        </head>
        <body>
        <div class="navbar">
            <a href="index.php?action=dashboard">Tableau de bord</a>
            <a href="index.php?action=analyse-charge">Analyse de Charge</a>
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
     * Affiche l'analyse de charge avec sélecteur de semaine
     *
     * @param array $userInfo Informations de l'utilisateur
     * @param string $fileName Nom du fichier Excel
     * @param array $resultats Résultats de l'analyse de charge
     * @param array $availableWeeks Liste des semaines disponibles
     * @param string $currentWeek Semaine actuellement sélectionnée
     * @param array $chartPaths Chemins des images de graphiques générées (pour la semaine courante)
     * @return string Le contenu HTML généré
     */
    public function showChargeAnalysis($userInfo, $fileName, $resultats, $availableWeeks = [], $currentWeek = '', $chartPaths = []) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Analyse de Charge - Gestion de Charge</title>
            <link rel="stylesheet" href="_assets/css/dashboard.css">
            <link rel="stylesheet" href="_assets/css/excel.css">
        </head>
        <body>
        <div class="navbar">
            <a href="index.php?action=dashboard">Tableau de bord</a>
            <a href="index.php?action=analyse-charge">Analyse de Charge</a>
            <a href="index.php?action=logout">Déconnexion</a>
        </div>

        <div class="container">
            <div class="card">
                <h1>Analyse de Charge - Gestion de Charge</h1>
                <p>Bienvenue <?php echo htmlspecialchars($userInfo['nom']); ?></p>

                <div class="summary-box">
                    <div class="summary-title">Résumé de l'analyse</div>
                    <p>Période analysée: <?php echo htmlspecialchars($resultats['dateDebut']); ?> au <?php echo htmlspecialchars($resultats['dateFin']); ?></p>
                    <p>Fichier analysé: <?php echo htmlspecialchars($fileName); ?></p>
                </div>

                <!-- 🆕 SÉLECTEUR DE SEMAINE -->
                <div class="week-selector-container">
                    <h2>Sélection de la semaine à analyser</h2>
                    <div class="week-selector">
                        <label for="week-dropdown">Semaine :</label>
                        <select id="week-dropdown" name="selected_week" onchange="changeWeek()">
                            <?php foreach ($availableWeeks as $week): ?>
                                <option value="<?php echo htmlspecialchars($week['value']); ?>"
                                    <?php echo ($week['value'] === $currentWeek) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($week['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Informations sur la semaine sélectionnée -->
                        <div class="week-info">
                            <span class="week-period">
                                <?php
                                $selectedWeekInfo = array_filter($availableWeeks, function($w) use ($currentWeek) {
                                    return $w['value'] === $currentWeek;
                                });
                                if ($selectedWeekInfo) {
                                    $weekInfo = array_values($selectedWeekInfo)[0];
                                    echo "Du " . htmlspecialchars($weekInfo['debut']) . " au " . htmlspecialchars($weekInfo['fin']);
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Section des graphiques par jour pour la semaine sélectionnée -->
                <div class="graphiques-container">
                    <h2>Évolution de la charge par jour - Semaine sélectionnée</h2>

                    <div class="week-chart-info">
                        <p><strong>📅 Affichage :</strong> Charge quotidienne pour la semaine sélectionnée</p>
                        <p><strong>📊 Axe X :</strong> Jours de la semaine (Lundi à Dimanche)</p>
                        <p><strong>📈 Axe Y :</strong> Nombre de personnes</p>
                    </div>

                    <!-- BOUTONS DE SÉLECTION DES GRAPHIQUES -->
                    <div class="graphiques-tabs">
                        <button onclick="showChart('production')" id="btn-production" class="tab-button active">
                            🏭 Production
                        </button>
                        <button onclick="showChart('etude')" id="btn-etude" class="tab-button">
                            📊 Étude
                        </button>
                        <button onclick="showChart('methode')" id="btn-methode" class="tab-button">
                            🔧 Méthode
                        </button>
                        <button onclick="showChart('qualite')" id="btn-qualite" class="tab-button">
                            ✅ Qualité
                        </button>
                    </div>

                    <!-- GRAPHIQUE PRODUCTION (affiché par défaut) -->
                    <div id="chart-production" class="graphique-section chart-content">
                        <h3>Production - Charge par jour</h3>
                        <?php if (!empty($chartPaths['production'])): ?>
                            <img src="_assets/images/<?php echo htmlspecialchars($chartPaths['production']); ?>"
                                 alt="Graphique charge Production par jour" class="chart-image">
                            <p class="chart-description">Répartition quotidienne : Chaudronnerie, Soudure et Contrôle</p>
                        <?php else: ?>
                            <div class="chart-placeholder">
                                <p>Aucune donnée de production pour cette semaine</p>
                                <small>Sélectionnez une autre semaine ou vérifiez les données</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- GRAPHIQUE ÉTUDE (masqué par défaut) -->
                    <div id="chart-etude" class="graphique-section chart-content hidden">
                        <h3>Étude - Charge par jour</h3>
                        <?php if (!empty($chartPaths['etude'])): ?>
                            <img src="_assets/images/<?php echo htmlspecialchars($chartPaths['etude']); ?>"
                                 alt="Graphique charge Étude par jour" class="chart-image">
                            <p class="chart-description">Répartition quotidienne : Calcul et Projet</p>
                        <?php else: ?>
                            <div class="chart-placeholder">
                                <p>Aucune donnée d'étude pour cette semaine</p>
                                <small>Sélectionnez une autre semaine ou vérifiez les données</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- GRAPHIQUE MÉTHODE (masqué par défaut) -->
                    <div id="chart-methode" class="graphique-section chart-content hidden">
                        <h3>Méthode - Charge par jour</h3>
                        <?php if (!empty($chartPaths['methode'])): ?>
                            <img src="_assets/images/<?php echo htmlspecialchars($chartPaths['methode']); ?>"
                                 alt="Graphique charge Méthode par jour" class="chart-image">
                            <p class="chart-description">Répartition quotidienne : Méthode</p>
                        <?php else: ?>
                            <div class="chart-placeholder">
                                <p>Aucune donnée de méthode pour cette semaine</p>
                                <small>Sélectionnez une autre semaine ou vérifiez les données</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- GRAPHIQUE QUALITÉ (masqué par défaut) -->
                    <div id="chart-qualite" class="graphique-section chart-content hidden">
                        <h3>Qualité - Charge par jour</h3>
                        <?php if (!empty($chartPaths['qualite'])): ?>
                            <img src="_assets/images/<?php echo htmlspecialchars($chartPaths['qualite']); ?>"
                                 alt="Graphique charge Qualité par jour" class="chart-image">
                            <p class="chart-description">Répartition quotidienne : Qualité et Qualité Spécialisée</p>
                        <?php else: ?>
                            <div class="chart-placeholder">
                                <p>Aucune donnée de qualité pour cette semaine</p>
                                <small>Sélectionnez une autre semaine ou vérifiez les données</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Section récapitulatif hebdomadaire (données textuelles) -->
                <?php if (!empty($resultats['chargeParSemaine'])): ?>
                    <div class="weekly-summary-section">
                        <h2>📋 Récapitulatif par semaine</h2>
                        <div class="summary-box">
                            <div class="summary-title">Charge par processus (toutes semaines)</div>
                            <?php foreach ($resultats['chargeParSemaine'] as $semaine): ?>
                                <div class="weekly-summary">
                                    <h4>Semaine du <?php echo htmlspecialchars($semaine['debut']); ?> au <?php echo htmlspecialchars($semaine['fin']); ?></h4>
                                    <div class="weekly-processes">
                                        <?php foreach ($semaine['processus'] as $processus => $charge): ?>
                                            <div class="process-charge">
                                                <span class="process-name"><?php echo htmlspecialchars($processus); ?>:</span>
                                                <span class="charge-value"><?php echo number_format($charge, 2); ?> personne(s)</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <script>
                    // FONCTION POUR CHANGER DE SEMAINE
                    function changeWeek() {
                        console.log('Changement de semaine détecté');
                        const dropdown = document.getElementById('week-dropdown');
                        const selectedWeek = dropdown.value;
                        console.log('Semaine sélectionnée:', selectedWeek);

                        // Redirection avec paramètre de semaine
                        const currentUrl = new URL(window.location);
                        currentUrl.searchParams.set('selected_week', selectedWeek);
                        window.location.href = currentUrl.toString();
                    }

                    // FONCTION POUR AFFICHER/MASQUER LES GRAPHIQUES
                    function showChart(chartType) {
                        console.log('Affichage du graphique:', chartType);

                        // Masquer tous les graphiques
                        const allCharts = document.querySelectorAll('.chart-content');
                        allCharts.forEach(chart => {
                            chart.classList.add('hidden');
                        });

                        // Désactiver tous les boutons
                        const allButtons = document.querySelectorAll('.tab-button');
                        allButtons.forEach(button => {
                            button.classList.remove('active');
                        });

                        // Afficher le graphique sélectionné
                        const selectedChart = document.getElementById('chart-' + chartType);
                        if (selectedChart) {
                            selectedChart.classList.remove('hidden');
                        }

                        // Activer le bouton sélectionné
                        const selectedButton = document.getElementById('btn-' + chartType);
                        if (selectedButton) {
                            selectedButton.classList.add('active');
                        }
                    }

                    // Initialisation au chargement de la page
                    document.addEventListener('DOMContentLoaded', function() {
                        console.log('Page chargée - Initialisation des graphiques');
                        showChart('production'); // Afficher Production par défaut
                    });
                </script>
            </div>
        </div>

        <style>
            /* 🆕 STYLES POUR LE SÉLECTEUR DE SEMAINE */
            .week-selector-container {
                margin: 30px 0;
                padding: 20px;
                border: 2px solid #2196F3;
                border-radius: 8px;
                background-color: #f8f9ff;
            }

            .week-selector-container h2 {
                color: #2196F3;
                margin-top: 0;
                margin-bottom: 20px;
                border-bottom: 1px solid #2196F3;
                padding-bottom: 10px;
            }

            .week-selector {
                display: flex;
                align-items: center;
                gap: 15px;
                flex-wrap: wrap;
            }

            .week-selector label {
                font-weight: bold;
                color: #333;
                font-size: 16px;
            }

            .week-selector select {
                padding: 10px 15px;
                border: 2px solid #ddd;
                border-radius: 6px;
                background-color: white;
                font-size: 16px;
                min-width: 300px;
                cursor: pointer;
                transition: border-color 0.3s;
            }

            .week-selector select:focus {
                outline: none;
                border-color: #2196F3;
                box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
            }

            .week-info {
                padding: 8px 12px;
                background-color: #e3f2fd;
                border-radius: 4px;
                border-left: 4px solid #2196F3;
            }

            .week-period {
                font-weight: 500;
                color: #1976d2;
                font-size: 14px;
            }

            /* 🆕 STYLES POUR INFO GRAPHIQUES */
            .week-chart-info {
                margin: 20px 0;
                padding: 15px;
                background-color: #f5f5f5;
                border-radius: 6px;
                border-left: 4px solid #4CAF50;
            }

            .week-chart-info p {
                margin: 5px 0;
                font-size: 14px;
                color: #555;
            }

            /* Styles existants pour l'affichage par semaine */
            .weekly-summary-section {
                margin-top: 40px;
                border-top: 2px solid #eee;
                padding-top: 30px;
            }

            .weekly-summary {
                margin-bottom: 20px;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                background-color: #fafafa;
            }

            .weekly-summary h4 {
                margin: 0 0 10px 0;
                color: #333;
                font-size: 16px;
            }

            .weekly-processes {
                width: 100%;
            }

            .process-charge {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }

            .process-charge:last-child {
                border-bottom: none;
            }

            .process-name {
                font-weight: 500;
                color: #555;
            }

            .charge-value {
                font-weight: bold;
                color: #2196F3;
            }

            /* Styles existants pour les graphiques */
            .graphiques-container {
                margin-top: 30px;
            }

            .graphiques-container h2 {
                color: #333;
                border-bottom: 2px solid #2196F3;
                padding-bottom: 10px;
                margin-bottom: 30px;
            }

            .graphique-section {
                margin-bottom: 40px;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 8px;
                background-color: #fafafa;
                text-align: center;
            }

            .graphique-section h3 {
                color: #333;
                margin-top: 0;
                margin-bottom: 15px;
                font-size: 18px;
            }

            .chart-image {
                max-width: 100%;
                height: auto;
                border: 1px solid #ccc;
                border-radius: 4px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            .chart-description {
                margin-top: 10px;
                color: #666;
                font-style: italic;
                font-size: 14px;
            }

            .chart-placeholder {
                height: 200px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                border: 2px dashed #ccc;
                border-radius: 8px;
                background-color: #f9f9f9;
                color: #666;
                font-style: italic;
            }

            .chart-placeholder small {
                margin-top: 5px;
                font-size: 12px;
                color: #999;
            }

            /* STYLES POUR LES ONGLETS DE GRAPHIQUES */
            .graphiques-tabs {
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
                justify-content: center;
            }

            .tab-button {
                background-color: #f5f5f5;
                color: #333;
                border: 2px solid #ddd;
                padding: 12px 20px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 16px;
                font-weight: bold;
                transition: all 0.3s;
            }

            .tab-button:hover {
                background-color: #e0e0e0;
                border-color: #bbb;
            }

            .tab-button.active {
                background-color: #2196F3;
                color: white;
                border-color: #2196F3;
            }

            .chart-content {
                display: block;
            }

            .chart-content.hidden {
                display: none;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .week-selector {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .week-selector select {
                    min-width: 100%;
                }

                .graphiques-tabs {
                    flex-direction: column;
                    gap: 5px;
                }

                .tab-button {
                    padding: 10px 15px;
                    font-size: 14px;
                }

                .graphique-section {
                    padding: 15px;
                }

                .chart-image {
                    width: 100%;
                }
            }
        </style>

        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}