<!-- Page content ends here -->
    </main>

    <footer class="page-footer blue darken-3">
        <div class="footer-copyright">
            <div class="container">
            © <?php echo date("Y"); ?> Student Management System
            <a class="grey-text text-lighten-4 right" href="<?php echo BASE_URL; ?>index.php">Home</a>
            </div>
        </div>
    </footer>

    <!-- Passive Event Listeners Patch for Materialize Touch/Scroll Violations -->
    <script src="<?php echo BASE_URL; ?>js/passive-listeners.js"></script>

    <!-- Materialize JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    
    <!-- Custom JS files -->
    <script src="<?php echo BASE_URL; ?>js/confirmation.js"></script>
    <script src="<?php echo BASE_URL; ?>js/search-filter.js"></script>

    <script>
        // Initialize Materialize components
        document.addEventListener('DOMContentLoaded', function() {
            var elems = document.querySelectorAll('.sidenav');
            var sidenavInstances = M.Sidenav.init(elems, {
                draggable: true,
                preventScrolling: true
            });

            // Ensure sidenav closes and overlay clears when a link is clicked
            elems.forEach(function(sidenavEl) {
                sidenavEl.querySelectorAll('li > a').forEach(function(link) {
                    link.addEventListener('click', function() {
                        var inst = M.Sidenav.getInstance(sidenavEl);
                        if (inst && inst.isOpen) {
                            inst.close();
                        }
                    });
                });
            });
            var datepickers = document.querySelectorAll('.datepicker');
            M.Datepicker.init(datepickers, {
                format: 'yyyy-mm-dd',
                yearRange: [1950, 2030]
            });
            var selects = document.querySelectorAll('select');
            M.FormSelect.init(selects);
            var modals = document.querySelectorAll('.modal');
            M.Modal.init(modals);
            var tooltips = document.querySelectorAll('.tooltipped');
            M.Tooltip.init(tooltips);
        });
    </script>
</body>
</html>