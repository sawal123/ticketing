<div
    x-data="{
        uid: '',
        invoice: '-',
        paymentType: 'N/A',
        isCash: false,
        email: '',
        verification: 'unverified',
        saving: false,
        openEdit(detail) {
            if (!detail) return;
            this.uid = detail.uid || '';
            this.invoice = detail.invoice || '-';
            this.paymentType = detail.paymentType || 'N/A';
            this.isCash = Boolean(detail.isCash);
            this.email = detail.email || '';
            this.verification = detail.verification || 'unverified';
            this.saving = false;
            $dispatch('open-modal', { name: 'edit-transaction-modal' });
        },
        submitEdit() {
            if (!this.uid || this.saving) return;
            this.saving = true;
            $wire.saveEditedTransaction(this.uid, this.email, this.verification).finally(() => {
                this.saving = false;
            });
        },
    }"
    x-on:open-edit-transaction-modal.window="openEdit($event.detail)"
>
    <x-admin.modal name="edit-transaction-modal" title="Edit Transaksi" icon="pencil">
        <form x-on:submit.prevent="submitEdit()" class="relative space-y-4">
            <div x-show="saving" style="display: none;"
                class="absolute inset-0 z-20 flex items-center justify-center rounded-2xl bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm">
                <div class="flex flex-col items-center gap-3">
                    <svg class="h-10 w-10 animate-spin text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                    </svg>
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Menyimpan perubahan...</p>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 px-4 py-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Invoice</p>
                    <p class="text-xs font-mono font-bold text-slate-800 dark:text-white" x-text="invoice"></p>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-700"
                    x-text="paymentType">
                </span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Email Pembeli</label>
                <div x-show="isCash" style="display: none;">
                    <input type="email" x-model="email" x-bind:required="isCash" placeholder="Masukkan email pembeli cash..."
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                    @error('editingTransactionEmail')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
                <div x-show="!isCash" style="display: none;">
                    <input type="email" x-bind:value="email" disabled
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 text-sm cursor-not-allowed">
                    <p class="mt-1.5 text-xs text-slate-400">Email hanya dapat diedit jika tipe pembayaran adalah <strong>CASH</strong>.</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status Verifikasi</label>
                <select x-model="verification"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 transition-all">
                    <option value="unverified">BELUM VERIFIKASI</option>
                    <option value="verified">VERIFIKASI</option>
                </select>
                @error('editingTransactionVerification')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <x-admin.button type="button" x-on:click="show = false" variant="secondary" x-bind:disabled="saving">
                    Batal
                </x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="save" x-bind:disabled="saving">
                    <span x-show="!saving">Simpan Perubahan</span>
                    <span x-show="saving" style="display: none;">Menyimpan...</span>
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>
