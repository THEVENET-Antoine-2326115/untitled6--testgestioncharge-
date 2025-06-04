<?php
namespace modules\blog\models;

/**
 * Classe ChargeModel
 *
 * Cette classe gère l'analyse de la charge de travail à partir des données
 * récupérées depuis la base de données via ImportModel.
 */
class ChargeModel {
    /**
     * @var ImportModel $importModel Instance pour récupérer les données
     */
    private $importModel;

    /**
     * Constructeur de ChargeModel
     */
    public function __construct() {
        // Utiliser le Singleton pour ImportModel - MÊME instance que DashboardModel !
        $this->importModel = ImportModel::getInstance();
    }

    /**
     * Analyser les données de la base de données pour obtenir la charge par période
     *
     * @return array Données de charge analysées
     */
    public function analyserChargeParPeriode() {
        // Récupérer les données depuis ImportModel (même instance que Dashboard)
        $donneesDb = $this->importModel->getAllData();

        if (empty($donneesDb)) {
            return [
                'error' => "Aucune donnée disponible dans la base de données."
            ];
        }

        // Filtrer pour ne garder que les jours présents et futurs
        $donneesFiltrees = $this->filterFutureAndTodayData($donneesDb);

        if (empty($donneesFiltrees)) {
            return [
                'error' => "Aucune donnée disponible pour les jours présents et futurs."
            ];
        }

        // Convertir les données filtrées en format utilisable pour l'analyse
        $chargeParJour = $this->convertDbDataToChargeData($donneesFiltrees);

        // Calculer la charge par processus et par semaine (données filtrées)
        $chargeParSemaine = $this->calculateWeeklyChargeByProcess($donneesFiltrees);

        // Trouver la date de début et de fin globale du projet
        $dates = array_keys($chargeParJour);
        sort($dates);
        $dateDebut = new \DateTime(reset($dates));
        $dateFin = new \DateTime(end($dates));

        return [
            'chargeParJour' => array_values($chargeParJour),
            'chargeParSemaine' => $chargeParSemaine,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin
        ];
    }

    /**
     * Filtre les données pour ne garder que les jours présents et futurs
     *
     * @param array $donneesDb Données de la base de données
     * @return array Données filtrées
     */
    private function filterFutureAndTodayData($donneesDb) {
        // Obtenir la date d'aujourd'hui à minuit pour la comparaison
        $aujourdhui = new \DateTime();
        $aujourdhui->setTime(0, 0, 0); // Minuit aujourd'hui

        $donneesFiltrees = [];

        foreach ($donneesDb as $donnee) {
            $dateDonnee = new \DateTime($donnee['Date']);
            $dateDonnee->setTime(0, 0, 0); // Minuit pour cette date

            // Garder seulement si la date est aujourd'hui ou dans le futur
            if ($dateDonnee >= $aujourdhui) {
                $donneesFiltrees[] = $donnee;
            }
        }

        return $donneesFiltrees;
    }

    /**
     * Calcule la charge par processus et par semaine
     *
     * @param array $donneesDb Données de la base de données
     * @return array Charge par semaine et par processus
     */
    private function calculateWeeklyChargeByProcess($donneesDb) {
        $chargeParSemaine = [];

        foreach ($donneesDb as $donnee) {
            $date = new \DateTime($donnee['Date']);
            $processus = $donnee['Processus'];
            $charge = floatval($donnee['Charge']);

            // Calculer le début de la semaine (lundi)
            $debutSemaine = clone $date;
            $jourSemaine = $date->format('N'); // 1 = lundi, 7 = dimanche
            $debutSemaine->sub(new \DateInterval('P' . ($jourSemaine - 1) . 'D'));

            // Calculer la fin de la semaine (dimanche)
            $finSemaine = clone $debutSemaine;
            $finSemaine->add(new \DateInterval('P6D'));

            // Créer la clé de semaine
            $cleSemaine = $debutSemaine->format('Y-m-d') . '_' . $finSemaine->format('Y-m-d');

            // Initialiser la semaine si nécessaire
            if (!isset($chargeParSemaine[$cleSemaine])) {
                $chargeParSemaine[$cleSemaine] = [
                    'debut' => $debutSemaine,
                    'fin' => $finSemaine,
                    'processus' => [],
                    'total' => 0
                ];
            }

            // Initialiser le processus si nécessaire
            if (!isset($chargeParSemaine[$cleSemaine]['processus'][$processus])) {
                $chargeParSemaine[$cleSemaine]['processus'][$processus] = 0;
            }

            // Ajouter la charge
            $chargeParSemaine[$cleSemaine]['processus'][$processus] += $charge;
            $chargeParSemaine[$cleSemaine]['total'] += $charge;
        }

        // Trier par date de début de semaine
        ksort($chargeParSemaine);

        return $chargeParSemaine;
    }

