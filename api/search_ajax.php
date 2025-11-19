<?php
header("Content-Type: text/html; charset=UTF-8");
require_once __DIR__ . '/tmdb.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Permissions Romance
$canSeeRomance = false;
if (!empty($_SESSION['isLog']) && !empty($_SESSION['user']['dateDeNaissance'])) {
    $dob = DateTime::createFromFormat('Y-m-d', $_SESSION['user']['dateDeNaissance']);
    if (!$dob) {
        try { $dob = new DateTime($_SESSION['user']['dateDeNaissance']); } catch (Exception $e) { $dob = null; }
    }
    if ($dob) $canSeeRomance = ((new DateTime())->diff($dob)->y) >= 18;
}

// Paramètres
$query = trim($_POST['query'] ?? "");
$genreId = intval($_POST['genre'] ?? 0); // will be ignored when $query != ""
$mode = $_POST['mode'] ?? ""; // optional: "suggest" or "results"

// If query is present => we treat this as suggestions (search), IGNORE genre selection
if ($query !== "") {
    // suggestions from search endpoint
    $data = tmdbRequest("/search/movie", [
        "query" => $query,
        "page"  => 1,
        "language" => "fr-FR"
    ]);

    $items = $data["results"] ?? [];
    if (empty($items)) {
        echo "<div class='p-2 text-muted'>Aucun résultat trouvé</div>";
        exit;
    }

    // limit suggestions and filter romance for minors
    $count = 0;
    foreach ($items as $item) {
        if (empty($item["id"])) continue;
        $genreIds = $item["genre_ids"] ?? [];
        if (!$canSeeRomance && in_array(10749, $genreIds)) continue;

        $title = htmlspecialchars($item["title"] ?? "Sans titre");
        $poster = !empty($item["poster_path"])
            ? "https://image.tmdb.org/t/p/w92" . $item["poster_path"]
            : "/ProjetFilm/images/no-poster.png";

        echo "
            <a href='/ProjetFilm/mainPages/film.php?id={$item["id"]}'
               class='d-flex align-items-center p-2 border-bottom text-light text-decoration-none'
               style='background:#212529'>
                <img src='$poster' width='40' class='me-2 rounded' alt='$title'>
                <span>$title</span>
            </a>
        ";

        $count++;
        if ($count >= 8) break; // max 8 suggestions
    }

    exit;
}

// If query is empty, we can optionally return "results" filtered by genre (if caller asks)
// Default behaviour when query empty: return popular movies (but JS uses get_movies.php for full page)
$data = tmdbRequest("/movie/popular", [
    "page" => 1,
    "language" => "fr-FR"
]);

$items = $data["results"] ?? [];
if (empty($items)) {
    echo "<div class='p-2 text-muted'>Aucun résultat trouvé</div>";
    exit;
}

// If a genre is provided, filter by it
if ($genreId > 0) {
    $filtered = [];
    foreach ($items as $item) {
        $genreIds = $item["genre_ids"] ?? [];
        if (!$canSeeRomance && in_array(10749, $genreIds)) continue;
        if (in_array($genreId, $genreIds)) $filtered[] = $item;
    }
    $items = $filtered;
}

// Default output for "results" mode or fallback: small list (same structure as suggestions)
foreach ($items as $item) {
    if (empty($item["id"])) continue;
    $genreIds = $item["genre_ids"] ?? [];
    if (!$canSeeRomance && in_array(10749, $genreIds)) continue;

    $title = htmlspecialchars($item["title"] ?? "Sans titre");
    $poster = !empty($item["poster_path"])
        ? "https://image.tmdb.org/t/p/w92" . $item["poster_path"]
        : "/ProjetFilm/images/no-poster.png";

    echo "
        <a href='/ProjetFilm/mainPages/film.php?id={$item["id"]}'
           class='d-flex align-items-center p-2 border-bottom text-light text-decoration-none'
           style='background:#212529'>
            <img src='$poster' width='40' class='me-2 rounded' alt='$title'>
            <span>$title</span>
        </a>
    ";
}