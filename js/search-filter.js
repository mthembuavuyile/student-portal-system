/**
 * search-filter.js
 * Implements live search/filter functionality for HTML tables.
 */

// Wait for the DOM to be fully loaded before attaching the event listener
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const studentTable = document.getElementById('studentTable');

    if (searchInput && studentTable) {
        searchInput.addEventListener('keyup', () => {
            const filter = searchInput.value.toUpperCase();
            const rows = studentTable.getElementsByTagName("tr");

            // Loop through all table rows, starting from the second row (index 1) to skip the header
            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName("td");
                let rowShouldBeVisible = false;

                if (cells.length > 0) {
                    // Check the "Full Name" (index 0) and "Student ID" (index 1) columns
                    const nameText = cells[0].textContent || cells[0].innerText;
                    const idText = cells[1].textContent || cells[1].innerText;

                    if (nameText.toUpperCase().indexOf(filter) > -1 || idText.toUpperCase().indexOf(filter) > -1) {
                        rowShouldBeVisible = true;
                    }
                }
                
                // Show or hide the row based on whether a match was found
                rows[i].style.display = rowShouldBeVisible ? "" : "none";
            }
        });
    }
});