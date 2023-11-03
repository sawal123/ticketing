<div class="modal fade" id="modalCash">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Jual Ticket Cash</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ url('dashboard/addCash') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">

                    <input type="hidden" name="user" value="{{ Auth::user()->uid }}">
                    <div class="row mb-4">
                        <label class="col-md-3 	d-none d-lg-block form-label">Event</label>
                        <div class="col-md-9">
                            <select id="select-event" class="form-select" name="event"
                                aria-label="Default select example">
                                <option selected>Pilih Event</option>
                                @foreach ($event as $key => $events)
                                    <option value="{{ $event[$key]['event'] }}" class="{{ $key + 1 }}">
                                        {{ $event[$key]['event'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4 d-flex" id="ticket-select-container" style="display: none;">
                        <label class="col-md-3 	d-none d-lg-block form-label">Ticket</label>
                        <div class="col-md-9">
                            <select id="select-ticket" class="form-select" name="ticket"
                                aria-label="Default select example">
                                <option selected>Pilih Ticket</option>
                                <!-- Opsi tiket akan ditambahkan di sini melalui JavaScript -->
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-md-3 	d-none d-lg-block form-label">Qty</label>
                        <div class="col-md-9">
                            <select id="select-jumlah" class="form-select" name="qty"
                                aria-label="Default select example">
                                <option selected disabled>Isi Qty</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 	d-none d-lg-block form-label">Name</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="name" placeholder="Masukan Nama.."
                                autocomplete="off" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 	d-none d-lg-block form-label">Alamat</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="alamat" placeholder="Masukan Alamat.."
                                autocomplete="off" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 	d-none d-lg-block form-label">Tanggal Lahir</label>
                        <div class="col-md-9">
                            <input type="date" class="form-control" name="ttl" autocomplete="off" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div class="d-flex align-items-center">
                        <input type="hidden" value="{{ Auth::user()->uid }}" name="uid" readonly>
                        <input type="hidden" value="" id="total" name="total" readonly>
                        <h5>Total : </h5>
                        <h6 id="total-harga"></h6>
                    </div>
                    <div class="">
                        <button type="submit" class="btn btn-primary">Cash</button>
                        <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    </div>

                </div>
            </form>
        </div>
    </div>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const eventSelect = document.getElementById("select-event");
        const ticketSelectContainer = document.getElementById("ticket-select-container");
        const ticketSelect = document.getElementById("select-ticket");
        const jumlahTiket = document.getElementById("select-jumlah");
        const totalHarga = document.getElementById("total-harga");
        const totali = document.getElementById("total");


        const ticketOptions = {!! html_entity_decode(json_encode($ticketEvent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !!};
        const hargaTicket = {!! html_entity_decode(json_encode($hargaTicket, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !!};

        // Fungsi untuk menghitung total harga
        function hitungTotalHarga() {
            const selectedEventId = eventSelect.value;
            const selectedTicketName = ticketSelect.value;
            const selectedJumlah = parseInt(jumlahTiket.value);

            const selectedOption = eventSelect.options[eventSelect.selectedIndex];
            const selectEventClass = selectedOption.className;
            


            if (selectEventClass in ticketOptions) {
                const ticketOptionsForEvent = ticketOptions[selectEventClass];
                const hargaTicketForEvent = hargaTicket[selectEventClass]
                console.log(ticketOptionsForEvent)
                console.log(hargaTicketForEvent)
                // Cari kunci tiket yang sesuai dengan nama tiket yang dipilih
                let selectedTicketKey = null;
                for (const key in ticketOptionsForEvent) {
                    if (ticketOptionsForEvent[key] === selectedTicketName) {
                        selectedTicketKey = parseInt(key);
                        break;
                    }
                }
                // console.log(selectedTicketKey)
                if (selectedTicketKey !== null) {
                    const hargaPerTiket = parseFloat(hargaTicketForEvent[selectedTicketKey]);
                    const total = selectedJumlah * hargaPerTiket;

                    const formattedTotal = new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'IDR',

                    }).format(total);

                    if (formattedTotal === "IDRNaN") {
                        totalHarga.textContent = "IDR 0";
                    } else {
                        totalHarga.textContent = formattedTotal;
                        totali.value = total;
                    }
                }
            }
        }

        // Ketika event select berubah
        eventSelect.addEventListener("change", function() {
            const selectedEventId = eventSelect.value;

            const selectedOption = eventSelect.options[eventSelect.selectedIndex];
            const selectEventClass = selectedOption.className;
            

            // Hapus opsi tiket yang ada
            ticketSelect.innerHTML = "";
            // Tambahkan opsi tiket yang sesuai dengan event yang dipilih
            if (selectEventClass in ticketOptions) {
                const tickets = ticketOptions[selectEventClass];
                for (const ticket of tickets) {
                    const option = document.createElement("option");
                    option.value = ticket;
                    option.textContent = ticket;
                    ticketSelect.appendChild(option);
                }
                // Tampilkan elemen select tiket
                ticketSelectContainer.style.display = "block";

            } else {
                // Jika event tidak dipilih, sembunyikan elemen select tiket
                ticketSelectContainer.style.display = "none";

            }
        });

        ticketSelect.addEventListener("change", hitungTotalHarga);
        jumlahTiket.addEventListener("input", hitungTotalHarga);
    });
</script>
