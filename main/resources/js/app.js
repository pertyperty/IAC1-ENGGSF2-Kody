const accountType = document.querySelector('#account-type');
const instructorFields = document.querySelector('#instructor-fields');

if (accountType && instructorFields) {
    const updateInstructorFields = () => {
        const applying = accountType.value === 'instructor';
        instructorFields.hidden = !applying;
        instructorFields.querySelectorAll('input').forEach((input) => {
            input.disabled = !applying;
            input.required = applying;
        });
    };

    accountType.addEventListener('change', updateInstructorFields);
    updateInstructorFields();
}

const verificationForm = document.querySelector('#verify-link-form');
const verificationInput = document.querySelector('#verification-token');
if (verificationForm && verificationInput && window.location.hash) {
    const token = new URLSearchParams(window.location.hash.slice(1)).get('token');
    // Fragments are never sent to web servers; remove the secret from browser history.
    window.history.replaceState(null, '', window.location.pathname);
    if (token && /^[a-f0-9]{64}$/.test(token)) {
        verificationInput.value = token;
        verificationForm.submit();
    }
}
