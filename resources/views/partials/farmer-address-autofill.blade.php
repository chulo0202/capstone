<script>
    document.addEventListener('DOMContentLoaded', () => {
        const province = document.getElementById('farmer-province');
        const municipality = document.getElementById('farmer-municipality');
        const form = province.form;
        const address = form.elements.namedItem('address');
        const barangay = form.elements.namedItem('barangay');
        const fields = [barangay, municipality, province];
        const selectedLocality = () => fields.map(field => field.value.trim()).filter(Boolean);
        const normalize = value => value.toLowerCase().trim();
        const addressParts = () => address.value.split(',').map(part => part.trim()).filter(Boolean);

        let previous = selectedLocality();

        function updateAddress() {
            const parts = addressParts();
            const current = selectedLocality();

            // Remove the previous auto-filled locality and keep any street or purok.
            for (const locality of [current, previous, previous.slice(-2)]) {
                if (!locality.length) continue;

                const index = parts.findIndex((_, start) => locality.every(
                    (value, offset) => normalize(parts[start + offset] || '') === normalize(value)
                ));

                if (index >= 0) {
                    parts.splice(index, locality.length);
                    break;
                }
            }

            address.value = [...parts, ...current].join(', ');
            previous = current;
        }

        fields.forEach(field => field.addEventListener('change', updateAddress));
        form.addEventListener('submit', updateAddress);
        updateAddress();
    });
</script>
