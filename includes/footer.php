<?php
/**
 * Footer Component
 */
?>

<!-- Footer -->
<footer class="bg-dark text-light mt-5 py-4">
    <div class="container">
        <div class="row">
            <!-- About Section -->
            <div class="col-md-4 mb-3">
                <h5><i class="bi bi-palette-fill"></i> Art Marketplace</h5>
                <p class="text-muted small">
                    Discover and support independent artists. Buy unique, authentic artworks directly from creators.
                </p>
            </div>
            
            <!-- Quick Links -->
            <div class="col-md-4 mb-3">
                <h5>Quick Links</h5>
                <ul class="list-unstyled small">
                    <li><a href="/art-marketplace/index.php" class="text-decoration-none text-light">Home</a></li>
                    <li><a href="/art-marketplace/gallery/view_all.php" class="text-decoration-none text-light">Gallery</a></li>
                    <li><a href="/art-marketplace/auth/register.php" class="text-decoration-none text-light">Become an Artist</a></li>
                </ul>
            </div>
            
            <!-- Contact Section -->
            <div class="col-md-4 mb-3">
                <h5>Follow Us</h5>
                <div class="d-flex gap-2">
                    <a href="#" class="text-light text-decoration-none"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-light text-decoration-none"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="text-light text-decoration-none"><i class="bi bi-instagram"></i></a>
                </div>
            </div>
        </div>
        
        <hr class="bg-secondary">
        
        <!-- Copyright -->
        <div class="text-center text-muted small">
            <p>&copy; <?php echo date('Y'); ?> Art Marketplace. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="/art-marketplace/assets/js/script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const toggleButtons = document.querySelectorAll('.toggle-password');

    toggleButtons.forEach(button => {

        button.addEventListener('click', function () {

            const input = this.parentElement.querySelector('.password-field');
            const icon = this.querySelector('i');

            if (input.type === 'password') {

                input.type = 'text';

                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');

            } else {

                input.type = 'password';

                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');

            }

        });

    });

});
</script>
</body>
</html>
