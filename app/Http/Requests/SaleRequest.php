<?php
namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $saleId = $this->route('penjualan')?->id;
        $canceledStatuses = [
            'canceled',
            Sale::STATUS_CANCELED_HAPUS,
            Sale::STATUS_CANCELED_REFUND,
            Sale::STATUS_CANCELED_OPER_KREDIT,
        ];

        return [
            'lot_id' => [
                'required',
                Rule::exists('lots', 'id'),
                Rule::unique('sales', 'lot_id')
                    ->where(fn($q) => $q->whereNotIn('status', $canceledStatuses))
                    ->ignore($saleId),
            ],
            'buyer_id' => ['required', 'exists:buyers,id'],
            'marketer_id' => ['nullable', 'exists:marketers,id'],
            'booking_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,installment,kpr'],
            'price' => ['nullable', 'integer', 'min:0'],
            'base_price' => ['required', 'integer', 'min:0'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'extra_ppjb' => ['nullable', 'integer', 'min:0'],
            'extra_shm' => ['nullable', 'integer', 'min:0'],
            'extra_other' => ['nullable', 'integer', 'min:0'],
            'down_payment' => ['nullable', 'integer', 'min:0'],
            'dp_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tenor_months' => [
                Rule::requiredIf(fn() => $this->input('payment_method') === 'installment'),
                'nullable',
                'integer',
                'min:1',
                'max:480',
            ],
            'due_day' => [
                Rule::requiredIf(fn() => $this->input('payment_method') === 'installment'),
                'nullable',
                'integer',
                'between:1,28',
            ],
            'parent_sale_id' => ['nullable', 'exists:sales,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
