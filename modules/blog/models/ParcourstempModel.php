<?php
namespace modules\blog\models;

/**
 * Classe ParcoursClasse
 * Permet de parcourir un dossier et de filtrer les fichiers selon leur extension
 */
class ParcourstempModel {

    /**
     * @var string Chemin du dossier à parcourir
     */
    private $cheminDossier;

    /**
     * @var array Extensions de fichiers autorisées
     */
    private $extensionsAutorisees;

    /**
     * Constructeur de la classe
     *
     * @param string $chemin Chemin du dossier à parcourir
     * @param array $extensions Extensions autorisées (par défaut : ['mpp'])
     */
    public function __construct($chemin = null, $extensions = ['mpp']) {
        // Si aucun chemin spécifié, utiliser le dossier uploads du projet
        if ($chemin === null) {
            $chemin = __DIR__ . '/uploads';
        }

        $this->cheminDossier = rtrim($chemin, '/\\'); // Enlève les slash de fin
        $this->extensionsAutorisees = array_map('strtolower', $extensions);
    }

    /**
     * Vérifie si le dossier existe et est accessible
     *
     * @return bool
     */
    public function dossierExiste() {
        echo "<script>console.log('=== TEST PERMISSIONS ===');</script>";
        echo "<script>console.log('Utilisateur PHP: " . addslashes(get_current_user()) . "');</script>";
        echo "<script>console.log('Utilisateur processus: " . addslashes(posix_getpwuid(posix_geteuid())['name']) . "');</script>";
        echo "<script>console.log('Chemin testé: " . addslashes($this->cheminDossier) . "');</script>";
        echo "<script>console.log('is_dir(): " . (is_dir($this->cheminDossier) ? 'true' : 'false') . "');</script>";
        echo "<script>console.log('is_readable(): " . (is_readable($this->cheminDossier) ? 'true' : 'false') . "');</script>";
        echo "<script>console.log('file_exists(): " . (file_exists($this->cheminDossier) ? 'true' : 'false') . "');</script>";

        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $processUser = posix_getpwuid(posix_geteuid());
            echo "<script>console.log('UID processus: " . posix_geteuid() . "');</script>";
            echo "<script>console.log('GID processus: " . posix_getegid() . "');</script>";
        }

        // Test d'un dossier Windows accessible (C:\Windows\System32)
        $testPath = "C:\\Windows\\System32";
        echo "<script>console.log('TEST: C:\\\\Windows\\\\System32 readable: " . (is_readable($testPath) ? 'true' : 'false') . "');</script>";

        // Test du dossier parent
        $parentPath = dirname($this->cheminDossier);
        echo "<script>console.log('Dossier parent: " . addslashes($parentPath) . "');</script>";
        echo "<script>console.log('Parent readable: " . (is_readable($parentPath) ? 'true' : 'false') . "');</script>";

        echo "<script>console.log('=========================');</script>";

