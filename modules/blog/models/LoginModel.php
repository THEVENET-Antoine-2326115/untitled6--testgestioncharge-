<?php
namespace modules\blog\models;
use PDO;
use PDOException;

/**
 * Classe LoginModel
 *
 * Cette classe gère les opérations de Login des utilisateurs.
 */
class LoginModel {
    /**
     * @var \PDO $db Connexion à la base de données
     */
    private $db; // pour la connexion à la base de données

    /**
     * Constructeur de la classe LoginModel
     *
     * Initialise la connexion à la base de données via SingletonModel.
     */
    public function __construct()
    {
        // Connexion à la base de données via SingletonModel
        $this->db = SingletonModel::getInstance()->getConnection();
    }

    /**
     * Tester le mot de passe
     *
     * Vérifie si l'utilisateur (util ou admin) existe et si le mot de passe correspond.
     *
     * @param string $identifiant Identifiant de l'utilisateur
     * @param string $password Mot de passe de l'utilisateur
     * @return array|false Les informations de l'utilisateur si le Login réussit, sinon false
     */
    public function test_Pass($identifiant, $password)
    {
        // Vérifier d'abord si c'est un utilisateur normal
        $stmt = $this->db->prepare("SELECT * FROM Utilisateur WHERE id_util = :identifiant");
        $stmt->bindParam(':identifiant', $identifiant);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si utilisateur trouvé et mot de passe correct
        if ($result && password_verify($password, $result['mdp'])) {
            $result['type'] = 'utilisateur'; // Ajouter un type pour différencier
            return $result;
        }

        // Si ce n'est pas un utilisateur normal, vérifier si c'est un admin
        $stmt = $this->db->prepare("SELECT * FROM Utilisateur WHERE id_admin = :identifiant");
        $stmt->bindParam(':identifiant', $identifiant);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si admin trouvé et mot de passe correct
        if ($result && password_verify($password, $result['mdp'])) {
            $result['type'] = 'admin'; // Ajouter un type pour différencier
            return $result;
        }

        // Aucune correspondance trouvée
        return false;
    }
}
?>