const searchInput = $("#search");
const resultsDiv = $("#search-results");
const genreSelect = $("#genre-select");
const movieListDiv = $("#movie-list");

if (searchInput.length === 0) {
    console.warn("Search.js : pas de barre de recherche sur cette page.");
}

let debounceTimer;
let activeGenre;

function initStateFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const urlGenre = urlParams.get("genre");

    activeGenre = urlGenre !== null ? urlGenre : genreSelect.val();
    genreSelect.val(activeGenre);

    return parseInt(urlParams.get("page")) || 1;
}

function loadMovies(page = 1) {
    const query = searchInput.val().trim();
    const activeGenre = genreSelect.val();

    let endpoint = "";
    let data = { genre: activeGenre, page: page };

    // 🔍 Si on tape quelque chose → SUGGESTIONS
    if (query.length > 0) {
        endpoint = "/ProjetFilm/api/search_ajax.php";
        data.query = query;
        data.mode = "suggest";
    }
    // 🎬 Sinon → CHARGER FILMS DE L’INDEX
    else {
        endpoint = "/ProjetFilm/api/get_movies.php";  // ← CORRECTION ICI
    }

    $.ajax({
        url: endpoint,
        type: "POST",
        data: data,
        success: function(response) {

            // 🔍 Recherche → dropdown
            if (query.length > 0) {
                resultsDiv.show().html(response);
            }

            // 🎬 Page index → remplacer films
            else {
                movieListDiv.html(response);
            }
        }
    });
}

searchInput.on("keyup", function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadMovies(1), 200);
});

genreSelect.on("change", function() {
    loadMovies(1);
});

$(document).on("click", function(e) {
    if (!$(e.target).closest("#search, #search-results").length) {
        resultsDiv.fadeOut();
    }
});

$(document).ready(function() {
    const page = initStateFromURL();
    loadMovies(page);
});

