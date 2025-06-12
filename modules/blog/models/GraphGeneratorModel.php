<?php
namespace modules\blog\models;

// Utiliser les namespaces modernes d'amenadiel/jpgraph
use Amenadiel\JpGraph\Graph\Graph;
use Amenadiel\JpGraph\Plot\BarPlot;

/**
 * Classe GraphGeneratorModel
 *
 * Cette classe gère la génération des graphiques JPGraph pour l'analyse de charge.
 *
 * VERSION REFACTORISÉE : Génération pour période libre avec affichage par semaines
 * Les graphiques affichent maintenant des moyennes par semaine au lieu de données par jour
 */
class GraphGeneratorModel {

    /**
     * Dossier de stockage des graphiques générés
     */
    const CHARTS_FOLDER = '_assets/images/';

    /**
     * Largeur de base des graphiques (sera ajustée dynamiquement)
     */
    const CHART_BASE_WIDTH = 900;

    /**
     * Largeur minimale des graphiques
     */
    const CHART_MIN_WIDTH = 600;

    /**
     * Largeur maximale des graphiques (pour éviter des fichiers trop volumineux)
     */
    const CHART_MAX_WIDTH = 5000;

    /**
     * Hauteur des graphiques (reste fixe)
     */
    const CHART_HEIGHT = 450;

    /**
     * 🆕 Largeur par semaine (pour calcul dynamique)
     */
    const WIDTH_PER_WEEK = 120;

    /**
     * JPGraph chargé ou non
     */
    private $jpgraphLoaded = false;

    /**
     * Constructeur
     */
    public function __construct() {
        $this->ensureChartsDirectoryExists();
        $this->jpgraphLoaded = $this->loadJpGraph();
    }

