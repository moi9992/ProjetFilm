<?php
// Vérification et démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../api/tmdb.php';
include '../headerFooter/header.php';

// Définition de l'état de connexion et du pseudo
$isLog = isset($_SESSION['isLog']) && $_SESSION['isLog'] === true;
$current_user_name = ($isLog && isset($_SESSION['user']['pseudo'])) ? $_SESSION['user']['pseudo'] : null;

if (empty($_GET['id'])) {
    echo "<div class='container my-5'><p class='text-danger'>Film non trouvé.</p></div>";
    include '../headerFooter/footer.php';
    exit;
}

$filmId = intval($_GET['id']);

require_once __DIR__ . '/../session/db.php'; 

// Variable d'état pour le mode édition dans l'affichage
$is_editing = isset($_GET['edit_comment_id']) && $isLog ? intval($_GET['edit_comment_id']) : 0;

// --- LOGIQUE D'ENREGISTREMENT DU COMMENTAIRE ---
$comment_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- GESTION DE LA SUPPRESSION ---
    if (isset($_POST['delete_comment'])) {
        if (!$isLog) {
            $comment_error = "Vous devez être connecté pour supprimer un commentaire.";
        } else {
            $commentIdToDelete = intval($_POST['comment_id']);
            
            try {
                // DELETE en vérifiant que l'utilisateur est l'auteur (sécurité)
                $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? AND user_name = ? AND movie_id = ?");
                $stmt->execute([$commentIdToDelete, $current_user_name, $filmId]);

                if ($stmt->rowCount() > 0) {
                     header("Location: film.php?id=" . $filmId . "&comment_deleted=1#comments-section");
                     exit;
                } else {
                     $comment_error = "Impossible de supprimer le commentaire (non trouvé ou vous n'êtes pas l'auteur).";
                }
            } catch (\PDOException $e) {
                $comment_error = "Erreur lors de la suppression.";
            }
        }
    }
    
    // --- GESTION DE LA MODIFICATION ---
    elseif (isset($_POST['update_comment'])) {
        if (!$isLog) {
            $comment_error = "Vous devez être connecté pour modifier un commentaire.";
        } else {
            $commentIdToUpdate = intval($_POST['comment_id']);
            $newCommentText = trim(htmlspecialchars($_POST['comment_text']));
    
            if (empty($newCommentText)) {
                $comment_error = "Le commentaire modifié ne peut pas être vide.";
            } else {
                try {
                    // UPDATE en vérifiant que l'utilisateur est l'auteur (sécurité)
                    $stmt = $pdo->prepare("UPDATE comments SET comment_text = ? WHERE id = ? AND user_name = ? AND movie_id = ?");
                    $stmt->execute([$newCommentText, $commentIdToUpdate, $current_user_name, $filmId]);
    
                    if ($stmt->rowCount() > 0) {
                        header("Location: film.php?id=" . $filmId . "&comment_updated=1#comments-section");
                        exit;
                    } else {
                        $comment_error = "Impossible de modifier le commentaire (non trouvé ou vous n'êtes pas l'auteur).";
                    }
    
                } catch (\PDOException $e) {
                    $comment_error = "Erreur lors de la modification.";
                }
            }
        }
    }
    
    // --- GESTION DE L'INSERTION (INITIALE) ---
    elseif (isset($_POST['submit_comment'])) {
        
        if (!$isLog) {
            $comment_error = "Vous devez être connecté pour poster un commentaire.";
        } else {
            $user_name = $current_user_name; 
            
            $comment_text = trim(htmlspecialchars($_POST['comment_text']));
            $comment_movie_id = intval($_POST['movie_id']); 

            if (empty($comment_text)) {
                $comment_error = "Veuillez écrire un commentaire.";
            } elseif ($comment_movie_id !== $filmId) {
                $comment_error = "Erreur de sécurité lors de la soumission.";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO comments (movie_id, user_name, comment_text, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$comment_movie_id, $user_name, $comment_text]);
                    
                    header("Location: film.php?id=" . $filmId . "&comment_posted=1#comments-section");
                    exit;
                    
                } catch (\PDOException $e) {
                    $comment_error = "Une erreur est survenue lors de l'enregistrement du commentaire.";
                }
            }
        }
    }
}


// --- LOGIQUE DE RÉCUPÉRATION DES COMMENTAIRES ---
$comments = [];
try {
    // IMPORTANT : Récupérer l'ID (id) du commentaire pour les opérations de suppression/modification
    $stmt = $pdo->prepare("SELECT id, user_name, comment_text, created_at FROM comments WHERE movie_id = ? ORDER BY created_at DESC");
    $stmt->execute([$filmId]);
    $comments = $stmt->fetchAll();
} catch (\PDOException $e) {
    // La liste des commentaires sera vide en cas d'erreur de BD
}

// 🔹 Récupération du film
$movie = getMovie($filmId);

if (!$movie) {
    echo "<div class='container my-5'><p class='text-danger'>Film non disponible (Romance filtrée pour mineurs ou non connecté).</p></div>";
    include '../headerFooter/footer.php';
    exit;
}

// 🔹 Films similaires
$similarMovies = getSimilarMovies($filmId, 8);

