<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<body class="d-flex flex-column min-vh-100">
    <main class="flex-grow-1"></main>

<footer class="bg-dark border-top border-secondary py-3 mt-5">
    <div class="container text-center">
        <?php
        if (isset($_SESSION['user']['pseudo']) && !empty($_SESSION['user']['pseudo'])) {        
            echo '<p class="mb-0 text-light">Bonjour ' . htmlspecialchars($_SESSION['user']['pseudo']) . '</p>';
        } else {
            echo '<p class="mb-0 text-light">Bonjour invité</p>';
        }
        ?>

    </div>
</footer>
</body>
</html>

