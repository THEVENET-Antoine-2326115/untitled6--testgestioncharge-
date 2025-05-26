<?php
namespace modules\blog\views;

/**
 * Classe ChargeView
 *
 * Cette classe gère l'affichage de l'analyse de charge.
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
     * Affiche l'analyse de charge
     *
     * @param array $userInfo Informations de l'utilisateur
     * @param string $fileName Nom du fichier Excel
     * @param array $resultats Résultats de l'analyse de charge
     * @return string Le contenu HTML généré
     */
    public function showChargeAnalysis($userInfo, $fileName, $resultats) {
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
                    <p>Données analysé: <?php echo htmlspecialchars($fileName); ?></p>
                </div>

                <!-- Nouvelle section : Charge par processus et par semaine -->
                <?php if (!empty($resultats['chargeParSemaine'])): ?>
                    <div class="summary-box">
                        <div class="summary-title">Charge par processus et par semaine</div>

                        <?php foreach ($resultats['chargeParSemaine'] as $semaine): ?>
                            <div class="weekly-summary">
                                <h4>Semaine du <?php echo htmlspecialchars($semaine['debut']); ?> au <?php echo htmlspecialchars($semaine['fin']); ?></h4>
                                <div class="weekly-details">
                                    <div class="weekly-processes">
                                        <?php foreach ($semaine['processus'] as $processus => $charge): ?>
                                            <div class="process-charge">
                                                <span class="process-name"><?php echo htmlspecialchars($processus); ?>:</span>
                                                <span class="charge-value"><?php echo number_format($charge, 2); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="charge-container">
                    <h2>Répartition de la charge par jour</h2>

                    <?php if (empty($resultats['donneesMensuelles'])): ?>
                        <p>Aucune donnée de charge disponible.</p>
                    <?php else: ?>
                        <?php foreach ($resultats['donneesMensuelles'] as $mois => $jours): ?>
                            <div class="month-section">
                                <h3 class="month-title"><?php echo htmlspecialchars($mois); ?></h3>

                                <table class="charge-table">
                                    <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Jour</th>
                                        <th>Processus</th>
                                        <th>Tâches</th>
                                        <th>Personne</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($jours as $jour): ?>
                                        <tr <?php
                                        $isWeekend = $jour['estWeekend'] ?? ($jour['jour_semaine'] === 'Samedi' || $jour['jour_semaine'] === 'Dimanche');

                                        if ($isWeekend) {
                                            echo 'class="weekend"';
                                        }
                                        ?>>
                                            <td><?php echo htmlspecialchars($jour['date']); ?></td>
                                            <td><?php echo htmlspecialchars($jour['jour_semaine']); ?></td>
                                            <td><?php echo htmlspecialchars($jour['processus'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($jour['taches']); ?></td>
                                            <td><?php echo htmlspecialchars($jour['charge']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
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