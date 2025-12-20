<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Store a flexible payment with Waterfall allocation.
     * Allocates payment to oldest unpaid installments first.
     * Supports PARTIAL PAYMENT - reduces installment amounts.
     * DOES NOT create separate records for allocated payments (keeps list clean).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'note' => 'nullable|string|max:255',
        ]);

        $sale = Sale::findOrFail($data['sale_id']);
        if ($sale->status === 'paid_off' || (int) ($sale->outstanding_amount ?? 0) <= 0) {
            $message = 'Transaksi sudah lunas. Pembayaran fleksibel tidak diperlukan.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return back()->withErrors(['msg' => $message]);
        }
        $paymentDate = $data['date'];
        $remainingAmount = (int) $data['amount'];
        $allocatedTotal = 0;
        $overpayAmount = 0;
        $paymentNote = trim((string) ($data['note'] ?? ''));

        $this->ensurePartialStatusAllowed();

        // Debug info to trace active database during runtime
        Log::info('flex-payment-db-debug', [
            'default_connection' => config('database.default'),
            'default_db' => DB::connection()->getDatabaseName(),
            'nativephp_db' => DB::connection('nativephp')->getDatabaseName(),
            'sqlite_db' => DB::connection('sqlite')->getDatabaseName(),
        ]);

        DB::beginTransaction();
        try {
            // Lock unpaid/partial installments ordered by due_date (oldest first)
            $installments = $sale->payments()
                ->whereIn('status', ['unpaid', 'overdue', 'partial'])
                ->orderBy('due_date', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($installments as $installment) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $installmentAmount = (int) $installment->amount;
                $toAllocate = min($remainingAmount, $installmentAmount);

                if ($toAllocate >= $installmentAmount) {
                    $installment->status = 'paid';
                    $installment->paid_at = $paymentDate;
                    $installment->save();
                } else {
                    $installment->amount = $installmentAmount - $toAllocate;
                    $installment->status = 'partial';
                    $installment->save();
                }

                $remainingAmount -= $toAllocate;
                $allocatedTotal += $toAllocate;
            }

            // Catat satu entry pembayaran untuk riwayat (total yang dibayarkan user)
            Payment::create([
                'sale_id' => $sale->id,
                'due_date' => $paymentDate,
                'amount' => (int) $data['amount'],
                'status' => 'paid',
                'paid_at' => $paymentDate,
                'note' => $paymentNote !== '' ? $paymentNote : 'Pembayaran Fleksibel',
            ]);

            $this->recalculateSale($sale);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $message = $allocatedTotal > 0
            ? 'Pembayaran Rp ' . number_format($allocatedTotal, 0, ',', '.') . ' berhasil dialokasikan.'
            : 'Pembayaran berhasil dicatat.';

        if ($overpayAmount > 0) {
            $message .= ' Kelebihan Rp ' . number_format($overpayAmount, 0, ',', '.') . ' dicatat.';
        }

        return redirect()
            ->route('penjualan.show', $sale)
            ->with('success', $message);
    }

    /**
     * Mark a single payment/installment as paid.
     */
    public function update(Request $request, Payment $payment)
    {
        $payment->status = 'paid';
        $payment->paid_at = now();
        $payment->save();

        $sale = $payment->sale;
        $this->recalculateSale($sale);

        return redirect()
            ->route('penjualan.show', $sale)
            ->with('success', 'Pembayaran berhasil dicatat.');
    }

    /**
     * Recalculate sale's paid_amount and outstanding_amount.
     * Uses TOTAL PRICE - REMAINING UNPAID method to handle partial reductions.
     */
    private function recalculateSale(Sale $sale): void
    {
        $outstandingFromSchedule = $sale->payments()
            ->whereIn('status', ['unpaid', 'overdue', 'partial'])
            ->sum('amount');

        $paidSum = $sale->payments()
            ->where('status', 'paid')
            ->sum('amount');

        $dpBuffer = $sale->payments()->where('note', 'Down Payment')->exists()
            ? 0
            : (int) ($sale->down_payment ?? 0);

        if ($outstandingFromSchedule > 0) {
            $sale->outstanding_amount = $outstandingFromSchedule;
            $sale->paid_amount = max(0, $sale->price - $outstandingFromSchedule);
        } else {
            // Fallback ke pembayaran yang tercatat (termasuk DP & fleksibel)
            $totalPaid = $paidSum + $dpBuffer;
            $sale->paid_amount = min($sale->price, $totalPaid);
            $sale->outstanding_amount = max(0, $sale->price - $sale->paid_amount);
        }

        $sale->status = $sale->outstanding_amount <= 0 ? 'paid_off' : 'active';
        $sale->save();
    }

    /**
     * Ensure column payments.status accepts 'partial' (self-heal if old CHECK/enum still exists).
     */
    private function ensurePartialStatusAllowed(): void
    {
        $conn = DB::connection();
        $driver = $conn->getDriverName();

        if ($driver === 'sqlite') {
            $row = $conn->selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name='payments'");
            $tableSql = $row->sql ?? '';
            $hasPartial = stripos($tableSql, 'partial') !== false;
            $hasCheck = stripos($tableSql, 'check') !== false;

            if (!$hasPartial || $hasCheck) {
                $conn->statement('PRAGMA foreign_keys=OFF;');
                $conn->statement("
                    CREATE TABLE IF NOT EXISTS payments_temp (
                        id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                        sale_id INTEGER,
                        due_date DATE,
                        amount INTEGER DEFAULT 0 NOT NULL,
                        status VARCHAR(255) DEFAULT 'unpaid' NOT NULL,
                        note VARCHAR(255),
                        created_at DATETIME,
                        updated_at DATETIME,
                        paid_at DATETIME
                    );
                ");
                $conn->statement("
                    INSERT INTO payments_temp (id, sale_id, due_date, amount, status, note, created_at, updated_at, paid_at)
                    SELECT id, sale_id, due_date, amount, status, note, created_at, updated_at, paid_at FROM payments;
                ");
                $conn->statement("DROP TABLE payments;");
                $conn->statement("ALTER TABLE payments_temp RENAME TO payments;");
                $conn->statement('PRAGMA foreign_keys=ON;');
                Log::info('flex-payment-db-heal-sqlite', ['db' => $conn->getDatabaseName()]);
            }
        } elseif ($driver === 'mysql') {
            $conn->statement("ALTER TABLE payments MODIFY status VARCHAR(20) NOT NULL DEFAULT 'unpaid'");
            Log::info('flex-payment-db-heal-mysql');
        } elseif ($driver === 'pgsql') {
            $conn->statement("ALTER TABLE payments ALTER COLUMN status TYPE VARCHAR(20);");
            $conn->statement("ALTER TABLE payments ALTER COLUMN status SET DEFAULT 'unpaid';");
            Log::info('flex-payment-db-heal-pgsql');
        }
    }
}
