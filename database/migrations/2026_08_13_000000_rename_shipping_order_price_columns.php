<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_orders', function (Blueprint $table) {
            $table->renameColumn('product_price', 'amount');
            $table->renameColumn('cod_amount', 'shipping_cost');
        });

        $rows = DB::table('shipping_orders')->select(['id', 'raw_payload'])->get();
        foreach ($rows as $row) {
            $payload = json_decode((string) $row->raw_payload, true);
            if (! is_array($payload)) {
                continue;
            }

            $update = [];

            $gross = $payload['gross_revenue'] ?? '';
            if ($gross !== '' && $gross !== null) {
                $update['amount'] = $this->cleanDecimal($gross);
            }

            $shipping = $payload['shipping_cost'] ?? '';
            if ($shipping !== '' && $shipping !== null) {
                $update['shipping_cost'] = $this->cleanDecimal($shipping);
            } else {
                $update['shipping_cost'] = 0;
            }

            if ($update) {
                DB::table('shipping_orders')->where('id', $row->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        Schema::table('shipping_orders', function (Blueprint $table) {
            $table->renameColumn('amount', 'product_price');
            $table->renameColumn('shipping_cost', 'cod_amount');
        });
    }

    private function cleanDecimal($val): float
    {
        $val = str_replace(['.', ','], ['', ','], (string) $val);
        if (str_contains($val, ',')) {
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
        }

        return (float) preg_replace('/[^0-9.\-]/', '', $val);
    }
};
