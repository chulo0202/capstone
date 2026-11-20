<div class="mt-2">
    <small class="text-muted">Use at least 8 characters, with uppercase and lowercase letters, a number, and a symbol.</small>
    <button type="button" class="btn btn-link btn-sm"
        onclick="const fields = this.form.querySelectorAll('[name=password], [name=password_confirmation]'); const show = fields[0].type === 'password'; fields.forEach(field => field.type = show ? 'text' : 'password'); this.textContent = show ? 'Hide password' : 'Show password'; this.setAttribute('aria-pressed', show);"
        aria-pressed="false">Show password</button>
</div>
