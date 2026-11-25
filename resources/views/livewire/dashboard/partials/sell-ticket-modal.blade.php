<!-- MODAL JUAL TIKET (POS SYSTEM - 0ms Alpine.js + Eager-Loaded Data) -->
<div
    x-data="{
        events: [],
        selectedEventUid: '',
        selectedEvent: null,
        selectedTickets: [],
        buyerName: '',
        buyerEmail: '',
        buyerBirthday: '',
        buyerGender: '',
        partnerId: '',
        isDirectEntry: false,
        submitting: false,
        clientError: '',
        init() {
            this.syncEvents();
        },
        syncEvents() {
            const raw = this.$refs.posData?.dataset?.posEvents;
            if (!raw) return;
            const bytes = Uint8Array.from(atob(raw), c => c.charCodeAt(0));
            this.events = JSON.parse(new TextDecoder().decode(bytes));
            if (this.selectedEventUid) {
                this.selectedEvent = this.events.find(e => e.uid === this.selectedEventUid) || null;
            }
        },
        formatNumber(val) {
            return (Number(val) || 0).toLocaleString('en-US');
        },
        selectEvent(uid) {
            const ev = this.events.find(e => e.uid === uid);
            if (!ev) return;
            this.selectedEventUid = ev.uid;
            this.selectedEvent = ev;
            this.selectedTickets = [];
            this.clientError = '';
        },
        addTicket(ticketId, selectEl) {
            const id = parseInt(ticketId, 10);
            if (selectEl) selectEl.value = '';
            if (!id || !this.selectedEvent) return;
            const ticket = (this.selectedEvent.tickets || []).find(t => Number(t.id) === id);
            if (!ticket || ticket.status !== 'active' || ticket.remaining_stock < 1) {
                this.clientError = 'Tiket tidak aktif atau sudah sold out.';
                return;
            }
            this.clientError = '';
            const existing = this.selectedTickets.find(i => Number(i.id) === id);
            if (existing) {
                if (existing.qty < existing.max_qty) {
                    existing.qty += 1;
                }
                return;
            }
            this.selectedTickets.push({
                id: ticket.id,
                name: ticket.kategori,
                price: Number(ticket.harga) || 0,
                qty: 1,
                max_qty: Number(ticket.remaining_stock) || 0,
            });
        },
        increaseQty(index) {
            const item = this.selectedTickets[index];
            if (!item) return;
            item.qty = Math.min(item.max_qty, (parseInt(item.qty, 10) || 1) + 1);
        },
        decreaseQty(index) {
            const item = this.selectedTickets[index];
            if (!item) return;
            item.qty = Math.max(1, (parseInt(item.qty, 10) || 1) - 1);
        },
        clampQty(index) {
            const item = this.selectedTickets[index];
            if (!item) return;
            let qty = parseInt(item.qty, 10);
            if (isNaN(qty) || qty < 1) qty = 1;
            if (qty > item.max_qty) qty = item.max_qty;
            item.qty = qty;
        },
        removeTicket(index) {
            this.selectedTickets.splice(index, 1);
        },
        get subtotal() {
            return this.selectedTickets.reduce((sum, item) => sum + ((Number(item.price) || 0) * (parseInt(item.qty, 10) || 0)), 0);
        },
        get taxPercent() {
            return this.selectedEvent ? (Number(this.selectedEvent.fee) || 0) : 0;
        },
        get tax() {
            return Math.round((this.taxPercent / 100) * this.subtotal);
        },
        get total() {
            return this.subtotal + this.tax;
        },
        resetForm() {
            this.selectedEventUid = '';
            this.selectedEvent = null;
            this.selectedTickets = [];
            this.buyerName = '';
            this.buyerEmail = '';
            this.buyerBirthday = '';
            this.buyerGender = '';
            this.partnerId = '';
            this.isDirectEntry = false;
            this.clientError = '';
        },
        submitCheckout() {
            if (this.submitting) return;
            if (!this.selectedEventUid || this.selectedTickets.length === 0) {
                this.clientError = 'Pilih minimal 1 kategori tiket terlebih dahulu.';
                return;
            }
            this.clientError = '';
            this.submitting = true;
            $wire.submitCashPos({
                event_uid: this.selectedEventUid,
                selected_tickets: this.selectedTickets.map(i => ({ id: i.id, qty: parseInt(i.qty, 10) || 1 })),
                buyer_name: this.buyerName,
                buyer_email: this.buyerEmail,
                buyer_birthday: this.buyerBirthday,
                buyer_gender: this.buyerGender,
                partner_id: this.partnerId || null,
                is_direct_entry: this.isDirectEntry,
            }).finally(() => {
                this.submitting = false;
            });
        },
    }"
    x-on:open-modal.window="
        if ($event.detail && $event.detail.name === 'sell-modal') { syncEvents(); }
        if ($event.detail && $event.detail.name === 'cash-transaction-success-modal') { resetForm(); syncEvents(); }
    "
