@extends('layouts.app')

@section('content')
    <div class="space-y-8">
        <div class="page-heading flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
                <h1 class="heading-title text-2xl font-bold text-gray-900">Dashboard</h1>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="filter-row flex flex-wrap items-center gap-2"
                id="periodForm">
                @foreach ($periodOptions as $option)
                    <button type="submit" name="periode" value="{{ $option }}"
                        class="chip px-4 py-2 rounded-full text-sm font-medium transition-colors {{ $option === $activePeriod ? 'bg-blue-600 text-white is-active' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $option }}
                    </button>
                @endforeach

                <button type="button"
                    class="chip px-4 py-2 rounded-full text-sm font-medium transition-colors {{ $activePeriod === 'Kustom' ? 'bg-blue-600 text-white is-active' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 ghost' }}"
                    id="customToggle">
                    Kustom
                </button>

                <input type="hidden" name="compare" value="0">

                <label class="checkbox flex items-center gap-2 cursor-pointer ml-2"
                    title="Bandingkan tidak tersedia untuk periode &quot;Semua&quot;.">
                    <input type="checkbox" name="compare" value="1" id="compareToggle"
                        class="rounded text-blue-600 focus:ring-blue-500" {{ $compareEnabled ? 'checked' : '' }} {{ $activePeriod === 'Semua' ? 'disabled' : '' }}>
                    <span class="text-sm text-gray-700">Bandingkan</span>
                </label>

                <div id="customRange" style="display: {{ $activePeriod === 'Kustom' ? 'flex' : 'none' }};"
                    class="w-full lg:w-auto flex flex-wrap items-center gap-2 mt-2 lg:mt-0 lg:ml-2">
                    <input type="date" name="custom_from" class="input border-gray-300 rounded-md shadow-sm text-sm"
                        value="{{ request('custom_from') }}">
                    <input type="date" name="custom_to" class="input border-gray-300 rounded-md shadow-sm text-sm"
                        value="{{ request('custom_to') }}">
                    <button type="submit" name="periode" value="Kustom"
                        class="btn primary bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">Terapkan</button>
            </form>
        </div>

        <style>
            .summary-grid {
                width: 100%;
            }

            .summary-grid .card {
                flex: 1;
            }

            @media (min-width: 1080px) {
                .summary-grid .wide-card {
                    grid-column: span 2;
                }
            }
        </style>

        <div class="summary-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse ($summary as $card)
                @php
                    $isWideCard = in_array($card['label'], ['Total Piutang (Global)', 'Nilai Persediaan Kavling']);
                @endphp
                <article
                    class="card bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex flex-col justify-between h-full min-h-[170px] sm:min-h-[190px] {{ $isWideCard ? 'wide-card' : '' }}"
                    style="gap:8px;">
                    <div class="card-header flex justify-between items-start mb-4">
                        <span class="card-label text-gray-500 text-sm font-medium truncate">{{ $card['label'] }}</span>
                        <span class="pill soft bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">Realtime</span>
                    </div>

                    <div class="stat mb-4" style="align-items:flex-start; gap:6px;">
                        @if (!empty($card['isUnit']))
                            <div class="flex items-baseline gap-1 mb-1">
                                <span class="stat-value text-2xl font-bold text-gray-900">{{ number_format($card['value']) }}</span>
                                <span class="stat-unit text-sm text-gray-500">unit</span>
                            </div>
                        @else
                            <span class="stat-value text-2xl font-bold text-gray-900">Rp
                                {{ number_format($card['value'], 0, ',', '.') }}</span>
                        @endif

                        @if (!empty($card['trend']))
                            @php
                                $dir = $card['trend']['direction'] ?? 'up';
                                $delta = abs($card['trend']['delta'] ?? 0);
                                $isUp = $dir === 'up';
                                $textClasses = $isUp ? 'text-green-700' : 'text-red-700';
                            @endphp
                            <div class="w-full mt-1 leading-tight">
                                <span class="trend-inline {{ $textClasses }}">
                                    <span aria-hidden="true">{!! $isUp ? '&#8593;' : '&#8595;' !!}</span>
                                    <span>{{ number_format($delta, 1) }}% vs periode lalu</span>
                                </span>
                            </div>
                        @endif
                    </div>

                    @if (!empty($card['statuses']))
                        <div class="progress-multi flex h-2 rounded-full overflow-hidden mb-3 bg-gray-100">
                            @foreach ($card['statuses'] as $status)
                                <span class="progress-slice h-full"
                                    style="width: {{ $status['value'] * 100 }}%; background: {{ $status['color'] }};"></span>
                            @endforeach
                        </div>
                        <div class="legend-row flex flex-wrap gap-3">
                            @foreach ($card['statuses'] as $status)
                                <span class="legend flex items-center gap-1 text-xs text-gray-600">
                                    <span class="legend-dot w-2 h-2 rounded-full"
                                        style="background: {{ $status['color'] }};"></span>{{ $status['label'] }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if (!empty($card['inventories']))
                        <div class="progress-multi flex h-2 rounded-full overflow-hidden mb-3 bg-gray-100">
                            @foreach ($card['inventories'] as $inventory)
                                <span class="progress-slice h-full"
                                    style="width: {{ $inventory['value'] * 100 }}%; background: {{ $inventory['color'] }};"></span>
                            @endforeach
                        </div>
                        <div class="legend-row flex flex-wrap gap-3">
                            @foreach ($card['inventories'] as $inventory)
                                <span class="legend flex items-center gap-1 text-xs text-gray-600">
                                    <span class="legend-dot w-2 h-2 rounded-full"
                                        style="background: {{ $inventory['color'] }};"></span>{{ $inventory['label'] }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if (!empty($card['hint']))
                        <p class="hint text-xs text-gray-400 mt-2">{{ $card['hint'] }}</p>
                        @if (!empty($card['previous']))
                            <p class="hint text-xs text-gray-400 mt-1">Periode lalu:
                                @if(!empty($card['isUnit'])){{ number_format($card['previous']) }} unit @else Rp
                                {{ number_format($card['previous'], 0, ',', '.') }} @endif
                            </p>
                        @endif
                    @endif
                </article>
            @empty
                <article class="card bg-white rounded-xl shadow-sm p-6 col-span-full text-center">
                    <div class="card-header mb-2">
                        <span class="card-label font-bold text-gray-700">Belum ada data</span>
                    </div>
                    <p class="hint text-gray-500">Mulai tambahkan penjualan untuk melihat ringkasan.</p>
                </article>
            @endforelse
        </div>

        <div class="space-y-8">
            <div class="panel-grid grid grid-cols-1 lg:grid-cols-2 gap-6">
                <section class="panel bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div
                        class="panel-header flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <div>
                            <h3 class="panel-title text-lg font-bold text-gray-900">Tren Penjualan</h3>
                            <p class="panel-sub text-sm text-gray-500">Nilai penjualan (juta rupiah) sepanjang tahun
                                berjalan.</p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <div class="pill-switch bg-gray-100 p-1 rounded-lg flex">
                                <button class="chip small px-3 py-1 rounded-md text-xs font-medium is-active transition-all"
                                    data-toggle="sales-mode" data-mode="value">Nilai (Rp)</button>
                                <button
                                    class="chip small px-3 py-1 rounded-md text-xs font-medium text-gray-500 hover:text-gray-900 transition-all"
                                    data-toggle="sales-mode" data-mode="unit">Jumlah Unit</button>
                            </div>
                            <label class="toggle flex items-center gap-2 cursor-pointer">
                                <input id="projectionToggle" type="checkbox" checked
                                    class="rounded text-blue-600 focus:ring-blue-500">
                                <span class="text-xs text-gray-600">Proyeksi AI</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <div class="w-full h-72 relative px-2 pb-4">
                            <canvas id="salesTrendChart" class="w-full h-full"></canvas>
                        </div>
                    </div>
                </section>

                <section class="panel bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div
                        class="panel-header flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <div>
                            <h3 class="panel-title text-lg font-bold text-gray-900">Performa Tim Marketing</h3>
                            <p class="panel-sub text-sm text-gray-500">Nilai closing (juta rupiah) per marketer.</p>
                        </div>
                        <div class="pill-switch bg-gray-100 p-1 rounded-lg flex">
                            <button class="chip small px-3 py-1 rounded-md text-xs font-medium is-active transition-all"
                                data-toggle="marketing-mode" data-mode="value">Nilai (Rp)</button>
                            <button
                                class="chip small px-3 py-1 rounded-md text-xs font-medium text-gray-500 hover:text-gray-900 transition-all"
                                data-toggle="marketing-mode" data-mode="unit">Jumlah Unit</button>
                        </div>
                    </div>
                    <div>
                        <div class="w-full h-72 relative px-2 pb-4">
                            <canvas id="marketingChart" class="w-full h-full"></canvas>
                        </div>
                    </div>
                </section>
            </div>

            <div class="panel-grid grid grid-cols-1 lg:grid-cols-2 gap-6">
                <section class="panel bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div
                        class="panel-header flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <div>
                            <h3 class="panel-title text-lg font-bold text-gray-900">Penjualan per Proyek</h3>
                            <p class="panel-sub text-sm text-gray-500">Nilai & unit terjual per proyek pada periode
                                aktif.</p>
                        </div>
                        <div class="pill-switch bg-gray-100 p-1 rounded-lg flex">
                            <button class="chip small px-3 py-1 rounded-md text-xs font-medium is-active transition-all"
                                data-toggle="project-sales-mode" data-mode="value">Nilai (Rp)</button>
                            <button
                                class="chip small px-3 py-1 rounded-md text-xs font-medium text-gray-500 hover:text-gray-900 transition-all"
                                data-toggle="project-sales-mode" data-mode="unit">Jumlah Unit</button>
                        </div>
                    </div>
                    <div>
                        <div class="w-full h-72 flex justify-center px-2 pb-4">
                            <canvas id="projectSalesChart" class="w-full h-full"></canvas>
                        </div>
                    </div>
                </section>

                <section class="panel bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div
                        class="panel-header flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <div>
                            <h3 class="panel-title text-lg font-bold text-gray-900">Nilai Persediaan per Proyek</h3>
                            <p class="panel-sub text-sm text-gray-500">Kavling tersedia dan nilai (Rp) per proyek.</p>
                        </div>
                        <div class="pill-switch bg-gray-100 p-1 rounded-lg flex">
                            <button class="chip small px-3 py-1 rounded-md text-xs font-medium is-active transition-all"
                                data-toggle="inventory-mode" data-mode="value">Nilai (Rp)</button>
                            <button
                                class="chip small px-3 py-1 rounded-md text-xs font-medium text-gray-500 hover:text-gray-900 transition-all"
                                data-toggle="inventory-mode" data-mode="unit">Jumlah Unit</button>
                        </div>
                    </div>
                    <div>
                        <div class="w-full h-72 flex justify-center px-2 pb-4">
                            <canvas id="projectInventoryChart" class="w-full h-full"></canvas>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            let salesTrend = @json($salesTrend);
            let comparisonSalesTrend = [];
            let marketingPerformance = @json($marketingPerformance);
            let projectSales = @json($projectSales);
            let projectInventory = @json($projectInventory);
            let salesMode = 'value';
            let marketingMode = 'value';
            let projectSalesMode = 'value';
            let inventoryMode = 'value';
            const activePeriod = @json($activePeriod);
            const projectionMeta = @json($projectionMeta);
            const comparisonMeta = @json($comparisonMeta);
            const compareEnabled = @json($compareEnabled);
            const trendInterval = @json($trendInterval ?? 'month');

            if (!salesTrend || salesTrend.length === 0) {
                salesTrend = [{ label: '-', value: 0, unit: 0, period: null }];
            }
            comparisonSalesTrend = [];
            if (!marketingPerformance || marketingPerformance.length === 0) {
                marketingPerformance = [{ name: '-', value: 0 }];
            }

            const salesCtx = document.getElementById('salesTrendChart');
            const marketingCtx = document.getElementById('marketingChart');
            const projectSalesCtx = document.getElementById('projectSalesChart');
            const projectInventoryCtx = document.getElementById('projectInventoryChart');
            const projectionToggle = document.getElementById('projectionToggle');
            const compareToggle = document.getElementById('compareToggle');
            const projectionContainer = projectionToggle?.closest('label');

            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const maxValue = (...arrays) => {
                const numbers = arrays.flat().filter(v => Number.isFinite(v));
                return numbers.length ? Math.max(...numbers) : 1;
            };
            const parsePeriod = (period) => {
                if (!period || typeof period !== 'string') return null;
                if (trendInterval === 'day') {
                    const d = new Date(period);
                    return isNaN(d) ? null : d;
                }
                if (trendInterval === 'week') {
                    const match = period.match(/^(\d{4})-W?(\d{1,2})$/);
                    if (!match) return null;
                    const year = Number(match[1]);
                    const week = Number(match[2]);
                    const simple = new Date(year, 0, 1 + (week - 1) * 7);
                    const dow = simple.getDay();
                    const ISOweekStart = simple;
                    if (dow <= 4) {
                        ISOweekStart.setDate(simple.getDate() - simple.getDay() + 1);
                    } else {
                        ISOweekStart.setDate(simple.getDate() + 8 - simple.getDay());
                    }
                    return ISOweekStart;
                }
                if (trendInterval === 'quarter') {
                    const match = period.match(/^(\d{4})-Q(\d)$/);
                    if (!match) return null;
                    const year = Number(match[1]);
                    const q = Number(match[2]);
                    if (q < 1 || q > 4) return null;
                    return new Date(year, (q - 1) * 3, 1);
                }
                if (trendInterval === 'year') {
                    const year = Number(period);
                    if (!year) return null;
                    return new Date(year, 0, 1);
                }
                const [year, month] = period.split('-').map(Number);
                if (!year || !month) return null;
                return new Date(year, month - 1, 1);
            };
            const addStep = (date, count) => {
                const base = date instanceof Date && !isNaN(date) ? new Date(date) : new Date();
                switch (trendInterval) {
                    case 'day':
                        return new Date(base.getFullYear(), base.getMonth(), base.getDate() + count);
                    case 'week':
                        return new Date(base.getFullYear(), base.getMonth(), base.getDate() + 7 * count);
                    case 'quarter':
                        return new Date(base.getFullYear(), base.getMonth() + 3 * count, 1);
                    case 'year':
                        return new Date(base.getFullYear() + count, 0, 1);
                    case 'month':
                    default:
                        return new Date(base.getFullYear(), base.getMonth() + count, 1);
                }
            };
            const formatPeriodLabel = (date) => {
                if (!(date instanceof Date) || isNaN(date)) return '-';
                const weekday = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
                switch (trendInterval) {
                    case 'day':
                        return weekday[date.getDay()];
                    case 'week': {
                        const onejan = new Date(date.getFullYear(), 0, 1);
                        const millisecsInDay = 86400000;
                        const week = Math.ceil((((date - onejan) / millisecsInDay) + onejan.getDay() + 1) / 7);
                        return `W${week}`;
                    }
                    case 'quarter': {
                        const q = Math.floor(date.getMonth() / 3) + 1;
                        return `Q${q} ${date.getFullYear()}`;
                    }
                    case 'year':
                        return `${date.getFullYear()}`;
                    case 'month':
                    default: {
                        const year = String(date.getFullYear()).slice(-2);
                        return `${monthNames[date.getMonth()]} '${year}`;
                    }
                }
            };
            const linearRegressionForecast = (values, steps) => {
                const clean = values.filter(v => Number.isFinite(v));
                const n = clean.length;
                if (!n) return Array(steps).fill(0);
                if (n === 1) return Array.from({ length: steps }, () => Number(clean[0].toFixed(2)));
                const xs = clean.map((_, i) => i + 1);
                const meanX = xs.reduce((a, b) => a + b, 0) / n;
                const meanY = clean.reduce((a, b) => a + b, 0) / n;
                let numerator = 0;
                let denominator = 0;
                for (let i = 0; i < n; i++) {
                    numerator += (xs[i] - meanX) * (clean[i] - meanY);
                    denominator += (xs[i] - meanX) ** 2;
                }
                const slope = denominator === 0 ? 0 : numerator / denominator;
                const intercept = meanY - slope * meanX;
                return Array.from({ length: steps }, (_, idx) => {
                    const x = n + idx + 1;
                    return Math.max(0, Number((intercept + slope * x).toFixed(2)));
                });
            };
            const fallbackForecast = (values, steps) => {
                const clean = values.filter(v => Number.isFinite(v));
                if (!clean.length) return Array(steps).fill(0);
                if (clean.length === 1) return Array.from({ length: steps }, () => Number(clean[0].toFixed(2)));
                const deltas = [];
                for (let i = 1; i < clean.length; i++) {
                    deltas.push(clean[i] - clean[i - 1]);
                }
                const avgDelta = deltas.length ? deltas.reduce((a, b) => a + b, 0) / deltas.length : 0;
                return Array.from({ length: steps }, (_, idx) => {
                    const next = clean[clean.length - 1] + avgDelta * (idx + 1);
                    const safety = idx > 2 ? avgDelta * 0.25 * (idx + 1) : 0;
                    return Math.max(0, Number((next + safety).toFixed(2)));
                });
            };
            const buildProjection = (periods, values) => {
                // Disable projection outside "Tahun Ini"
                if (activePeriod !== 'Tahun Ini') {
                    return { futureLabels: [], futureValues: [], usingAI: false, label: '' };
                }
                // Random Walk with Drift (deterministic seed -> hasil statis)
                const meta = projectionMeta || {};
                const mode = meta.mode || 'short';
                const horizon = mode === 'all'
                    ? (meta.horizonQuarters || 4)
                    : mode === 'year'
                        ? (meta.horizonMonths || 12)
                        : 12; // default 12 bulan supaya zigzag cukup panjang

                const clean = values.filter(v => Number.isFinite(v));
                const hasHistory = clean.length >= 3;
                const useAI = hasHistory;
                const changes = [];
                for (let i = 1; i < clean.length; i++) {
                    changes.push(clean[i] - clean[i - 1]);
                }
                const drift = changes.length ? changes.reduce((a, b) => a + b, 0) / changes.length : 0;
                const mean = drift;
                const variance = changes.length
                    ? changes.reduce((s, v) => s + Math.pow(v - mean, 2), 0) / changes.length
                    : 0;
                const volBase = Math.sqrt(variance) || Math.abs(drift) * 0.6 || Math.max((clean[clean.length - 1] || 1) * 0.05, 1);

                // Deterministic seed dari data historis supaya proyeksi statis
                const seedFromValues = (arr) => {
                    let h = 0;
                    arr.forEach((v, i) => {
                        const n = Math.floor((v ?? 0) * 1000) + i;
                        h = Math.imul(31, h) + n;
                        h |= 0;
                    });
                    return h >>> 0;
                };
                const mulberry32 = (a) => () => {
                    a |= 0; a = a + 0x6D2B79F5 | 0;
                    let t = Math.imul(a ^ a >>> 15, 1 | a);
                    t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t;
                    return ((t ^ t >>> 14) >>> 0) / 4294967296;
                };
                const rng = mulberry32(seedFromValues(clean.length ? clean : [0]));
                const randUniform = () => rng() * 2 - 1; // -1..1

                const lastPeriod = (() => {
                    const reversed = [...periods].reverse();
                    for (const period of reversed) {
                        const parsed = parsePeriod(period);
                        if (parsed) return parsed;
                    }
                    return new Date();
                })();

                let last = clean.length ? clean[clean.length - 1] : 0;
                const futureValues = [];
                for (let i = 0; i < horizon; i++) {
                    const step = drift + volBase * 0.9 * randUniform();
                    last = Math.max(0, last + step);
                    futureValues.push(Number(last.toFixed(2)));
                }

                const futureLabels = Array.from({ length: horizon }, (_, idx) => formatPeriodLabel(addStep(lastPeriod, idx + 1)));
                return {
                    futureLabels,
                    futureValues,
                    usingAI: useAI,
                    label: 'Proyeksi AI',
                };
            };
            const buildTimeline = (projection, modeKey) => {
                const actualMap = new Map(salesTrend.map(item => [item.period || item.label, Number(item[modeKey]) || 0]));
                const timeline = [];
                const pushEntry = (key, label, date, futureIndex = null) => {
                    const finalKey = key || `label-${label}`;
                    if (!finalKey) return;
                    timeline.push({ key: finalKey, label, date, futureIndex });
                };
                if (trendInterval === 'month' && (activePeriod === 'Tahun Ini' || activePeriod === 'Tahun Lalu')) {
                    const year = activePeriod === 'Tahun Ini' ? new Date().getFullYear() : new Date().getFullYear() - 1;
                    for (let m = 0; m < 12; m++) {
                        const date = new Date(year, m, 1);
                        const periodKey = `${date.getFullYear()}-${String(m + 1).padStart(2, '0')}`;
                        pushEntry(periodKey, formatPeriodLabel(date), date);
                    }
                }
                salesTrend.forEach(item => pushEntry(item.period, item.label, parsePeriod(item.period)));
                const lastPeriodDate = (() => {
                    const reversed = [...salesTrend].reverse();
                    for (const item of reversed) {
                        const parsed = parsePeriod(item.period);
                        if (parsed) return parsed;
                    }
                    return new Date();
                })();
                projection.futureLabels.forEach((label, idx) => {
                    pushEntry(`future-${idx}`, label, addStep(lastPeriodDate, idx + 1), idx);
                });
                const unique = new Map();
                timeline.forEach(entry => {
                    if (!unique.has(entry.key)) unique.set(entry.key, entry);
                });
                const sorted = Array.from(unique.values()).sort((a, b) => {
                    if (a.date && b.date && !isNaN(a.date) && !isNaN(b.date)) return a.date - b.date;
                    if (a.date && !b.date) return -1;
                    if (!a.date && b.date) return 1;
                    return (a.label || '').localeCompare(b.label || '');
                });
                const labels = sorted.map((e, idx) => {
                    if (trendInterval === 'week') {
                        return `W${idx + 1}`;
                    }
                    const label = formatPeriodLabel(e.date);
                    return (label === '-' && e.label) ? e.label : label;
                });
                const values = sorted.map(e => actualMap.get(e.key) ?? null);
                const comparisonData = sorted.map(() => null);
                // Pastikan projected dimulai dengan titik historis terakhir untuk sambungan mulus
                const projected = sorted.map((e, idx) => {
                    if (Number.isInteger(e.futureIndex)) {
                        return projection.futureValues[e.futureIndex];
                    }
                    // jika ini titik terakhir historis dan setelahnya ada proyeksi, duplikasi sebagai anchor
                    const isLastHist = idx === sorted.length - 1 && projection.futureValues.length > 0;
                    return isLastHist ? (values[idx] ?? null) : null;
                });
                return { labels, values, projected, projectionLabel: projection.label, comparisonData };
            };
            const buildSalesData = () => {
                const modeKey = salesMode === 'unit' ? 'unit' : 'value';
                const values = salesTrend.map(item => Number(item[modeKey]) || 0);
                const periods = salesTrend.map(item => item.period);
                // Untuk filter "Semua" atau data terlalu sedikit, jangan tampilkan proyeksi supaya sumbu tidak kacau
                const hasHistoryForProjection = values.filter(v => Number.isFinite(v)).length >= 3;
                const projection = (activePeriod === 'Semua' || !hasHistoryForProjection)
                    ? { futureLabels: [], futureValues: [], usingAI: false, label: '' }
                    : buildProjection(periods, values);
                return buildTimeline(projection, modeKey);
            };

            let { labels: salesLabels, values: salesValues, projected, projectionLabel, comparisonData } = buildSalesData();
            let comparisonSeries = comparisonData;

            const formatRupiahCompact = (valueMillions) => {
                const nominal = Number(valueMillions || 0) * 1_000_000;
                const abs = Math.abs(nominal);
                let unit = 'Jt';
                let divisor = 1_000_000;
                if (abs >= 1_000_000_000_000) {
                    unit = 'T';
                    divisor = 1_000_000_000_000;
                } else if (abs >= 1_000_000_000) {
                    unit = 'M';
                    divisor = 1_000_000_000;
                }
                const scaled = nominal / divisor;
                const fractionDigits = Math.abs(scaled) >= 10 ? 0 : 1;
                const formatted = scaled.toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: fractionDigits,
                });
                return `Rp${formatted} ${unit}`;
            };
            const formatRupiahFull = (valueMillions) => {
                const nominal = Number(valueMillions || 0) * 1_000_000;
                return nominal.toLocaleString('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
            };
            const currencyTick = (value) => formatRupiahCompact(value);
            const unitTick = (value) => `${value} unit`;
            const buildAxisConfig = (maxVal, isUnit) => {
                const safeMax = Number.isFinite(maxVal) && maxVal > 0 ? maxVal : 1;
                if (isUnit) {
                    const step = safeMax <= 10 ? 1 : Math.ceil(safeMax / 5);
                    return {
                        suggestedMax: safeMax + step,
                        stepSize: step,
                        callback: unitTick,
                    };
                }
                return {
                    suggestedMax: safeMax * 1.1,
                    stepSize: undefined,
                    callback: currencyTick,
                };
            };

            const sharedOptions = {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: { intersect: false, mode: 'index' },
            };

            const upColor = '#0F9D58';
            const downColor = '#DB4437';

            const salesChart = new Chart(salesCtx, {
                type: 'bar',
                data: {
                    labels: salesLabels,
                    datasets: [
                        {
                            label: 'Nilai (Rp)',
                            data: salesValues,
                            backgroundColor: '#9c0f2f',
                            borderRadius: 10,
                            maxBarThickness: 32
                        },
                        {
                            type: 'line',
                            id: 'projection',
                            label: projectionLabel,
                            data: projected,
                            borderColor: downColor,
                            borderWidth: 2.5,
                            tension: 0,
                            pointRadius: 6,
                            pointHoverRadius: 7,
                            pointBorderColor: '#0b1a2b',
                            pointBorderWidth: 1.2,
                            spanGaps: true,
                            segment: {
                                borderColor: ctx => {
                                    const { p0, p1 } = ctx;
                                    if (!p0 || !p1 || p0.skip || p1.skip) return downColor;
                                    return p1.parsed.y >= p0.parsed.y ? upColor : downColor;
                                }
                            },
                            pointBackgroundColor: ctx => {
                                const i = ctx.dataIndex;
                                const data = ctx.dataset.data || [];
                                const curr = data[i];
                                const prev = i > 0 ? data[i - 1] : null;
                                if (curr === null || curr === undefined) return 'transparent';
                                if (prev === null || prev === undefined) return upColor;
                                return curr >= prev ? upColor : downColor;
                            },
                        }
                    ]
                },
                options: {
                    ...sharedOptions,
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#6b7280' }
                        },
                        y: {
                            grid: { color: '#e5e7eb' },
                            ticks: {
                                color: '#6b7280',
                                callback: value => salesMode === 'unit' ? unitTick(value) : currencyTick(value),
                                stepSize: buildAxisConfig(maxValue(salesValues, projected), salesMode === 'unit').stepSize,
                            },
                            suggestedMax: buildAxisConfig(maxValue(salesValues, projected), salesMode === 'unit').suggestedMax
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                color: '#111827',
                                boxWidth: 14,
                                boxHeight: 14,
                                usePointStyle: true,
                                filter: (item) => item.datasetIndex === 0 // Only show first dataset (Nilai Rp)
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.dataset.label}: ${salesMode === 'unit' ? unitTick(ctx.parsed.y) : formatRupiahFull(ctx.parsed.y)}`
                            }
                        }
                    }
                }
            });

            const buildMarketingData = () => {
                const labels = marketingPerformance.map(item => item.name);
                const values = marketingPerformance.map(item => Number(item[marketingMode === 'unit' ? 'unit' : 'value']));
                return { labels, values };
            };

            let { labels: marketingLabels, values: marketingValues } = buildMarketingData();
            const applyMarketingAxis = () => {
                const axisCfg = buildAxisConfig(maxValue(marketingValues), marketingMode === 'unit');
                marketingChart.options.scales.y.ticks.callback = axisCfg.callback;
                marketingChart.options.scales.y.ticks.stepSize = axisCfg.stepSize;
                marketingChart.options.scales.y.suggestedMax = axisCfg.suggestedMax;
            };

            const marketingChart = new Chart(marketingCtx, {
                type: 'bar',
                data: {
                    labels: marketingLabels,
                    datasets: [
                        {
                            label: 'Nilai (Rp)',
                            data: marketingValues,
                            backgroundColor: '#9c0f2f',
                            borderRadius: 12,
                            maxBarThickness: 38
                        }
                    ]
                },
                options: {
                    ...sharedOptions,
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#6b7280', maxRotation: 50, minRotation: 30 }
                        },
                        y: {
                            grid: { color: '#e5e7eb' },
                            ticks: {
                                color: '#6b7280',
                                callback: value => marketingMode === 'unit' ? unitTick(value) : currencyTick(value),
                            },
                            suggestedMax: Math.max(...marketingValues) + 1
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.dataset.label}: ${marketingMode === 'unit' ? unitTick(ctx.parsed.y) : formatRupiahFull(ctx.parsed.y)}`
                            }
                        }
                    }
                }
            });
            applyMarketingAxis();
            if (!projectSales || projectSales.length === 0) {
                projectSales = [{ name: '-', value: 0, unit: 0 }];
            }
            const buildProjectSalesData = () => {
                const labels = projectSales.map(item => item.name);
                const values = projectSales.map(item => Number(item[projectSalesMode === 'unit' ? 'unit' : 'value']) || 0);
                return { labels, values };
            };
            let { labels: projectSalesLabels, values: projectSalesValues } = buildProjectSalesData();
            const projectSalesColors = ['#9c0f2f', '#2563eb', '#f59e0b', '#10b981', '#8b5cf6', '#0ea5e9', '#ef4444', '#6b7280'];
            const projectSalesChart = new Chart(projectSalesCtx, {
                type: 'doughnut',
                data: {
                    labels: projectSalesLabels,
                    datasets: [
                        {
                            label: projectSalesMode === 'unit' ? 'Jumlah Unit' : 'Nilai (Rp)',
                            data: projectSalesValues,
                            backgroundColor: projectSalesLabels.map((_, idx) => projectSalesColors[idx % projectSalesColors.length]),
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    ...sharedOptions,
                    cutout: '55%',
                    plugins: {
                        legend: { display: true, position: 'right' },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.label}: ${projectSalesMode === 'unit' ? unitTick(ctx.parsed) : formatRupiahFull(ctx.parsed)}`
                            }
                        }
                    }
                }
            });

            const projectSalesChips = document.querySelectorAll('[data-toggle="project-sales-mode"]');
            projectSalesChips.forEach(chip => {
                chip.addEventListener('click', () => {
                    projectSalesMode = chip.dataset.mode;
                    projectSalesChips.forEach(c => c.classList.remove('is-active'));
                    chip.classList.add('is-active');
                    const { labels, values } = buildProjectSalesData();
                    projectSalesLabels = labels;
                    projectSalesValues = values;
                    projectSalesChart.data.labels = labels;
                    projectSalesChart.data.datasets[0].data = values;
                    projectSalesChart.data.datasets[0].label = projectSalesMode === 'unit' ? 'Jumlah Unit' : 'Nilai (Rp)';
                    projectSalesChart.update();
                });
            });

            if (!projectInventory || projectInventory.length === 0) {
                projectInventory = [{ name: '-', value: 0, unit: 0 }];
            }
            const buildInventoryData = () => {
                const labels = projectInventory.map(item => item.name);
                const values = projectInventory.map(item => Number(item[inventoryMode === 'unit' ? 'unit' : 'value']) || 0);
                return { labels, values };
            };
            let { labels: inventoryLabels, values: inventoryValues } = buildInventoryData();
            const inventoryColors = ['#0f9d58', '#9c0f2f', '#2563eb', '#f59e0b', '#6b21a8', '#0ea5e9', '#ef4444', '#6b7280'];
            const projectInventoryChart = new Chart(projectInventoryCtx, {
                type: 'doughnut',
                data: {
                    labels: inventoryLabels,
                    datasets: [
                        {
                            label: inventoryMode === 'unit' ? 'Jumlah Unit' : 'Nilai (Rp)',
                            data: inventoryValues,
                            backgroundColor: inventoryLabels.map((_, idx) => inventoryColors[idx % inventoryColors.length]),
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    ...sharedOptions,
                    cutout: '55%',
                    plugins: {
                        legend: { display: true, position: 'right' },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.label}: ${inventoryMode === 'unit' ? unitTick(ctx.parsed) : formatRupiahFull(ctx.parsed)}`
                            }
                        }
                    }
                }
            });

            const inventoryChips = document.querySelectorAll('[data-toggle="inventory-mode"]');
            inventoryChips.forEach(chip => {
                chip.addEventListener('click', () => {
                    inventoryMode = chip.dataset.mode;
                    inventoryChips.forEach(c => c.classList.remove('is-active'));
                    chip.classList.add('is-active');
                    const { labels, values } = buildInventoryData();
                    inventoryLabels = labels;
                    inventoryValues = values;
                    projectInventoryChart.data.labels = labels;
                    projectInventoryChart.data.datasets[0].data = values;
                    projectInventoryChart.data.datasets[0].label = inventoryMode === 'unit' ? 'Jumlah Unit' : 'Nilai (Rp)';
                    projectInventoryChart.update();
                });
            });

            const syncProjectionToggle = () => {
                if (!projectionToggle) return;
                const hasProjection = projected.some(v => Number.isFinite(v));
                if (!hasProjection) {
                    projectionToggle.checked = false;
                }
                const projectionIndex = salesChart.data.datasets.findIndex(ds => ds.id === 'projection');
                if (projectionIndex >= 0) {
                    const meta = salesChart.getDatasetMeta(projectionIndex);
                    meta.hidden = !projectionToggle.checked;
                }
            };

            if (projectionToggle && projectionMeta && !projectionMeta.aiEligible) {
                projectionToggle.title = 'Diperlukan data penjualan selama periode >1 tahun';
            }

            projectionToggle?.addEventListener('change', () => {
                syncProjectionToggle();
                salesChart.update();
            });
            syncProjectionToggle();

            if (compareToggle) {
                if (!compareEnabled) {
                    compareToggle.checked = false;
                }
                if (activePeriod === 'Semua') {
                    compareToggle.title = 'Bandingkan tidak tersedia untuk periode "Semua"';
                }
                compareToggle.addEventListener('change', () => {
                    if (compareToggle.disabled) return;
                    document.getElementById('periodForm')?.submit();
                });
            }

            const salesChips = document.querySelectorAll('[data-toggle="sales-mode"]');
            salesChips.forEach(chip => {
                chip.addEventListener('click', () => {
                    salesMode = chip.dataset.mode;
                    salesChips.forEach(c => c.classList.remove('is-active'));
                    chip.classList.add('is-active');
                    const rebuilt = buildSalesData();
                    salesLabels = rebuilt.labels;
                    salesValues = rebuilt.values;
                    projected = rebuilt.projected;
                    projectionLabel = rebuilt.projectionLabel;
                    comparisonSeries = rebuilt.comparisonData;
                    salesChart.data.labels = salesLabels;
                    const projectionIndex = salesChart.data.datasets.findIndex(ds => ds.id === 'projection');
                    salesChart.data.datasets[0].data = salesValues;
                    salesChart.data.datasets[0].label = salesMode === 'unit' ? 'Jumlah Unit' : 'Nilai (Rp)';
                    if (projectionIndex >= 0) {
                        salesChart.data.datasets[projectionIndex].data = projected;
                        salesChart.data.datasets[projectionIndex].label = projectionLabel;
                    }
                    const axisCfg = buildAxisConfig(maxValue(salesValues, projected), salesMode === 'unit');
                    salesChart.options.scales.y.ticks.callback = axisCfg.callback;
                    salesChart.options.scales.y.ticks.stepSize = axisCfg.stepSize;
                    salesChart.options.scales.y.suggestedMax = axisCfg.suggestedMax;
                    syncProjectionToggle();
                    salesChart.update();
                });
            });

            const marketingChips = document.querySelectorAll('[data-toggle="marketing-mode"]');
            marketingChips.forEach(chip => {
                chip.addEventListener('click', () => {
                    marketingMode = chip.dataset.mode;
                    marketingChips.forEach(c => c.classList.remove('is-active'));
                    chip.classList.add('is-active');
                    const { labels, values } = buildMarketingData();
                    marketingChart.data.labels = labels;
                    marketingChart.data.datasets[0].data = values;
                    marketingChart.data.datasets[0].label = marketingMode === 'unit' ? 'Jumlah Unit' : 'Nilai (Rp)';
                    const axisCfg = buildAxisConfig(maxValue(values), marketingMode === 'unit');
                    marketingChart.options.scales.y.ticks.callback = axisCfg.callback;
                    marketingChart.options.scales.y.ticks.stepSize = axisCfg.stepSize;
                    marketingChart.options.scales.y.suggestedMax = axisCfg.suggestedMax;
                    marketingChart.update();
                });
            });

            document.querySelectorAll('.filter-row .chip').forEach((chip) => {
                chip.addEventListener('click', () => {
                    document.querySelectorAll('.filter-row .chip').forEach(c => {
                        if (c.id !== 'customToggle') c.classList.remove('is-active');
                    });
                    if (chip.id !== 'customToggle') {
                        document.getElementById('customRange')?.setAttribute('style', 'display:none; gap:8px; align-items:center;');
                    }
                });
            });

            const customToggle = document.getElementById('customToggle');
            const customRange = document.getElementById('customRange');
            customToggle?.addEventListener('click', () => {
                const visible = customRange?.style.display !== 'none';
                customRange.style.display = visible ? 'none' : 'flex';
                if (!visible) {
                    document.querySelectorAll('.filter-row .chip').forEach(c => c.classList.remove('is-active'));
                    customToggle.classList.add('is-active');
                }
            });

            if (activePeriod === 'Kustom' && customRange) {
                customRange.style.display = 'flex';
                customToggle?.classList.add('is-active');
            }
        })();
    </script>
@endpush