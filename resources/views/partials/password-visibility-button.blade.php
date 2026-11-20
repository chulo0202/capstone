<button type="button" class="btn btn-outline-secondary" data-password-label="{{ $passwordLabel ?? 'password' }}"
    aria-controls="{{ $inputId }}" aria-label="Show {{ $passwordLabel ?? 'password' }}"
    title="Show {{ $passwordLabel ?? 'password' }}"
    onclick="
        const password = document.getElementById(this.getAttribute('aria-controls'));
        const show = password.type === 'password';
        const icon = this.querySelector('i');
        const label = (show ? 'Hide ' : 'Show ') + this.dataset.passwordLabel;
        password.type = show ? 'text' : 'password';
        icon.classList.toggle('bi-eye', !show);
        icon.classList.toggle('bi-eye-slash', show);
        this.setAttribute('aria-label', label);
        this.title = label;
    ">
    <i class="bi bi-eye" aria-hidden="true"></i>
</button>
