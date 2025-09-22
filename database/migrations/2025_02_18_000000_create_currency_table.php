<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrencyTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('currency', function (Blueprint $table) {
            $table->id(); // Auto-incrementing 'id' field
            $table->string('name', 20)->nullable();
            $table->string('code', 3)->nullable();
            $table->string('symbol', 5)->nullable();
            $table->timestamps(); // For created_at and updated_at fields
        });

        // Insert data
        DB::table('currency')->insert([
            ['name' => 'Bangladeshi Taka', 'code' => 'BDT', 'symbol' => '৳'],
            ['name' => 'Dollars', 'code' => 'USD', 'symbol' => '$'],
            ['name' => 'Leke', 'code' => 'ALL', 'symbol' => 'Lek'],
            ['name' => 'Afghanis', 'code' => 'AFN', 'symbol' => '؋'],
            ['name' => 'Pesos', 'code' => 'ARS', 'symbol' => '$'],
            ['name' => 'Guilders', 'code' => 'AWG', 'symbol' => 'ƒ'],
            ['name' => 'Dollars', 'code' => 'AUD', 'symbol' => '$'],
            ['name' => 'New Manats', 'code' => 'AZN', 'symbol' => 'ман'],
            ['name' => 'Dollars', 'code' => 'BSD', 'symbol' => '$'],
            ['name' => 'Dollars', 'code' => 'BBD', 'symbol' => '$'],
            ['name' => 'Rubles', 'code' => 'BYR', 'symbol' => 'p.'],
            ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€'],
            ['name' => 'Dollars', 'code' => 'BZD', 'symbol' => 'BZ$'],
            ['name' => 'Dollars', 'code' => 'BMD', 'symbol' => '$'],
            ['name' => 'Bolivianos', 'code' => 'BOB', 'symbol' => '$b'],
            ['name' => 'Convertible Marka', 'code' => 'BAM', 'symbol' => 'KM'],
            ['name' => 'Pula', 'code' => 'BWP', 'symbol' => 'P'],
            ['name' => 'Leva', 'code' => 'BGN', 'symbol' => 'лв'],
            ['name' => 'Reais', 'code' => 'BRL', 'symbol' => 'R$'],
            ['name' => 'Pounds', 'code' => 'GBP', 'symbol' => '£'],
            ['name' => 'Dollars', 'code' => 'BND', 'symbol' => '$'],
            ['name' => 'Riels', 'code' => 'KHR', 'symbol' => '៛'],
            ['name' => 'Dollars', 'code' => 'CAD', 'symbol' => '$'],
            ['name' => 'Dollars', 'code' => 'KYD', 'symbol' => '$'],
            ['name' => 'Pesos', 'code' => 'CLP', 'symbol' => '$'],
            ['name' => 'Yuan Renminbi', 'code' => 'CNY', 'symbol' => '¥'],
            ['name' => 'Pesos', 'code' => 'COP', 'symbol' => '$'],
            ['name' => 'Colón', 'code' => 'CRC', 'symbol' => '₡'],
            ['name' => 'Kuna', 'code' => 'HRK', 'symbol' => 'kn'],
            ['name' => 'Pesos', 'code' => 'CUP', 'symbol' => '₱'],
            ['name' => 'Koruny', 'code' => 'CZK', 'symbol' => 'Kč'],
            ['name' => 'Kroner', 'code' => 'DKK', 'symbol' => 'kr'],
            ['name' => 'Pesos', 'code' => 'DOP', 'symbol' => 'RD$'],
            ['name' => 'Dollars', 'code' => 'XCD', 'symbol' => '$'],
            ['name' => 'Pounds', 'code' => 'EGP', 'symbol' => '£'],
            ['name' => 'Colones', 'code' => 'SVC', 'symbol' => '$'],
            ['name' => 'Pounds', 'code' => 'FKP', 'symbol' => '£'],
            ['name' => 'Dollars', 'code' => 'FJD', 'symbol' => '$'],
            ['name' => 'Cedis', 'code' => 'GHC', 'symbol' => '¢'],
            ['name' => 'Pounds', 'code' => 'GIP', 'symbol' => '£'],
            ['name' => 'Quetzales', 'code' => 'GTQ', 'symbol' => 'Q'],
            ['name' => 'Pounds', 'code' => 'GGP', 'symbol' => '£'],
            ['name' => 'Dollars', 'code' => 'GYD', 'symbol' => '$'],
            ['name' => 'Lempiras', 'code' => 'HNL', 'symbol' => 'L'],
            ['name' => 'Dollars', 'code' => 'HKD', 'symbol' => '$'],
            ['name' => 'Forint', 'code' => 'HUF', 'symbol' => 'Ft'],
            ['name' => 'Kronur', 'code' => 'ISK', 'symbol' => 'kr'],
            ['name' => 'Rupees', 'code' => 'INR', 'symbol' => 'Rp'],
            ['name' => 'Rupiahs', 'code' => 'IDR', 'symbol' => 'Rp'],
            ['name' => 'Rials', 'code' => 'IRR', 'symbol' => '﷼'],
            ['name' => 'Pounds', 'code' => 'IMP', 'symbol' => '£'],
            ['name' => 'New Shekels', 'code' => 'ILS', 'symbol' => '₪'],
            ['name' => 'Dollars', 'code' => 'JMD', 'symbol' => 'J$'],
            ['name' => 'Yen', 'code' => 'JPY', 'symbol' => '¥'],
            ['name' => 'Pounds', 'code' => 'JEP', 'symbol' => '£'],
            ['name' => 'Tenge', 'code' => 'KZT', 'symbol' => 'лв'],
            ['name' => 'Won', 'code' => 'KPW', 'symbol' => '₩'],
            ['name' => 'Won', 'code' => 'KRW', 'symbol' => '₩'],
            ['name' => 'Soms', 'code' => 'KGS', 'symbol' => 'лв'],
            ['name' => 'Kips', 'code' => 'LAK', 'symbol' => '₭'],
            ['name' => 'Lati', 'code' => 'LVL', 'symbol' => 'Ls'],
            ['name' => 'Pounds', 'code' => 'LBP', 'symbol' => '£'],
            ['name' => 'Dollars', 'code' => 'LRD', 'symbol' => '$'],
            ['name' => 'Switzerland Francs', 'code' => 'CHF', 'symbol' => 'CHF'],
            ['name' => 'Litai', 'code' => 'LTL', 'symbol' => 'Lt'],
            ['name' => 'Denars', 'code' => 'MKD', 'symbol' => 'ден'],
            ['name' => 'Ringgits', 'code' => 'MYR', 'symbol' => 'RM'],
            ['name' => 'Rupees', 'code' => 'MUR', 'symbol' => '₨'],
            ['name' => 'Pesos', 'code' => 'MXN', 'symbol' => '$'],
            ['name' => 'Tugriks', 'code' => 'MNT', 'symbol' => '₮'],
            ['name' => 'Meticais', 'code' => 'MZN', 'symbol' => 'MT'],
            ['name' => 'Dollars', 'code' => 'NAD', 'symbol' => '$'],
            ['name' => 'Rupees', 'code' => 'NPR', 'symbol' => '₨'],
            ['name' => 'Guilders', 'code' => 'ANG', 'symbol' => 'ƒ'],
            ['name' => 'Dollars', 'code' => 'NZD', 'symbol' => '$'],
            ['name' => 'Cordobas', 'code' => 'NIO', 'symbol' => 'C$'],
            ['name' => 'Nairas', 'code' => 'NGN', 'symbol' => '₦'],
            ['name' => 'Krone', 'code' => 'NOK', 'symbol' => 'kr'],
            ['name' => 'Rials', 'code' => 'OMR', 'symbol' => '﷼'],
            ['name' => 'Rupees', 'code' => 'PKR', 'symbol' => '₨'],
            ['name' => 'Balboa', 'code' => 'PAB', 'symbol' => 'B/.'],
            ['name' => 'Guarani', 'code' => 'PYG', 'symbol' => 'Gs'],
            ['name' => 'Nuevos Soles', 'code' => 'PEN', 'symbol' => 'S/.'],
            ['name' => 'Pesos', 'code' => 'PHP', 'symbol' => 'Php'],
            ['name' => 'Zlotych', 'code' => 'PLN', 'symbol' => 'zł'],
            ['name' => 'Rials', 'code' => 'QAR', 'symbol' => '﷼'],
            ['name' => 'New Lei', 'code' => 'RON', 'symbol' => 'lei'],
            ['name' => 'Rubles', 'code' => 'RUB', 'symbol' => '₽'],
            ['name' => 'Tugrik', 'code' => 'MNT', 'symbol' => '₮'],
            ['name' => 'Rupees', 'code' => 'SCR', 'symbol' => '₨'],
            ['name' => 'Dollars', 'code' => 'SGD', 'symbol' => '$'],
            ['name' => 'Pounds', 'code' => 'SHP', 'symbol' => '£'],
            ['name' => 'Cedis', 'code' => 'GHS', 'symbol' => '¢'],
            ['name' => 'Dollars', 'code' => 'ZAR', 'symbol' => 'R'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('currency');
    }
}
