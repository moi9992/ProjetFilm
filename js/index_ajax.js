(function() {
    const genreSelect = $("#genre-select");
    const movieListDiv = $("#movie-list");
    const paginationDiv = $("#pagination-film");

    let currentPage = 1;

    function loadMovies(page = 1) {
        currentPage = page;
        const genre = genreSelect.val() || '';

        $.ajax({
            url: "/ProjetFilm/api/get_movies.php",
            type: "POST",
            data: { genre: genre, page: currentPage },
            success: function(response) {
                // Créer un conteneur temporaire pour parser la réponse
                const tempDiv = $("<div>").html(response);

                // Mettre à jour les films
                movieListDiv.html(tempDiv.find(".row").html());

                // Mettre à jour la pagination
                paginationDiv.html(tempDiv.find(".pagination").parent().html());
            },
            error: function(xhr, status, error) {
                console.error("Erreur AJAX :", error);
                movieListDiv.html("<p class='text-danger'>Impossible de charger les films.</p>");
            }
        });
    }

    // Changement de genre → page 1
    genreSelect.on("change", function() {
        loadMovies(1);
    });

    // Pagination AJAX
    $(document).on("click", "#pagination-film .page-link", function(e) {
        e.preventDefault();
        const page = $(this).data("page");
        if (page) loadMovies(page);
    });

    // Chargement initial
    $(document).ready(function() {
        loadMovies(1);
    });
})();