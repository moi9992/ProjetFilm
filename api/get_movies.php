<?php
require_once __DIR__ . '/tmdb.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ➡️ NOUVEAU BLOC : Définition de l'état de connexion et de la permission Romance
// 1. Détermine si l'utilisateur est connecté (adaptez la variable si nécessaire)
$isLoggedIn = isset($_SESSION['isLog']) && $_SESSION['isLog'] === true; 

// 2. Détermine si l'utilisateur peut voir la Romance (doit être connecté ET majeur)
$canSeeRomance = $isLoggedIn && isUserMajeur(); 

// 3. Prépare le paramètre d'exclusion pour l'API TMDB
$exclusionGenres = '';
if (!$canSeeRomance) {
    // Si l'utilisateur est mineur ou déconnecté, on exclut l'ID de la Romance (10749)
    $exclusionGenres = '10749'; 
}
// FIN DU NOUVEAU BLOC

// Lecture paramètres POST
$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$selectedGenre = !empty($_POST['genre']) && is_numeric($_POST['genre'])
    ? intval($_POST['genre'])
    : null;

// Récupération des films
if ($selectedGenre) {
    // Cas 1 : Filtre par Genre sélectionné
    $data = tmdbRequest('/discover/movie', [
        'with_genres' => $selectedGenre,
        'page' => $page,
        'sort_by' => 'popularity.desc',
        'language' => 'fr-FR',
        // ➡️ AJOUT CLÉ pour l'exclusion API et éviter les trous lors du filtrage par genre
        'without_genres' => $exclusionGenres 
    ]);
} else {
    // Cas 2 : Affichage par défaut (Films Populaire/Découverte)
    // On utilise /discover/movie pour pouvoir utiliser without_genres
    $data = tmdbRequest('/discover/movie', [
        'page' => $page,
        'sort_by' => 'popularity.desc', // Maintien du tri par popularité par défaut
        'language' => 'fr-FR',
        // ➡️ AJOUT CLÉ pour l'exclusion API et éviter les trous sur la page principale
        'without_genres' => $exclusionGenres 
    ]);
}

if (!$data || empty($data['results'])) {
    echo "<p class='text-danger'>Aucun résultat trouvé.</p>";
    exit;
}

$items = $data['results'];
$totalPages = $data['total_pages'] ?? 1;

// ➡️ SUPPRESSION DU FILTRE ROMANCE PHP QUI CRÉAIT LES TROUS
// Les résultats sont déjà filtrés par l'API (without_genres)
$filtered = $items; 

if (empty($filtered)) {
    echo "<p class='text-danger'>Aucun film disponible pour ce critère.</p>";
    exit;
}

// Tri par Score (du meilleur au pire)
if (!empty($filtered)) {
    usort($filtered, function ($a, $b) {
        return $b['vote_average'] <=> $a['vote_average'];
    });
}

// 🔹 AFFICHAGE HTML DES FILMS
echo '<div class="row">'; 
foreach ($filtered as $item) {
    $id = $item['id'];
    $title = htmlspecialchars($item['title'] ?? "Sans titre");
    $poster = !empty($item['poster_path'])
        ? "https://image.tmdb.org/t/p/w500" . $item['poster_path']
        : "/ProjetFilm/images/no-poster.png";
    $release = htmlspecialchars($item['release_date'] ?? '');

    echo "
    <div class='col-md-3 mb-4'>
        <a href='/ProjetFilm/mainPages/film.php?id=$id' class='text-decoration-none text-light'>
            <div class='card bg-dark text-light border-secondary shadow-sm h-100'>
                <img src='$poster' class='card-img-top' alt='$title'>
                <div class='card-body'>
                    <h5 class='card-title'>$title</h5>
                    <p class='card-text text-warning mb-0'>⭐ {$item['vote_average']}/10</p>
                    <p class='card-text text-secondary small'>$release</p>
                </div>
            </div>
        </a>
    </div>";
}
echo '</div>'; 

// 🔹 PAGINATION AJAX
if ($totalPages > 1) {
    echo "<div class='col-12 mt-4'>
            <nav aria-label='Pagination'>
            <ul class='pagination justify-content-center'>";

    // Bouton « Première page » (uniquement si on n’est pas à la page 1)
    if ($page > 1) {
        echo "<li class='page-item'>
                <a class='page-link' href='#' data-page='1'>« Première</a>
              </li>";
    }

    // Précédent
    $prevDisabled = ($page <= 1) ? 'disabled' : '';
    $prevPage = $page - 1;
    echo "<li class='page-item $prevDisabled'>
            <a class='page-link' href='#' data-page='$prevPage'>Précédent</a>
          </li>";

    // Pages centrales
    $maxShow = 5;
    $start = max(1, $page - floor($maxShow / 2));
    $end = min($totalPages, $start + $maxShow - 1);

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $page) ? "active" : "";
        echo "<li class='page-item $active'>
                <a class='page-link' href='#' data-page='$i'>$i</a>
              </li>";
    }

    // Suivant
    $nextDisabled = ($page >= $totalPages) ? 'disabled' : '';
    $nextPage = $page + 1;
    echo "<li class='page-item $nextDisabled'>
            <a class='page-link' href='#' data-page='$nextPage'>Suivant</a>
          </li>";

    echo "</ul></nav></div>";
}