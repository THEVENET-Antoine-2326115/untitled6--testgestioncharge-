<?php
namespace modules\blog\models;

// Utiliser les namespaces modernes d'amenadiel/jpgraph
use Amenadiel\JpGraph\Graph\Graph;
use Amenadiel\JpGraph\Plot\LinePlot;

/**
 * Classe GraphGeneratorModel
 *
 * Cette classe gère la génération des graphiques JPGraph pour l'analyse de charge.
 * Version compatible avec amenadiel/jpgraph (namespaces)
 */
class GraphGeneratorModel {

    /**
     * Dossier de stockage des graphiques générés
     */
    const CHARTS_FOLDER = '_assets/images/';

    /**
     * Largeur des graphiques
     */
    const CHART_WIDTH = 800;

    /**
     * Hauteur des graphiques
     */
    const CHART_HEIGHT = 400;

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
            if (class_exists('Amenadiel\\JpGraph\\Graph\\Graph') && class_exists('Amenadiel\\JpGraph\\Plot\\LinePlot')) {
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
     * Génère les 3 graphiques de charge par semaine
     *
     * @param array $graphiquesData Données formatées pour les graphiques
     * @return array Chemins des images générées
     */
    public function generateAllCharts($graphiquesData) {
        $this->console_log("=== GÉNÉRATION GRAPHIQUES ===");
        $this->console_log("JPGraph chargé: " . ($this->jpgraphLoaded ? 'OUI' : 'NON'));

        if (!$this->jpgraphLoaded) {
            $this->console_log("JPGraph non disponible, génération d'images d'erreur");
            return $this->generateErrorImages("JPGraph non installé ou non chargé");
        }

        $this->console_log("Données reçues: " . json_encode(array_keys($graphiquesData)));

        $chartPaths = [
            'production' => null,
            'etude' => null,
            'methode' => null
        ];

        try {
            // Debug: vérifier les données reçues
            if (isset($graphiquesData['semaines_labels'])) {
                $this->console_log("Semaines disponibles: " . count($graphiquesData['semaines_labels']));
                $this->console_log("Labels: " . json_encode($graphiquesData['semaines_labels']));
            } else {
                $this->console_log("ERREUR: Pas de semaines_labels dans les données");
                return $this->generateErrorImages("Données semaines_labels manquantes");
            }

            // Vérifier chaque catégorie avant génération
            $categories = ['production', 'etude', 'methode'];
            foreach ($categories as $cat) {
                if (isset($graphiquesData[$cat])) {
                    $totalData = 0;
                    foreach ($graphiquesData[$cat] as $proc => $data) {
                        $sum = array_sum($data);
                        $totalData += $sum;
                        if ($sum > 0) {
                            $this->console_log($cat . " - " . $proc . ": " . $sum . " total");
                        }
                    }
                    $this->console_log("Total données " . $cat . ": " . $totalData);

                    if ($totalData > 0) {
                        $this->console_log("Génération graphique " . $cat);
                        switch ($cat) {
                            case 'production':
                                $chartPaths['production'] = $this->generateProductionChart($graphiquesData);
                                break;
                            case 'etude':
                                $chartPaths['etude'] = $this->generateEtudeChart($graphiquesData);
                                break;
                            case 'methode':
                                $chartPaths['methode'] = $this->generateMethodeChart($graphiquesData);
                                break;
                        }
                    } else {
                        $this->console_log("Aucune donnée pour " . $cat . ", graphique non généré");
                        $chartPaths[$cat] = $this->createErrorImage($cat, 'Aucune donnée disponible');
                    }
                } else {
                    $this->console_log("Catégorie " . $cat . " manquante dans les données");
                    $chartPaths[$cat] = $this->createErrorImage($cat, 'Catégorie manquante');
                }
            }

            $this->console_log("Génération terminée");

        } catch (\Exception $e) {
            $this->console_log("Erreur génération graphiques: " . $e->getMessage());
            $chartPaths = $this->generateErrorImages($e->getMessage());
        }

        return $chartPaths;
    }

    /**
     * Génère le graphique Production
     *
     * @param array $data Données des graphiques
     * @return string Chemin de l'image générée
     */
    private function generateProductionChart($data) {
        $this->console_log("=== GÉNÉRATION GRAPHIQUE PRODUCTION ===");

        $filename = 'production_' . date('Y-m-d_H-i-s') . '.png';
        $filepath = self::CHARTS_FOLDER . $filename;

        // Données des processus
        $chaudron_data = $data['production']['CHAUDNQ'] ?? [];
        $soudure_data = $data['production']['SOUDNQ'] ?? [];
        $ct_data = $data['production']['CT'] ?? [];

        $this->console_log("Chaudronnerie: " . json_encode($chaudron_data));
        $this->console_log("Soudure: " . json_encode($soudure_data));
        $this->console_log("CT: " . json_encode($ct_data));

        // Vérifier qu'il y a au moins des données (même des zéros)
        if (empty($chaudron_data) && empty($soudure_data) && empty($ct_data)) {
            $this->console_log("Aucune donnée production, création image vide");
            return $this->createErrorImage('production', 'Aucune donnée de production');
        }

        try {
            // Créer le graphique avec namespace moderne
            $graph = new Graph(self::CHART_WIDTH, self::CHART_HEIGHT);
            $graph->SetScale('textlin');
            $graph->SetMargin(60, 30, 30, 70);

            // Titre et labels
            $graph->title->Set('Charge Production par Semaine');
            $graph->xaxis->title->Set('Semaines');
            $graph->yaxis->title->Set('Nombre de personnes');
            $graph->xaxis->SetTickLabels($data['semaines_labels'] ?? []);

            $hasData = false;

            // Créer les lignes
            if (!empty($chaudron_data)) {
                $lineplot1 = new LinePlot($chaudron_data);
                $lineplot1->SetColor('red');
                $lineplot1->SetWeight(3);
                $lineplot1->SetLegend('Chaudronnerie');
                $graph->Add($lineplot1);
                $hasData = true;
                $this->console_log("Ligne chaudronnerie ajoutée");
            }

            if (!empty($soudure_data)) {
                $lineplot2 = new LinePlot($soudure_data);
                $lineplot2->SetColor('blue');
                $lineplot2->SetWeight(3);
                $lineplot2->SetLegend('Soudure');
                $graph->Add($lineplot2);
                $hasData = true;
                $this->console_log("Ligne soudure ajoutée");
            }

            if (!empty($ct_data)) {
                $lineplot3 = new LinePlot($ct_data);
                $lineplot3->SetColor('green');
                $lineplot3->SetWeight(3);
                $lineplot3->SetLegend('Contrôle');
                $graph->Add($lineplot3);
                $hasData = true;
                $this->console_log("Ligne CT ajoutée");
            }

            if (!$hasData) {
                $this->console_log("Aucune ligne ajoutée, création image d'erreur");
                return $this->createErrorImage('production', 'Aucune ligne de donnée valide');
            }

            // Légende
            $graph->legend->SetPos(0.05, 0.15, 'right', 'top');

            // Sauvegarder l'image
            $graph->Stroke($filepath);
            $this->console_log("Graphique production sauvegardé: " . $filename);
            return $filename;

        } catch (\Exception $e) {
            $this->console_log("Erreur sauvegarde production: " . $e->getMessage());
            return $this->createErrorImage('production', 'Erreur sauvegarde: ' . $e->getMessage());
        }
    }

    /**
     * Génère le graphique Étude
     *
     * @param array $data Données des graphiques
     * @return string Chemin de l'image générée
     */
    private function generateEtudeChart($data) {
        $this->console_log("=== GÉNÉRATION GRAPHIQUE ÉTUDE ===");

        $filename = 'etude_' . date('Y-m-d_H-i-s') . '.png';
        $filepath = self::CHARTS_FOLDER . $filename;

        // Données des processus
        $calc_data = $data['etude']['CALC'] ?? [];
        $proj_data = $data['etude']['PROJ'] ?? [];

        $this->console_log("Calcul: " . json_encode($calc_data));
        $this->console_log("Projet: " . json_encode($proj_data));

        if (empty($calc_data) && empty($proj_data)) {
            return $this->createErrorImage('etude', 'Aucune donnée d\'étude');
        }

        try {
            $graph = new Graph(self::CHART_WIDTH, self::CHART_HEIGHT);
            $graph->SetScale('textlin');
            $graph->SetMargin(60, 30, 30, 70);

            $graph->title->Set('Charge Étude par Semaine');
            $graph->xaxis->title->Set('Semaines');
            $graph->yaxis->title->Set('Nombre de personnes');
            $graph->xaxis->SetTickLabels($data['semaines_labels'] ?? []);

            $hasData = false;

            if (!empty($calc_data)) {
                $lineplot1 = new LinePlot($calc_data);
                $lineplot1->SetColor('orange');
                $lineplot1->SetWeight(3);
                $lineplot1->SetLegend('Calcul');
                $graph->Add($lineplot1);
                $hasData = true;
            }

            if (!empty($proj_data)) {
                $lineplot2 = new LinePlot($proj_data);
                $lineplot2->SetColor('purple');
                $lineplot2->SetWeight(3);
                $lineplot2->SetLegend('Projet');
                $graph->Add($lineplot2);
                $hasData = true;
            }

            if (!$hasData) {
                return $this->createErrorImage('etude', 'Aucune ligne de donnée valide');
            }

            $graph->legend->SetPos(0.05, 0.15, 'right', 'top');
            $graph->Stroke($filepath);
            $this->console_log("Graphique étude sauvegardé: " . $filename);
            return $filename;

        } catch (\Exception $e) {
            $this->console_log("Erreur sauvegarde étude: " . $e->getMessage());
            return $this->createErrorImage('etude', 'Erreur sauvegarde: ' . $e->getMessage());
        }
    }

    /**
     * Génère le graphique Méthode
     *
     * @param array $data Données des graphiques
     * @return string Chemin de l'image générée
     */
    private function generateMethodeChart($data) {
        $this->console_log("=== GÉNÉRATION GRAPHIQUE MÉTHODE ===");

        $filename = 'methode_' . date('Y-m-d_H-i-s') . '.png';
        $filepath = self::CHARTS_FOLDER . $filename;

        $meth_data = $data['methode']['METH'] ?? [];

        if (empty($meth_data)) {
            return $this->createErrorImage('methode', 'Aucune donnée de méthode');
        }

        try {
            $graph = new Graph(self::CHART_WIDTH, self::CHART_HEIGHT);
            $graph->SetScale('textlin');
            $graph->SetMargin(60, 30, 30, 70);

            $graph->title->Set('Charge Méthode par Semaine');
            $graph->xaxis->title->Set('Semaines');
            $graph->yaxis->title->Set('Nombre de personnes');
            $graph->xaxis->SetTickLabels($data['semaines_labels'] ?? []);

            $lineplot1 = new LinePlot($meth_data);
            $lineplot1->SetColor('brown');
            $lineplot1->SetWeight(3);
            $lineplot1->SetLegend('Méthode');
            $graph->Add($lineplot1);

            $graph->legend->SetPos(0.05, 0.15, 'right', 'top');
            $graph->Stroke($filepath);
            $this->console_log("Graphique méthode sauvegardé: " . $filename);
            return $filename;

        } catch (\Exception $e) {
            $this->console_log("Erreur sauvegarde méthode: " . $e->getMessage());
            return $this->createErrorImage('methode', 'Erreur sauvegarde: ' . $e->getMessage());
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
            'methode' => $this->createErrorImage('methode', $errorMessage)
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
        $shortMessage = substr($errorMessage, 0, 60);
        imagestring($im, 3, 50, 100, $shortMessage, $black);

        // Instructions
        imagestring($im, 2, 50, 150, 'Installez JPGraph:', $black);
        imagestring($im, 2, 50, 170, 'composer require amenadiel/jpgraph', $black);

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