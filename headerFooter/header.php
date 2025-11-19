<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary">
    <div class="container d-flex align-items-center" style="height: 60px;">
        <a class="navbar-brand fw-bold mb-0" href="index.php" style="line-height: 1;">Les Films</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse d-flex align-items-center" id="navbarNav">

            <!-- 🔍 Barre de recherche AJAX + sélection de genre -->
            <form class="d-flex align-items-center ms-auto me-3 position-relative" 
                  role="search" 
                  onsubmit="return false;" 
                  style="margin-bottom: 0;">

                <input 
                    id="search"
                    class="form-control form-control-sm me-2"
                    type="search"
                    placeholder="Rechercher un film..."
                    autocomplete="off"
                    style="height: 36px; margin-top: 2px;"
                >

                <!-- Genre (FILMS UNIQUEMENT) -->
                <select id="genre-select" 
                        class="form-select form-select-sm me-2"
                        style="height: 36px; margin-top: 2px; width: 150px;">
                    <option value="">Tous les genres</option>
                    <option value="28">Action</option>
                    <option value="12">Aventure</option>
                    <option value="35">Comédie</option>
                    <option value="18">Drame</option>
                    <option value="14">Fantastique</option>
                    <option value="27">Horreur</option>
                    <option value="10749">Romance</option>
                    <option value="878">Science-fiction</option>
                    <option value="53">Thriller</option>
                </select>

                <div id="search-results"
                     class="position-absolute bg-dark text-light border rounded w-100"
                     style="z-index: 1050; max-height: 300px; overflow-y: auto; display: none; top: 38px;">
                </div>
            </form>

            <!-- Connexion / Déconnexion -->
            <?php if (!empty($_SESSION['isLog']) && $_SESSION['isLog'] === true): ?>
                <a href="../session/logout.php"
                   class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center"
                   style="height: 36px; margin-top: 2px;">
                    Déconnexion
                </a>
            <?php else: ?>
                <a href="../session/connexion.php"
                   class="btn btn-outline-light btn-sm d-flex align-items-center justify-content-center"
                   style="height: 36px; margin-top: 2px;">
                    Connexion
                </a>
            <?php endif; ?>

        </div>
    </div>
</nav>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="/ProjetFilm/js/search.js?v=1"></script>