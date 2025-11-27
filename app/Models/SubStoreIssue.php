<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubStoreIssue extends Model {

    use HasFactory;

    protected $table = 'sub_store_issues';
    protected $fillable = [
        'part_id',
        'user_id',
        'substore_id',
        'issue_type',
        'issue_date',
        'issue_to',
        'line',
        'reference',
        'issuing_company',
        'challan_copy',
        'company_id',
        'remarks',
        'qty',
        'request_id'
    ];
    // Automatically include computed attributes in JSON
    protected $appends = ['challan_file', 'issue_to_show'];

    // Relationships
    public function substore() {
        return $this->belongsTo(SubStore::class, 'substore_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function issueToUser() {
        return $this->belongsTo(User::class, 'issue_to');
    }

    public function issueToCompany() {
        return $this->belongsTo(Company::class, 'issuing_company');
    }

    public function part() {
        return $this->belongsTo(Part::class, 'part_id');
    }

    // Accessors
    public function getChallanFileAttribute() {
        return $this->challan_copy ? url('challan-copies/' . $this->challan_copy) : '';
    }

    public function getIssueToShowAttribute() {
        if ($this->issue_type === 'Self') {
            return $this->issueToUser?->full_name ?? '';
        } elseif ($this->issue_type === 'Sister-Factory') {
            return $this->issueToCompany?->title ?? '';
        }
        return '';
    }
}
