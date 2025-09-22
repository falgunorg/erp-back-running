<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkOrder;
use App\Models\Buyer;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use App\Models\TechnicalPackage;
use App\Models\Po;

class WorkOrderController extends Controller {

    public function index(Request $request) {
        $statusCode = 422;
        $workorders = WorkOrder::with('techpack', 'pos.techpack', 'techpack.buyer', 'user', 'techpack.company')->orderBy('created_at', 'desc')->paginate(10);
        $return['workorders'] = $workorders;
        $statusCode = 200;

        return $this->response($return, $statusCode);
    }

    public function public_index(Request $request) {
        $statusCode = 422;
        $workorders = WorkOrder::orderBy('created_at', 'desc')->get();
        $return['data'] = $workorders;
        $statusCode = 200;

        return $this->response($return, $statusCode);
    }

    public function store(Request $request) {
        $statusCode = 422;

        $validator = Validator::make($request->all(), [
            'technical_package_id' => 'required|exists:technical_packages,id',
            'create_date' => 'required|date',
            'delivery_date' => 'required|date',
            'sewing_sam' => 'required|numeric',
            'po_list' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return $this->response(['errors' => $validator->errors()], $statusCode);
        }

        $techpack = TechnicalPackage::find($request->technical_package_id);
        if (!$techpack) {
            return $this->response(['errors' => ['technical_package_id' => 'Invalid technical package']], $statusCode);
        }

        $year = date('Y', strtotime($techpack->received_date));

        $workorder = new WorkOrder();
        $workorder->technical_package_id = $techpack->id;
        $workorder->user_id = $request->user->id;
        $workorder->create_date = $request->input('create_date');
        $workorder->release_date = $request->input('create_date');
        $workorder->delivery_date = $request->input('delivery_date');
        $workorder->sewing_sam = $request->input('sewing_sam');
        $workorder->wo_ref = $request->input('wo_ref') ?? null;

        $workorder->wo_number = $this->generateWoNo(
                $techpack->buyer_id,
                $techpack->company_id,
                $techpack->season,
                $year
        );

        $workorder->save();

        // ✅ Attach PO list to workorder
        foreach ($request->po_list as $poId) {
            $po = Po::find($poId);
            if ($po) {
                $po->wo_id = $workorder->id;
                $po->save();
            }
        }

        return $this->response(['workorder' => $workorder], 200);
    }

    private function generateWoNo($buyer_id, $company_id, $season, $year) {
        $prefix = 'WO';

        $buyer = Buyer::find($buyer_id);
        $company = Company::find($company_id);

        if (!$buyer || !$company) {
            throw new \Exception('Invalid Buyer or Company ID.');
        }

        $season_code = strtoupper(substr($season, 0, 2));
        $year_code = substr($year, -2);

        $latestWorkOrder = WorkOrder::where('wo_number', 'like', $prefix . $buyer->nickname . $company->nickname . $season_code . $year_code . '%')
                ->orderBy('id', 'desc')
                ->first();

        if ($latestWorkOrder && preg_match('/\d{4}$/', $latestWorkOrder->wo_number, $matches)) {
            $nextSerial = intval($matches[0]) + 1;
        } else {
            $nextSerial = 1;
        }

        $serial_no = str_pad($nextSerial, 4, '0', STR_PAD_LEFT);

        return $prefix . $buyer->nickname . $company->nickname . $season_code . $year_code . $serial_no;
    }

    public function show(Request $request) {
        $id = $request->input('id');

        if (!$id) {
            return $this->response(['message' => 'Work Order ID is required'], 422);
        }

        $workorder = WorkOrder::with([
                    'techpack.buyer',
                    'techpack.company',
                    'costing',
                    'costing.items.item',
                    'costing.items.item_type',
                    'costing.items.supplier',
                    'user',
                    'pos.items',
                    'pos.user'
                ])->find($id);

        if (!$workorder) {
            return $this->response(['message' => 'Work Order not found'], 404);
        }

        return $this->response(['workorder' => $workorder], 200);
    }

    public function update(Request $request) {
        $statusCode = 422;
        $id = $request->input('id');
        $validator = Validator::make($request->all(), [
            'technical_package_id' => 'required|exists:technical_packages,id',
            'create_date' => 'required|date',
            'delivery_date' => 'required|date',
            'sewing_sam' => 'required|numeric',
            'po_list' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return $this->response(['errors' => $validator->errors()], $statusCode);
        }

        $workorder = WorkOrder::find($id);
        if (!$workorder) {
            return $this->response(['errors' => ['id' => 'WorkOrder not found']], 404);
        }

        $techpack = TechnicalPackage::find($request->technical_package_id);
        if (!$techpack) {
            return $this->response(['errors' => ['technical_package_id' => 'Invalid technical package']], $statusCode);
        }

        $year = date('Y', strtotime($techpack->received_date));

        $workorder->technical_package_id = $techpack->id;
        $workorder->user_id = $request->user->id;
        $workorder->create_date = $request->input('create_date');
        $workorder->release_date = $request->input('create_date');
        $workorder->delivery_date = $request->input('delivery_date');
        $workorder->sewing_sam = $request->input('sewing_sam');
        $workorder->wo_ref = $request->input('wo_ref') ?? null;

        // Optional: regenerate wo_number if techpack changed
        if ($workorder->isDirty('technical_package_id')) {
            $workorder->wo_number = $this->generateWoNo(
                    $techpack->buyer_id,
                    $techpack->company_id,
                    $techpack->season,
                    $year
            );
        }

        $workorder->save();

        // ✅ Detach all old POs
        Po::where('wo_id', $workorder->id)->update(['wo_id' => null]);

        // ✅ Attach new PO list
        foreach ($request->po_list as $poId) {
            $po = Po::find($poId);
            if ($po) {
                $po->wo_id = $workorder->id;
                $po->save();
            }
        }

        return $this->response(['workorder' => $workorder], 200);
    }

    public function destroy(Request $request) {
        $id = $request->input('id');
        $statusCode = 422;

        $workorder = WorkOrder::find($id);

        if (!$workorder) {
            return $this->response(['errors' => ['id' => 'WorkOrder not found']], 404);
        }

        // ✅ Detach all related POs
        Po::where('wo_id', $workorder->id)->update(['wo_id' => null]);

        // ✅ Delete the work order
        $workorder->delete();

        return $this->response(['message' => 'WorkOrder deleted successfully.'], 200);
    }

    public function destroy_multiple(Request $request) {
        $ids = $request->input('ids'); // expects an array of WorkOrder IDs

        if (!is_array($ids) || empty($ids)) {
            return $this->response(['error' => 'No WorkOrder IDs provided.'], 400);
        }

        $notDeleted = [];
        $deleted = [];

        foreach ($ids as $id) {
            $workorder = WorkOrder::with('teckpack', 'pos')->find($id);

            if (!$workorder) {
                $notDeleted[] = ['id' => $id, 'reason' => 'WorkOrder not found.'];
                continue;
            }

            if ($workorder->teckpack()->exists()) {
                $notDeleted[] = ['id' => $id, 'reason' => 'Associated teckpack exist.'];
                continue;
            }

            if ($workorder->pos()->exists()) {
                $notDeleted[] = ['id' => $id, 'reason' => 'Associated POs exist.'];
                continue;
            }

            $workorder->delete();
            $deleted[] = $id;
        }
        return $this->response([
                    'message' => 'WorkOrder deletion process completed.',
                    'deleted_ids' => $deleted,
                    'not_deleted' => $notDeleted
                        ], 200);
    }
}
