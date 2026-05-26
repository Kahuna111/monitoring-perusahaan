
</main><!-- /page-content -->
</div><!-- /main-content -->
</div><!-- /app-wrapper -->

<!-- ==============================
     SCRIPTS
     ============================== -->
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.to/flatpickr/dist/l10n/id.js"></script>

<!-- Main JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

<!-- Init DataTables -->
<script>
$(document).ready(function() {
    // Init DataTables
    if ($('.data-table').length) {
        $('.data-table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
            },
            pageLength: 10,
            responsive: true,
            columnDefs: [{ orderable: false, targets: -1 }]
        });
    }

    // Init Flatpickr
    flatpickr('.datepicker', {
        locale: 'id',
        dateFormat: 'Y-m-d',
        allowInput: true
    });
    // Monthpicker is not used, so we remove the code that throws ReferenceError
    // because monthSelectPlugin is not loaded.
});
</script>

</body>
</html>