        return is_dir($this->cheminDossier) && is_readable($this->cheminDossier);
    }

    /**
     * Récupère tous les fichiers du dossier avec les extensions autorisées
     *
     * @return array Tableau des fichiers trouvés avec leurs informations
     */
    public function obtenirFichiers() {
        if (!$this->dossierExiste()) {
            throw new \Exception("Le dossier '{$this->cheminDossier}' n'existe pas ou n'est pas accessible.");
        }

        echo "<script>console.log('DEBUG: Dossier accessible : " . addslashes($this->cheminDossier) . "');</script>";

        $fichiersTrouves = [];
        $contenuDossier = scandir($this->cheminDossier);

        echo "<script>console.log('DEBUG: Éléments trouvés par scandir : " . count($contenuDossier) . "');</script>";
        echo "<script>console.log('DEBUG: Contenu brut : " . addslashes(implode(', ', $contenuDossier)) . "');</script>";

        foreach ($contenuDossier as $element) {
            if ($element === '.' || $element === '..') {
                continue;
            }

            echo "<script>console.log('DEBUG: Examen de : " . addslashes($element) . "');</script>";

            $cheminComplet = $this->cheminDossier . DIRECTORY_SEPARATOR . $element;

            if (is_file($cheminComplet)) {
                echo "<script>console.log('DEBUG: " . addslashes($element) . " est un fichier');</script>";

                if ($this->extensionAutorisee($element)) {
                    echo "<script>console.log('DEBUG: " . addslashes($element) . " a une extension autorisée');</script>";

                    $fichiersTrouves[] = [
                        'nom' => $element,
                        'chemin_complet' => $cheminComplet,
                        'taille' => filesize($cheminComplet),
                        'date_modification' => filemtime($cheminComplet),
                        'extension' => strtolower(pathinfo($element, PATHINFO_EXTENSION))
                    ];
                } else {
                    $ext = strtolower(pathinfo($element, PATHINFO_EXTENSION));
                    echo "<script>console.log('DEBUG: " . addslashes($element) . " a extension ." . addslashes($ext) . " (non autorisée)');</script>";
                }
            } else {
                echo "<script>console.log('DEBUG: " . addslashes($element) . " n\\'est pas un fichier');</script>";
            }
        }

        echo "<script>console.log('DEBUG: Total fichiers trouvés : " . count($fichiersTrouves) . "');</script>";

        return $fichiersTrouves;
    }

    /**
     * Vérifie si un fichier a une extension autorisée
     *
     * @param string $nomFichier
     * @return bool
     */
    private function extensionAutorisee($nomFichier) {
        $extension = strtolower(pathinfo($nomFichier, PATHINFO_EXTENSION));
        return in_array($extension, $this->extensionsAutorisees);
    }

    /**
     * Affiche la liste des fichiers dans le terminal
     *
     * @return void
     */
    public function afficherFichiers() {
        try {
            $fichiers = $this->obtenirFichiers();

            echo "<script>console.log('=== Parcours du dossier : " . addslashes($this->cheminDossier) . " ===\\n\\n');</script>";
            echo "<script>console.log('Extensions recherchées : " . implode(', ', $this->extensionsAutorisees) . "\\n\\n');</script>";

            if (empty($fichiers)) {
                echo "<script>console.log('❌ Aucun fichier trouvé avec les extensions autorisées.\\n\\n');</script>";
            } else {
                echo "<script>console.log('Fichiers trouvés :\\n-----------------\\n');</script>";

                foreach ($fichiers as $fichier) {
                    echo "<script>console.log('📄 " . addslashes($fichier['nom']) . "\\n   └─ Taille: {$fichier['taille']} octets\\n   └─ Modifié: " . date('Y-m-d H:i:s', $fichier['date_modification']) . "\\n   └─ Extension: .{$fichier['extension']}\\n\\n');</script>";
                }
            }

            $this->afficherResume($fichiers);

        } catch (\Exception $e) {
            echo "<script>console.log('❌ Erreur : " . addslashes($e->getMessage()) . "\\n');</script>";
        }
    }

    /**
     * Affiche un résumé du parcours
     *
     * @param array $fichiers
     */
    private function afficherResume($fichiers) {
        echo "<script>console.log('=== RÉSUMÉ ===\\n');</script>";
        echo "<script>console.log('Nombre de fichiers trouvés : " . count($fichiers) . "\\n');</script>";

        if (!empty($fichiers)) {
            $tailleTotal = array_sum(array_column($fichiers, 'taille'));
            echo "<script>console.log('Taille totale : " . $this->formatTaille($tailleTotal) . "\\n');</script>";
        }
    }

    /**
     * Formate la taille en octets de manière lisible
     *
     * @param int $octets
     * @return string
     */
    private function formatTaille($octets) {
        $unites = ['o', 'Ko', 'Mo', 'Go'];
        $puissance = 0;

        while ($octets >= 1024 && $puissance < count($unites) - 1) {
            $octets /= 1024;
            $puissance++;
        }

        return round($octets, 2) . ' ' . $unites[$puissance];
    }

    /**
     * Compte le nombre de fichiers sans les afficher
     *
     * @return int
     */
    public function compterFichiers() {
        try {
            return count($this->obtenirFichiers());
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Modifie le chemin du dossier
     *
     * @param string $nouveauChemin
     */
    public function setCheminDossier($nouveauChemin) {
        $this->cheminDossier = rtrim($nouveauChemin, '/\\');
    }

    /**
     * Modifie les extensions autorisées
     *
     * @param array $extensions
     */
    public function setExtensions($extensions) {
        $this->extensionsAutorisees = array_map('strtolower', $extensions);
    }

    /**
     * Récupère le chemin actuel
     *
     * @return string
     */
    public function getCheminDossier() {
        return $this->cheminDossier;
    }

    /**
     * Récupère les extensions autorisées
     *
     * @return array
     */
    public function getExtensions() {
        return $this->extensionsAutorisees;
    }
}