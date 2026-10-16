<!-- Page content ends here -->
    </main>

    <footer class="page-footer blue darken-3">
        <div class="footer-copyright">
            <div class="container">
            © <?php echo date("Y"); ?> Student Management System
            <a class="grey-text text-lighten-4 right" href="../index.php">Home</a>
            </div>
        </div>
    </footer>

    <!-- Materialize JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    
    <!-- Custom JS files -->
    <script src="<?php echo BASE_URL; ?>js/confirmation.js"></script>
    <script src="<?php echo BASE_URL; ?>js/search-filter.js"></script>

    <script>
        // Initialize Materialize components
        document.addEventListener('DOMContentLoaded', function() {
            var elems = document.querySelectorAll('.sidenav');
            M.Sidenav.init(elems);
            var datepickers = document.querySelectorAll('.datepicker');
            M.Datepicker.init(datepickers, {
                format: 'yyyy-mm-dd',
                yearRange: [1950, 2030]
            });
            var selects = document.querySelectorAll('select');
            M.FormSelect.init(selects);
        });
    </script>
</body>
</html>