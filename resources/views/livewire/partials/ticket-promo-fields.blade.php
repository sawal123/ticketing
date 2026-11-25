@php
    $isPromoField = $modelPrefix . '.is_promo';
    $promoTypeField = $modelPrefix . '.promo_type';
    $promoValueField = $modelPrefix . '.promo_value';
    $hargaField = $modelPrefix . '.harga';
@endphp

<div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-800/60 p-4 space-y-3"
    x-data="{
        isPromo: @entangle($isPromoField),
        promoType: @entangle($promoTypeField),
        promoValue: @entangle($promoValueField),
        basePrice: @entangle($hargaField),
        get numericBase() {
            return Math.max(0, parseInt(this.basePrice, 10) || 0);
        },
        get numericVal() {
            return Math.max(0, parseInt(this.promoValue, 10) || 0);
        },
        get discountAmount() {
            if (!this.isPromo || this.numericBase <= 0 || this.numericVal <= 0) return 0;
            if (this.promoType === 'persen') {
                const pct = Math.min(99, this.numericVal);
                return Math.min(this.numericBase - 1, Math.round((this.numericBase * pct) / 100));
            }
            return Math.min(this.numericBase - 1, this.numericVal);
        },
        get finalPrice() {
            return Math.max(0, this.numericBase - this.discountAmount);
        },
        get discountPercent() {
            if (this.discountAmount <= 0 || this.numericBase <= 0) return 0;
            if (this.promoType === 'persen') return Math.max(1, Math.min(99, this.numericVal));
            return Math.max(1, Math.min(99, Math.round((this.discountAmount / this.numericBase) * 100)));
        },
        formatRp(n) {
            return 'Rp ' + (Number(n) || 0).toLocaleString('id-ID');
        }
    }">
    <label class="flex items-center justify-between gap-3 cursor-pointer">
        <div>
            <span class="block text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Harga Promo</span>
            <span class="block text-[11px] text-slate-500 dark:text-slate-400">Aktifkan potongan Rupiah atau Persen untuk menampilkan harga coret di landing</span>
        </div>
        <input type="checkbox" x-model="isPromo"
            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
    </label>

    <div x-show="isPromo" x-cloak class="space-y-3 pt-2 border-t border-slate-200 dark:border-slate-700">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Jenis Promo</label>
                <select wire:model="{{ $promoTypeField }}" x-model="promoType"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="persen">Persen (%)</option>
                    <option value="rupiah">Rupiah (Rp)</option>
                </select>
                @error($promoTypeField)
                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1"
                    x-text="promoType === 'persen' ? 'Potongan (%)' : 'Potongan (Rp)'">Potongan</label>
                <input type="number" min="1" :max="promoType === 'persen' ? 99 : null"
                    wire:model="{{ $promoValueField }}" x-model="promoValue"
                    :placeholder="promoType === 'persen' ? 'Contoh: 15' : 'Contoh: 25000'"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error($promoValueField)
                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div x-show="discountAmount > 0"
            class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200/70 dark:border-emerald-700/40 px-3.5 py-2.5 text-xs">
            <span class="font-semibold text-emerald-700 dark:text-emerald-300">Preview di Landing:</span>
            <div class="flex items-center gap-2">
                <span class="line-through text-slate-400" x-text="formatRp(numericBase)"></span>
                <span class="font-extrabold text-emerald-700 dark:text-emerald-300" x-text="formatRp(finalPrice)"></span>
                <span class="px-2 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-extrabold"
                    x-text="'-' + discountPercent + '%'"></span>
            </div>
        </div>
    </div>
</div>
