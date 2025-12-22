<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Lot;
use App\Models\Buyer;
use App\Models\Marketer;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\SaleRequest;
use App\Models\Payment;
use Carbon\Carbon;
use App\Models\CompanyProfile;
class PenjualanController extends Controller
{
    private function availableLots(?Sale $currentSale = null)
    {
        return Lot::with('project')->where(function (Builder $outer) use ($currentSale) {
            $outer->where('status', 'available')->orWhere(function (Builder $q) use ($currentSale) {
                $q->whereDoesntHave('sale', function (Builder $q2) {
                    $q2->whereNotIn('status', ['canceled', Sale::STATUS_CANCELED_HAPUS, Sale::STATUS_CANCELED_REFUND, Sale::STATUS_CANCELED_OPER_KREDIT,]);
                });
                if ($currentSale) {
                    $q->orWhereHas('sale', function (Builder $q2) use ($currentSale) {
                        $q2->where('id', $currentSale->id);
                    });
                }
            });
        })->get();
    }
    public function cancel(Request $request, Sale $sale)
    {
        $type = $request->input('type');
        $sale->status_before_cancel = $sale->status_before_cancel ?? $sale->status;
        if ($type === 'hapus') {
            $sale->status = Sale::STATUS_CANCELED_HAPUS;
            $sale->save();
            $sale->lot?->update(['status' => 'available']);
        } elseif ($type === 'refund') {
            $amount = (int) str_replace('.', '', $request->input('refund_amount', 0));

            // Strict Accounting Fix: Before marking as refund, ensure paid_amount is ONLY what was actually paid.
            // This fixes legacy KPR data where paid_amount might be inflated (Price).
            $realPaid = $sale->payments()->where('status', 'paid')->sum('amount');
            $sale->paid_amount = $realPaid;
            $sale->outstanding_amount = 0; // No more debt for canceled item

            $sale->status = Sale::STATUS_CANCELED_REFUND;
            $sale->refund_amount = $amount;
            $sale->save();
            $sale->lot?->update(['status' => 'available']);
        } elseif ($type === 'oper_kredit') {
            $newBuyerId = $request->input('new_buyer_id');
            $newMarketerId = $request->input('new_marketer_id');
            $oldBuyerName = optional($sale->buyer)->name;
            $sale->status_before_cancel = "Oper dari: {$oldBuyerName} (ID: {$sale->buyer_id})";
            $sale->buyer_id = $newBuyerId;
            if ($newMarketerId) {
                $sale->marketer_id = $newMarketerId;
            }
            $sale->save();
            return redirect()->route('penjualan.show', $sale)->with('success', "Oper kredit berhasil! Pembeli diubah dari {$oldBuyerName} ke pembeli baru.");
        }
        return back()->with('success', 'Penjualan berhasil dibatalkan');
    }
    public function updateNotes(Request $request, Sale $sale)
    {
        $sale->notes = $request->input('notes');
        $sale->save();
        return redirect()->route('penjualan.show', $sale)->with('success', 'Catatan berhasil diperbarui');
    }
    public function index(Request $request)
    {
        $filters = ['kavling' => $request->query('kavling', ''), 'pembeli' => $request->query('pembeli', ''), 'status_tagihan' => $request->query('status_tagihan', 'Semua'), 'status_dp' => $request->query('status_dp', 'Semua'), 'status_penjualan' => $request->query('status_penjualan', 'Semua'), 'marketing' => $request->query('marketing', 'Semua'), 'metode_bayar' => $request->query('metode_bayar', 'Semua'), 'tgl_booking_dari' => $request->query('tgl_booking_dari', ''), 'tgl_booking_sampai' => $request->query('tgl_booking_sampai', ''), 'harga_min' => $request->query('harga_min', ''), 'harga_max' => $request->query('harga_max', ''), 'sort_by' => $request->query('sort_by', 'booking_date'), 'sort_dir' => $request->query('sort_dir', 'desc'),];
        $query = Sale::with(['lot.project', 'buyer', 'marketer']);
        if ($filters['kavling'] !== '') {
            $key = $filters['kavling'];
            $parts = array_map('trim', explode('/', $key));
            $query->where(function ($q) use ($key, $parts) {
                if (count($parts) >= 2) {
                    [$projectPart, $blockPart] = $parts;
                    $q->whereHas('lot', function ($q2) use ($projectPart, $blockPart) {
                        $q2->where('block_number', 'like', "%{$blockPart}%")->whereHas('project', function ($q3) use ($projectPart) {
                            $q3->where('name', 'like', "%{$projectPart}%");
                        });
                    });
                } else {
                    $q->whereHas('lot', function ($q2) use ($key) {
                        $q2->where('block_number', 'like', "%{$key}%");
                    })->orWhereHas('lot.project', function ($q3) use ($key) {
                        $q3->where('name', 'like', "%{$key}%");
                    });
                }
            });
        }
        if ($filters['pembeli'] !== '') {
            $key = $filters['pembeli'];
            $query->whereHas('buyer', function ($q) use ($key) {
                $q->where('name', 'like', "%{$key}%");
            });
        }
        if ($filters['metode_bayar'] !== 'Semua') {
            $map = ['Cash Keras' => 'cash', 'Angsuran In-house' => 'installment', 'KPR Bank' => 'kpr', 'cash' => 'cash', 'installment' => 'installment', 'kpr' => 'kpr',];
            $method = $map[$filters['metode_bayar']] ?? $filters['metode_bayar'];
            $query->where('payment_method', $method);
        }
        if ($filters['marketing'] !== 'Semua' && $filters['marketing'] !== '') {
            $query->where('marketer_id', $filters['marketing']);
        }
        if ($filters['status_penjualan'] !== 'Semua') {
            $statusMap = ['Paid Off' => ['paid_off'], 'Active' => ['active'], 'Canceled' => [\App\Models\Sale::STATUS_CANCELED_REFUND, 'canceled'], 'Batal (Refund)' => [\App\Models\Sale::STATUS_CANCELED_REFUND, 'canceled'],];
            $statuses = $statusMap[$filters['status_penjualan']] ?? [$filters['status_penjualan']];
            $query->whereIn('status', $statuses);
        }
        if ($filters['status_tagihan'] !== 'Semua') {
            $today = Carbon::today();
            if ($filters['status_tagihan'] === 'Ada Tunggakan') {
                $query->whereHas('payments', function ($q) use ($today) {
                    $q->where('status', 'unpaid')->whereDate('due_date', '<', $today);
                });
            } elseif ($filters['status_tagihan'] === 'Jatuh Tempo < 7 Hari') {
                $targetDate = $today->copy()->addDays(7);
                $query->whereHas('payments', function ($q) use ($today, $targetDate) {
                    $q->where('status', 'unpaid')->whereDate('due_date', '>=', $today)->whereDate('due_date', '<=', $targetDate);
                })->whereDoesntHave('payments', function ($q) use ($today) {
                    $q->where('status', 'unpaid')->whereDate('due_date', '<', $today);
                });
            } elseif ($filters['status_tagihan'] === 'Aman') {
                $query->where('outstanding_amount', '<=', 0);
            }
        }
        if ($filters['status_dp'] !== 'Semua') {
            if ($filters['status_dp'] === 'Lunas') {
                // DP Lunas: has down_payment > 0 AND DP payment is paid
                $query->where('down_payment', '>', 0)
                    ->whereHas('payments', function ($q) {
                        $q->where('note', 'Down Payment')->where('status', 'paid');
                    });
            } elseif ($filters['status_dp'] === 'Belum') {
                // DP Belum: has down_payment > 0 AND (DP payment is unpaid OR no DP payment record)
                $query->where('down_payment', '>', 0)
                    ->where(function ($q) {
                        $q->whereHas('payments', function ($q2) {
                            $q2->where('note', 'Down Payment')->where('status', '!=', 'paid');
                        })->orWhereDoesntHave('payments', function ($q2) {
                            $q2->where('note', 'Down Payment');
                        });
                    });
            }
        }
        if ($filters['tgl_booking_dari']) {
            $query->whereDate('booking_date', '>=', $filters['tgl_booking_dari']);
        }
        if ($filters['tgl_booking_sampai']) {
            $query->whereDate('booking_date', '<=', $filters['tgl_booking_sampai']);
        }
        if ($filters['harga_min'] !== '') {
            $query->where('price', '>=', (int) $filters['harga_min']);
        }
        if ($filters['harga_max'] !== '') {
            $query->where('price', '<=', (int) $filters['harga_max']);
        }
        $penjualan = $query->get()->map(function ($sale) {
            $today = Carbon::today();
            $targetDate = $today->copy()->addDays(7);
            $overduePayment = $sale->payments()->where('status', 'unpaid')->whereDate('due_date', '<', $today)->first();
            $upcomingPayment = $sale->payments()->where('status', 'unpaid')->whereDate('due_date', '>=', $today)->whereDate('due_date', '<=', $targetDate)->first();
            $statusTagihan = 'Aman';
            if ($overduePayment) {
                $statusTagihan = 'Ada Tunggakan';
            } elseif ($upcomingPayment) {
                $statusTagihan = 'Jatuh Tempo < 7 Hari';
            } elseif (($sale->outstanding_amount ?? 0) > 0) {
                $statusTagihan = 'Aktif';
            }$bookingTs = $sale->booking_date ? $sale->booking_date->timestamp : 0;
            $estimasiDate = optional(optional($sale->booking_date)?->addMonths($sale->tenor_months));
            $estimasiTs = $estimasiDate ? $estimasiDate->timestamp : 0;
            $statusColor = 'info';
            if ($sale->status === 'paid_off')
                $statusColor = 'success';
            elseif (in_array($sale->status, ['canceled', Sale::STATUS_CANCELED_HAPUS, Sale::STATUS_CANCELED_REFUND, Sale::STATUS_CANCELED_OPER_KREDIT]))
                $statusColor = 'danger';
            $statusValMap = ['canceled' => 0, Sale::STATUS_CANCELED_HAPUS => 0, Sale::STATUS_CANCELED_REFUND => 0, Sale::STATUS_CANCELED_OPER_KREDIT => 0, 'active' => 1, 'paid_off' => 2];
            $statusVal = $statusValMap[$sale->status] ?? 0;
            $statusLabel = 'Canceled';
            if ($sale->status === 'active')
                $statusLabel = 'Active';
            elseif ($sale->status === 'paid_off')
                $statusLabel = 'Paid Off';
            elseif ($sale->status === Sale::STATUS_CANCELED_HAPUS)
                $statusLabel = 'Batal (Hapus)';
            elseif ($sale->status === Sale::STATUS_CANCELED_REFUND)
                $statusLabel = 'Batal (Refund)';
            elseif ($sale->status === Sale::STATUS_CANCELED_OPER_KREDIT)
                $statusLabel = 'Oper Kredit';
            // Status DP: check actual DP payment status
            $dpPayment = $sale->payments->where('note', 'Down Payment')->first();
            if ($sale->payment_method === 'cash') {
                $statusDp = 'N/A';  // Cash Keras tidak ada DP
            } elseif ($sale->down_payment <= 0) {
                $statusDp = 'N/A';  // Tidak ada DP
            } elseif ($dpPayment && $dpPayment->status === 'paid') {
                $statusDp = 'Lunas';
            } else {
                $statusDp = 'Belum';
            }
            return ['id' => $sale->id, 'kavling' => optional($sale->lot)->project?->name . ' / ' . optional($sale->lot)->block_number, 'pembeli' => optional($sale->buyer)->name, 'buyer_phone' => optional($sale->buyer)->phone, 'tgl_booking' => optional($sale->booking_date)?->format('d M Y'), 'tgl_booking_ts' => $bookingTs, 'metode_bayar' => $sale->payment_method === 'cash' ? 'Cash Keras' : ($sale->payment_method === 'kpr' ? 'KPR Bank' : 'Angsuran In-house'), 'harga_jual' => $sale->price, 'sisa_piutang' => $sale->outstanding_amount, 'status_dp' => $statusDp, 'status' => $statusLabel, 'estimasi_lunas' => $estimasiDate?->format('M Y'), 'estimasi_ts' => $estimasiTs, 'status_value' => $statusVal, 'marketing' => optional($sale->marketer)->name, 'status_color' => $statusColor, 'status_tagihan' => $statusTagihan,];
        })->toArray();
        $sortBy = $filters['sort_by'];
        $sortDir = strtolower($filters['sort_dir']) === 'asc' ? 'asc' : 'desc';
        $penjualan = collect($penjualan)->sortBy(function ($item) use ($sortBy) {
            return match ($sortBy) { 'booking_date' => $item['tgl_booking_ts'] ?? 0, 'estimasi_lunas' => $item['estimasi_ts'] ?? 0, 'sisa_piutang' => $item['sisa_piutang'] ?? 0, 'status' => $item['status_value'] ?? 0, default => $item['tgl_booking_ts'] ?? 0, };
        }, SORT_REGULAR, $sortDir === 'desc')->values()->all();
        if ($filters['kavling'] !== '') {
            $needle = mb_strtolower($filters['kavling']);
            $penjualan = array_values(array_filter($penjualan, function ($row) use ($needle) {
                $label = mb_strtolower($row['kavling'] ?? '');
                return stripos($label, $needle) !== false;
            }));
        }
        $marketers = Marketer::all();
        if ($request->wantsJson()) {
            $kavlingSuggestions = collect($penjualan)->pluck('kavling')->filter()->unique()->take(12)->values();
            $pembeliSuggestions = collect($penjualan)->pluck('pembeli')->filter()->unique()->take(12)->values();
            return response()->json(['data' => $penjualan, 'suggestions' => ['kavling' => $kavlingSuggestions, 'pembeli' => $pembeliSuggestions,],]);
        }
        return view('penjualan.index', ['filters' => $filters, 'penjualan' => $penjualan, 'marketers' => $marketers,]);
    }
    public function show(string $id)
    {
        $sale = Sale::with(['lot.project', 'buyer', 'marketer', 'payments'])->findOrFail($id);
        $companyProfile = CompanyProfile::first();

        // Check if company profile is configured
        if (!$companyProfile || !$companyProfile->name) {
            return redirect()->route('profile.index')->with('error', 'Silahkan isi Profil Perusahaan terlebih dahulu sebelum memulai.');
        }

        $invoiceFormat = $companyProfile->invoice_format ?? 'INV/{YYYY}/{MM}/{####}';
        $receiptFormat = $companyProfile->receipt_format ?? 'KW/{YYYY}/{MM}/{####}';
        $booking = $sale->booking_date ?? now();
        $invoiceNumber = $this->formatDocumentNumber($invoiceFormat, $sale, $booking);
        $receiptNumber = $this->formatDocumentNumber($receiptFormat, $sale, $booking);

        // Calculate DP status
        $dpAmount = (int) ($sale->down_payment ?? 0);
        $dpPayment = $sale->payments->where('note', 'Down Payment')->first();
        $dpPaid = $dpPayment && $dpPayment->status === 'paid' ? $dpPayment->amount : 0;
        $dpRemaining = max(0, $dpAmount - $dpPaid);
        $dpStatus = $dpAmount > 0 ? ($dpRemaining > 0 ? 'unpaid' : 'paid') : null;

        // Calculate Cash Keras payment status with flexible payments support
        // Refresh to ensure we have latest payment data
        $sale->load('payments');

        $cashPayment = $sale->payments->where('note', 'Pembayaran Cash Keras')->first();
        $cashAmount = $cashPayment ? (int) $cashPayment->amount : 0;
        $cashStatus = $cashPayment ? $cashPayment->status : null;
        $cashPaymentId = $cashPayment ? $cashPayment->id : null;

        // Calculate flexible payments already made (for cash keras) 
        // Exclude: Booking Fee/DP (handled separately) + cash bill/final payoff entries 
        $flexiblePaid = $sale->payments
            ->where('status', 'paid')
            ->whereNotIn('note', ['Pembayaran Cash Keras', 'Pelunasan Cash Keras', 'Booking Fee', 'Down Payment'])
            ->sum('amount');
        $cashRemaining = max(0, $cashAmount - $flexiblePaid);

        // Calculate Booking Fee status
        $bfPayment = $sale->payments->where('note', 'Booking Fee')->first();
        $bfAmount = $bfPayment ? (int) $bfPayment->amount : 0;
        $bfPaid = $bfPayment && $bfPayment->status === 'paid' ? $bfAmount : 0;
        $bfRemaining = max(0, $bfAmount - $bfPaid);
        $bfStatus = $bfAmount > 0 ? ($bfRemaining > 0 ? 'unpaid' : 'paid') : null;

        $penjualan = [
            'id' => $sale->id,
            'invoice' => $invoiceNumber,
            'kavling' => optional($sale->lot)->project?->name . ' / ' . optional($sale->lot)->block_number,
            'pembeli' => optional($sale->buyer)->name,
            'buyer_phone' => optional($sale->buyer)->phone,
            'tgl_booking' => optional($sale->booking_date)?->format('d M Y'),
            'metode_bayar' => $sale->payment_method === 'cash' ? 'Cash Keras' : ($sale->payment_method === 'kpr' ? 'KPR Bank' : 'Angsuran In-house'),
            'marketing' => optional($sale->marketer)->name,
            'harga_jual' => $sale->price,
            'total_terbayar' => $sale->paid_amount,
            'sisa_piutang' => $sale->outstanding_amount,
            'tenor' => $sale->tenor_months,
            'tgl_jatuh_tempo' => $sale->due_day,
            'dp_amount' => $dpAmount,
            'dp_paid' => $dpPaid,
            'dp_remaining' => $dpRemaining + ($bfStatus === 'unpaid' ? $bfRemaining : 0),
            'dp_status' => $dpStatus,
            'dp_payment_id' => $dpPayment?->id,
            'bf_amount' => $bfAmount,
            'bf_paid' => $bfPaid,
            'bf_remaining' => $bfRemaining,
            'bf_status' => $bfStatus,
            'bf_payment_id' => $bfPayment ? $bfPayment->id : null,
            // Cash Keras data
            'cash_amount' => $cashAmount,
            'cash_status' => $cashStatus,
            'cash_payment_id' => $cashPaymentId,
            'cash_remaining' => $cashRemaining + ($bfStatus === 'unpaid' ? $bfRemaining : 0),
            'cash_flexible_paid' => $flexiblePaid,
            'company' => ['nama' => $companyProfile->name ?? 'Nama Perusahaan', 'alamat' => $companyProfile->address ?? 'Alamat Belum Diatur', 'telepon' => $companyProfile->phone ?? '-', 'email' => $companyProfile->email ?? '-', 'logo_url' => $companyProfile->logo_path ? asset('storage/' . $companyProfile->logo_path) : null,],
            'schedule' => $sale->payments->map(function ($p) {
                return ['no' => $p->id, 'jatuh_tempo' => optional($p->due_date)?->format('d M Y'), 'jumlah' => $p->amount, 'status' => $p->status,];
            })->values()->toArray(),
            'payments' => $sale->payments->map(function ($p) {
                return ['tanggal' => optional($p->due_date)?->format('d M Y'), 'keterangan' => $p->note ?? 'Pembayaran', 'jumlah' => $p->amount,];
            })->values()->toArray(),
        ];
        $buyers = Buyer::all();
        $marketers = Marketer::all();
        return view('penjualan.show', compact('penjualan', 'sale', 'receiptNumber', 'buyers', 'marketers'));
    }
    public function create(Request $request)
    {
        $lots = $this->availableLots();
        $buyers = Buyer::all();
        $marketers = Marketer::all();
        $parentSaleId = $request->query('from_sale');
        $buyerId = $request->query('buyer_id');
        return view('penjualan.create', compact('lots', 'buyers', 'marketers', 'parentSaleId', 'buyerId'));
    }
    public function store(SaleRequest $request)
    {
        $data = $request->validated();
        $base = (int) ($data['base_price'] ?? 0);
        $discount = (int) ($data['discount'] ?? 0);
        $ppjb = (int) ($data['extra_ppjb'] ?? 0);
        $shm = (int) ($data['extra_shm'] ?? 0);
        $other = (int) ($data['extra_other'] ?? 0);
        $bookingFee = (int) ($data['booking_fee'] ?? 0);
        $includeBookingFee = $request->has('booking_fee_included') && $request->input('booking_fee_included');

        // Harga Netto = Harga Dasar - Diskon
        // Grand Total = Harga Netto + Biaya Tambahan (PPJB, SHM, Lain) + Booking Fee (selalu)
        // Jika dicentang: Booking Fee masuk ke harga jual (dihitung sebagai penerimaan)
        $netPrice = max(0, $base - $discount);
        $grandTotal = $netPrice + $ppjb + $shm + $other + ($includeBookingFee ? 0 : $bookingFee);

        // Price for revenue: add booking fee only if NOT checked (not already included in base price)
        $salePrice = $netPrice + $ppjb + $shm + $other + ($includeBookingFee ? 0 : $bookingFee);

        // Set common data - use salePrice (may or may not include booking fee based on checkbox)
        $data['price'] = $salePrice;

        // Handle Cash Keras - create unpaid payment record, NOT auto paid_off
        if ($data['payment_method'] === 'cash') {
            $data['down_payment'] = 0;
            $data['tenor_months'] = 0;
            $data['due_day'] = null;
            $data['paid_amount'] = 0;
            $data['outstanding_amount'] = $grandTotal;
            $data['status'] = 'active';

            $sale = Sale::create($data);

            $this->syncBookingFeePayment($sale, $bookingFee);

            // Create payment record with unpaid status - user must manually pay
            $sale->payments()->create([
                'due_date' => $sale->booking_date ?? now(),
                'amount' => max(0, $grandTotal - $bookingFee),
                'status' => 'unpaid',
                'note' => "Pembayaran Cash Keras",
                'paid_at' => null,
            ]);

            // Update lot status to 'sold'
            if ($sale->lot) {
                $sale->lot->update(['status' => 'sold']);
            }

            return redirect()->route('penjualan.index')->with('success', 'Penjualan ditambahkan');
        }

        // Handle KPR Bank - full payment to developer via Bank, but generate schedule for reference
        if ($data['payment_method'] === 'kpr') {
            $dpPercent = (float) ($data['dp_percent'] ?? 0);
            $dpInput = (int) ($data['down_payment'] ?? 0);
            $dp = $dpInput > 0 ? $dpInput : (int) round($grandTotal * ($dpPercent / 100));

            $data['down_payment'] = $dp;
            // Capture KPR tenor for schedule reference
            $data['tenor_months'] = (int) ($data['tenor_months'] ?? 0);
            $data['due_day'] = max(1, min(28, (int) ($data['due_day'] ?? 1)));

            // Initial state: Paid 0, Outstanding = Price (until sync fixes it)
            // Note: Developer receivables are technically only DP + Disbursement.
            // But before verify, we can just set it to defaults and let sync handle it.
            $data['paid_amount'] = 0;
            $data['outstanding_amount'] = $grandTotal;
            $data['status'] = 'active';

            $sale = Sale::create($data);

            $this->syncBookingFeePayment($sale, $bookingFee);

            // Build schedule if tenor > 0
            if ($data['tenor_months'] > 0) {
                $this->rebuildSchedule($sale);
            }

            $this->syncDownPaymentHistory($sale);

            // Update lot status to 'sold'
            if ($sale->lot) {
                $sale->lot->update(['status' => 'sold']);
            }

            return redirect()->route('penjualan.index')->with('success', 'Penjualan ditambahkan');
        }

        // Handle Installment only (Angsuran In-house)
        $tenor = (int) ($data['tenor_months'] ?? 0);
        $dueDay = max(1, min(28, (int) ($data['due_day'] ?? 1)));
        $dpPercent = (float) ($data['dp_percent'] ?? 0);
        $dpInput = (int) ($data['down_payment'] ?? 0);
        $dp = $dpInput > 0 ? $dpInput : (int) round($grandTotal * ($dpPercent / 100));

        // Handle DP 100% or DP >= price - treat as full payment
        if ($dp >= $grandTotal || $dpPercent >= 100) {
            $data['down_payment'] = $grandTotal;
            $data['tenor_months'] = 0;
            $data['due_day'] = null;
            $data['paid_amount'] = $grandTotal;
            $data['outstanding_amount'] = 0;
            $data['status'] = 'paid_off';

            $sale = Sale::create($data);

            $this->syncBookingFeePayment($sale, $bookingFee);

            // Create single DP payment record
            $sale->payments()->create([
                'due_date' => $sale->booking_date ?? now(),
                'amount' => max(0, $grandTotal - $bookingFee),
                'status' => 'paid',
                'note' => 'Down Payment (100%)',
                'paid_at' => $sale->booking_date ?? now(),
            ]);

            // Update lot status to 'sold'
            if ($sale->lot) {
                $sale->lot->update(['status' => 'sold']);
            }

            return redirect()->route('penjualan.index')->with('success', 'Penjualan ditambahkan');
        }

        $data['down_payment'] = $dp;
        $data['tenor_months'] = $tenor;
        $data['due_day'] = $dueDay;
        // Initial state: Paid 0, Outstanding Full Price (DP + Installments)
        // syncDownPaymentHistory will adjust if DP is actually paid later (but usually starts unpaid)
        $data['paid_amount'] = 0;
        $data['outstanding_amount'] = $grandTotal;
        $data['status'] = 'active';

        $sale = Sale::create($data);
        $this->syncBookingFeePayment($sale, $bookingFee);
        $this->rebuildSchedule($sale);      // Build Installments first
        $this->syncDownPaymentHistory($sale); // Then Calculate Totals

        // Update lot status to 'sold'
        if ($sale->lot) {
            $sale->lot->update(['status' => 'sold']);
        }

        return redirect()->route('penjualan.index')->with('success', 'Penjualan ditambahkan');
    }
    public function destroy(Sale $penjualan)
    {
        $penjualan->delete();
        return redirect()->route('penjualan.index')->with('success', 'Penjualan dihapus');
    }
    public function edit(Sale $penjualan)
    {
        $lots = $this->availableLots($penjualan);
        $buyers = Buyer::all();
        $marketers = Marketer::all();
        return view('penjualan.edit', ['sale' => $penjualan, 'lots' => $lots, 'buyers' => $buyers, 'marketers' => $marketers,]);
    }
    public function update(SaleRequest $request, Sale $penjualan)
    {
        if ($request->has('notes_only')) {
            $penjualan->notes = $request->input('notes');
            $penjualan->save();
            return redirect()->route('penjualan.show', $penjualan)->with('success', 'Catatan berhasil diperbarui');
        }

        $data = $request->validated();
        $base = (int) ($data['base_price'] ?? 0);
        $discount = (int) ($data['discount'] ?? 0);
        $ppjb = (int) ($data['extra_ppjb'] ?? 0);
        $shm = (int) ($data['extra_shm'] ?? 0);
        $other = (int) ($data['extra_other'] ?? 0);
        $bookingFee = (int) ($data['booking_fee'] ?? 0);
        $includeBookingFee = $request->has('booking_fee_included') && $request->input('booking_fee_included');

        // Harga Netto = Harga Dasar - Diskon
        // Grand Total = Harga Netto + Biaya Tambahan (PPJB, SHM, Lain) + Booking Fee (selalu)
        // Jika dicentang: Booking Fee masuk ke harga jual (dihitung sebagai penerimaan)
        $netPrice = max(0, $base - $discount);
        $grandTotal = $netPrice + $ppjb + $shm + $other + ($includeBookingFee ? 0 : $bookingFee);

        // Price for revenue: add booking fee only if NOT checked (not already included in base price)
        $salePrice = $netPrice + $ppjb + $shm + $other + ($includeBookingFee ? 0 : $bookingFee);

        // Set common data - use salePrice (may or may not include booking fee based on checkbox)
        $data['price'] = $salePrice;

        // Handle Cash Keras - full payment
        if ($data['payment_method'] === 'cash') {
            $data['down_payment'] = 0;
            $data['tenor_months'] = 0;
            $data['due_day'] = null;
            $data['paid_amount'] = 0;
            $data['outstanding_amount'] = $salePrice;
            $data['status'] = 'active';

            $penjualan->update($data);

            $penjualan->payments()->delete();

            $this->syncBookingFeePayment($penjualan, $bookingFee);

            $penjualan->payments()->create([
                'due_date' => $penjualan->booking_date ?? now(),
                'amount' => max(0, $salePrice - $bookingFee),
                'status' => 'unpaid',
                'note' => "Pembayaran Cash Keras",
                'paid_at' => null,
            ]);

            return redirect()->route('penjualan.index')->with('success', 'Penjualan diperbarui');
        }

        // Handle KPR Bank
        if ($data['payment_method'] === 'kpr') {
            $dpPercent = (float) ($data['dp_percent'] ?? 0);
            $dpInput = (int) ($data['down_payment'] ?? 0);
            $dp = $dpInput > 0 ? $dpInput : (int) round($salePrice * ($dpPercent / 100));

            $data['down_payment'] = $dp;
            $data['tenor_months'] = 0;
            $data['due_day'] = null;

            if ($dp <= 0) {
                // No DP - Full Payment
                $data['paid_amount'] = $salePrice;
                $data['outstanding_amount'] = 0;
                $data['status'] = 'paid_off';
                $penjualan->update($data);

                $penjualan->payments()->delete();

                $this->syncBookingFeePayment($penjualan, $bookingFee);

                $penjualan->payments()->create([
                    'due_date' => $penjualan->booking_date ?? now(),
                    'amount' => max(0, $salePrice - $bookingFee),
                    'status' => 'paid',
                    'note' => "Pembayaran Penuh (KPR Bank)",
                    'paid_at' => $penjualan->booking_date ?? now(),
                ]);
            } else {
                // DP Exists
                $data['paid_amount'] = max(0, $salePrice - $dp);
                $data['outstanding_amount'] = $dp;
                $data['status'] = 'active';

                $penjualan->update($data);

                $this->syncBookingFeePayment($penjualan, $bookingFee);

                // Sync/Update DP Payment
                $this->syncDownPaymentHistory($penjualan);
            }

            return redirect()->route('penjualan.index')->with('success', 'Penjualan diperbarui');
        }

        // Handle Installment only (Angsuran In-house)
        $tenor = (int) ($data['tenor_months'] ?? 0);
        $dueDay = max(1, min(28, (int) ($data['due_day'] ?? 1)));
        $dpPercent = (float) ($data['dp_percent'] ?? 0);
        $dpInput = (int) ($data['down_payment'] ?? 0);
        $dp = $dpInput > 0 ? $dpInput : (int) round($salePrice * ($dpPercent / 100));

        // Handle DP 100% or DP >= price - treat as full payment (same as cash)
        if ($dp >= $salePrice || $dpPercent >= 100) {
            $data['down_payment'] = $salePrice;
            $data['tenor_months'] = 0;
            $data['due_day'] = null;
            $data['paid_amount'] = $salePrice;
            $data['outstanding_amount'] = 0;
            $data['status'] = 'paid_off';

            $penjualan->update($data);

            // Delete all existing payments and create single DP payment
            $penjualan->payments()->delete();

            $this->syncBookingFeePayment($penjualan, $bookingFee);

            $penjualan->payments()->create([
                'due_date' => $penjualan->booking_date ?? now(),
                'amount' => max(0, $salePrice - $bookingFee),
                'status' => 'paid',
                'note' => 'Down Payment (100%)',
                'paid_at' => $penjualan->booking_date ?? now(),
            ]);

            return redirect()->route('penjualan.index')->with('success', 'Penjualan diperbarui');
        }

        $outstandingFromSchedule = $penjualan->payments()->whereIn('status', ['unpaid', 'partial', 'overdue'])->sum('amount');
        $paidSum = $penjualan->payments()->where('status', 'paid')->sum('amount');
        $dpBuffer = $penjualan->payments()->where('note', 'Down Payment')->exists() ? 0 : $dp;

        if ($outstandingFromSchedule > 0) {
            $paidAmount = max(0, $salePrice - $outstandingFromSchedule);
            $outstanding = $outstandingFromSchedule;
        } else {
            $paidAmount = min($salePrice, $dpBuffer + $paidSum);
            $outstanding = max(0, $salePrice - $paidAmount);
        }

        $data['down_payment'] = $dp;
        $data['tenor_months'] = $tenor;
        $data['due_day'] = $dueDay;
        $data['paid_amount'] = $paidAmount;
        $data['outstanding_amount'] = $outstanding;
        $data['status'] = $outstanding > 0 ? 'active' : 'paid_off';

        $penjualan->update($data);
        $this->syncBookingFeePayment($penjualan, $bookingFee);
        $this->rebuildSchedule($penjualan);      // Build/Update Installments first
        $this->syncDownPaymentHistory($penjualan); // Then Recalculate Totals based on new structure

        return redirect()->route('penjualan.index')->with('success', 'Penjualan diperbarui');
    }
    private function formatDocumentNumber(string $format, Sale $sale, Carbon $date): string
    {
        $replaced = str_replace(['{YYYY}', '{MM}', '{DD}', '{####}'], [$date->format('Y'), $date->format('m'), $date->format('d'), str_pad((string) $sale->id, 4, '0', STR_PAD_LEFT)], $format);
        return $replaced;
    }
    private function rebuildSchedule(Sale $sale): void
    {
        // Calculate amount that needs to be covered by installments
        // Total Price - DP - Already Paid Installments

        $price = (int) $sale->price;
        $dp = (int) $sale->down_payment;
        $paidInstallments = $sale->payments()
            ->where('status', 'paid')
            ->where(function ($q) {
                $q->whereNull('note')->orWhere('note', 'like', 'Angsuran%');
            })->sum('amount');

        $bookingFee = $sale->payments()->where('note', 'Booking Fee')->sum('amount');

        // The remaining principal to be split into FUTURE installments
        $outstandingForSchedule = max(0, $price - $dp - $paidInstallments - $bookingFee);

        if ($outstandingForSchedule <= 0 || ($sale->tenor_months ?? 0) <= 0) {
            $sale->payments()->where('status', 'unpaid')->where('note', 'like', 'Angsuran%')->delete();
            return;
        }

        $paid = $sale->payments()->where('status', 'paid')->where(function ($q) {
            $q->whereNull('note')->orWhere('note', 'like', 'Angsuran%');
        })->orderBy('due_date')->get();
        $paidCount = $paid->count();
        $remainingTenor = max(1, (int) $sale->tenor_months - $paidCount);

        $baseDate = $paid->last()?->due_date ?? ($sale->booking_date ?? Carbon::now());
        $day = max(1, min(28, (int) ($sale->due_day ?? ($baseDate instanceof Carbon ? $baseDate->day : 1))));
        $startDate = ($baseDate instanceof Carbon ? $baseDate->copy() : Carbon::parse($baseDate ?? now()))->day($day);

        // Logic for start date: if first installment, start next month? Or same month?
        // Usually if booking date is today, first installment is next month.
        if ($paid->last()) {
            $startDate->addMonth();
        } else {
            // First installment
            $startDate->addMonth();
        }

        // Only delete UNPAID installments to regenerate them
        // Also delete kpr_bank status if KPR
        $statusesToDelete = ['unpaid'];
        if ($sale->payment_method === 'kpr') {
            $statusesToDelete[] = 'kpr_bank';
        }

        $sale->payments()->whereIn('status', $statusesToDelete)->where(function ($q) {
            $q->where('note', 'like', 'Angsuran%')->orWhereNull('note');
        })->delete();

        $perTerm = intdiv($outstandingForSchedule, $remainingTenor);
        $remainder = $outstandingForSchedule - ($perTerm * $remainingTenor);

        // Determine status and note prefix based on method
        $status = $sale->payment_method === 'kpr' ? 'kpr_bank' : 'unpaid';
        $notePrefix = $sale->payment_method === 'kpr' ? 'Angsuran Bank ke-' : 'Angsuran ke-';

        for ($i = 0; $i < $remainingTenor; $i++) {
            $amount = $perTerm + ($i < $remainder ? 1 : 0);
            $dueDate = $startDate->copy()->addMonths($i);
            $sale->payments()->create(['due_date' => $dueDate, 'amount' => $amount, 'status' => $status, 'note' => $notePrefix . ($paidCount + $i + 1),]);
        }
    }
    private function syncBookingFeePayment(Sale $sale, int $amount): void
    {
        $payment = $sale->payments()->where('note', 'Booking Fee')->first();
        if ($amount > 0) {
            if (!$payment) {
                // Create Booking Fee payment with 'unpaid' status
                $sale->payments()->create([
                    'due_date' => $sale->booking_date ?? now(),
                    'amount' => $amount,
                    'status' => 'unpaid',
                    'note' => 'Booking Fee',
                    'paid_at' => null,
                ]);
            } else {
                // Update amount but preserve status
                $payment->update(['amount' => $amount]);
            }
        } elseif ($payment) {
            $payment->delete();
        }
    }

