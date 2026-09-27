<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // A pending or capacity-held order created before the wallet reset
        // must never replenish the new zero-based wallets via a late callback.
        DB::table('sms_credit_orders')->whereIn('status', ['pending', 'awaiting_allocation'])->update([
            'status' => 'failed',
            'failure_reason' => 'Cancelled by the platform-wide SMS wallet reset. Start a new purchase to receive credits.',
            'updated_at' => now(),
        ]);
    }
    public function down(): void { /* cancelled pre-reset orders are not restored */ }
};