// 🔹 Casting principal (top 5)
$credits = tmdbRequest("/movie/$filmId/credits")['cast'] ?? [];
$topCast = array_slice($credits, 0, 5);
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-4">
            <?php if (!empty($movie['poster_path'])): ?>
                <img src="https://image.tmdb.org/t/p/w500<?= $movie['poster_path'] ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($movie['title']) ?>">
            <?php else: ?>
                <div class="bg-secondary d-flex align-items-center justify-content-center" style="height:500px;">
                    <span class="text-light">Pas d’image</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-8">
            <h1><?= htmlspecialchars($movie['title']) ?></h1>
            <p class="text-muted">Sortie : <?= htmlspecialchars($movie['release_date']) ?></p>
            <p class="text-warning">⭐ <?= $movie['vote_average'] ?>/10</p>
            
            <p class="text-dark">Durée : 
                <?php 
                if (!empty($movie['runtime'])) {
                    $hours = floor($movie['runtime'] / 60);
                    $minutes = $movie['runtime'] % 60;
                    echo $hours . "h " . $minutes . "min";
                } else {
                    echo "N/A";
                }
                ?>
            </p>

            <h5>Description :</h5>
            <p><?= htmlspecialchars($movie['overview']) ?></p>

            <?php if (!empty($movie['genres'])): ?>
                <h6>Genres :</h6>
                <ul>
                    <?php foreach ($movie['genres'] as $genre): ?>
                        <li><?= htmlspecialchars($genre['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($topCast)): ?>
                <h6>Casting principal :</h6>
                <ul>
                    <?php foreach ($topCast as $actor): ?>
                        <li><?= htmlspecialchars($actor['name']) ?> dans le rôle de <?= htmlspecialchars($actor['character']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container my-5" id="comments-section">
    <h3 class="mb-4">Espace Commentaires</h3>
    
    <?php if (isset($_GET['comment_posted'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Commentaire posté ! Merci.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['comment_deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Commentaire supprimé !
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['comment_updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Commentaire modifié !
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (!empty($comment_error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($comment_error) ?></div>
    <?php endif; ?>

    <?php if ($isLog): ?>
        <div class="card p-4 mb-5 shadow-sm">
            <h5 class="card-title">Ajouter votre avis en tant que <?= htmlspecialchars($current_user_name ?? '') ?></h5>
            <form method="POST" action="film.php?id=<?= $filmId ?>#comments-section">
                
                <input type="hidden" name="movie_id" value="<?= $filmId ?>">
                
                <div class="mb-3">
                    <label for="comment_text" class="form-label">Votre commentaire</label>
                    <textarea class="form-control" id="comment_text" name="comment_text" rows="3" required></textarea>
                </div>
                <button type="submit" name="submit_comment" class="btn btn-primary">Poster le commentaire</button>
            </form>
        </div>
    <?php else: ?>
        <div class="alert alert-warning mb-5">
            Pour laisser un commentaire, veuillez vous <a href="../session/connexion.php" class="alert-link">connecter</a>.
        </div>
    <?php endif; ?>

    <?php if (!empty($comments)): ?>
        <h4 class="mb-3">Tous les commentaires (<?= count($comments) ?>)</h4>
        
        <?php foreach ($comments as $comment): ?>
            <div class="card mb-3 shadow-sm border-0">
                <div class="card-body">
                    <p class="card-text mb-1 d-flex justify-content-between">
                        <strong class="text-primary"><?= htmlspecialchars($comment['user_name']) ?></strong>
                        <small class="text-muted">
                            Posté le 
                            <?php 
                            // Formatage de la date 
                            $date = new DateTime($comment['created_at']);
                            echo $date->format('d/m/Y à H:i');
                            ?>
                        </small>
                    </p>
                    
                    <?php 
                    // Vérification de la propriété du commentaire pour l'édition/suppression
                    $is_owner = $isLog && ($comment['user_name'] === $current_user_name);
                    
                    if ($is_owner && $comment['id'] === $is_editing): 
                    ?>
                        <form method="POST" action="film.php?id=<?= $filmId ?>#comments-section">
                            <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                            <input type="hidden" name="movie_id" value="<?= $filmId ?>">
                            <div class="mb-2">
                                <textarea class="form-control" name="comment_text" rows="3" required><?= htmlspecialchars($comment['comment_text']) ?></textarea>
                            </div>
                            <button type="submit" name="update_comment" class="btn btn-success btn-sm me-2">Sauvegarder</button>
                            <a href="film.php?id=<?= $filmId ?>#comments-section" class="btn btn-secondary btn-sm">Annuler</a>
                        </form>
                    <?php else: ?>
                        <p class="card-text border-top pt-2"><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>

                        <?php if ($is_owner): ?>
                            <div class="mt-2 text-end">
                                <a href="film.php?id=<?= $filmId ?>&edit_comment_id=<?= $comment['id'] ?>#comments-section" class="btn btn-sm btn-info me-2">Modifier</a>
                                
                                <form method="POST" action="film.php?id=<?= $filmId ?>#comments-section" class="d-inline-block" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce commentaire ?')">
                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                    <input type="hidden" name="movie_id" value="<?= $filmId ?>">
                                    <button type="submit" name="delete_comment" class="btn btn-sm btn-danger">Supprimer</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

    <?php else: ?>
        <p class="alert alert-info">Soyez le premier à commenter ce film !</p>
    <?php endif; ?>

</div>

<?php if (!empty($similarMovies)): ?>
<div class="container my-5">
    <h3 class="mb-3">Films similaires</h3>
    <div class="row">
        <?php foreach ($similarMovies as $sim): ?>
            <div class="col-md-3 mb-4">
                <a href="film.php?id=<?= $sim['id'] ?>" class="text-decoration-none text-light">
                    <div class="card bg-dark text-light h-100">
                        <?php if (!empty($sim['poster_path'])): ?>
                            <img src="https://image.tmdb.org/t/p/w300<?= $sim['poster_path'] ?>" class="card-img-top" alt="<?= htmlspecialchars($sim['title']) ?>">
                        <?php else: ?>
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height:375px;">
                                <span class="text-muted">Pas d’image</span>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($sim['title']) ?></h6>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php include '../headerFooter/footer.php'; ?>