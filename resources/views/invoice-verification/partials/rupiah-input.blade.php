@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    const formatter = new Intl.NumberFormat('id-ID');

    function cleanRupiah(value) {
        return String(value || '').replace(/[^0-9]/g, '');
    }

    function formatRupiah(value) {
        const clean = cleanRupiah(value);

        return clean ? 'Rp ' + formatter.format(Number(clean)) : '';
    }

    function formatElement(input) {
        if (!input) return;
        input.value = formatRupiah(input.value);
    }

    function initializeInput(input) {
        if (!input || input.dataset.rupiahReady === '1') return;
        input.dataset.rupiahReady = '1';
        input.type = 'text';
        input.inputMode = 'numeric';
        input.autocomplete = 'off';

        if (input.value) {
            formatElement(input);
        }

        input.addEventListener('input', function () {
            formatElement(input);
        });
    }

    window.InvoiceRupiahInput = {
        cleanValue: cleanRupiah,
        formatValue: formatRupiah,
        formatElement: formatElement,
        initializeInput: initializeInput,
    };

    document.querySelectorAll('[data-rupiah-input]').forEach(initializeInput);

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('[data-rupiah-input]').forEach(function (input) {
                input.value = cleanRupiah(input.value);
            });
        });
    });
});
</script>
@endonce