    private function syncDownPaymentHistory(Sale $sale): void
    {
        $dpAmount = max(0, (int) $sale->down_payment);
        $dpPayment = $sale->payments()->where('note', 'Down Payment')->first();
        if ($dpAmount > 0) {
            if (!$dpPayment) {
                // Create DP payment with 'unpaid' status - user must pay separately
                $dpPayment = $sale->payments()->create([
                    'due_date' => $sale->booking_date ?? now(),
                    'amount' => $dpAmount,
                    'status' => 'unpaid',  // Initially unpaid
                    'note' => 'Down Payment',
                    'paid_at' => null,
                ]);
            } else {
                // Preserve existing status when updating amount
                $dpPayment->update([
                    'amount' => $dpAmount,
                    'due_date' => $sale->booking_date ?? $dpPayment->due_date ?? now(),
                    'note' => 'Down Payment',
                    // Keep existing status and paid_at  
                ]);
            }
        } elseif ($dpPayment) {
            $dpPayment->delete();
        }
        $outstandingFromSchedule = $sale->payments()->whereIn('status', ['unpaid', 'partial', 'overdue', 'kpr_bank'])->sum('amount');
        $paidSum = $sale->payments()->where('status', 'paid')->sum('amount');

        // Strict Accounting for ALL types (Cash, KPR, In-house)
        // Paid = What is in the payment records.
        // Outstanding = Price - Paid.

        $sale->paid_amount = min($sale->price, $paidSum);
        $sale->outstanding_amount = max(0, $sale->price - $sale->paid_amount);

        // Status Determination
        if ($sale->outstanding_amount <= 0) {
            $sale->status = 'paid_off';
        } else {
            // For KPR, even if Outstanding > 0 (Bank portion), we might want to flag it specially?
            // Current logic: If Outstanding > 0 -> Active.
            // But verify manual override isn't overwritten? 
            // Actually, if we use strict accounting, 'paid_off' ONLY happens if paid >= price.
            // So if Bank hasn't paid, it MUST be active.

            if ($sale->status === 'paid_off') {
                // If it was marked paid_off but math says otherwise, revert to active?
                // Exception: Maybe slight rounding errors? Ignoring for now.
                $sale->status = 'active';
            }

            // Allow manual 'active' status for specific workflows if needed, 
            // but generally logic dictates status.
            // However, keep existing status if it is not 'paid_off' to preserve specific canceled statuses?
            if (!in_array($sale->status, ['canceled', Sale::STATUS_CANCELED_HAPUS, Sale::STATUS_CANCELED_REFUND, Sale::STATUS_CANCELED_OPER_KREDIT])) {
                $sale->status = 'active';
            }
        }

        $sale->save();
    }

