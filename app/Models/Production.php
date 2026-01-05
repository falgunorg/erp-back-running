<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Production extends Model {

    use HasFactory;

    protected $table = 'productions';
    protected $fillable = [
        'user_id',
        'production_date',
        'company_id',
        'unit',
        'line_no',
        'buyer',
        'item',
        'style',
        'cm_val',
        'fob_val',
        'smv',
        'mp',
        'run_day',
        'wh',
        'target',
        'last_day_achieve',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'h7',
        'h8',
        'h9',
        'h10',
        'h11',
        'h12',
        'h13',
        'h14',
        'h15',
        'h16',
        'h17',
        'h18',
        'remarks',
    ];
    protected $casts = [
        'production_date' => 'date',
        'cm_val' => 'decimal:2',
        'fob_val' => 'decimal:2',
        'smv' => 'decimal:2',
    ];
    // Ensure these are included in JSON responses
    protected $appends = ['total_prod', 'variance', 'cm_earning', 'fob_earning'];

    // Sum of all 18 hours
    public function getTotalProdAttribute() {
        $sum = 0;
        for ($i = 1; $i <= 18; $i++) {
            $sum += $this->{"h$i"};
        }
        return $sum;
    }

    // Day Excess/Short (Total - Target)
    public function getVarianceAttribute() {
        return $this->total_prod - $this->target;
    }

    // CM$ Earning (Total * cm_val)
    public function getCmEarningAttribute() {
        return round($this->total_prod * $this->cm_val, 2);
    }

    // FOB$ Earning (Total * fob_val)
    public function getFobEarningAttribute() {
        return round($this->total_prod * $this->fob_val, 2);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function company() {
        return $this->belongsTo(Company::class);
    }
}