>
    <span x-ref="posData" data-pos-events="{{ base64_encode(json_encode($posEventsPayload)) }}" class="hidden"></span>
    <x-admin.modal name="sell-modal" title="Jual Tiket" icon="shopping-cart" :viewportScroll="true">
        <div class="space-y-6">
            <!-- Dynamic Event Title Subheader -->
            <div x-show="selectedEvent" style="display: none;" class="-mt-3 flex items-center justify-between gap-2 rounded-xl bg-indigo-50/70 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/50 px-3.5 py-2">
                <span class="text-xs font-extrabold uppercase tracking-wide text-indigo-700 dark:text-indigo-300 truncate" x-text="selectedEvent?.name || ''"></span>
                <button type="button" x-on:click="resetForm()" class="text-[11px] font-bold text-slate-500 hover:text-indigo-600 dark:text-slate-400 whitespace-nowrap">
                    Ganti Event
                </button>
            </div>

            <!-- Flash & Validation Messages -->
            @if (session()->has('success'))
                <div class="p-4 text-sm text-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl border border-emerald-100 dark:border-emerald-800 flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-bold">{{ session('success') }}</span>
                </div>
            @endif
            @if (session()->has('error'))
                <div class="p-4 text-sm text-rose-700 bg-rose-50 dark:bg-rose-900/30 rounded-2xl border border-rose-100 dark:border-rose-800 flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-bold">{{ session('error') }}</span>
                </div>
            @endif
            <div x-show="clientError" style="display: none;" class="p-3.5 text-xs font-semibold text-rose-700 bg-rose-50 dark:bg-rose-900/30 rounded-2xl border border-rose-200 dark:border-rose-800" x-text="clientError"></div>

            <!-- STEP 1: PILIH EVENT (0ms Instant) -->
            <div x-show="!selectedEvent" class="space-y-4">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest px-1">Daftar Event Aktif</p>
                <div class="grid grid-cols-1 gap-3 max-h-[50vh] overflow-y-auto pr-1 custom-scrollbar">
                    <template x-for="ev in events" :key="ev.uid">
                        <button type="button" x-on:click="selectEvent(ev.uid)"
                            class="w-full p-4 flex items-center gap-4 bg-slate-50 dark:bg-slate-900 rounded-2xl hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-all border border-slate-200 dark:border-slate-700 hover:border-indigo-300 text-left group">
                            <div class="w-12 h-12 rounded-xl overflow-hidden flex-shrink-0 ring-2 ring-slate-200 dark:ring-slate-700 group-hover:ring-indigo-300">
                                <img :src="ev.cover" class="w-full h-full object-cover" :alt="ev.name">
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-slate-800 dark:text-white truncate uppercase text-xs" x-text="ev.name"></h4>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-bold tracking-wider">Mulai Transaksi</p>
                            </div>
                            <i data-lucide="arrow-right" class="w-4 h-4 text-slate-300 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all"></i>
                        </button>
                    </template>
                    <template x-if="events.length === 0">
                        <div class="text-center py-8">
                            <i data-lucide="calendar-x" class="w-12 h-12 text-slate-300 mx-auto mb-3"></i>
                            <p class="text-slate-500 italic text-sm">Tidak ada event aktif.</p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- STEP 2: FORM TRANSAKSI (0ms Instant State & Live Total) -->
            <div x-show="selectedEvent" style="display: none;" class="flex flex-col h-full">
                <div class="space-y-5 max-h-[58vh] overflow-y-auto px-1 custom-scrollbar pb-4">
                    <!-- Ticket Selector -->
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Tambah Kategori Tiket</label>
                        <div class="relative">
                            <select x-on:change="addTicket($event.target.value, $event.target)"
                                class="w-full p-3 pl-10 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold focus:ring-2 focus:ring-indigo-500 transition-all outline-none appearance-none">
                                <option value="">-- Pilih Tiket --</option>
                                <template x-for="ticket in (selectedEvent?.tickets || [])" :key="ticket.id">
                                    <option :value="ticket.id"
                                        :disabled="ticket.status !== 'active' || ticket.remaining_stock < 1"
                                        x-text="ticket.kategori + ' (Rp ' + formatNumber(ticket.harga) + ') - ' + (ticket.status !== 'active' ? 'Nonaktif' : (ticket.remaining_stock < 1 ? 'Sold Out' : 'Sisa ' + formatNumber(ticket.remaining_stock)))">
                                    </option>
                                </template>
                            </select>
                            <i data-lucide="tag" class="w-4 h-4 text-slate-400 absolute left-4 top-3.5 pointer-events-none"></i>
                        </div>
                        @error('selectedTickets')
                            <p class="text-xs font-medium text-rose-500 px-1">{{ $message }}</p>
                        @enderror

                        <!-- Cart Items -->
                        <div class="space-y-2">
                            <template x-for="(item, index) in selectedTickets" :key="item.id">
                                <div class="flex items-center gap-4 p-4 bg-indigo-50/50 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-800/50 rounded-2xl">
                                    <div class="flex-1 min-w-0">
                                        <h5 class="text-sm font-black text-slate-800 dark:text-white uppercase truncate" x-text="item.name"></h5>
                                        <p class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400" x-text="'Rp ' + formatNumber(item.price) + ' / tiket'"></p>
                                        <p class="text-[10px] font-medium text-slate-400" x-text="'Stok tersedia: ' + formatNumber(item.max_qty)"></p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden h-9 shadow-sm">
                                            <button type="button" x-on:click="decreaseQty(index)"
                                                class="px-2.5 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-500 font-bold">-</button>
                                            <input type="number" min="1" :max="item.max_qty"
                                                x-model.number="item.qty" x-on:change="clampQty(index)"
                                                class="w-12 text-center text-xs font-black bg-transparent border-x border-slate-100 dark:border-slate-700 outline-none">
                                            <button type="button" x-on:click="increaseQty(index)"
                                                :disabled="item.qty >= item.max_qty"
                                                class="px-2.5 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-500 font-bold disabled:opacity-30 disabled:cursor-not-allowed">+</button>
                                        </div>
                                        <button type="button" x-on:click="removeTicket(index)"
                                            class="w-9 h-9 flex items-center justify-center text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors border border-rose-100 dark:border-rose-900/50"
                                            title="Hapus item">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4h8v2m-1 0v14a2 2 0 01-2 2H9a2 2 0 01-2-2V6h10z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="selectedTickets.length === 0">
                                <div class="p-4 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl text-center">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Keranjang Kosong</p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="h-px bg-slate-100 dark:bg-slate-700 mx-2"></div>

                    <!-- Buyer Info Form -->
                    <div class="space-y-4">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Informasi Pembeli</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <input type="text" x-model="buyerName" placeholder="Nama Lengkap" class="w-full p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                @error('buyerName') <p class="text-xs text-rose-500 px-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1">
                                <input type="email" x-model="buyerEmail" placeholder="Email Pembeli" class="w-full p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                @error('buyerEmail') <p class="text-xs text-rose-500 px-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1">
                                <input type="date" x-model="buyerBirthday" class="w-full p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                @error('buyerBirthday') <p class="text-xs text-rose-500 px-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1">
                                <select x-model="buyerGender" class="w-full p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                    <option value="">Jenis Kelamin</option>
                                    <option value="pria">Pria</option>
                                    <option value="wanita">Wanita</option>
                                </select>
                                @error('buyerGender') <p class="text-xs text-rose-500 px-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Extra Options -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Partner (Opsional)</label>
                            <select x-model="partnerId" class="w-full p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                <option value="">-- Pilih Partner --</option>
                                @foreach($availablePartners as $partner)
                                    <option value="{{ $partner->uid }}">{{ $partner->name }}</option>
                                @endforeach
                            </select>
                            @error('partnerId') <p class="text-xs text-rose-500 px-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex flex-col justify-end gap-2 pb-1 px-1">
                            <div class="flex items-center gap-2 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                <i data-lucide="badge-check" class="w-4 h-4"></i>
                                <span>Transaksi cash langsung dianggap lunas</span>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="isDirectEntry" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">Langsung Masuk</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- STICKY FOOTER -->
                <div class="pt-5 border-t border-slate-100 dark:border-slate-700 space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Subtotal</p>
                            <p class="text-sm font-black text-slate-800 dark:text-white" x-text="'Rp ' + formatNumber(subtotal)"></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1" x-text="'Pajak (' + taxPercent + '%)'"></p>
                            <p class="text-sm font-black text-slate-800 dark:text-white" x-text="'Rp ' + formatNumber(tax)"></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-emerald-500 uppercase mb-1">Layanan</p>
                            <p class="text-sm font-black text-emerald-600 uppercase italic">Free</p>
                        </div>
                    </div>

                    <x-admin.button type="button" x-on:click="submitCheckout()" x-bind:disabled="submitting" variant="primary"
                        class="w-full py-4 text-lg font-black tracking-widest shadow-xl shadow-indigo-100 dark:shadow-none">
                        <span x-show="!submitting" x-text="'BAYAR Rp ' + formatNumber(total)"></span>
                        <span x-show="submitting" style="display: none;" class="inline-flex items-center gap-2 uppercase">
                            <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Memproses...
                        </span>
                    </x-admin.button>

                    <button type="button" x-on:click="resetForm(); close()" class="w-full text-center text-[10px] font-bold text-slate-400 hover:text-rose-500 transition-colors uppercase tracking-widest">
                        Batalkan Transaksi
                    </button>
                </div>
            </div>
        </div>
    </x-admin.modal>
</div>
