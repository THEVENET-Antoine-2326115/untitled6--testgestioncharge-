<?php
namespace modules\blog\models;

/**
 * Classe ChargeModel
 *
 * Cette classe gère l'analyse de la charge de travail à partir des données Excel.
 */
class ChargeModel {
    /**
     * Analyser les données Excel pour obtenir la charge par période
     *
     * @param array $excelData Données du fichier Excel
     * @return array Données de charge analysées
     */
    public function analyserChargeParPeriode($excelData) {
        // Vérifier que les feuilles nécessaires existent
        if (!isset($excelData['Table_tâches']) || !isset($excelData['Table_affectation'])) {
            return [
                'error' => "Les feuilles Table_tâches et Table_affectation sont requises."
            ];
        }

        // Récupérer les données des tâches et des affectations
        $taches = $excelData['Table_tâches']['rows'];
        $affectations = $excelData['Table_affectation']['rows'];

        // Créer une structure de données pour les tâches avec leurs dates et noms
        $tachesAvecDates = [];
        foreach ($taches as $tache) {
            if (!isset($tache['Nom']) || !isset($tache['Début']) || !isset($tache['Fin'])) {
                continue;
            }

            $nomTache = $tache['Nom'];
            $tachesAvecDates[] = [
                'nom' => $nomTache,
                'debut' => $this->parseDate($tache['Début']),
                'fin' => $this->parseDate($tache['Fin']),
                'capacite' => 0, // Sera mis à jour avec les données d'affectation
                'processus' => '' // Sera mis à jour avec les données d'affectation
            ];
        }

        // Associer les capacités des affectations aux tâches
        // Utiliser un tableau associatif pour éviter les doublons
        $tachesAssociees = [];
        foreach ($tachesAvecDates as $tache) {
            $nomTache = $tache['nom'];

            // Si cette tâche existe déjà, on passe
            if (isset($tachesAssociees[$nomTache])) {
                continue;
            }

            foreach ($affectations as $affectation) {
                if (!isset($affectation['Nom de la tâche']) || !isset($affectation['Capacité'])) {
                    continue;
                }

                if ($affectation['Nom de la tâche'] === $nomTache) {
                    // Convertir la capacité de "20%" à 0.2
                    $capacite = str_replace('%', '', $affectation['Capacité']);
                    $tache['capacite'] = floatval($capacite) / 100;

                    // Récupérer le nom de la ressource comme processus
                    if (isset($affectation['Nom de la ressource'])) {
                        $tache['processus'] = $affectation['Nom de la ressource'];
                    }

                    break;
                }
            }

            // Ajouter la tâche au tableau associatif pour éviter les doublons
            $tachesAssociees[$nomTache] = $tache;
        }

        // Convertir le tableau associatif en tableau indexé
        $tachesAvecDates = array_values($tachesAssociees);

        // Trouver la date de début et de fin globale du projet
        $dateDebut = null;
        $dateFin = null;
        foreach ($tachesAvecDates as $tache) {
            if ($dateDebut === null || $tache['debut'] < $dateDebut) {
                $dateDebut = $tache['debut'];
            }
            if ($dateFin === null || $tache['fin'] > $dateFin) {
                $dateFin = $tache['fin'];
            }
        }

        // Générer tous les jours entre début et fin
        $jours = $this->getJoursEntreDates($dateDebut, $dateFin);

        // Pour chaque jour, calculer la charge totale
        $chargeParJour = [];
        foreach ($jours as $jour) {
            $tachesDuJour = [];
            $processusDuJour = [];
            $chargeTotal = 0;
            $jourSemaine = $jour->format('N'); // 1 (lundi) à 7 (dimanche)
            $estWeekend = ($jourSemaine == 6 || $jourSemaine == 7); // 6 = samedi, 7 = dimanche

            // Si c'est un weekend, on n'attribue pas de charge
            if (!$estWeekend) {
                foreach ($tachesAvecDates as $tache) {
                    if ($jour >= $tache['debut'] && $jour <= $tache['fin']) {
                        $tachesDuJour[] = $tache['nom'];
                        $processusDuJour[$tache['nom']] = $tache['processus'];
                        $chargeTotal += $tache['capacite'];
                    }
                }
            }

            $chargeParJour[] = [
                'date' => $jour,
                'charge' => $chargeTotal,
                'taches' => $tachesDuJour,
                'processus' => $processusDuJour,
                'chargePleine' => $chargeTotal == 1, // Charge à 100%
                'surcharge' => $chargeTotal > 1,     // Surcharge si > 100%
                'estWeekend' => $estWeekend
            ];
        }

        // Identifier les périodes de surcharge (charge > 100%)
        $periodesSurcharge = array_filter($chargeParJour, function($jour) {
            return $jour['surcharge'];
        });

        // Identifier les périodes de charge pleine (charge = 100%)
        $periodesChargePleine = array_filter($chargeParJour, function($jour) {
            return $jour['chargePleine'];
        });

        return [
            'chargeParJour' => $chargeParJour,
            'periodesSurcharge' => $periodesSurcharge,
            'periodesChargePleine' => $periodesChargePleine,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin
        ];
    }

