<?php
namespace modules\blog\models;

// Utiliser les namespaces modernes d'amenadiel/jpgraph
use Amenadiel\JpGraph\Graph\Graph;
use Amenadiel\JpGraph\Plot\BarPlot;

/**
 * Classe GraphGeneratorModel
 *
 * Cette classe gère la génération des graphiques JPGraph pour l'analyse de charge.
 * Version adaptée pour génération par jour d'une semaine sélectionnée.
 */
class GraphGeneratorModel {

    /**
     * Dossier de stockage des graphiques générés
     */
    const CHARTS_FOLDER = '_assets/images/';

    /**
     * Largeur des graphiques
     */
    const CHART_WIDTH = 900;

    /**
     * Hauteur des graphiques
     */
    const CHART_HEIGHT = 450;

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
     * 🆕 Génère les graphiques pour une semaine spécifique (par jour)
     *
     * @param array $weekData Données formatées pour une semaine (7 jours)
     * @return array Chemins des images générées pour chaque catégorie
     */
    public function generateWeeklyCharts($weekData) {
        $this->console_log("=== GÉNÉRATION GRAPHIQUES HEBDOMADAIRES ===");
        $this->console_log("JPGraph chargé: " . ($this->jpgraphLoaded ? 'OUI' : 'NON'));

        if (!$this->jpgraphLoaded) {
            $this->console_log("JPGraph non disponible, génération d'images d'erreur");
            return $this->generateErrorImages("JPGraph non installé ou non chargé");
        }

        // 🆕 NETTOYAGE COMPLET avant génération
        $this->cleanupAllCharts();

        $this->console_log("Données reçues pour la semaine: " . json_encode(array_keys($weekData)));

        $chartPaths = [
            'production' => null,
            'etude' => null,
            'methode' => null,
            'qualite' => null
        ];

        try {
            // Debug: vérifier les données reçues
            if (isset($weekData['jours_labels'])) {
                $this->console_log("Jours disponibles: " . count($weekData['jours_labels']));
                $this->console_log("Labels jours: " . json_encode($weekData['jours_labels']));
            } else {
                $this->console_log("ERREUR: Pas de jours_labels dans les données");
                return $this->generateErrorImages("Données jours_labels manquantes");
            }

            // Vérifier chaque catégorie avant génération
            $categories = ['production', 'etude', 'methode', 'qualite'];
            foreach ($categories as $cat) {
                if (isset($weekData[$cat])) {
                    $totalData = 0;
                    foreach ($weekData[$cat] as $proc => $data) {
                        $sum = array_sum($data);
                        $totalData += $sum;
                        if ($sum > 0) {
                            $this->console_log($cat . " - " . $proc . ": " . $sum . " total semaine");
                        }
                    }
                    $this->console_log("Total données " . $cat . " pour la semaine: " . $totalData);

                    if ($totalData > 0) {
                        $this->console_log("Génération graphique " . $cat . " pour la semaine");
                        switch ($cat) {
                            case 'production':
                                $chartPaths['production'] = $this->generateWeeklyProductionChart($weekData);
                                break;
                            case 'etude':
                                $chartPaths['etude'] = $this->generateWeeklyEtudeChart($weekData);
                                break;
                            case 'methode':
                                $chartPaths['methode'] = $this->generateWeeklyMethodeChart($weekData);
                                break;
                            case 'qualite':
                                $chartPaths['qualite'] = $this->generateWeeklyQualiteChart($weekData);
                                break;
                        }
                    } else {
                        $this->console_log("Aucune donnée pour " . $cat . " cette semaine, graphique non généré");
                        $chartPaths[$cat] = $this->createErrorImage($cat, 'Aucune donnée disponible pour cette semaine');
                    }
                } else {
                    $this->console_log("Catégorie " . $cat . " manquante dans les données");
                    $chartPaths[$cat] = $this->createErrorImage($cat, 'Catégorie manquante');
                }
            }

            $this->console_log("Génération hebdomadaire terminée");

        } catch (\Exception $e) {
            $this->console_log("Erreur génération graphiques hebdomadaires: " . $e->getMessage());
            $chartPaths = $this->generateErrorImages($e->getMessage());
        }

        return $chartPaths;
    }

