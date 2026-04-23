<?php

namespace App\Jobs;

use App\Models\StudentBilling;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BroadcastArrearsWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  array<int>  $billingIds
     */
    public function __construct(
        public array $billingIds,
        public ?string $customMessage = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsApp): void
    {
        $billings = StudentBilling::with(['student.profiles.profileable', 'feeCategory'])
            ->whereIn('id', $this->billingIds)
            ->where('status', '!=', 'paid')
            ->get();

        foreach ($billings as $billing) {
            $student = $billing->student;
            $profile = $student?->profiles?->first()?->profileable;

            // Priority: guardian_phone, then student phone, then user phone
            $target = $profile?->guardian_phone ?: ($profile?->phone ?: $student?->phone);

            if (! $target) {
                Log::info("Broadcast WhatsApp skipped for student: {$student?->name} (No phone number)");

                continue;
            }

            // Cleanup target phone number (remove non-digits, ensure standard format if needed)
            $target = preg_replace('/[^0-9]/', '', $target);
            if (empty($target)) {
                continue;
            }

            $amount = $billing->amount - $billing->paid_amount;
            $formattedAmount = 'Rp '.number_format((float) $amount, 0, ',', '.');

            $message = $this->customMessage ?: "Assalamu'alaikum, Bapak/Ibu Wali Murid dari {student_name}.\n\nKami menginformasikan bahwa terdapat tagihan {fee_name} periode {month} sebesar {amount} yang belum terlunasi.\n\nHarap segera melakukan konfirmasi atau pembayaran. Terima kasih.";

            $message = strtr($message, [
                '{student_name}' => $student?->name ?? 'Siswa',
                '{fee_name}' => $billing->feeCategory?->name ?? 'Biaya Pendidikan',
                '{month}' => $billing->month ?? '-',
                '{amount}' => $formattedAmount,
            ]);

            $whatsApp->sendMessage($target, $message);

            // Small delay to prevent carrier-level spam detection if sending many
            if ($billings->count() > 10) {
                usleep(500000); // 0.5 sec
            }
        }
    }
}
