<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proforma extends Model {

    protected $table = 'proformas';
    protected $fillable = [
        'user_id',
        'proforma_number',
        'purchase_contract_id',
        'supplier_id',
        'company_id',
        'title',
        'currency',
        'issued_date',
        'delivery_date',
        'pi_validity',
        'net_weight',
        'gross_weight',
        'freight_charge',
        'description',
        'bank_account_name',
        'bank_account_number',
        'bank_brunch_name',
        'bank_name',
        'bank_address',
        'bank_swift_code',
        'total',
    ];

    protected static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $lastProforma = static::latest('proforma_number')->first();

            if ($lastProforma) {
                $lastProformaNumber = $lastProforma->proforma_number;
                $model->proforma_number = $model->incrementProformaNumber($lastProformaNumber);
            } else {
                $model->proforma_number = 'PI-1';
            }
        });
    }

    private function incrementProformaNumber($lastProformaNumber) {
        // Extract the numeric part
        $numericPart = (int) substr($lastProformaNumber, strlen('PI-'));

        // Increment the numeric part
        $newNumericPart = $numericPart + 1;

        // Set the new SOR number
        return 'PI-' . $newNumericPart;
    }

    public function items() {
        $this->hasMany(ProformaItem::class);
    }

}
