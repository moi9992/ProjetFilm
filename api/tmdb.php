<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('TMDB_API_KEY', '62c39b708c45d92c639d63457299a766');
define('TMDB_API_BASE', 'https://api.themoviedb.org/3');

// -----------------------------------------------------------
// 🔹 Vérification de majorité
// -----------------------------------------------------------
function isUserMajeur(): bool {
    if (!empty($_SESSION['isLog']) && !empty($_SESSION['user']['dateNaissance'])) {
        $dob = DateTime::createFromFormat('Y-m-d', $_SESSION['user']['dateNaissance']);
        if (!$dob) return false;

        $age = (new DateTime())->diff($dob)->y;
        return ($age >= 18);
    }
    return false;
}

// -----------------------------------------------------------
// 🔹 Appel générique TMDB
// -----------------------------------------------------------
function tmdbRequest(string $endpoint, array $params = []): ?array {
    $params['api_key'] = TMDB_API_KEY;
    $params['language'] = 'fr-FR';

    $url = TMDB_API_BASE . $endpoint . '?' . http_build_query($params);

    $response = @file_get_contents($url);
    if (!$response) return null;

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

// -----------------------------------------------------------
// 🔹 Films populaires (avec pagination)
// -----------------------------------------------------------
function getPopularMovies(int $limit = 12, int $page = 1): array {
    $data = tmdbRequest('/movie/popular', ['page' => $page]);
    if (!is_array($data) || empty($data['results'])) return [];

    $majeur = isUserMajeur();
    $movies = [];

    foreach ($data['results'] as $movie) {
        if (!$majeur && in_array(10749, $movie['genre_ids'] ?? [])) continue;
        $movies[] = $movie;
        if (count($movies) >= $limit) break;
    }

    return [
        "results" => $movies,
        "total_pages" => min($data['total_pages'] ?? 1, 500)
    ];
}

// -----------------------------------------------------------
// 🔹 Total pages TMDB
// -----------------------------------------------------------
function getPopularMoviesTotalPages(): int {
    $data = tmdbRequest('/movie/popular', ['page' => 1]);
    return min(intval($data['total_pages'] ?? 1), 500);
}

// -----------------------------------------------------------
// 🔹 Détails film
// -----------------------------------------------------------
function getMovie(int $id): ?array {
    $data = tmdbRequest("/movie/$id");
    if (!is_array($data)) return null;

    if (!isUserMajeur() && in_array(10749, array_column($data['genres'] ?? [], 'id'))) {
        return null;
    }
    return $data;
}

// -----------------------------------------------------------
// 🔹 Films similaires
// -----------------------------------------------------------
function getSimilarMovies(int $id, int $limit = 8): array {
    $data = tmdbRequest("/movie/$id/similar");
    if (!is_array($data) || empty($data['results'])) return [];

    $majeur = isUserMajeur();
    $results = [];

    foreach ($data['results'] as $movie) {
        if (!$majeur && in_array(10749, $movie['genre_ids'] ?? [])) continue;
        $results[] = $movie;
        if (count($results) >= $limit) break;
    }

    return $results;
}

// -----------------------------------------------------------
// 🔹 Recherche films
// -----------------------------------------------------------
function searchMovies(string $query, int $limit = 10): array {
    $data = tmdbRequest('/search/movie', ['query' => $query]);
    if (!is_array($data) || empty($data['results'])) return [];

    $majeur = isUserMajeur();
    $results = [];

    foreach ($data['results'] as $movie) {
        if (!$majeur && in_array(10749, $movie['genre_ids'] ?? [])) continue;
        $results[] = $movie;
        if (count($results) >= $limit) break;
    }

    return $results;
}

// -----------------------------------------------------------
// 🔹 Liste des genres films
// -----------------------------------------------------------
function getTmdbGenres(): array {
    $data = tmdbRequest('/genre/movie/list');
    return $data['genres'] ?? [];
}

// -----------------------------------------------------------
// 🔹 Discover films par genre (pagination OK)
// -----------------------------------------------------------
function discoverMoviesByGenre(int $genreId, int $limit = 12, int $page = 1): array {
    $data = tmdbRequest('/discover/movie', [
        'with_genres' => $genreId,
        'page' => $page,
        'sort_by' => 'popularity.desc'
    ]);

    if (!is_array($data) || empty($data['results'])) return [];

    $majeur = isUserMajeur();
    $movies = [];

    foreach ($data['results'] as $movie) {
        if (!$majeur && in_array(10749, $movie['genre_ids'] ?? [])) continue;
        $movies[] = $movie;
        if (count($movies) >= $limit) break;
    }

    return [
        "results" => $movies,
        "total_pages" => min($data['total_pages'] ?? 1, 500)
    ];
}