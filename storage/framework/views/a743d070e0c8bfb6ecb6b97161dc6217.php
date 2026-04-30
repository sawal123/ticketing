<div class="modal fade" id="modalCash">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content modal-content-demo" style="border-radius: 16px; border: none;">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark" style="font-size: 18px;">Jual Tiket Cash</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="opacity: 0.5;"></button>
            </div>
            <form action="<?php echo e(url('dashboard/addCash')); ?>" method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body px-4 pt-3 pb-2">
                    <input type="hidden" name="user" value="<?php echo e(Auth::user()->uid); ?>">

                    <style>
                        .form-custom {
                            background-color: #F8F9FA;
                            border: none;
                            border-radius: 8px;
                            padding: 10px 15px;
                            color: #495057;
                            font-size: 14px;
                            box-shadow: none !important;
                            height: auto;
                        }
                        .form-custom:focus {
                            background-color: #F1F3F5;
                        }
                        select.form-custom {
                            appearance: none;
                            -webkit-appearance: none;
                            -moz-appearance: none;
                            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236c757d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
                            background-repeat: no-repeat;
                            background-position: right 15px center;
                            background-size: 16px 12px;
                        }
                    </style>

                    <div class="mb-3">
                        <select class="form-select form-custom" name="partner" aria-label="Partner">
                            <option selected>Pilih Partner (optional)</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $partner; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $partners): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($partners->uid); ?>" class="<?php echo e($key + 1); ?>">
                                    <?php echo e($partners->name); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <select id="select-event" class="form-select form-custom fw-bold" style="color: #6c757d" name="event" aria-label="Event">
                            <option selected disabled>PILIH EVENT</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $event; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($e['event']); ?>" class="<?php echo e($key + 1); ?>" data-fee="<?php echo e($e['eventFee'] ?? 0); ?>">
                                    <?php echo e(strtoupper($e['event'])); ?>

                                </option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="ticket-select-container" style="display: none;">
                        <select id="select-ticket" class="form-select form-custom" name="ticket" aria-label="Ticket">
                            <option selected>Pilih tiket</option>
                            <!-- Opsi tiket akan ditambahkan di sini -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <select id="select-jumlah" class="form-select form-custom" name="qty" aria-label="Qty">
                            <option selected disabled>Qty tiket</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <input type="text" class="form-control form-custom" name="name" placeholder="Nama lengkap" autocomplete="off" required>
                    </div>

                    <div class="mb-3">
                        <input type="email" class="form-control form-custom" name="email" placeholder="Email" autocomplete="off" required>
                    </div>

                    <div class="mb-3 d-none">
                        <!-- Disembunyikan karena di referensi screenshot tidak ada field whatsapp, tapi jika wajib (required di form), kita sediakan text placeholder -->
                        <input type="number" class="form-control form-custom" name="nomor" placeholder="WhatsApp (Optional)" autocomplete="off" value="0800000000">
                    </div>

                    <div class="mb-3 d-none">
                        <!-- Disembunyikan karena di screenshot tidak telihat alamat, jika form butuh alamat biarkan terisi default -->
                        <input type="text" class="form-control form-custom" name="alamat" placeholder="Alamat" autocomplete="off" value="-">
                    </div>

                    <div class="mb-3 position-relative">
                        <input type="date" class="form-control form-custom" name="ttl" autocomplete="off" required>
                    </div>

                    <div class="mb-4">
                        <select id="jenis" class="form-select form-custom" name="gender" aria-label="Gender">
                            <option selected disabled>Jenis kelamin</option>
                            <option value="wanita">Wanita</option>
                            <option value="pria">Pria</option>
                        </select>
                    </div>

                    <!-- Checkboxes -->
                    <div class="d-flex justify-content-between mb-4 px-1">
                        <div class="form-check d-flex align-items-center">
                            <input class="form-check-input me-2 mt-0" type="checkbox" value="1" id="defaultCheck1" required name="check" style="width: 18px; height: 18px; border-radius: 4px; border: 1px solid #adb5bd;">
                            <label class="form-check-label text-muted" for="defaultCheck1" style="font-size: 13px;">
                                Sudah Bayar Cash ?
                            </label>
                        </div>
                        <div class="form-check d-flex align-items-center">
                            <input class="form-check-input me-2 mt-0" type="checkbox" value="1" id="defaultCheck2" name="konfirmasi" style="width: 18px; height: 18px; border-radius: 4px; border: 1px solid #adb5bd;">
                            <label class="form-check-label text-muted" for="defaultCheck2" style="font-size: 13px;">
                                Langsung Masuk?
                            </label>
                        </div>
                    </div>

                    <!-- Total Details -->
                    <div class="px-1 mb-2">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted" style="font-size: 13px;">Sub total</span>
                            <strong id="display-subtotal" class="text-dark" style="font-size: 14px;">Rp 0</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted" id="label-pajak" style="font-size: 13px;">Pajak (0%)</span>
                            <strong id="display-pajak" class="text-dark" style="font-size: 14px;">Rp 0</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark" style="font-size: 16px;">Total Akhir</span>
                            <strong id="display-total" style="font-size: 18px; color: #5A67D8;">Rp 0</strong>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                    <input type="hidden" value="<?php echo e(Auth::user()->uid); ?>" name="uid" readonly>
                    <input type="hidden" value="0" id="total" name="total" readonly>
                    
                    <button type="submit" id="btn-submit" class="btn btn-primary w-100 py-2 d-flex justify-content-center align-items-center gap-2" style="background-color: #5A67D8; border: none; border-radius: 8px; font-weight: 600; font-size: 15px;">
                        <span id="btn-text">Check Out</span>
                        <div id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </button>
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

        // Elemen Rincian Harga
        const displaySubtotal = document.getElementById("display-subtotal");
        const labelPajak = document.getElementById("label-pajak");
        const displayPajak = document.getElementById("display-pajak");
        const displayTotal = document.getElementById("display-total");
        const totali = document.getElementById("total");

        const ticketOptions = <?php echo json_encode($ticketEvent); ?>;
        const hargaTicket = <?php echo json_encode($hargaTicket); ?>;

        // Fungsi Format Uang
        const formatRupiah = (angka) => {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(angka);
        };

        function hitungTotalHarga() {
            const selectedOption = eventSelect.options[eventSelect.selectedIndex];
            if (!selectedOption || selectedOption.disabled) return;

            const eventIndex = selectedOption.className;
            const ticketName = ticketSelect.value;
            const qty = parseInt(jumlahTiket.value) || 0;

            const persenPajak = parseFloat(selectedOption.getAttribute('data-fee')) || 0;

            if (ticketOptions[eventIndex]) {
                const ticketsForEvent = ticketOptions[eventIndex];
                let ticketIndex = null;

                for (const key in ticketsForEvent) {
                    if (ticketsForEvent[key] === ticketName) {
                        ticketIndex = key;
                        break;
                    }
                }

                if (ticketIndex !== null && qty > 0) {
                    const price = parseFloat(hargaTicket[eventIndex][ticketIndex]);
                    const subtotal = price * qty;
                    const nilaiPajak = (persenPajak / 100) * subtotal;
                    const totalAkhir = subtotal + nilaiPajak;

                    // Update UI Rincian
                    displaySubtotal.textContent = formatRupiah(subtotal);
                    labelPajak.textContent = `Pajak (${persenPajak}%):`;
                    displayPajak.textContent = formatRupiah(nilaiPajak);
                    displayTotal.textContent = formatRupiah(totalAkhir);

                    // Update input hidden
                    totali.value = totalAkhir;
                }
            }
        }

        eventSelect.addEventListener("change", function() {
            const eventIndex = this.options[this.selectedIndex].className;
            ticketSelect.innerHTML = "<option selected disabled>Pilih Ticket</option>";

            if (ticketOptions[eventIndex]) {
                for (const ticket of ticketOptions[eventIndex]) {
                    const option = document.createElement("option");
                    option.value = ticket;
                    option.textContent = ticket;
                    ticketSelect.appendChild(option);
                }
                ticketSelectContainer.style.display = "flex";
            } else {
                ticketSelectContainer.style.display = "none";
            }
            hitungTotalHarga();
        });

        ticketSelect.addEventListener("change", hitungTotalHarga);
        jumlahTiket.addEventListener("input", hitungTotalHarga);
        jumlahTiket.addEventListener("change", hitungTotalHarga);

        // FITUR ANTI DOUBLE SUBMIT (LOADING)
        const formCash = document.querySelector("#modalCash form");
        const btnSubmit = document.getElementById("btn-submit");
        const btnText = document.getElementById("btn-text");
        const btnSpinner = document.getElementById("btn-spinner");

        formCash.addEventListener("submit", function() {
            // Disable tombol
            btnSubmit.disabled = true;

            // Ubah teks dan tampilkan animasi berputar
            btnText.textContent = "Memproses...";
            btnSpinner.classList.remove("d-none");
        });
    });
</script>
<?php /**PATH J:\PROJECT\GOTIK\TiketKonser\resources\views/penyewa/molecul/modalCash.blade.php ENDPATH**/ ?>