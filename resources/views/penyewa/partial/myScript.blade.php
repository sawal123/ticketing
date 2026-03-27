@if (request()->is('dashboard'))
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var ctx = document.getElementById("chart").getContext('2d');
            var myChart = new Chart(ctx, {
                type: "line",
                data: {
                    // Kita oper data JSON langsung ke Javascript! (Sangat aman & rapi)
                    labels: {!! json_encode($chartDates) !!},
                    datasets: [{
                            label: "Omset Online (Rp)",
                            borderColor: "#6c5ffc",
                            borderWidth: 3,
                            backgroundColor: "rgba(108, 95, 252, 0.1)",
                            fill: true,
                            lineTension: 0.3,
                            data: {!! json_encode($amountOnline) !!},
                        },
                        {
                            label: "Omset Cash (Rp)",
                            borderColor: "#05c3fb",
                            borderWidth: 3,
                            backgroundColor: "rgba(5, 195, 251, 0.1)",
                            fill: true,
                            lineTension: 0.3,
                            data: {!! json_encode($amountCash) !!},
                        },
                        {
                            label: "Tiket Online (Qty)",
                            borderColor: "#19b159",
                            borderWidth: 3,
                            backgroundColor: "transparent",
                            borderDash: [5, 5], // Garis putus-putus supaya beda dengan uang
                            fill: false,
                            lineTension: 0.3,
                            data: {!! json_encode($qtyOnline) !!},
                        },
                        {
                            label: "Tiket Cash (Qty)",
                            borderColor: "#f5b849",
                            borderWidth: 3,
                            backgroundColor: "transparent",
                            borderDash: [5, 5],
                            fill: false,
                            lineTension: 0.3,
                            data: {!! json_encode($qtyCash) !!},
                        }
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    tooltips: {
                        mode: "index",
                        intersect: false,
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var label = data.datasets[tooltipItem.datasetIndex].label || '';
                                var value = tooltipItem.yLabel;
                                // Format rupiah khusus untuk omset
                                if (label.includes("Rp")) {
                                    return label + ': Rp ' + value.toString().replace(
                                        /\B(?=(\d{3})+(?!\d))/g, ".");
                                }
                                return label + ': ' + value;
                            }
                        }
                    },
                    hover: {
                        mode: "nearest",
                        intersect: true,
                    },
                    scales: {
                        xAxes: [{
                            ticks: {
                                fontColor: "#9ba6b5"
                            },
                            gridLines: {
                                color: "rgba(119, 119, 142, 0.2)"
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                fontColor: "#9ba6b5",
                                callback: function(value) {
                                    if (value >= 1000000) return 'Rp ' + (value / 1000000) +
                                    'M';
                                    if (value >= 1000) return 'Rp ' + (value / 1000) + 'K';
                                    return value;
                                }
                            },
                            gridLines: {
                                color: "rgba(119, 119, 142, 0.2)"
                            }
                        }],
                    },
                    legend: {
                        display: false // Kita matikan legend bawaan karena sudah buat HTML sendiri di atas
                    },
                },
            });
        });
    </script>
@endif
