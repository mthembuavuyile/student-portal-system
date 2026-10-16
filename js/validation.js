/**
 * validation.js
 * Handles client-side form validation for the Student Management System.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Select all forms that require validation
    const formsToValidate = document.querySelectorAll('form[action="register_student.php"], form[action^="update_student.php"]');

    formsToValidate.forEach(form => {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            let firstInvalidField = null;

            // 1. Check all 'required' input fields
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    showError(field, `${field.labels[0].innerText} is required.`);
                    if (!firstInvalidField) firstInvalidField = field;
                } else {
                    clearError(field);
                }
            });

            // 2. Check for valid email format
            const emailField = form.querySelector('input[type="email"]');
            if (emailField && emailField.value.trim()) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(emailField.value)) {
                    isValid = false;
                    showError(emailField, 'Please enter a valid email address.');
                    if (!firstInvalidField) firstInvalidField = emailField;
                } else {
                    clearError(emailField);
                }
            }
            
            // If the form is not valid, prevent submission and focus on the first error
            if (!isValid) {
                event.preventDefault();
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
                M.toast({html: 'Please correct the errors before submitting.', classes: 'red'});
            }
        });
    });

    /**
     * Displays an error message for a specific form field.
     * @param {HTMLElement} field The input field with the error.
     * @param {string} message The error message to display.
     */
    function showError(field, message) {
        const parent = field.parentElement;
        // Remove existing error to avoid duplicates
        clearError(field);
        const errorSpan = document.createElement('span');
        errorSpan.className = 'helper-text red-text';
        errorSpan.innerText = message;
        parent.appendChild(errorSpan);
        field.classList.add('invalid');
    }

    /**
     * Clears the error message for a specific form field.
     * @param {HTMLElement} field The input field to clear the error from.
     */
    function clearError(field) {
        const parent = field.parentElement;
        const errorSpan = parent.querySelector('span.helper-text.red-text');
        if (errorSpan) {
            parent.removeChild(errorSpan);
        }
        field.classList.remove('invalid');
    }
});