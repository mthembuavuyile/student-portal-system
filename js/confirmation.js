/**
 * confirmation.js
 * Prompts the admin for confirmation before proceeding with student deletion.
 * If confirmed, it creates and submits a hidden form to the delete handler.
 * This ensures the deletion is handled via a secure POST request.
 *
 * @param {number} studentId The ID of the student to be deleted.
 */
function confirmDelete(studentId) {
    // Prevent multiple toasts from appearing
    M.Toast.dismissAll();

    // Use Materialize Toast for a better-looking confirmation dialog
    var toastHTML = `<span>Are you sure you want to delete this record?</span><button class="btn-flat toast-action" id="confirm-delete-btn-${studentId}">Confirm</button>`;
    
    M.toast({
        html: toastHTML, 
        displayLength: 6000,
        classes: 'red darken-2'
    });

    const confirmButton = document.getElementById(`confirm-delete-btn-${studentId}`);
    
    if (confirmButton) {
        confirmButton.addEventListener('click', function() {
            // Create a temporary form to submit the ID via POST
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'delete_student.php'; // The PHP handler script

            var hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'id';
            hiddenInput.value = studentId;

            form.appendChild(hiddenInput);
            document.body.appendChild(form);
            form.submit();

            // Clean up the DOM by removing the form
            document.body.removeChild(form);

            // Dismiss the toast
            var toastInstance = M.Toast.getInstance(this.parentElement);
            if (toastInstance) {
                toastInstance.dismiss();
            }
        });
    }
}