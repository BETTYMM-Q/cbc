<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Explicit platform reset requested by the owner: retain the paid
        // purchase ledger, but bring every usable wallet to zero and record
        // an auditable balancing adjustment for each non-zero school.
        DB::transaction(function (): void {
            DB::table('schools')->orderBy('id')->get(['id', 'sms_credits'])->each(function (object $school): void {
                $balance = (int) $school->sms_credits;
                if ($balance > 0) {
                    DB::table('sms_credit_transactions')->insert([
                        'school_id' => $school->id, 'amount' => -$balance, 'type' => 'adjustment',
                        'note' => 'Platform SMS wallet reset; future credits require a confirmed purchase.',
                        'created_by' => null, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                DB::table('schools')->where('id', $school->id)->update(['sms_credits' => 0, 'updated_at' => now()]);
            });
        });
    }

    public function down(): void
    {
        // A reset is deliberately irreversible: historic purchased credits
        // must not be silently restored without a new confirmed payment.
    }
};