    public function approveKpr(Sale $sale)
    {
        if ($sale->payment_method !== 'kpr') {
            return back()->with('error', 'Hanya untuk penjualan KPR');
        }

        // Refresh model to ensure we have latest state
        $sale->refresh();

        // Calculate actual paid amount from transactions
        $totalPaid = $sale->payments()->where('status', 'paid')->sum('amount');

        // Use price from model
        $price = $sale->price;

        $remaining = max(0, $price - $totalPaid);

        if ($remaining > 0) {
            $sale->payments()->create([
                'due_date' => now(),
                'amount' => $remaining,
                'status' => 'paid',
                'note' => 'Pencairan KPR Bank',
                'paid_at' => now(),
            ]);
        }

        // Delete KPR Bank installments to resolve outstanding amount (replaced by Paid disbursement)
        $sale->payments()->where('status', 'kpr_bank')->delete();

        // Recalculate totals
        $this->syncDownPaymentHistory($sale);

        return back()->with('success', 'KPR Disetujui. Pembayaran Bank tercatat.');
    }

    public function payOffCash(Sale $sale)
    {
        if ($sale->payment_method !== 'cash') {
            return back()->with('error', 'Hanya untuk penjualan Cash Keras');
        }

        // Auto-settle Booking Fee if unpaid (since user is paying full amount)
        $bfPayment = $sale->payments()->where('note', 'Booking Fee')->where('status', 'unpaid')->first();
        if ($bfPayment) {
            $bfPayment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        // Find the unpaid cash payment
        $cashPayment = $sale->payments()->where('note', 'Pembayaran Cash Keras')->where('status', 'unpaid')->first();

        if (!$cashPayment) {
            return back()->with('error', 'Tidak ada pembayaran Cash Keras yang belum lunas');
        }

        // Calculate flexible payments already made (exclude booking fee/DP and cash bill/final payoff entries) 
        $flexiblePaid = $sale->payments()
            ->where('status', 'paid')
            ->whereNotIn('note', ['Pembayaran Cash Keras', 'Pelunasan Cash Keras', 'Booking Fee', 'Down Payment'])
            ->sum('amount');

        $remaining = max(0, $cashPayment->amount - $flexiblePaid);

        if ($remaining > 0) {
            // Create a new payment record for the remaining amount
            $sale->payments()->create([
                'due_date' => now(),
                'amount' => $remaining,
                'status' => 'paid',
                'note' => 'Pelunasan Cash Keras',
                'paid_at' => now(),
            ]);
        }

        // Delete the original unpaid "bill" record to avoid double counting
        // (because we now have flexible payments + pelunasan record = Total Price)
        $cashPayment->delete();

        // Recalculate totals
        $this->syncDownPaymentHistory($sale);

        return back()->with('success', 'Penjualan Cash Keras berhasil dilunasi.');
    }
}


