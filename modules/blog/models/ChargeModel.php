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
        $this->importModel = new ImportModel();
    }

    /**
     * Analyser les données de la base de données pour obtenir la charge par période
     *
     * @return array Données de charge analysées
     */
    public function analyserChargeParPeriode() {
        // Récupérer les données depuis ImportModel
        $donneesDb = $this->importModel->getAllData();

        if (empty($donneesDb)) {
            return [
                'error' => "Aucune donnée disponible dans la base de données."
            ];
        }

        // Convertir les données de la BD en format utilisable pour l'analyse
        $chargeParJour = $this->convertDbDataToChargeData($donneesDb);

        // Trouver la date de début et de fin globale du projet
        $dates = array_keys($chargeParJour);
        sort($dates);
        $dateDebut = new \DateTime(reset($dates));
        $dateFin = new \DateTime(end($dates));

        // Identifier les périodes de surcharge (charge > 100%)
        $periodesSurcharge = array_filter($chargeParJour, function($jour) {
            return $jour['surcharge'];
        });

        // Identifier les périodes de charge pleine (charge = 100%)
        $periodesChargePleine = array_filter($chargeParJour, function($jour) {
            return $jour['chargePleine'] ?? false;
        });

        return [
            'chargeParJour' => array_values($chargeParJour),
            'periodesSurcharge' => array_values($periodesSurcharge),
            'periodesChargePleine' => array_values($periodesChargePleine),
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin
        ];
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
                    'chargePleine' => false,
                    'surcharge' => false,
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

        // Calculer les indicateurs de charge pour chaque jour
        foreach ($chargeParJour as $date => &$jour) {
            $charge = $jour['charge'];
            $jour['chargePleine'] = (abs($charge - 1.0) < 0.01); // Charge à 100% (avec tolérance)
            $jour['surcharge'] = ($charge > 1.0); // Surcharge si > 100%
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
                    'surcharge' => false,
                    'chargePleine' => false,
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
                    'surcharge' => $jour['surcharge'],
                    'chargePleine' => $jour['chargePleine'] ?? false,
                    'jour_semaine' => $joursEnFrancais[$date->format('N')],
                    'estWeekend' => false
                ];
            }
        }

        if (!empty($donneesMois)) {
            $donneesMensuellesFormat[$moisActuel] = $donneesMois;
        }

        // Formater les périodes de surcharge pour mise en évidence
        $surcharges = [];
        foreach ($resultatAnalyse['periodesSurcharge'] as $jour) {
            $surcharges[] = [
                'date' => $jour['date']->format('d/m/Y'),
                'charge' => number_format($jour['charge'], 2),
                'taches' => implode(', ', $jour['taches'])
            ];
        }

        // Formater les périodes de charge pleine (100%)
        $chargesPleine = [];
        if (isset($resultatAnalyse['periodesChargePleine'])) {
            foreach ($resultatAnalyse['periodesChargePleine'] as $jour) {
                $chargesPleine[] = [
                    'date' => $jour['date']->format('d/m/Y'),
                    'charge' => number_format($jour['charge'], 2),
                    'taches' => implode(', ', $jour['taches'])
                ];
            }
        }

        return [
            'donneesMensuelles' => $donneesMensuellesFormat,
            'surcharges' => $surcharges,
            'chargesPleine' => $chargesPleine,
            'dateDebut' => $resultatAnalyse['dateDebut']->format('d/m/Y'),
            'dateFin' => $resultatAnalyse['dateFin']->format('d/m/Y')
        ];
    }
}