<?php
?>

<footer class="km-footer mt-auto">
    <div class="container-fluid px-4 py-5">
        <div class="row g-4">

            <div class="col-12 col-md-4">
                <h5 class="km-footer-brand">Kasi<span class="km-brand-accent">Market</span></h5>
                <p class="text-white small mt-2">
                    The people's marketplace. Buy &amp; sell clothes, electronics, books,
                    and more right in your community, safely.
                </p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="km-social-icon"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="km-social-icon"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="km-social-icon"><i class="bi bi-whatsapp"></i></a>
                    <a href="#" class="km-social-icon"><i class="bi bi-instagram"></i></a>
                </div>
            </div>

            <div class="col-6 col-md-2">
                <h6 class="km-footer-heading">Browse</h6>
                <ul class="list-unstyled km-footer-links">
                    <li><a href="<?= APP_URL ?>/index.php?cat=Electronics">Electronics</a></li>
                    <li><a href="<?= APP_URL ?>/index.php?cat=Clothing+%26+Shoes">Clothing</a></li>
                    <li><a href="<?= APP_URL ?>/index.php?cat=Books+%26+Stationery">Books</a></li>
                    <li><a href="<?= APP_URL ?>/index.php?cat=Furniture+%26+Home">Furniture</a></li>
                    <li><a href="<?= APP_URL ?>/index.php?cat=Other">Other</a></li>
                </ul>
            </div>

            <div class="col-6 col-md-2">
                <h6 class="km-footer-heading">Account</h6>
                <ul class="list-unstyled km-footer-links">
                    <li><a href="<?= APP_URL ?>/register.php">Register</a></li>
                    <li><a href="<?= APP_URL ?>/login.php">Login</a></li>
                    <li><a href="<?= APP_URL ?>/upload.php">Sell an Item</a></li>
                </ul>
            </div>

            <div class="col-12 col-md-4">
                <h6 class="km-footer-heading"><i class="bi bi-shield-check me-1 text-success"></i>Safety Tips</h6>
                <ul class="list-unstyled km-footer-links small text-white">
                    <li><i class="bi bi-check2 me-1 text-success"></i>Always meet in a public place</li>
                    <li><i class="bi bi-check2 me-1 text-success"></i>Inspect items before paying</li>
                    <li><i class="bi bi-check2 me-1 text-success"></i>Use verified sellers only</li>
                    <li><i class="bi bi-check2 me-1 text-success"></i>Avoid sharing personal banking info</li>
                    <li><i class="bi bi-check2 me-1 text-success"></i>Pay cash-on-delivery where possible</li>
                </ul>
            </div>
        </div>

        <hr class="km-footer-divider mt-4 mb-3">

        <div class="d-flex flex-column align-items-center text-center">
            <small class="text-white mb-2">
                &copy; 2026 KasiMarket. Built with <3 for South African hustlers.
                    </small>
                    <small class="text-white">
                        <a href="<?= APP_URL ?>/privacy.php" class="text-white me-3">Privacy Policy</a>
                        <a href="<?= APP_URL ?>/terms.php" class="text-white">Terms of Use</a>
                    </small>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>

</html>