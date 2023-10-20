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
                            <select id="select-event" class="form-select" aria-label="Default select example">
                                <option selected>Pilih Event</option>
                                @foreach ($event as $key => $events)
                                    <option value="{{ $event[$key]['event'] }}">{{ $event[$key]['event'] }}</option>
                                @endforeach

                            </select>
                        </div>
                    </div>

                    <div class="row mb-4 d-flex" id="ticket-select-container" style="display: none;">
                        <label class="col-md-3 	d-none d-lg-block form-label">Ticket</label>
                        <div class="col-md-9">
                            <select id="select-ticket" class="form-select" aria-label="Default select example">
                                <option selected>Pilih Ticket</option>
                                <!-- Opsi tiket akan ditambahkan di sini melalui JavaScript -->
                            </select>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 	d-none d-lg-block form-label">Qty</label>
                        <div class="col-md-9">
                            <select id="select-event" class="form-select" aria-label="Default select example">
                                <option selected>Isi Qty</option>
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
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Cash</button>
                    <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const eventSelect = document.getElementById("select-event");
    const ticketSelectContainer = document.getElementById("ticket-select-container");
    const ticketSelect = document.getElementById("select-ticket");

    // Daftar tiket untuk setiap event
    const ticketOptions = {
      

        2: ["Ticket X", "Ticket Y", "Ticket Z"],
    };

    // Ketika event select berubah
    eventSelect.addEventListener("change", function() {
        const selectedEventId = eventSelect.value;

        // Hapus opsi tiket yang ada
        ticketSelect.innerHTML = "";

        // Tambahkan opsi tiket yang sesuai dengan event yang dipilih
        if (selectedEventId in ticketOptions) {
            const tickets = ticketOptions[selectedEventId];
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
</script>
