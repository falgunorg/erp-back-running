<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubstoreReportMail;
use App\Models\User;
use App\Models\SubStore;
use App\Models\SubStoreIssue;
use App\Models\SubStoreReceive;
use Carbon\Carbon;

class SendDailySubstoreReport extends Command {

    protected $signature = 'send:daily-substore-report';
    protected $description = 'Send daily substore report email';

    public function __construct() {
        parent::__construct();
    }

    //MAIL SENDING
    public function handle() {
        $this->mail_daily_report();
    }

    public function mail_daily_report() {
        $users = User::whereIn('email', ['faisal@fashion-product.com.bd'])
                ->get();

        $usersByCompany = $users->groupBy('company');

        foreach ($usersByCompany as $companyId => $companyUsers) {
            $reportData = $this->generateReport($companyId);
            $this->sendReportEmail($companyUsers, $reportData);
        }
    }

    private function generateReport($companyId) {
        $receives = SubStoreReceive::where('company_id', $companyId)
                ->whereDate('created_at', Carbon::today())
                ->get();

        foreach ($receives as $val) {
            $part = \App\Models\Part::find($val->part_id);
            if ($part) {
                $val->part_name = $part->title;
                $val->unit = $part->unit;
            }
            $requsition = \App\Models\Requisition::find($val->requisition_id);
            if ($requsition) {
                $val->requsition_number = $requsition->requsition_number;
            }
            $receiver = User::find($val->user_id);
            if ($receiver) {
                $val->received_by = $receiver->full_name;
            }
        }

        $issues = SubStoreIssue::where('company_id', $companyId)
                ->whereDate('created_at', Carbon::today())
                ->get();

        foreach ($issues as $val) {
            $issue_by = \App\Models\User::find($val->user_id);
            if ($issue_by) {
                $val->issue_by = $issue_by->full_name;
            }
            if ($val->issue_type == "Self") {
                $issue_to_user = \App\Models\User::find($val->issue_to);
                if ($issue_to_user) {
                    $val->issue_to_show = $issue_to_user->full_name;
                }
            } else if ($val->issue_type == "Sister-Factory") {
                $issue_to_company = \App\Models\Company::find($val->issuing_company);
                if ($issue_to_company) {
                    $val->issue_to_show = $issue_to_company->title;
                }
            }
            $part = \App\Models\Part::find($val->part_id);
            if ($part) {
                $val->part_name = $part->title;
                $val->unit = $part->unit;
            }
        }

        $storeSummary = SubStore::where('company_id', $companyId)->get();

        foreach ($storeSummary as $val) {
            $part = \App\Models\Part::find($val->part_id);
            if ($part) {
                $val->part_name = $part->title;
                $val->unit = $part->unit;
            }
            $company = \App\Models\Company::find($val->company_id);
            if ($company) {
                $val->company = $company->title;
            }
            $val->total_receives = SubStoreReceive::where('substore_id', $val->id)->sum('qty');
            $val->total_issues = SubStoreIssue::where('substore_id', $val->id)->sum('qty');
        }

        $reportData = [
            'receives' => $receives,
            'issues' => $issues,
            'summary' => $storeSummary,
        ];

        return $reportData;
    }

    private function sendReportEmail($users, $reportData) {
        foreach ($users as $user) {
            $username = $user->full_name;
            Mail::to($user->email)->send(new SubstoreReportMail($reportData, $username));
        }
    }

}
