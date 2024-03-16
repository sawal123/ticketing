<script src="{{asset('penyewa/js/style.js')}}"></script>


@if (request()->is('dashboard'))
<script>
    var ctx = document.getElementById("chart");
    var myChart = new Chart(ctx, {
        type: "line",
        data: {
            labels: [
                @foreach ($gr as $key => $grs)
                    "{{ $grs }}",
                @endforeach
            ],
            datasets: [{
                    label: "Ticket",
                    borderColor: "#6c5ffc",
                    borderWidth: "3",
                    lineTension: 0.3,
                    backgroundColor: "rgba(108, 95, 252, .1)",
                    fill: true,
                    data: [
                        @foreach ($qty as $key => $qtys)
                            {{ $qtys }},
                        @endforeach
                    ],
                },
                {
                    label: "Money",
                    borderColor: "rgba(5, 195, 251 ,0.9)",
                    borderWidth: "3",
                    lineTension: 0.3,
                    backgroundColor: "rgba(5, 195, 251, 0.7)",
                    pointHighlightStroke: "rgba(5, 195, 251 ,1)",
                    fill: true,
                    data: [
                        @foreach ($amount as $key => $amounts)
                            {{ $amounts }},
                        @endforeach
                    ],
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
@endif
