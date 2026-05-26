
</main><!-- /page-content -->
</div><!-- /main-content -->
</div><!-- /app-wrapper -->

<!-- ==============================
     SCRIPTS (deferred for performance)
     ============================== -->
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js" defer></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>

<!-- Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>
<script src="https://npmcdn.to/flatpickr/dist/l10n/id.js" defer></script>

<!-- Main JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>

<!-- Init DataTables & Flatpickr (wait for deferred scripts to load) -->
<script>
window.addEventListener('load', function() {
    // Init DataTables
    if (typeof $ !== 'undefined' && $.fn.DataTable && $('.data-table').length) {
        // Suppress DataTables error alerts (show in console instead)
        $.fn.dataTable.ext.errMode = 'none';

        $('.data-table').each(function() {
            try {
                $(this).DataTable({
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
                        emptyTable: 'Tidak ada data yang tersedia',
                        zeroRecords: 'Tidak ada data yang cocok',
                        info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entri',
                        infoEmpty: 'Menampilkan 0 sampai 0 dari 0 entri',
                        infoFiltered: '(disaring dari _MAX_ total entri)',
                        lengthMenu: 'Tampilkan _MENU_ entri',
                        search: 'Cari:',
                        paginate: { first: 'Pertama', last: 'Terakhir', next: 'Selanjutnya', previous: 'Sebelumnya' }
                    },
                    pageLength: 10,
                    responsive: true,
                    columnDefs: [{ orderable: false, targets: -1 }]
                });
            } catch(e) {
                console.warn('DataTables init skipped for', this.id, e.message);
            }
        });
    }

    // Init Flatpickr
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.datepicker', {
            locale: 'id',
            dateFormat: 'Y-m-d',
            allowInput: true
        });
    }
});
</script>

</body>
</html>
