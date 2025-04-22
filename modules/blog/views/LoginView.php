<?php
namespace modules\blog\views;

/**
 * Classe LoginView
 *
 * Cette classe gère l'affichage de la page de connexion.
 */
class LoginView {

    /**
     * Affiche le formulaire de connexion
     *
     * @param string|null $error Message d'erreur à afficher, le cas échéant
     * @return string Le contenu HTML généré
     */
    public function showLoginForm($error = null) {
        // Début de la mise en mémoire tampon
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Connexion</title>
            <link rel="stylesheet" href="assets/css/login.css">

            <body>
        <header>
        </header>
        </body>

        </head>
        <body>
        <div>
            <img href="
" alt="logo" class="logo">
            <h1><gr>Moscatelli, leader mondial</gr></h1>
        </div>
        <div class="login-container">
            <h2>Connexion</h2>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="index.php?action=login" method="post">
                <div class="form-group">
                    <label for="identifiant">Identifiant:</label>
                    <input type="text" id="identifiant" name="identifiant" required>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Se connecter</button>
            </form>
        </div>
        </body>
        </html>
        <?php
        // Retourne le contenu mis en mémoire tampon
        return ob_get_clean();
    }
}