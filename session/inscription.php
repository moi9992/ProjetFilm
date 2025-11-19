<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Les Films - Inscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>

<!-- Header -->
<?php include '../headerFooter/header.php'; ?>

<div class="container main-content my-5">
    <div class="form-container border p-4 rounded bg-light shadow">
        <h2 class="mb-4 text-center">Inscription</h2>

        <?php 
        if (isset($_GET['status'])) {
            $status = $_GET['status'];
            $message = '';
            $alertClass = 'info';
            
            if ($status === 'success') {
                $message = "✅ L'utilisateur a été ajouté avec succès !";
                $alertClass = 'success';
            } elseif ($status === 'db_error') {
                $message = "❌ Erreur lors de l'ajout dans la base de données.";
                $alertClass = 'danger';
            } elseif ($status === 'missing_fields') {
                $message = "⚠️ Veuillez remplir tous les champs.";
                $alertClass = 'warning';
            } elseif ($status === 'user_exists') {
                $message = "❌ Email ou pseudo déjà associé à un compte.";
                $alertClass = 'danger';
            }
            
            if ($message) {
                echo '<div class="alert alert-' . $alertClass . ' mt-3" role="alert">';
                echo htmlspecialchars($message);
                echo '</div>';
            }
        }
        ?>

        <form action="../insert/insert.php" method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="Prenom" class="form-label">Prénom</label>
                    <input type="text" name="Prenom" id="Prenom" class="form-control border-dark" placeholder="Entrez votre prénom" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="Nom" class="form-label">Nom</label>
                    <input type="text" name="Nom" id="Nom" class="form-control border-dark" placeholder="Entrer votre nom" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="DateDeNaissance" class="form-label">Date de naissance</label>
                <input type="date" name="DateDeNaissance" id="DateDeNaissance" class="form-control border-dark" required>
            </div>

            <div class="mb-3">
                <label for="Email" class="form-label">E-mail</label>
                <input type="email" name="Email" id="Email" class="form-control border-dark" placeholder="Entrer votre adresse mail" required>
            </div>

            <div class="mb-3">
                <label for="Pseudo" class="form-label">Pseudo</label>
                <input type="text" name="Pseudo" id="Pseudo" class="form-control border-dark" placeholder="Entrer votre pseudo" required>
            </div>

            <div class="mb-3">
                <label for="Mdp" class="form-label">Mot de passe</label>
                <input type="password" name="Mdp" id="Mdp" class="form-control border-dark" placeholder="Entrer votre mot de passe" required>
            </div>

            <div class="d-grid col-6 mx-auto mt-4">
                <button type="submit" class="btn btn-primary">Valider</button>
            </div>
        </form>
    </div>
</div>

<!-- Footer -->
<?php include '../headerFooter/footer.php'; ?>
</body>
</html>