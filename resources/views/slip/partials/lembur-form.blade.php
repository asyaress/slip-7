<section class="card p-6 border-amber-100">
    <h2 class="text-base font-semibold text-slate-900 mb-1">7. Lembur</h2>
    <p class="text-xs text-slate-500 mb-4">Per minggu (Senin–Sabtu). Tidak masuk THP, dijumlahkan ke Total Pendapatan.</p>

    <div class="hidden sm:grid sm:grid-cols-12 gap-3 mb-2 text-xs font-medium text-slate-400 uppercase tracking-wide">
        <div class="sm:col-span-1">Minggu</div>
        <div class="sm:col-span-4">Periode (Sen–Sab)</div>
        <div class="sm:col-span-4">Nominal</div>
        <div class="sm:col-span-3">Status</div>
    </div>

    <div id="lembur-rows" class="space-y-3">
        @foreach($lemburWeeks as $index => $week)
        @php
            $status = \App\Services\LemburWeekService::normalizeStatus($week['status'] ?? null);
        @endphp
        <div class="lembur-row grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="sm:col-span-1 text-sm font-medium text-slate-500">{{ $week['minggu'] }}</div>
            <div class="sm:col-span-4 text-sm text-slate-700">
                {{ $week['periode'] }}
                <input type="hidden" name="lembur[{{ $index }}][minggu]" value="{{ $week['minggu'] }}">
                <input type="hidden" name="lembur[{{ $index }}][periode]" value="{{ $week['periode'] }}">
            </div>
            <div class="sm:col-span-4 rupiah-field">
                <span class="rupiah-prefix">Rp</span>
                <input type="text" inputmode="numeric"
                    name="lembur[{{ $index }}][nominal]"
                    value="{{ number_format((float) ($week['nominal'] ?? 0), 0, ',', '.') }}"
                    placeholder="0"
                    class="rupiah-input lembur-input">
            </div>
            <div class="sm:col-span-3">
                <select name="lembur[{{ $index }}][status]" class="select-field text-sm lembur-status">
                    <option value="belum_dibayar" @selected($status === 'belum_dibayar')>Belum Dibayar</option>
                    <option value="sudah_dibayar" @selected($status === 'sudah_dibayar')>Sudah Dibayar</option>
                </select>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between items-center">
        <span class="text-sm font-medium text-slate-700">Total Lembur Bulan Ini</span>
        <span id="summary-lembur" class="text-base font-bold text-amber-700">Rp 0</span>
    </div>
</section>
