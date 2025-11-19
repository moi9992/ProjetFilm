<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../headerFooter/header.php';
?>

<div class="container my-5 text-center">
    <h1 class="mb-4">Bienvenue sur <span class="text-primary">Les Films</span></h1>
    <p class="text-secondary mb-5">Découvrez les films populaires du moment 🍿</p>

    <!-- LISTE DES FILMS -->
    <div class="row" id="movie-list" data-total-pages="1">
        <!-- Films chargés via AJAX -->
    </div>

    <!-- PAGINATION -->
    <nav id="pagination-film" aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center"></ul>
    </nav>
</div>

<?php include '../headerFooter/footer.php'; ?>

<!-- Script AJAX pour genre + pagination -->
<script src="/ProjetFilm/js/index_ajax.js?v=1"></script>