    /**
     * Convertit les données de la base de données en format d'analyse de charge
     *
     * @param array $donneesDb Données de la base de données
     * @return array Données formatées pour l'analyse
     */
    private function convertDbDataToChargeData($donneesDb) {
        $chargeParJour = [];

        // Grouper les données par date
        foreach ($donneesDb as $donnee) {
            $date = $donnee['Date'];
            $processus = $donnee['Processus'];
            $tache = $donnee['Tache'];
            $charge = floatval($donnee['Charge']);

            // Initialiser le jour s'il n'existe pas
            if (!isset($chargeParJour[$date])) {
                $dateObj = new \DateTime($date);
                $jourSemaine = $dateObj->format('N'); // 1 (lundi) à 7 (dimanche)
                $estWeekend = ($jourSemaine == 6 || $jourSemaine == 7);

                $chargeParJour[$date] = [
                    'date' => $dateObj,
                    'charge' => 0,
                    'taches' => [],
                    'processus' => [],
                    'estWeekend' => $estWeekend
                ];
            }

            // Ajouter la charge et les informations
            $chargeParJour[$date]['charge'] += $charge;

            // Ajouter la tâche si pas déjà présente
            if (!in_array($tache, $chargeParJour[$date]['taches'])) {
                $chargeParJour[$date]['taches'][] = $tache;
            }

            // Ajouter le processus si pas déjà présent
            if (!in_array($processus, $chargeParJour[$date]['processus'])) {
                $chargeParJour[$date]['processus'][] = $processus;
            }
        }

        return $chargeParJour;
    }

    /**
     * Formater les résultats pour l'affichage
     *
     * @param array $resultatAnalyse Résultat de l'analyse de charge
     * @return array Données formatées pour l'affichage
     */
    public function formaterResultats($resultatAnalyse) {
        $donneesMensuellesFormat = [];
        $moisActuel = '';
        $donneesMois = [];

        // Définir les noms des jours de la semaine en français
        $joursEnFrancais = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche'
        ];

        // Traiter tous les jours analysés
        foreach ($resultatAnalyse['chargeParJour'] as $jour) {
            $date = $jour['date'];
            $mois = $date->format('F Y'); // Nom du mois et année

            if ($mois !== $moisActuel) {
                if (!empty($donneesMois)) {
                    $donneesMensuellesFormat[$moisActuel] = $donneesMois;
                }
                $moisActuel = $mois;
                $donneesMois = [];
            }

            // Préparer les processus pour l'affichage
            $processusTexte = implode(', ', $jour['processus']);

            // Déterminer l'affichage en fonction du type de jour
            if ($jour['estWeekend']) {
                // Week-end - pas de charge
                $donneesMois[] = [
                    'date' => $date->format('d/m/Y'),
                    'charge' => '0',
                    'taches' => '',
                    'processus' => '',
                    'jour_semaine' => $joursEnFrancais[$date->format('N')],
                    'estWeekend' => true
                ];
            } else {
                // Jour de semaine - afficher la charge et les tâches
                $donneesMois[] = [
                    'date' => $date->format('d/m/Y'),
                    'charge' => number_format($jour['charge'], 2),
                    'taches' => implode(', ', $jour['taches']),
                    'processus' => $processusTexte,
                    'jour_semaine' => $joursEnFrancais[$date->format('N')],
                    'estWeekend' => false
                ];
            }
        }