    /**
     * 🆕 Génère le graphique Production pour une semaine (par jour)
     *
     * @param array $data Données des graphiques hebdomadaires
     * @return string Chemin de l'image générée
     */
    private function generateWeeklyProductionChart($data) {
        $this->console_log("=== GÉNÉRATION GRAPHIQUE PRODUCTION HEBDOMADAIRE ===");

        $filename = 'production_week_' . date('Y-m-d_H-i-s') . '.png';
        $filepath = self::CHARTS_FOLDER . $filename;

        // Données des processus (par jour)
        $chaudron_data = $data['production']['CHAUDNQ'] ?? [];
        $soudure_data = $data['production']['SOUDNQ'] ?? [];
        $ct_data = $data['production']['CT'] ?? [];

        $this->console_log("Chaudronnerie (7 jours): " . json_encode($chaudron_data));
        $this->console_log("Soudure (7 jours): " . json_encode($soudure_data));
        $this->console_log("CT (7 jours): " . json_encode($ct_data));

        // Vérifier qu'il y a au moins des données
        if (empty($chaudron_data) && empty($soudure_data) && empty($ct_data)) {
            $this->console_log("Aucune donnée production, création image vide");
            return $this->createErrorImage('production', 'Aucune donnée de production cette semaine');
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
            $this->console_log("Valeur max données: " . $maxValue . " → Axe Y fixé à: " . $yMax);

            // Créer le graphique avec namespace moderne
            $graph = new Graph(self::CHART_WIDTH, self::CHART_HEIGHT);
            $graph->SetScale('textlin', 0, $yMax); // Forcer l'axe Y de 0 à $yMax
            $graph->SetMargin(80, 40, 40, 80);

            // Titre et labels adaptés pour les jours
            $graph->title->Set('Charge Production par Jour - Semaine sélectionnée');
            $graph->title->SetFont(FF_ARIAL, FS_BOLD, 16);
            $graph->xaxis->title->Set('Jours de la semaine');
            $graph->xaxis->title->SetFont(FF_ARIAL, FS_NORMAL, 12);
            $graph->yaxis->title->Set('Nombre de personnes');
            $graph->yaxis->title->SetFont(FF_ARIAL, FS_NORMAL, 12);

            // 🆕 Labels des jours (Lun, Mar, Mer, etc.)
            $graph->xaxis->SetTickLabels($data['jours_labels'] ?? []);
            $graph->xaxis->SetLabelAngle(45); // Incliner les labels pour lisibilité

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
                $this->console_log("Barre chaudronnerie ajoutée (hebdomadaire)");
            }

            if (!empty($soudure_data)) {
                $barplot2 = new BarPlot($soudure_data);
                $barplot2->SetColor('blue');
                $barplot2->SetFillColor('blue');
                $barplot2->SetLegend('Soudure');
                $barplots[] = $barplot2;
                $hasData = true;
                $this->console_log("Barre soudure ajoutée (hebdomadaire)");
            }

            if (!empty($ct_data)) {
                $barplot3 = new BarPlot($ct_data);
                $barplot3->SetColor('green');
                $barplot3->SetFillColor('green');
                $barplot3->SetLegend('Contrôle');
                $barplots[] = $barplot3;
                $hasData = true;
                $this->console_log("Barre CT ajoutée (hebdomadaire)");
            }

            if (!$hasData) {
                $this->console_log("Aucune barre ajoutée, création image d'erreur");
                return $this->createErrorImage('production', 'Aucune barre de donnée valide');
            }

            // 🆕 Grouper les barres côte à côte pour chaque jour
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
            $this->console_log("Graphique production hebdomadaire sauvegardé: " . $filename);
            return $filename;

        } catch (\Exception $e) {
            $this->console_log("Erreur sauvegarde production hebdomadaire: " . $e->getMessage());
            return $this->createErrorImage('production', 'Erreur sauvegarde: ' . $e->getMessage());
        }
    }

    /**
     * 🆕 Génère le graphique Étude pour une semaine (par jour)
     */
    private function generateWeeklyEtudeChart($data) {
        $this->console_log("=== GÉNÉRATION GRAPHIQUE ÉTUDE HEBDOMADAIRE ===");

        $filename = 'etude_week_' . date('Y-m-d_H-i-s') . '.png';
        $filepath = self::CHARTS_FOLDER . $filename;

        $calc_data = $data['etude']['CALC'] ?? [];
        $proj_data = $data['etude']['PROJ'] ?? [];

        if (empty($calc_data) && empty($proj_data)) {
            return $this->createErrorImage('etude', 'Aucune donnée d\'étude cette semaine');
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

            $graph = new Graph(self::CHART_WIDTH, self::CHART_HEIGHT);
            $graph->SetScale('textlin', 0, $yMax); // Forcer l'axe Y de 0 à $yMax
            $graph->SetMargin(80, 40, 40, 80);

            $graph->title->Set('Charge Étude par Jour - Semaine sélectionnée');
            $graph->title->SetFont(FF_ARIAL, FS_BOLD, 16);
            $graph->xaxis->title->Set('Jours de la semaine');
            $graph->yaxis->title->Set('Nombre de personnes');
            $graph->xaxis->SetTickLabels($data['jours_labels'] ?? []);
            $graph->xaxis->SetLabelAngle(45);

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
            $this->console_log("Graphique étude hebdomadaire sauvegardé: " . $filename);
            return $filename;

        } catch (\Exception $e) {
            $this->console_log("Erreur sauvegarde étude hebdomadaire: " . $e->getMessage());
            return $this->createErrorImage('etude', 'Erreur sauvegarde: ' . $e->getMessage());
        }
    }

    /**
     * 🆕 Génère le graphique Méthode pour une semaine (par jour)
     */
    private function generateWeeklyMethodeChart($data) {
        $this->console_log("=== GÉNÉRATION GRAPHIQUE MÉTHODE HEBDOMADAIRE ===");

        $filename = 'methode_week_' . date('Y-m-d_H-i-s') . '.png';
        $filepath = self::CHARTS_FOLDER . $filename;

        $meth_data = $data['methode']['METH'] ?? [];

        if (empty($meth_data)) {
            return $this->createErrorImage('methode', 'Aucune donnée de méthode cette semaine');
        }

        try {
            // Calculer la valeur maximale des données pour ajuster l'axe Y
            $maxValue = 0;
            if (!empty($meth_data)) {
                $maxValue = max($meth_data);
            }

            // Définir l'échelle Y avec minimum de 3
            $yMax = max(3, ceil($maxValue * 1.2)); // 20% de marge au-dessus + minimum de 3

            $graph = new Graph(self::CHART_WIDTH, self::CHART_HEIGHT);
            $graph->SetScale('textlin', 0, $yMax); // Forcer l'axe Y de 0 à $yMax
            $graph->SetMargin(80, 40, 40, 80);

            $graph->title->Set('Charge Méthode par Jour - Semaine sélectionnée');
            $graph->title->SetFont(FF_ARIAL, FS_BOLD, 16);
            $graph->xaxis->title->Set('Jours de la semaine');
            $graph->yaxis->title->Set('Nombre de personnes');
            $graph->xaxis->SetTickLabels($data['jours_labels'] ?? []);
            $graph->xaxis->SetLabelAngle(45);

            $barplot1 = new BarPlot($meth_data);
            $barplot1->SetColor('brown');
            $barplot1->SetFillColor('brown');
            $barplot1->SetLegend('Méthode');
            $graph->Add($barplot1);

            $graph->legend->SetPos(0.05, 0.15, 'right', 'top');
            $graph->Stroke($filepath);
            $this->console_log("Graphique méthode hebdomadaire sauvegardé: " . $filename);
            return $filename;

        } catch (\Exception $e) {
            $this->console_log("Erreur sauvegarde méthode hebdomadaire: " . $e->getMessage());
            return $this->createErrorImage('methode', 'Erreur sauvegarde: ' . $e->getMessage());
        }
    }

    /**
     * 🆕 Génère le graphique Qualité pour une semaine (par jour)
     */
    private function generateWeeklyQualiteChart($data) {
        $this->console_log("=== GÉNÉRATION GRAPHIQUE QUALITÉ HEBDOMADAIRE ===");

        $filename = 'qualite_week_' . date('Y-m-d_H-i-s') . '.png';
        $filepath = self::CHARTS_FOLDER . $filename;

        $qual_data = $data['qualite']['QUAL'] ?? [];
        $quals_data = $data['qualite']['QUALS'] ?? [];

        if (empty($qual_data) && empty($quals_data)) {
            return $this->createErrorImage('qualite', 'Aucune donnée de qualité cette semaine');
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

            $graph = new Graph(self::CHART_WIDTH, self::CHART_HEIGHT);
            $graph->SetScale('textlin', 0, $yMax); // Forcer l'axe Y de 0 à $yMax
            $graph->SetMargin(80, 40, 40, 80);

            $graph->title->Set('Charge Qualité par Jour - Semaine sélectionnée');
            $graph->title->SetFont(FF_ARIAL, FS_BOLD, 16);
            $graph->xaxis->title->Set('Jours de la semaine');
            $graph->yaxis->title->Set('Nombre de personnes');
            $graph->xaxis->SetTickLabels($data['jours_labels'] ?? []);
            $graph->xaxis->SetLabelAngle(45);

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
            $this->console_log("Graphique qualité hebdomadaire sauvegardé: " . $filename);
            return $filename;

        } catch (\Exception $e) {
            $this->console_log("Erreur sauvegarde qualité hebdomadaire: " . $e->getMessage());
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
        $im = imagecreate(self::CHART_WIDTH, self::CHART_HEIGHT);
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

        // Informations sur l'affichage hebdomadaire
        imagestring($im, 2, 50, 150, 'Mode: Affichage par jour (semaine)', $black);
        imagestring($im, 2, 50, 170, 'Selectionnez une autre semaine ou verifiez les donnees', $black);

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