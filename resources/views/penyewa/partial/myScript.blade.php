<script>
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const activeTabId = $(e.target).attr('href'); // Ambil ID tab yang aktif
        localStorage.setItem('activeTab', activeTabId);
    });
    $(document).ready(function() {
        const activeTabId = localStorage.getItem('activeTab');

        if (activeTabId) {
            // Aktifkan tab yang disimpan dalam local storage
            $('a[href="' + activeTabId + '"]').tab('show');
        } else {
            // Aktifkan tab default (misalnya tab pertama)
            $('a[data-bs-toggle="tab"]').first().tab('show');
        }
    });


    $(document).on("show.bs.modal", "#updateTalent", function(e) {
        var tombol = $(e.relatedTarget);
        var uid = tombol.data('uid');
        var talent = tombol.data('talent');
        var modal = $(this);
        modal.find("#uidTalent").val(uid);
        modal.find("#namaTalent").val(talent);
    })
    $(document).on("show.bs.modal", "#updateHarga", function(e) {
        var tombol = $(e.relatedTarget);
        var id = tombol.data('id');
        var harga = tombol.data('harga');
        var kategori = tombol.data('kategori');
        var qty = tombol.data('qty');
        var modal = $(this);
        modal.find("#idHarga").val(id);
        modal.find("#updateHarga").val(harga);
        modal.find("#qtyHarga").val(qty);
        modal.find("#kategoriHarga").val(kategori);
    })
    $(document).on("show.bs.modal", "#updateSlide", function(e) {
        var tombol = $(e.relatedTarget);
        var uid = tombol.data('uid');
        var title = tombol.data('title');
        var sort = tombol.data('sort');
        var url = tombol.data('url');
        var modal = $(this);
        modal.find('#uidSlide').val(uid)
        modal.find('#titleSlide').val(title)
        // modal.find('#sortSlide').val(sort)
        modal.find('#urlSlide').val(url)
        $('#sortSelect').val(sort);
    })
    $(document).on("show.bs.modal", "#updateTerm", function(e) {
        var tombol = $(e.relatedTarget);
        var uid = tombol.data('uid');
        var title = tombol.data('title');
        var term = tombol.data('term');
        var modal = $(this);
        modal.find('#termUid').val(uid)
        modal.find('#termTitle').val(title)
        modal.find('#termTerm').text(term)
    })


    $(document).ready(function() {
        $(document).on('click', '.delete', function() {
            var getLink = $(this).attr('href');
            Swal.fire({
                title: "Yakin hapus data?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya',
                cancelButtonColor: '#3085d6',
                cancelButtonText: "Batal"
            }).then(result => {
                //jika klik ya maka arahkan ke proses.php
                if (result.isConfirmed) {
                    window.location.href = getLink;
                }
            });
            return false;
        });
    });

// ==================================================================================
// ==================================================================================
// ==================================================================================

    var ctx = document.getElementById("chartArea");
    var myChart = new Chart(ctx, {
        type: "line",
        data: {
            labels: ["1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12"],
            datasets: [{
                    label: "Data1",
                    borderColor: "#6c5ffc",
                    borderWidth: "3",
                    lineTension: 0.3,
                    backgroundColor: "rgba(108, 95, 252, .1)",
                    fill: true,
                    data: [22, 44, 67, 43, 90, 45, 12],
                },
                {
                    label: "Data2",
                    borderColor: "rgba(5, 195, 251 ,0.9)",
                    borderWidth: "3",
                    lineTension: 0.3,
                    backgroundColor: "rgba(5, 195, 251, 0.7)",
                    pointHighlightStroke: "rgba(5, 195, 251 ,1)",
                    fill: true,
                    data: [16, 32, 18, 26, 42, 33, 44],
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            tooltips: {
                mode: "index",
                intersect: false,
            },
            hover: {
                mode: "nearest",
                intersect: true,
            },
            scales: {
                x: {
                    ticks: {
                        color: "#9ba6b5",
                    },
                    grid: {
                        color: "rgba(119, 119, 142, 0.2)",
                    },
                },
                yAxes: {
                    ticks: {
                        beginAtZero: true,
                        color: "#9ba6b5",
                    },
                    grid: {
                        color: "rgba(119, 119, 142, 0.2)",
                    },
                },
            },
            legend: {
                labels: {
                    color: "#9ba6b5",
                },
            },
        },
    });
</script>