        if (!empty($donneesMois)) {
            $donneesMensuellesFormat[$moisActuel] = $donneesMois;
        }

        // Formater les données de charge par semaine
        $chargeParSemaineFormatee = [];
        if (isset($resultatAnalyse['chargeParSemaine'])) {
            foreach ($resultatAnalyse['chargeParSemaine'] as $semaine) {
                $chargeParSemaineFormatee[] = [
                    'debut' => $semaine['debut']->format('d/m/Y'),
                    'fin' => $semaine['fin']->format('d/m/Y'),
                    'processus' => $semaine['processus'],
                    'total' => number_format($semaine['total'], 2)
                ];
            }
        }

        // NOUVEAU : Préparer les données pour les graphiques
        $graphiquesData = $this->prepareGraphicsData($resultatAnalyse['chargeParSemaine']);

        return [
            'donneesMensuelles' => $donneesMensuellesFormat,
            'chargeParSemaine' => $chargeParSemaineFormatee,
            'dateDebut' => $resultatAnalyse['dateDebut']->format('d/m/Y'),
            'dateFin' => $resultatAnalyse['dateFin']->format('d/m/Y'),
            'graphiquesData' => $graphiquesData // AJOUT
        ];
    }

    /**
     * Prépare les données pour les graphiques JPGraph
     *
     * @param array $chargeParSemaine Données de charge par semaine
     * @return array Données formatées pour JPGraph
     */
    private function prepareGraphicsData($chargeParSemaine) {
        // Mapping des processus vers les catégories
        $mappingProcessus = [
            'production' => ['CHAUDNQ', 'SOUDNQ', 'CT'],
            'etude' => ['CALC', 'PROJ'],
            'methode' => ['METH']
        ];

        // Labels des semaines et initialisation des données
        $semainesLabels = [];
        $donnees = [
            'production' => ['CHAUDNQ' => [], 'SOUDNQ' => [], 'CT' => []],
            'etude' => ['CALC' => [], 'PROJ' => []],
            'methode' => ['METH' => []]
        ];

        // Debug
        echo "<script>console.log('[ChargeModel] Préparation données graphiques...');</script>";
        echo "<script>console.log('[ChargeModel] Nombre de semaines: " . count($chargeParSemaine) . "');</script>";

        // Parcourir chaque semaine
        foreach ($chargeParSemaine as $semaine) {
            // Créer le label de la semaine
            $label = $semaine['debut']->format('d/m') . ' - ' . $semaine['fin']->format('d/m');
            $semainesLabels[] = $label;

            echo "<script>console.log('[ChargeModel] Semaine: " . $label . "');</script>";

            // Initialiser les valeurs à 0 pour cette semaine
            foreach ($mappingProcessus as $categorie => $processus) {
                foreach ($processus as $proc) {
                    $charge = $semaine['processus'][$proc] ?? 0;
                    $donnees[$categorie][$proc][] = $charge;

                    if ($charge > 0) {
                        echo "<script>console.log('[ChargeModel] " . $proc . " (" . $categorie . "): " . $charge . "');</script>";
                    }
                }
            }
        }

        $graphiquesData = array_merge($donnees, ['semaines_labels' => $semainesLabels]);

        echo "<script>console.log('[ChargeModel] Données graphiques préparées');</script>";
        echo "<script>console.log('[ChargeModel] Labels: " . json_encode($semainesLabels) . "');</script>";

        return $graphiquesData;
    }


    /**
     * Supprime TOUS les fichiers dans le dossier des graphiques
     *
     * Cette fonction nettoie COMPLÈTEMENT le dossier _assets/images/ en supprimant
     * TOUS les fichiers (peu importe l'extension).
     *
     * @return array Résultat de l'opération avec compteurs et messages
     */

    public function nettoyerGraphiquesPng() {
        // 🆕 FONCTION DE LOG INTÉGRÉE (Option A)
        $console_log = function($message) {
            echo "<script>console.log('[ChargeModel] " . addslashes($message) . "');</script>";
        };

        $console_log("=== DÉBUT NETTOYAGE COMPLET DU DOSSIER ===");

        // Définir le dossier des images (même chemin que GraphGeneratorModel)
        $imageFolder = __DIR__ . '/../../../_assets/images/';

        $console_log("Dossier cible: " . $imageFolder);
        $console_log("Chemin absolu: " . realpath($imageFolder));

        // Résultat de l'opération
        $resultat = [
            'success' => false,
            'message' => '',
            'fichiers_trouves' => 0,
            'fichiers_supprimes' => 0,
            'erreurs' => 0,
            'liste_fichiers' => []
        ];

        try {
            $console_log("=== SCAN DU DOSSIER ===");

            // Parcourir le dossier
            $fichiers = scandir($imageFolder);
            $console_log("Éléments retournés par scandir(): " . count($fichiers));
            $console_log("Contenu brut du dossier: " . implode(', ', $fichiers));

            foreach ($fichiers as $fichier) {
                $console_log("--- Examen de: " . $fichier . " ---");

                // Ignorer les dossiers spéciaux
                if ($fichier === '.' || $fichier === '..') {
                    $console_log("Ignoré: dossier spécial");
                    continue;
                }

                $cheminComplet = $imageFolder . $fichier;
                $console_log("Chemin complet: " . $cheminComplet);

                // Vérifier que c'est un fichier
                if (!is_file($cheminComplet)) {
                    $console_log("Ignoré: pas un fichier (probablement un dossier)");
                    continue;
                }

                // 🆕 SUPPRIMER TOUS LES FICHIERS (plus de filtrage par extension)
                $resultat['fichiers_trouves']++;
                $resultat['liste_fichiers'][] = $fichier;

                $console_log("✓ FICHIER TROUVÉ: " . $fichier . " (sera supprimé)");

                // Tentative de suppression
                $console_log("Tentative de suppression...");

                if (unlink($cheminComplet)) {
                    $resultat['fichiers_supprimes']++;
                    $console_log("✅ SUPPRIMÉ AVEC SUCCÈS: " . $fichier);
                } else {
                    $resultat['erreurs']++;
                    $console_log("❌ ERREUR SUPPRESSION: " . $fichier);
                }
            }

            $console_log("=== BILAN FINAL ===");
            $console_log("Fichiers trouvés: " . $resultat['fichiers_trouves']);
            $console_log("Fichiers supprimés: " . $resultat['fichiers_supprimes']);
            $console_log("Erreurs: " . $resultat['erreurs']);

            // Déterminer le succès global
            $resultat['success'] = ($resultat['erreurs'] === 0);

            // Message de résumé
            if ($resultat['fichiers_trouves'] === 0) {
                $resultat['message'] = "Aucun fichier trouvé dans le dossier.";
                $console_log("RÉSULTAT: Dossier déjà vide");
            } else {
                $resultat['message'] = sprintf(
                    "Nettoyage terminé: %d fichier(s) trouvé(s), %d supprimé(s), %d erreur(s).",
                    $resultat['fichiers_trouves'],
                    $resultat['fichiers_supprimes'],
                    $resultat['erreurs']
                );
                $console_log("RÉSULTAT: " . $resultat['message']);
            }

        } catch (\Exception $e) {
            $console_log("💥 EXCEPTION: " . $e->getMessage());
            $console_log("Type d'exception: " . get_class($e));
            $console_log("Ligne: " . $e->getLine());
            $console_log("Fichier: " . $e->getFile());

            $resultat['success'] = false;
            $resultat['message'] = "Erreur lors du nettoyage: " . $e->getMessage();
            $resultat['erreurs']++;
        }

        $console_log("=== FIN NETTOYAGE COMPLET DU DOSSIER ===");

        return $resultat;
    }



}