    /**
     * Parse une date à partir d'une chaîne de caractères
     *
     * @param string $dateStr Format attendu: "DD Mois YYYY HH:MM"
     * @return \DateTime Objet DateTime
     */
    private function parseDate($dateStr) {
        // Format attendu: "DD Mois YYYY HH:MM"
        $moisFr = [
            'Janvier' => '01', 'Février' => '02', 'Mars' => '03', 'Avril' => '04',
            'Mai' => '05', 'Juin' => '06', 'Juillet' => '07', 'Août' => '08',
            'Septembre' => '09', 'Octobre' => '10', 'Novembre' => '11', 'Décembre' => '12'
        ];

        // Remplacer le nom du mois par son numéro
        foreach ($moisFr as $nom => $numero) {
            $dateStr = str_replace($nom, $numero, $dateStr);
        }

        // Convertir en format Y-m-d H:i
        $pattern = '/(\d{1,2}) (\d{1,2}) (\d{4}) (\d{1,2}):(\d{1,2})/';
        $replacement = '$3-$2-$1 $4:$5';
        $dateFormatted = preg_replace($pattern, $replacement, $dateStr);

        return new \DateTime($dateFormatted);
    }

    /**
     * Obtenir tous les jours entre deux dates
     *
     * @param \DateTime $debut Date de début
     * @param \DateTime $fin Date de fin
     * @return array Liste de tous les jours
     */
    private function getJoursEntreDates($debut, $fin) {
        $interval = new \DateInterval('P1D'); // Intervalle d'un jour
        $periode = new \DatePeriod($debut, $interval, $fin);

        $jours = [];
        foreach ($periode as $date) {
            $jours[] = clone $date;
        }

        // Ajouter le jour de fin en évitant un doublon
        $finFormatDate = $fin->format('Y-m-d');
        $dernierJourPresent = false;

        // Vérifier si le jour de fin est déjà présent
        foreach ($jours as $date) {
            if ($date->format('Y-m-d') === $finFormatDate) {
                $dernierJourPresent = true;
                break;
            }
        }

        // N'ajouter le jour de fin que s'il n'est pas déjà présent
        if (!$dernierJourPresent) {
            $jours[] = clone $fin;
        }

        return $jours;
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
            $processusTexte = '';
            if (!empty($jour['processus'])) {
                // Extraire uniquement les valeurs uniques de processus (sans doublons)
                $processusUniques = array_unique(array_values($jour['processus']));

                // Filtrer les valeurs vides
                $processusUniques = array_filter($processusUniques, function($val) {
                    return !empty($val);
                });

                $processusTexte = implode(', ', $processusUniques);
            }

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