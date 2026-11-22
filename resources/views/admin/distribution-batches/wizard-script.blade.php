<script>
(() => {
    const form = document.getElementById('distribution-wizard');
    const steps = [...form.querySelectorAll('[data-step]')];
    const links = [...form.querySelectorAll('[data-step-link]')];
    const { program_id: program, program_benefit_id: benefit, start_time: start, end_time: end } = form.elements;
    const next = form.querySelector('[data-next]');
    const back = form.querySelector('[data-back]');
    const create = form.querySelector('[data-create]');
    const selectAll = form.querySelector('[data-select-all]');
    const list = form.querySelector('[data-beneficiaries]');
    const template = form.querySelector('[data-beneficiary-template]');
    const loadError = form.querySelector('[data-load-error]');
    const selectionError = form.querySelector('[data-selection-error]');
    const scheduleFields = [...steps[2].querySelectorAll('input, textarea')];
    const catalog = {{ Js::from($programs->mapWithKeys(fn ($program) => [
        (string) $program->id => $program->activeBenefits->map->only(['id', 'name'])->values(),
    ])) }};
    const oldIds = new Set({{ Js::from(array_map('strval', (array) old('application_ids', []))) }});
    const hasBeneficiaryErrors = {{ Js::from(collect($errors->keys())->contains(fn ($key) => $key === 'application_ids' || str_starts_with($key, 'application_ids.'))) }};
    const hasScheduleErrors = {{ Js::from($errors->hasAny(['name', 'scheduled_date', 'start_time', 'end_time', 'location', 'instructions'])) }};
    const limit = 200;
    let current = 0;
    let loading = false;
    let submitting = false;
    let loadedBenefit = benefit.value;
    let rows = [];

    function selectedRows() {
        return rows.filter(row => row.checkbox.checked);
    }

    function selectionProblem(selected) {
        if (selected.length > limit) return 'Select up to 200 beneficiaries per distribution.';
        if (new Set(selected.map(row => row.farmerId)).size !== selected.length) {
            return 'Select only one approved application per farmer for this benefit.';
        }
        return '';
    }

    function updateSelection() {
        const selected = selectedRows();
        const problem = selectionProblem(selected);
        form.querySelector('[data-count]').textContent = `${selected.length} selected`;
        form.querySelector('[data-empty]').hidden = rows.length > 0;
        selectAll.disabled = rows.length === 0;
        selectAll.checked = rows.length > 0 && selected.length === rows.length;
        selectAll.indeterminate = selected.length > 0 && !selectAll.checked;
        selectionError.textContent = problem;
        selectionError.hidden = !problem;
    }

    function renderBeneficiaries(entries, checkedIds = new Set()) {
        rows = entries.map(entry => {
            const label = template.content.firstElementChild.cloneNode(true);
            const checkbox = label.querySelector('input');
            checkbox.value = entry.id;
            checkbox.checked = checkedIds.has(String(entry.id));
            label.querySelector('[data-farmer-name]').textContent = entry.name;
            label.querySelector('[data-farmer-reference]').textContent =
                `Application #${entry.id}${entry.barangay ? ` · ${entry.barangay}` : ''}`;
            return { ...entry, label, checkbox };
        });
        list.replaceChildren(...rows.map(row => row.label));
        form.querySelector('[data-selection-limit]').hidden = rows.length <= limit
            && new Set(rows.map(row => row.farmerId)).size === rows.length;
        updateSelection();
    }

    function validateSchedule() {
        for (const field of [form.elements.namedItem('name'), form.elements.location]) {
            field.setCustomValidity(field.value && !field.value.trim() ? 'Enter a value for this field.' : '');
        }
        end.setCustomValidity(start.value && end.value && start.value >= end.value
            ? 'End time must be later than start time.' : '');
        return scheduleFields.every(field => field.validity.valid);
    }

    function stepIsValid(step) {
        if (step === 0) return Boolean(program.value && benefit.value);
        if (step === 1) {
            const selected = selectedRows();
            return selected.length > 0 && !selectionProblem(selected);
        }
        return validateSchedule();
    }

    function updateButtons() {
        next.disabled = loading || (current < 3 && !stepIsValid(current));
        links.forEach((link, index) => {
            link.disabled = loading || index >= current;
        });
    }

    function renderReview() {
        form.querySelectorAll('[data-review]').forEach(item => {
            const field = form.elements.namedItem(item.dataset.review);
            const value = field instanceof HTMLSelectElement ? field.selectedOptions[0]?.text : field.value.trim();
            item.textContent = value || '—';
        });
        const selected = selectedRows();
        form.querySelector('[data-review-count]').textContent = `Beneficiaries (${selected.length})`;
        form.querySelector('[data-review-beneficiaries]').replaceChildren(...selected.map(row => {
            const item = document.createElement('li');
            item.textContent = `${row.name} · Application #${row.id}`;
            return item;
        }));
    }

    function showStep(index, focus = true) {
        current = index;
        steps.forEach((step, position) => { step.hidden = index !== position; });
        links.forEach((link, position) => {
            const complete = position < index;
            link.classList.toggle('is-complete', complete);
            link.querySelector('.step-number').textContent = complete ? '✓' : position + 1;
            if (position === index) link.setAttribute('aria-current', 'step');
            else link.removeAttribute('aria-current');
        });
        back.hidden = index === 0;
        next.hidden = index === 3;
        create.hidden = index !== 3;
        if (index === 3) renderReview();
        updateButtons();
        if (focus) steps[index].querySelector('h2').focus();
    }

    function invalidateBeneficiaries() {
        loadedBenefit = '';
        renderBeneficiaries([]);
        loadError.hidden = true;
        updateButtons();
    }

    program.addEventListener('change', () => {
        const options = catalog[program.value] ?? [];
        benefit.replaceChildren(new Option(program.value ? 'Select a benefit' : 'Select a program first', ''),
            ...options.map(option => new Option(option.name, option.id)));
        benefit.disabled = !program.value;
        invalidateBeneficiaries();
    });
    benefit.addEventListener('change', invalidateBeneficiaries);
    list.addEventListener('change', () => { updateSelection(); updateButtons(); });
    selectAll.addEventListener('change', () => {
        const farmers = new Set();
        rows.forEach(row => {
            row.checkbox.checked = selectAll.checked && farmers.size < limit && !farmers.has(row.farmerId);
            if (row.checkbox.checked) farmers.add(row.farmerId);
        });
        updateSelection();
        updateButtons();
    });
    steps[2].addEventListener('input', () => {
        updateButtons();
        const message = form.querySelector('[data-time-error]');
        message.textContent = end.validity.customError ? end.validationMessage : '';
        message.hidden = !message.textContent;
    });

    next.addEventListener('click', async () => {
        if (!stepIsValid(current) || loading || current === 3) return;
        if (current === 0 && loadedBenefit !== benefit.value) {
            loading = true;
            form.setAttribute('aria-busy', 'true');
            program.disabled = benefit.disabled = true;
            loadError.hidden = true;
            updateButtons();
            const url = new URL(form.dataset.optionsUrl);
            url.searchParams.set('program', program.value);
            url.searchParams.set('benefit', benefit.value);
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Beneficiaries unavailable');
                renderBeneficiaries(await response.json());
                loadedBenefit = benefit.value;
                history.replaceState(null, '', url);
            } catch {
                loadError.textContent = 'Could not load beneficiaries. Try again.';
                loadError.hidden = false;
                return;
            } finally {
                loading = false;
                form.removeAttribute('aria-busy');
                program.disabled = benefit.disabled = false;
                updateButtons();
            }
        }
        showStep(current + 1);
    });

    back.addEventListener('click', () => { if (current > 0) showStep(current - 1); });
    links.forEach((link, index) => link.addEventListener('click', () => {
        if (index < current) showStep(index);
    }));
    form.addEventListener('submit', event => {
        if (current !== 3 || submitting) {
            event.preventDefault();
            return;
        }
        const invalidStep = [0, 1, 2].find(step => !stepIsValid(step));
        if (invalidStep !== undefined) {
            event.preventDefault();
            showStep(invalidStep);
            return;
        }
        submitting = true;
        create.disabled = true;
    });

    renderBeneficiaries({{ Js::from($beneficiaries) }}, oldIds);
    form.hidden = false;
    const initialStep = loadedBenefit && hasBeneficiaryErrors ? 1
        : loadedBenefit && hasScheduleErrors && stepIsValid(1) ? 2 : 0;
    showStep(initialStep, false);
})();
</script>
