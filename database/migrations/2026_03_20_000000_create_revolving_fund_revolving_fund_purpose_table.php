<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('revolving_fund_revolving_fund_purpose', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('revolving_fund_id');
            $table->unsignedBigInteger('revolving_fund_purpose_id');
            $table->timestamps();
            $table->unique(['revolving_fund_id', 'revolving_fund_purpose_id'], 'revolving_fund_purpose_unique');

            $table->foreign('revolving_fund_id', 'rf_purpose_fund_fk')
                ->references('id')
                ->on('revolving_funds')
                ->cascadeOnDelete();

            $table->foreign('revolving_fund_purpose_id', 'rf_purpose_fk')
                ->references('id')
                ->on('revolving_fund_purposes')
                ->cascadeOnDelete();
        });

        if (! Schema::hasColumn('revolving_funds', 'revolving_fund_purpose_id')) {
            return;
        }

        $existing = DB::table('revolving_funds')
            ->select('id', 'revolving_fund_purpose_id', 'created_at', 'updated_at')
            ->whereNotNull('revolving_fund_purpose_id')
            ->get();

        foreach ($existing as $fund) {
            DB::table('revolving_fund_revolving_fund_purpose')->updateOrInsert(
                [
                    'revolving_fund_id' => $fund->id,
                    'revolving_fund_purpose_id' => $fund->revolving_fund_purpose_id,
                ],
                [
                    'created_at' => $fund->created_at ?? now(),
                    'updated_at' => $fund->updated_at ?? now(),
                ]
            );
        }

        Schema::table('revolving_funds', function (Blueprint $table) {
            $table->dropColumn('revolving_fund_purpose_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revolving_funds', function (Blueprint $table) {
            if (! Schema::hasColumn('revolving_funds', 'revolving_fund_purpose_id')) {
                $table->unsignedInteger('revolving_fund_purpose_id')->nullable()->after('revolving_fund_mode_of_transfer_id');
            }
        });

        if (Schema::hasTable('revolving_fund_revolving_fund_purpose')) {
            $pivotData = DB::table('revolving_fund_revolving_fund_purpose')
                ->select('revolving_fund_id', 'revolving_fund_purpose_id')
                ->get()
                ->groupBy('revolving_fund_id');

            foreach ($pivotData as $fundId => $purposes) {
                $primaryPurposeId = optional($purposes->first())->revolving_fund_purpose_id;

                if ($primaryPurposeId) {
                    DB::table('revolving_funds')
                        ->where('id', $fundId)
                        ->update(['revolving_fund_purpose_id' => $primaryPurposeId]);
                }
            }

            Schema::dropIfExists('revolving_fund_revolving_fund_purpose');
        }
    }
};
