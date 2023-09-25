$(document).ready(function () {
  var tableUser = $("#myTable").DataTable({
    buttons: [
      {
        extend: "print",
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7],
        },
      },
      {
        extend: "excel",
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7],
        },
      },
      {
        extend: "pdf",
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7],
        },
      },
    ],
  });

  var table = $("#file-datatable").DataTable({
    buttons: [
      {
        extend: "print",
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
        },
      },
      {
        extend: "excel",
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
        },
      },
      {
        extend: "pdf",
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
        },
      },
    ],
    language: {
      searchPlaceholder: "Search...",
      scrollX: "100%",
      sSearch: "",
    },
  });

  table
    .buttons()
    .container()
    .appendTo("#file-datatable_wrapper .col-md-6:eq(0)");

  tableUser.buttons().container().appendTo("#myTable_wrapper .col-md-6:eq(0)");

  $("#allTable").DataTable({
    bLengthChange: true,
    searching: true,
    responsive: true,
  });
});
