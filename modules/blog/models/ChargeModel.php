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
        // Mapping des processus vers les catégories (MISE À JOUR avec Qualité)
        $mappingProcessus = [
            'production' => ['CHAUDNQ', 'SOUDNQ', 'CT'],
            'etude' => ['CALC', 'PROJ'],
            'methode' => ['METH'],
            'qualite' => ['QUAL', 'QUALS']  // 🆕 NOUVEAU
        ];

        // Labels des semaines et initialisation des données
        $semainesLabels = [];
        $donnees = [
            'production' => ['CHAUDNQ' => [], 'SOUDNQ' => [], 'CT' => []],
            'etude' => ['CALC' => [], 'PROJ' => []],
            'methode' => ['METH' => []],
            'qualite' => ['QUAL' => [], 'QUALS' => []]  // 🆕 NOUVEAU
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
     * 🆕 Récupère la liste des semaines disponibles (présentes et futures uniquement)
     *
     * @return array Liste des semaines avec leurs informations
     */
    public function getAvailableWeeks() {
        echo "<script>console.log('[ChargeModel] === RÉCUPÉRATION SEMAINES DISPONIBLES ===');</script>";

        // Récupérer toutes les données depuis ImportModel
        $donneesDb = $this->importModel->getAllData();

        if (empty($donneesDb)) {
            echo "<script>console.log('[ChargeModel] Aucune donnée disponible');</script>";
            return [];
        }

        // Filtrer pour ne garder que les données présentes et futures
        $donneesFiltrees = $this->filterFutureAndTodayData($donneesDb);

        if (empty($donneesFiltrees)) {
            echo "<script>console.log('[ChargeModel] Aucune donnée présente/future');</script>";
            return [];
        }

        // Grouper par semaines
        $semaines = [];
        foreach ($donneesFiltrees as $donnee) {
            $date = new \DateTime($donnee['Date']);

            // Calculer le début de la semaine (lundi)
            $debutSemaine = clone $date;
            $jourSemaine = $date->format('N'); // 1 = lundi, 7 = dimanche
            $debutSemaine->sub(new \DateInterval('P' . ($jourSemaine - 1) . 'D'));

            // Calculer la fin de la semaine (dimanche)
            $finSemaine = clone $debutSemaine;
            $finSemaine->add(new \DateInterval('P6D'));

            // Créer l'identifiant unique de la semaine
            $weekId = $debutSemaine->format('Y-m-d');

            if (!isset($semaines[$weekId])) {
                $semaines[$weekId] = [
                    'value' => $weekId,
                    'debut' => $debutSemaine->format('d/m/Y'),
                    'fin' => $finSemaine->format('d/m/Y'),
                    'label' => 'Semaine du ' . $debutSemaine->format('d/m') . ' au ' . $finSemaine->format('d/m/Y'),
                    'debut_obj' => clone $debutSemaine,
                    'fin_obj' => clone $finSemaine
                ];
            }
        }

        // Trier par date de début (plus récente en premier)
        uasort($semaines, function($a, $b) {
            return $a['debut_obj'] <=> $b['debut_obj'];
        });

        // Supprimer les objets DateTime pour l'affichage
        $semainesFormatees = [];
        foreach ($semaines as $semaine) {
            unset($semaine['debut_obj'], $semaine['fin_obj']);
            $semainesFormatees[] = $semaine;
        }

        echo "<script>console.log('[ChargeModel] Semaines trouvées: " . count($semainesFormatees) . "');</script>";
        foreach ($semainesFormatees as $semaine) {
            echo "<script>console.log('[ChargeModel] - " . addslashes($semaine['label']) . " (ID: " . addslashes($semaine['value']) . ")');</script>";
        }

        return $semainesFormatees;
    }

    /**
     * 🆕 Récupère les données quotidiennes pour une semaine spécifique
     *
     * @param string $weekStartDate Date de début de la semaine (format Y-m-d)
     * @return array Données formatées pour cette semaine
     */
    public function getDailyDataForWeek($weekStartDate) {
        echo "<script>console.log('[ChargeModel] === RÉCUPÉRATION DONNÉES POUR SEMAINE: " . addslashes($weekStartDate) . " ===');</script>";

        try {
            $debutSemaine = new \DateTime($weekStartDate);
            $finSemaine = clone $debutSemaine;
            $finSemaine->add(new \DateInterval('P6D')); // +6 jours = dimanche

            echo "<script>console.log('[ChargeModel] Période: " . addslashes($debutSemaine->format('Y-m-d')) . " au " . addslashes($finSemaine->format('Y-m-d')) . "');</script>";

        } catch (\Exception $e) {
            echo "<script>console.log('[ChargeModel] Erreur parsing date: " . addslashes($e->getMessage()) . "');</script>";
            return ['error' => 'Date de semaine invalide: ' . $weekStartDate];
        }

        // Récupérer toutes les données depuis ImportModel
        $donneesDb = $this->importModel->getAllData();

        if (empty($donneesDb)) {
            return ['error' => 'Aucune donnée disponible dans la base de données.'];
        }

        // Filtrer les données pour cette semaine uniquement
        $donneesSemine = [];
        foreach ($donneesDb as $donnee) {
            try {
                $dateDonnee = new \DateTime($donnee['Date']);

                // Vérifier si la date est dans la semaine
                if ($dateDonnee >= $debutSemaine && $dateDonnee <= $finSemaine) {
                    $donneesSemine[] = $donnee;
                }
            } catch (\Exception $e) {
                echo "<script>console.log('[ChargeModel] Erreur parsing date donnée: " . addslashes($e->getMessage()) . "');</script>";
                continue;
            }
        }

        echo "<script>console.log('[ChargeModel] Données trouvées pour cette semaine: " . count($donneesSemine) . "');</script>";

        if (empty($donneesSemine)) {
            return ['error' => 'Aucune donnée trouvée pour cette semaine.'];
        }

        // Convertir en format graphique par jour
        $graphiquesData = $this->prepareWeeklyGraphicsData($donneesSemine, $debutSemaine, $finSemaine);

        return [
            'graphiquesData' => $graphiquesData,
            'debutSemaine' => $debutSemaine,
            'finSemaine' => $finSemaine,
            'donneesCount' => count($donneesSemine)
        ];
    }

    /**
     * 🆕 Prépare les données graphiques pour une semaine (7 jours)
     *
     * @param array $donneesSemine Données de la semaine
     * @param \DateTime $debutSemaine Date de début
     * @param \DateTime $finSemaine Date de fin
     * @return array Données formatées pour JPGraph
     */
    private function prepareWeeklyGraphicsData($donneesSemine, $debutSemaine, $finSemaine) {
        echo "<script>console.log('[ChargeModel] === PRÉPARATION DONNÉES GRAPHIQUES HEBDOMADAIRES ===');</script>";

        // Mapping des processus vers les catégories
        $mappingProcessus = [
            'production' => ['CHAUDNQ', 'SOUDNQ', 'CT'],
            'etude' => ['CALC', 'PROJ'],
            'methode' => ['METH'],
            'qualite' => ['QUAL', 'QUALS']
        ];

        // Créer les labels des 7 jours de la semaine
        $joursLabels = [];
        $joursDate = [];
        $current = clone $debutSemaine;

        $nomsJours = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

        for ($i = 0; $i < 7; $i++) {
            $jourLabel = $nomsJours[$i] . ' ' . $current->format('d/m');
            $joursLabels[] = $jourLabel;
            $joursDate[] = $current->format('Y-m-d');
            $current->add(new \DateInterval('P1D'));
        }

        echo "<script>console.log('[ChargeModel] Labels jours: " . addslashes(json_encode($joursLabels)) . "');</script>";

        // Initialiser les données par catégorie et par processus (7 jours)
        $donnees = [
            'production' => ['CHAUDNQ' => array_fill(0, 7, 0), 'SOUDNQ' => array_fill(0, 7, 0), 'CT' => array_fill(0, 7, 0)],
            'etude' => ['CALC' => array_fill(0, 7, 0), 'PROJ' => array_fill(0, 7, 0)],
            'methode' => ['METH' => array_fill(0, 7, 0)],
            'qualite' => ['QUAL' => array_fill(0, 7, 0), 'QUALS' => array_fill(0, 7, 0)]
        ];

        // Remplir les données jour par jour
        foreach ($donneesSemine as $donnee) {
            $dateData = $donnee['Date'];
            $processus = $donnee['Processus'];
            $charge = floatval($donnee['Charge']);

            // Trouver l'index du jour (0-6)
            $indexJour = array_search($dateData, $joursDate);

            if ($indexJour === false) {
                echo "<script>console.log('[ChargeModel] Date non trouvée dans la semaine: " . addslashes($dateData) . "');</script>";
                continue;
            }

            // Trouver la catégorie du processus
            $categorieProcessus = null;
            foreach ($mappingProcessus as $categorie => $processusListe) {
                if (in_array($processus, $processusListe)) {
                    $categorieProcessus = $categorie;
                    break;
                }
            }

            if ($categorieProcessus && isset($donnees[$categorieProcessus][$processus])) {
                $donnees[$categorieProcessus][$processus][$indexJour] += $charge;
                echo "<script>console.log('[ChargeModel] Ajout: " . addslashes($processus) . " (" . addslashes($categorieProcessus) . ") jour " . $indexJour . " = +" . $charge . "');</script>";
            } else {
                echo "<script>console.log('[ChargeModel] Processus ignoré: " . addslashes($processus) . " (catégorie non trouvée)');</script>";
            }
        }

        // Ajouter les labels aux données
        $graphiquesData = array_merge($donnees, ['jours_labels' => $joursLabels]);

        // Log des totaux par catégorie
        foreach ($mappingProcessus as $categorie => $processusListe) {
            $totalCategorie = 0;
            foreach ($processusListe as $proc) {
                if (isset($donnees[$categorie][$proc])) {
                    $totalProc = array_sum($donnees[$categorie][$proc]);
                    $totalCategorie += $totalProc;
                    if ($totalProc > 0) {
                        echo "<script>console.log('[ChargeModel] Total " . addslashes($proc) . ": " . $totalProc . "');</script>";
                    }
                }
            }
            echo "<script>console.log('[ChargeModel] Total catégorie " . addslashes($categorie) . ": " . $totalCategorie . "');</script>";
        }

        echo "<script>console.log('[ChargeModel] Données graphiques hebdomadaires préparées');</script>";

        return $graphiquesData;
    }

}