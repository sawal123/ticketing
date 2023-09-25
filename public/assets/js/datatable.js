$(function(e) {
    'use strict';

    // DATATABLE 1
    $('#datatable6').DataTable({
        processing: true,
        dom: "Blfrtip",
        
        // responsive: true,

        buttons: [
            {
              extend: "print",
              exportOptions: {
                columns: [0, 1, 2, 3, 4, 5,6,7],
              },
            },
            {
              extend: "excel",
              exportOptions: {
                columns: [0, 1, 2, 3, 4, 5,6,7],
              },
            },
            {
              extend: "pdf",
              exportOptions: {
                columns: [0, 1, 2, 3, 4, 5,6,7],
              },
            },
          ],
          bLengthChange: false,
          searching: true,
          responsive: true,
          minimumResultsForSearch: Infinity,
        language: {
            searchPlaceholder: 'Search...',
            sSearch: '',
            lengthMenu: '_MENU_ items',
        }
    });

    // DATATABLE 2
    $('#datatable2').DataTable({
        bLengthChange: false,
        searching: false,
        responsive: true
    });
    
    // SELECT2
    $('.dataTables_length select').select2({
        minimumResultsForSearch: Infinity
    });
});