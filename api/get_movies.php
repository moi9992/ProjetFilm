<?php
require_once __DIR__ . '/tmdb.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Permissions Romance
$canSeeRomance = isUserMajeur();

// Lecture paramètres POST
$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$selectedGenre = !empty($_POST['genre']) && is_numeric($_POST['genre'])
    ? intval($_POST['genre'])
    : null;

// Récupération des films
if ($selectedGenre) {
    $data = tmdbRequest('/discover/movie', [
        'with_genres' => $selectedGenre,
        'page' => $page,
        'sort_by' => 'popularity.desc',
        'language' => 'fr-FR'
    ]);
} else {
    $data = tmdbRequest('/movie/popular', [
        'page' => $page,
        'language' => 'fr-FR'
    ]);
}

if (!$data || empty($data['results'])) {
    echo "<p class='text-danger'>Aucun résultat trouvé.</p>";
    exit;
}

$items = $data['results'];
$totalPages = $data['total_pages'] ?? 1;

// Filtre romance si mineur
$filtered = [];
foreach ($items as $movie) {
    if (!$canSeeRomance && in_array(10749, $movie['genre_ids'] ?? [])) continue;
    $filtered[] = $movie;
}

if (empty($filtered)) {
    echo "<p class='text-danger'>Aucun film disponible pour ce critère.</p>";
    exit;
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

