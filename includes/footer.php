    </div><!-- .container -->
    
    <footer class="footer mt-5 py-3 bg-dark">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-white">CTF Platform</h5>
                    <p class="text-muted">Une plateforme d'apprentissage de la sécurité informatique</p>
                </div>
                <div class="col-md-3">
                    <h5 class="text-white">Liens utiles</h5>
                    <ul class="list-unstyled text-muted">
                        <li><a href="/about.php" class="text-muted">À propos</a></li>
                        <li><a href="/rules.php" class="text-muted">Règles</a></li>
                        <li><a href="/faq.php" class="text-muted">FAQ</a></li>
                        <li><a href="/contact.php" class="text-muted">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5 class="text-white">Ressources</h5>
                    <ul class="list-unstyled text-muted">
                        <li><a href="/docs/" class="text-muted">Documentation</a></li>
                        <li><a href="/writeups/" class="text-muted">Write-ups</a></li>
                        <li><a href="/tools.php" class="text-muted">Outils</a></li>
                    </ul>
                </div>
            </div>
            <hr class="mt-4 mb-3 border-secondary">
            <div class="row">
                <div class="col-md-6 text-muted">
                    &copy; <?php echo date('Y'); ?> CTF Platform. Tous droits réservés.
                </div>
                <div class="col-md-6 text-end">
                    <a href="/privacy.php" class="text-muted me-3">Politique de confidentialité</a>
                    <a href="/terms.php" class="text-muted">Conditions d'utilisation</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
    // Fermer automatiquement les alertes après 5 secondes
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
    </script>
</body>
</html>
