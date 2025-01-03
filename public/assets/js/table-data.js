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

    var file = $("#file-datatable").DataTable({
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
        language: {
            searchPlaceholder: "Search...",
            scrollX: "100%",
            sSearch: "",
        },
    });

    var penarikan = $("#penarikan").DataTable({
        buttons: [
            {
                extend: "print",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6],
                },
            },
            {
                extend: "excel",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6],
                },
            },
            {
                extend: "pdf",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6],
                },
            },
        ],
        language: {
            searchPlaceholder: "Search...",
            scrollX: "100%",
            sSearch: "",
        },
    });

    var tableMoney = $("#tableMoney").DataTable({
        buttons: [
            {
                extend: "print",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6],
                },
            },
            {
                extend: "excel",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6],
                },
            },
            {
                extend: "pdf",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6],
                },
            },
        ],
        language: {
            searchPlaceholder: "Search...",
            scrollX: "100%",
            sSearch: "",
        },
    });

    var tableVoucher = $("#tableVoucher").DataTable({
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

    var table = $("#tablePenyewa").DataTable({
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
    // var table = $("#tableTransPenyewa").DataTable({
    //     buttons: [
    //         {
    //             extend: "print",
    //             exportOptions: {
    //                 columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
    //             },
    //         },
    //         {
    //             extend: "excel",
    //             exportOptions: {
    //                 columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
    //             },
    //         },
    //         {
    //             extend: "pdf",
    //             exportOptions: {
    //                 columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
    //             },
    //         },
    //     ],
    // });
    // tableTransPenyewa
    //     .buttons()
    //     .container()
    //     .appendTo("#tableTransPenyewa_wrapper .col-md-6:eq(0)");

    var table = $("#tableAdminCash").DataTable({
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

    var partner = $("#tablePartner").DataTable({
        buttons: [
            {
                extend: "print",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4],
                },
            },
            {
                extend: "excel",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4],
                },
            },
            {
                extend: "pdf",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4],
                },
            },
        ],
        language: {
            searchPlaceholder: "Search...",
            scrollX: "100%",
            sSearch: "",
        },
    });
    var myContact = $("#myContact").DataTable({
        buttons: [
            {
                extend: "print",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4],
                },
            },
            {
                extend: "excel",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4],
                },
            },
            // {
            //     extend: "pdf",
            //     exportOptions: {
            //         columns: [0, 1, 2, 3, 4],
            //     },
            // },
        ],
        language: {
            searchPlaceholder: "Search...",
            scrollX: "100%",
            sSearch: "",
        },
    });

    

    myContact
        .buttons()
        .container()
        .appendTo("#myContact_wrapper .col-md-6:eq(0)");
    file.buttons()
        .container()
        .appendTo("#file-datatable_wrapper .col-md-6:eq(0)");
    penarikan
        .buttons()
        .container()
        .appendTo("#penarikan_wrapper .col-md-6:eq(0)");
    table
        .buttons()
        .container()
        .appendTo("#tablePenyewa_wrapper .col-md-6:eq(0)");

    tableMoney
        .buttons()
        .container()
        .appendTo("#tableMoney_wrapper .col-md-6:eq(0)");
    tableVoucher
        .buttons()
        .container()
        .appendTo("#tableVoucher_wrapper .col-md-6:eq(0)");
    tableUser
        .buttons()
        .container()
        .appendTo("#myTable_wrapper .col-md-6:eq(0)");
    partner
        .buttons()
        .container()
        .appendTo("#tablePartner_wrapper .col-md-6:eq(0)");

    $("#allTable").DataTable({
        bLengthChange: true,
        searching: true,
        responsive: true,
    });
});


$(document).ready(function() {
    var table = $("#tableTransPenyewa").DataTable({
        buttons: [{
                extend: "print",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                },
            },
            {
                extend: "excel",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                },
            },
            {
                extend: "pdf",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                },
            },
        ],
    });
    table
        .buttons()
        .container()
        .appendTo("#tableTransPenyewa_wrapper .col-md-6:eq(0)");
});