    /**
     * Charge JPGraph avec les namespaces modernes
     *
     * @return bool True si JPGraph est chargé avec succès
     */
    private function loadJpGraph() {
        try {
            $this->console_log("Tentative de chargement JPGraph moderne...");

            // Vérifier que les classes sont disponibles via Composer
            if (class_exists('Amenadiel\\JpGraph\\Graph\\Graph') && class_exists('Amenadiel\\JpGraph\\Plot\\BarPlot')) {
                $this->console_log("Classes JPGraph modernes disponibles");
                return true;
            } else {
                $this->console_log("ERREUR: Classes JPGraph modernes non disponibles");
                return false;
            }
        } catch (\Exception $e) {
            $this->console_log("ERREUR chargement JPGraph: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 🆕 Génère les graphiques pour une période libre (nombre de semaines variable)
     *
     * @param array $periodData Données formatées pour la période sélectionnée (par semaines)
     * @return array Chemins des images générées pour chaque catégorie
     */
    public function generatePeriodCharts($periodData) {
        $this->console_log("=== GÉNÉRATION GRAPHIQUES PÉRIODE LIBRE (PAR SEMAINES) ===");
        $this->console_log("JPGraph chargé: " . ($this->jpgraphLoaded ? 'OUI' : 'NON'));

        if (!$this->jpgraphLoaded) {
            $this->console_log("JPGraph non disponible, génération d'images d'erreur");
            return $this->generateErrorImages("JPGraph non installé ou non chargé");
        }

        // 🆕 NETTOYAGE COMPLET avant génération
        $this->cleanupAllCharts();

        $this->console_log("Données reçues pour la période: " . json_encode(array_keys($periodData)));

        // Vérifier la présence des métadonnées de période
        if (!isset($periodData['periode_info'])) {
            $this->console_log("ERREUR: Métadonnées periode_info manquantes");
            return $this->generateErrorImages("Métadonnées de période manquantes");
        }

        $periodeInfo = $periodData['periode_info'];
        $this->console_log("Période: " . $periodeInfo['debut'] . " → " . $periodeInfo['fin'] . " (" . ($periodeInfo['nombre_semaines'] ?? 'N/A') . " semaines)");

        // 🆕 Vérifier la présence des labels de semaines
        if (!isset($periodData['semaines_labels'])) {
            $this->console_log("ERREUR: Labels semaines_labels manquants");
            return $this->generateErrorImages("Labels de semaines manquants");
        }

        $this->console_log("Labels semaines disponibles: " . count($periodData['semaines_labels']));

        $chartPaths = [
            'production' => null,
            'etude' => null,
            'methode' => null,
            'qualite' => null
        ];

        try {
            // 🆕 Calculer la largeur dynamique du graphique (basé sur les semaines)
            $nombreSemaines = $periodeInfo['nombre_semaines'] ?? count($periodData['semaines_labels']);
            $chartWidth = $this->calculateChartWidthWeekly($nombreSemaines);
            $this->console_log("Largeur calculée pour " . $nombreSemaines . " semaines: " . $chartWidth . "px");

            // Vérifier chaque catégorie avant génération
            $categories = ['production', 'etude', 'methode', 'qualite'];
            foreach ($categories as $cat) {
                if (isset($periodData[$cat])) {
                    $totalData = 0;
                    foreach ($periodData[$cat] as $proc => $data) {
                        $sum = array_sum($data);
                        $totalData += $sum;
                        if ($sum > 0) {
                            $this->console_log($cat . " - " . $proc . ": " . $sum . " total moyennes période");
                        }
                    }
                    $this->console_log("Total moyennes " . $cat . " pour la période: " . $totalData);

                    if ($totalData > 0) {
                        $this->console_log("Génération graphique " . $cat . " pour la période (semaines)");
                        switch ($cat) {
                            case 'production':
                                $chartPaths['production'] = $this->generatePeriodProductionChartWeekly($periodData, $chartWidth);
                                break;
                            case 'etude':
                                $chartPaths['etude'] = $this->generatePeriodEtudeChartWeekly($periodData, $chartWidth);
                                break;
                            case 'methode':
                                $chartPaths['methode'] = $this->generatePeriodMethodeChartWeekly($periodData, $chartWidth);
                                break;
                            case 'qualite':
                                $chartPaths['qualite'] = $this->generatePeriodQualiteChartWeekly($periodData, $chartWidth);
                                break;
                        }
                    } else {
                        $this->console_log("Aucune donnée pour " . $cat . " cette période, graphique non généré");
                        $chartPaths[$cat] = $this->createErrorImage($cat, 'Aucune donnée disponible pour cette période');
                    }
                } else {
                    $this->console_log("Catégorie " . $cat . " manquante dans les données");
                    $chartPaths[$cat] = $this->createErrorImage($cat, 'Catégorie manquante');
                }
            }

            $this->console_log("Génération période libre par semaines terminée");

        } catch (\Exception $e) {
            $this->console_log("Erreur génération graphiques période libre par semaines: " . $e->getMessage());
            $chartPaths = $this->generateErrorImages($e->getMessage());
        }

        return $chartPaths;
    }

    /**
     * 🆕 Calcule la largeur optimale du graphique selon le nombre de semaines
     *
     * @param int $nombreSemaines Nombre de semaines dans la période
     * @return int Largeur en pixels
     */
    private function calculateChartWidthWeekly($nombreSemaines) {
        // Largeur de base + largeur par semaine
        $calculatedWidth = self::CHART_BASE_WIDTH + ($nombreSemaines * self::WIDTH_PER_WEEK);

        // Appliquer les limites min/max
        $finalWidth = max(self::CHART_MIN_WIDTH, min(self::CHART_MAX_WIDTH, $calculatedWidth));

        $this->console_log("Calcul largeur: base(" . self::CHART_BASE_WIDTH . ") + semaines(" . $nombreSemaines . ") * largeur_par_semaine(" . self::WIDTH_PER_WEEK . ") = " . $calculatedWidth . "px → " . $finalWidth . "px (avec limites)");

        return $finalWidth;
    }

    /**
     * 🆕 Génère le graphique Production pour une période libre (par semaines)
     *
     * @param array $data Données des graphiques de la période (moyennes par semaines)
     * @param int $chartWidth Largeur calculée du graphique
     * @return string Chemin de l'image générée
     */
    private function generatePeriodProductionChartWeekly($data, $chartWidth) {
        $this->console_log("=== GÉNÉRATION GRAPHIQUE PRODUCTION PÉRIODE LIBRE (SEMAINES) ===");

        $filename = 'production_weekly_' . date('Y-m-d_H-i-s') . '.png';
        $filepath = self::CHARTS_FOLDER . $filename;

        // Données des processus (moyennes par semaines)
        $chaudron_data = $data['production']['CHAUDNQ'] ?? [];
        $soudure_data = $data['production']['SOUDNQ'] ?? [];
        $ct_data = $data['production']['CT'] ?? [];

        $this->console_log("Chaudronnerie (" . count($chaudron_data) . " semaines): " . json_encode(array_slice($chaudron_data, 0, 3)) . (count($chaudron_data) > 3 ? '...' : ''));
        $this->console_log("Soudure (" . count($soudure_data) . " semaines): " . json_encode(array_slice($soudure_data, 0, 3)) . (count($soudure_data) > 3 ? '...' : ''));
        $this->console_log("CT (" . count($ct_data) . " semaines): " . json_encode(array_slice($ct_data, 0, 3)) . (count($ct_data) > 3 ? '...' : ''));

        // Vérifier qu'il y a au moins des données
        if (empty($chaudron_data) && empty($soudure_data) && empty($ct_data)) {
            $this->console_log("Aucune donnée production, création image vide");
            return $this->createErrorImage('production', 'Aucune donnée de production cette période');
        }

        try {
            // Calculer la valeur maximale des données pour ajuster l'axe Y
            $maxValue = 0;
            foreach ([$chaudron_data, $soudure_data, $ct_data] as $dataset) {
                if (!empty($dataset)) {
                    $maxValue = max($maxValue, max($dataset));
                }
            }

            // Définir l'échelle Y avec minimum de 3
            $yMax = max(3, ceil($maxValue * 1.2)); // 20% de marge au-dessus + minimum de 3
            $this->console_log("Valeur max moyennes: " . $maxValue . " → Axe Y fixé à: " . $yMax);

            // 🆕 Créer le graphique avec largeur dynamique
            $graph = new Graph($chartWidth, self::CHART_HEIGHT);
            $graph->SetScale('textlin', 0, $yMax); // Forcer l'axe Y de 0 à $yMax
            $graph->SetMargin(80, 40, 40, 80);

            // 🆕 Titre et labels adaptés pour les semaines
            $periodeInfo = $data['periode_info'];
            $graph->title->Set('Charge Production par Semaine - Période du ' . $periodeInfo['debut'] . ' au ' . $periodeInfo['fin']);
            $graph->title->SetFont(FF_ARIAL, FS_BOLD, 16);
            $graph->xaxis->title->Set('Semaines de la période sélectionnée');
            $graph->xaxis->title->SetFont(FF_ARIAL, FS_NORMAL, 12);
            $graph->yaxis->title->Set('Moyenne de personnes par semaine');
            $graph->yaxis->title->SetFont(FF_ARIAL, FS_NORMAL, 12);

            // 🆕 Labels de semaines (générés par ChargeModel)
            $graph->xaxis->SetTickLabels($data['semaines_labels'] ?? []);

            // Rotation des labels selon le nombre de semaines
            $nombreSemaines = count($data['semaines_labels'] ?? []);
            if ($nombreSemaines > 8) {
                $graph->xaxis->SetLabelAngle(90); // Vertical pour beaucoup de semaines
            } else {
                $graph->xaxis->SetLabelAngle(45); // Incliné pour peu de semaines
            }

            $hasData = false;

            // Créer les barres (groupées)
            $barplots = [];

            if (!empty($chaudron_data)) {
                $barplot1 = new BarPlot($chaudron_data);
                $barplot1->SetColor('red');
                $barplot1->SetFillColor('red');
                $barplot1->SetLegend('Chaudronnerie');
                $barplots[] = $barplot1;
                $hasData = true;
                $this->console_log("Barre chaudronnerie ajoutée (période libre, semaines)");
            }

            if (!empty($soudure_data)) {
                $barplot2 = new BarPlot($soudure_data);
                $barplot2->SetColor('blue');
                $barplot2->SetFillColor('blue');
                $barplot2->SetLegend('Soudure');
                $barplots[] = $barplot2;
                $hasData = true;
                $this->console_log("Barre soudure ajoutée (période libre, semaines)");
            }

            if (!empty($ct_data)) {
                $barplot3 = new BarPlot($ct_data);
                $barplot3->SetColor('green');
                $barplot3->SetFillColor('green');
                $barplot3->SetLegend('Contrôle');
                $barplots[] = $barplot3;
                $hasData = true;
                $this->console_log("Barre CT ajoutée (période libre, semaines)");
            }

            if (!$hasData) {
                $this->console_log("Aucune barre ajoutée, création image d'erreur");
                return $this->createErrorImage('production', 'Aucune barre de donnée valide');
            }

            // 🆕 Grouper les barres côte à côte pour chaque semaine
            if (count($barplots) > 1) {
                $groupedBarPlot = new \Amenadiel\JpGraph\Plot\GroupBarPlot($barplots);
                $graph->Add($groupedBarPlot);
            } else {
                $graph->Add($barplots[0]);
            }

            // Légende
            $graph->legend->SetPos(0.05, 0.15, 'right', 'top');

            // Sauvegarder l'image
            $graph->Stroke($filepath);
            $this->console_log("Graphique production période libre par semaines sauvegardé: " . $filename . " (" . $chartWidth . "x" . self::CHART_HEIGHT . ")");
            return $filename;

        } catch (\Exception $e) {
            $this->console_log("Erreur sauvegarde production période libre par semaines: " . $e->getMessage());
            return $this->createErrorImage('production', 'Erreur sauvegarde: ' . $e->getMessage());
        }
    }

    /**
     * 🆕 Génère le graphique Étude pour une période libre (par semaines)
     */
    private function generatePeriodEtudeChartWeekly($data, $chartWidth) {
        $this->console_log("=== GÉNÉRATION GRAPHIQUE ÉTUDE PÉRIODE LIBRE (SEMAINES) ===");

        $filename = 'etude_weekly_' . date('Y-m-d_H-i-s') . '.png';
        $filepath = self::CHARTS_FOLDER . $filename;

        $calc_data = $data['etude']['CALC'] ?? [];
        $proj_data = $data['etude']['PROJ'] ?? [];

        if (empty($calc_data) && empty($proj_data)) {
            return $this->createErrorImage('etude', 'Aucune donnée d\'étude cette période');
        }

        try {
            // Calculer la valeur maximale des données pour ajuster l'axe Y
            $maxValue = 0;
            foreach ([$calc_data, $proj_data] as $dataset) {
                if (!empty($dataset)) {
                    $maxValue = max($maxValue, max($dataset));
                }
            }

            // Définir l'échelle Y avec minimum de 3
            $yMax = max(3, ceil($maxValue * 1.2)); // 20% de marge au-dessus + minimum de 3

            $graph = new Graph($chartWidth, self::CHART_HEIGHT);
            $graph->SetScale('textlin', 0, $yMax); // Forcer l'axe Y de 0 à $yMax
            $graph->SetMargin(80, 40, 40, 80);

            $periodeInfo = $data['periode_info'];
            $graph->title->Set('Charge Étude par Semaine - Période du ' . $periodeInfo['debut'] . ' au ' . $periodeInfo['fin']);
            $graph->title->SetFont(FF_ARIAL, FS_BOLD, 16);
            $graph->xaxis->title->Set('Semaines de la période sélectionnée');
            $graph->yaxis->title->Set('Moyenne de personnes par semaine');
            $graph->xaxis->SetTickLabels($data['semaines_labels'] ?? []);

            $nombreSemaines = count($data['semaines_labels'] ?? []);
            if ($nombreSemaines > 8) {
                $graph->xaxis->SetLabelAngle(90);
            } else {
                $graph->xaxis->SetLabelAngle(45);
            }

            $barplots = [];
            $hasData = false;

            if (!empty($calc_data)) {
                $barplot1 = new BarPlot($calc_data);
                $barplot1->SetColor('orange');
                $barplot1->SetFillColor('orange');
                $barplot1->SetLegend('Calcul');
                $barplots[] = $barplot1;
                $hasData = true;
            }

            if (!empty($proj_data)) {
                $barplot2 = new BarPlot($proj_data);
                $barplot2->SetColor('purple');
                $barplot2->SetFillColor('purple');
                $barplot2->SetLegend('Projet');
                $barplots[] = $barplot2;
                $hasData = true;
            }

            if (!$hasData) {
                return $this->createErrorImage('etude', 'Aucune barre de donnée valide');
            }

            if (count($barplots) > 1) {
                $groupedBarPlot = new \Amenadiel\JpGraph\Plot\GroupBarPlot($barplots);
                $graph->Add($groupedBarPlot);
            } else {
                $graph->Add($barplots[0]);
            }

            $graph->legend->SetPos(0.05, 0.15, 'right', 'top');
            $graph->Stroke($filepath);
            $this->console_log("Graphique étude période libre par semaines sauvegardé: " . $filename . " (" . $chartWidth . "x" . self::CHART_HEIGHT . ")");
            return $filename;

        } catch (\Exception $e) {
            $this->console_log("Erreur sauvegarde étude période libre par semaines: " . $e->getMessage());
            return $this->createErrorImage('etude', 'Erreur sauvegarde: ' . $e->getMessage());
        }
    }

    /**
     * 🆕 Génère le graphique Méthode pour une période libre (par semaines)
     */
    private function generatePeriodMethodeChartWeekly($data, $chartWidth) {
        $this->console_log("=== GÉNÉRATION GRAPHIQUE MÉTHODE PÉRIODE LIBRE (SEMAINES) ===");

        $filename = 'methode_weekly_' . date('Y-m-d_H-i-s') . '.png';
        $filepath = self::CHARTS_FOLDER . $filename;

        $meth_data = $data['methode']['METH'] ?? [];

        if (empty($meth_data)) {
            return $this->createErrorImage('methode', 'Aucune donnée de méthode cette période');
        }

        try {
            // Calculer la valeur maximale des données pour ajuster l'axe Y
            $maxValue = 0;
            if (!empty($meth_data)) {
                $maxValue = max($meth_data);
            }

            // Définir l'échelle Y avec minimum de 3
            $yMax = max(3, ceil($maxValue * 1.2)); // 20% de marge au-dessus + minimum de 3

            $graph = new Graph($chartWidth, self::CHART_HEIGHT);
            $graph->SetScale('textlin', 0, $yMax); // Forcer l'axe Y de 0 à $yMax
            $graph->SetMargin(80, 40, 40, 80);

            $periodeInfo = $data['periode_info'];
            $graph->title->Set('Charge Méthode par Semaine - Période du ' . $periodeInfo['debut'] . ' au ' . $periodeInfo['fin']);
            $graph->title->SetFont(FF_ARIAL, FS_BOLD, 16);
            $graph->xaxis->title->Set('Semaines de la période sélectionnée');
            $graph->yaxis->title->Set('Moyenne de personnes par semaine');
            $graph->xaxis->SetTickLabels($data['semaines_labels'] ?? []);

            $nombreSemaines = count($data['semaines_labels'] ?? []);
            if ($nombreSemaines > 8) {
                $graph->xaxis->SetLabelAngle(90);
            } else {
                $graph->xaxis->SetLabelAngle(45);
            }

            $barplot1 = new BarPlot($meth_data);
            $barplot1->SetColor('brown');
            $barplot1->SetFillColor('brown');
            $barplot1->SetLegend('Méthode');
            $graph->Add($barplot1);

            $graph->legend->SetPos(0.05, 0.15, 'right', 'top');
            $graph->Stroke($filepath);
            $this->console_log("Graphique méthode période libre par semaines sauvegardé: " . $filename . " (" . $chartWidth . "x" . self::CHART_HEIGHT . ")");
            return $filename;

        } catch (\Exception $e) {
            $this->console_log("Erreur sauvegarde méthode période libre par semaines: " . $e->getMessage());
            return $this->createErrorImage('methode', 'Erreur sauvegarde: ' . $e->getMessage());
        }
    }

    /**
     * 🆕 Génère le graphique Qualité pour une période libre (par semaines)
     */
    private function generatePeriodQualiteChartWeekly($data, $chartWidth) {
        $this->console_log("=== GÉNÉRATION GRAPHIQUE QUALITÉ PÉRIODE LIBRE (SEMAINES) ===");

        $filename = 'qualite_weekly_' . date('Y-m-d_H-i-s') . '.png';
        $filepath = self::CHARTS_FOLDER . $filename;

        $qual_data = $data['qualite']['QUAL'] ?? [];
        $quals_data = $data['qualite']['QUALS'] ?? [];

        if (empty($qual_data) && empty($quals_data)) {
            return $this->createErrorImage('qualite', 'Aucune donnée de qualité cette période');
        }

        try {
            // Calculer la valeur maximale des données pour ajuster l'axe Y
            $maxValue = 0;
            foreach ([$qual_data, $quals_data] as $dataset) {
                if (!empty($dataset)) {
                    $maxValue = max($maxValue, max($dataset));
                }
            }

            // Définir l'échelle Y avec minimum de 3
            $yMax = max(3, ceil($maxValue * 1.2)); // 20% de marge au-dessus + minimum de 3

            $graph = new Graph($chartWidth, self::CHART_HEIGHT);
            $graph->SetScale('textlin', 0, $yMax); // Forcer l'axe Y de 0 à $yMax
            $graph->SetMargin(80, 40, 40, 80);

            $periodeInfo = $data['periode_info'];
            $graph->title->Set('Charge Qualité par Semaine - Période du ' . $periodeInfo['debut'] . ' au ' . $periodeInfo['fin']);
            $graph->title->SetFont(FF_ARIAL, FS_BOLD, 16);
            $graph->xaxis->title->Set('Semaines de la période sélectionnée');
            $graph->yaxis->title->Set('Moyenne de personnes par semaine');
            $graph->xaxis->SetTickLabels($data['semaines_labels'] ?? []);

            $nombreSemaines = count($data['semaines_labels'] ?? []);
            if ($nombreSemaines > 8) {
                $graph->xaxis->SetLabelAngle(90);
            } else {
                $graph->xaxis->SetLabelAngle(45);
            }

            $barplots = [];
            $hasData = false;

            if (!empty($qual_data)) {
                $barplot1 = new BarPlot($qual_data);
                $barplot1->SetColor('darkblue');
                $barplot1->SetFillColor('darkblue');
                $barplot1->SetLegend('Qualité');
                $barplots[] = $barplot1;
                $hasData = true;
            }

            if (!empty($quals_data)) {
                $barplot2 = new BarPlot($quals_data);
                $barplot2->SetColor('cyan');
                $barplot2->SetFillColor('cyan');
                $barplot2->SetLegend('Qualité Spécialisée');
                $barplots[] = $barplot2;
                $hasData = true;
            }

            if (!$hasData) {
                return $this->createErrorImage('qualite', 'Aucune barre de donnée valide');
            }

            if (count($barplots) > 1) {
                $groupedBarPlot = new \Amenadiel\JpGraph\Plot\GroupBarPlot($barplots);
                $graph->Add($groupedBarPlot);
            } else {
                $graph->Add($barplots[0]);
            }

            $graph->legend->SetPos(0.05, 0.15, 'right', 'top');
            $graph->Stroke($filepath);
            $this->console_log("Graphique qualité période libre par semaines sauvegardé: " . $filename . " (" . $chartWidth . "x" . self::CHART_HEIGHT . ")");
            return $filename;

        } catch (\Exception $e) {
            $this->console_log("Erreur sauvegarde qualité période libre par semaines: " . $e->getMessage());
            return $this->createErrorImage('qualite', 'Erreur sauvegarde: ' . $e->getMessage());
        }
    }

    /**
     * 🆕 Nettoie TOUS les graphiques existants avant génération
     */
    private function cleanupAllCharts() {
        $this->console_log("=== NETTOYAGE COMPLET DES GRAPHIQUES ===");

        try {
            if (!is_dir(self::CHARTS_FOLDER)) {
                $this->console_log("Dossier graphiques inexistant");
                return;
            }

            $files = scandir(self::CHARTS_FOLDER);
            $deletedCount = 0;

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = self::CHARTS_FOLDER . $file;

                if (is_file($filePath)) {
                    if (unlink($filePath)) {
                        $deletedCount++;
                        $this->console_log("Supprimé: " . $file);
                    } else {
                        $this->console_log("Erreur suppression: " . $file);
                    }
                }
            }

            $this->console_log("Nettoyage terminé: " . $deletedCount . " fichier(s) supprimé(s)");

        } catch (\Exception $e) {
            $this->console_log("Erreur lors du nettoyage: " . $e->getMessage());
        }
    }

    /**
     * Génère des images d'erreur si JPGraph ne fonctionne pas
     *
     * @param string $errorMessage Message d'erreur
     * @return array Chemins des images d'erreur
     */
    private function generateErrorImages($errorMessage) {
        $chartPaths = [
            'production' => $this->createErrorImage('production', $errorMessage),
            'etude' => $this->createErrorImage('etude', $errorMessage),
            'methode' => $this->createErrorImage('methode', $errorMessage),
            'qualite' => $this->createErrorImage('qualite', $errorMessage)
        ];

        return $chartPaths;
    }

    /**
     * Crée une image d'erreur simple
     *
     * @param string $type Type de graphique
     * @param string $errorMessage Message d'erreur
     * @return string Nom du fichier généré
     */
    private function createErrorImage($type, $errorMessage) {
        $filename = $type . '_error_' . date('Y-m-d_H-i-s') . '.png';
        $filepath = self::CHARTS_FOLDER . $filename;

        // Créer une image simple avec GD
        $im = imagecreate(self::CHART_BASE_WIDTH, self::CHART_HEIGHT);
        $white = imagecolorallocate($im, 255, 255, 255);
        $red = imagecolorallocate($im, 255, 0, 0);
        $black = imagecolorallocate($im, 0, 0, 0);

        // Fond blanc
        imagefill($im, 0, 0, $white);

        // Titre
        imagestring($im, 5, 50, 50, 'Erreur - Graphique ' . ucfirst($type), $red);

        // Message d'erreur (tronqué)
        $shortMessage = substr($errorMessage, 0, 80);
        imagestring($im, 3, 50, 100, $shortMessage, $black);

        // 🆕 Informations sur l'affichage par semaines
        imagestring($im, 2, 50, 150, 'Mode: Affichage par semaines (moyennes)', $black);
        imagestring($im, 2, 50, 170, 'Selectionnez une autre periode ou verifiez les donnees', $black);

        // Sauvegarder
        imagepng($im, $filepath);
        imagedestroy($im);

        $this->console_log("Image d'erreur créée: " . $filename);
        return $filename;
    }

    /**
     * S'assure que le dossier des graphiques existe
     */
    private function ensureChartsDirectoryExists() {
        if (!is_dir(self::CHARTS_FOLDER)) {
            mkdir(self::CHARTS_FOLDER, 0777, true);
            $this->console_log("Dossier graphiques créé: " . self::CHARTS_FOLDER);
        }
    }

    /**
     * Logging console
     *
     * @param string $message Message à logger
     */
    private function console_log($message) {
        echo "<script>console.log('[GraphGeneratorModel] " . addslashes($message) . "');</script>";
    }
}