<?php
require '../session/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des champs du formulaire
    $prenom = trim($_POST['Prenom']);
    $nom = trim($_POST['Nom']);
    $dateNaissance = $_POST['DateDeNaissance'];
    $email = trim($_POST['Email']);
    $pseudo = trim($_POST['Pseudo']);
    $mdp = $_POST['Mdp'];

    // Vérification que tout est rempli
    if (!empty($prenom) && !empty($nom) && !empty($dateNaissance) && !empty($email) && !empty($pseudo) && !empty($mdp)) {

        // Vérifier si l'utilisateur existe déjà (email OU pseudo)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE Email = ? OR Pseudo = ?");
        $stmt->execute([$email, $pseudo]);
        $userExists = $stmt->fetchColumn();

        if ($userExists > 0) {
            header('Location: ../session/inscription.php?status=user_exists');
            exit;
        } else {
            try {
                // Insertion dans la BDD
                $stmt = $pdo->prepare("INSERT INTO utilisateurs (Prenom, Nom, DateDeNaissance, Email, Mdp, Pseudo, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$prenom, $nom, $dateNaissance, $email, password_hash($mdp, PASSWORD_DEFAULT), $pseudo]);

                // Redirection après succès
                header('Location: ../session/connexion.php?status=success');
                exit;

            } catch (PDOException $e) {
                // Erreur d'insertion
                header('Location: ../session/inscription.php?status=db_error');
                exit;
            }
        }
    } else {
        // Champs manquants
        header('Location: ../session/inscription.php?status=missing_fields');
        exit;
    }
} else {
    echo "<p style='color:red;'>Méthode non autorisée.</p>";
}
?>
