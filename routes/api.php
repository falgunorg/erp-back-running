<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\NotebookController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SorController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\PurchaseContractController;
use App\Http\Controllers\LcController;
use App\Http\Controllers\CostingController;
use App\Http\Controllers\StyleController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SampleTypeController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\SampleStoreController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\ReceiveController;
use App\Http\Controllers\LeftOverController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\ProformaController;
use App\Http\Controllers\TechpackController;
use App\Http\Controllers\ConsumptionController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\HscodeController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\RequisitionController;
use App\Http\Controllers\SubStoreController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ParcelController;
//POWER CONTROLLER
use App\Http\Controllers\SubstorePowerController;
use App\Http\Controllers\MerchandisingPowerController;
use App\Http\Controllers\SubstoreAccessController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\TechnicalPackageController;
use App\Http\Controllers\PoController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Http\Request;

/*
  |--------------------------------------------------------------------------
  | API Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register API routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | is assigned the "api" middleware group. Enjoy building your API!
  |
 */

Route::post('/login', [UserController::class, 'login']);
Route::post("/refresh_token", [UserController::class, 'refreshToken']);

Route::post('/broadcasting/auth', function (Request $request) {
    if ($request->user) {
        return response()->json([
                    'user' => $request->user,
        ]);
    }

    return response()->json([
                'message' => 'Unauthorized',
                    ], 403);
})->middleware('apiauth');

Route::middleware(['apiauth'])->group(function () {
//    common Routes 
    Route::get('/countries', [CommonController::class, 'countries']);
    Route::get('/banks', [CommonController::class, 'banks']);
    Route::get('/currencies', [CommonController::class, 'currencies']);
    //Legacy Schedule Routes
    Route::post('/schedules', [ScheduleController::class, 'index']);
    Route::post('/schedules-create', [ScheduleController::class, 'store']);
    Route::post('/schedules-show', [ScheduleController::class, 'show']);
    Route::post('/schedules-update', [ScheduleController::class, 'update']);
    Route::post('/schedules-delete', [ScheduleController::class, 'destroy']);
    //Legacy Chat routes 
    Route::post('/conversations', [ChatController::class, 'index']);
    Route::post('/conversations-create', [ChatController::class, 'createConversation']);
    Route::post('/conversations-details', [ChatController::class, 'showMessages']);
    Route::post('/messages-send', [ChatController::class, 'sendMessage']);

    //Legacy Parcel Routes
    Route::post('/parcels', [ParcelController::class, 'index']);
    Route::post('/parcels-create', [ParcelController::class, 'store']);
    Route::post('/parcels-show', [ParcelController::class, 'show']);
    Route::post('/parcels-update', [ParcelController::class, 'update']);
    Route::post('/parcels-receive', [ParcelController::class, 'receive']);
    Route::post('/parcels-delete', [ParcelController::class, 'destroy']);

    //    Legacy Hscode router
    Route::post('/hscodes', [HscodeController::class, 'index']);
    Route::post('/hscodes-create', [HscodeController::class, 'store']);
    Route::post('/hscodes-show', [HscodeController::class, 'show']);
    Route::post('/hscodes-toggle', [HscodeController::class, 'toggleStaus']);
    Route::post('/hscodes-update', [HscodeController::class, 'update']);
    Route::post('/hscodes-delete', [HscodeController::class, 'destroy']);
    Route::post('/hscodes-toggle-bulk', [HscodeController::class, 'toggleStatusBulk']);

    //    Legacy requisitions router
    Route::post('/requisitions', [RequisitionController::class, 'index']);
    Route::post('/requisitions-for-receive', [RequisitionController::class, 'index_for_receive']);
    Route::post('/requisitions/special', [RequisitionController::class, 'index_special']);
    Route::post('/requisitions-create', [RequisitionController::class, 'store']);
    Route::post('/requisitions-show', [RequisitionController::class, 'show']);
    Route::post('/requisitions-toggle-status', [RequisitionController::class, 'toggle_status']);
    Route::post('/requisitions-update', [RequisitionController::class, 'update']);
    Route::post('/requisitions-revise', [RequisitionController::class, 'revise']);
    Route::post('/requisitions-delete', [RequisitionController::class, 'destroy']);
    Route::post('/requisitions-single-item', [RequisitionController::class, 'single_requisition_item']);
    Route::post('/requisitions-pending', [RequisitionController::class, 'pending_purchase']);

    // Legacy substores router
    Route::post('/substores', [SubStoreController::class, 'index']);
    Route::post('/substores-all', [SubStoreController::class, 'all_data']);
    Route::post('/substores-company-wise', [SubStoreController::class, 'company_wise']);
    Route::post('/substores-pending-receive', [SubStoreController::class, 'pending_receive']);
    Route::post('/substores-receive', [SubStoreController::class, 'receive']);
    Route::post('/substores-receive-undo', [SubStoreController::class, 'receive_undo']);
    Route::post('/substores-show', [SubStoreController::class, 'show']);
    Route::post('/substores-issue', [SubStoreController::class, 'issue']);
    Route::post('/substores-issue-undo', [SubStoreController::class, 'issue_undo']);
    Route::post('/substores-open', [SubStoreController::class, 'open']);
    Route::post('/substores-revise', [SubStoreController::class, 'revise_balance']);

    // REPORT (RECEIVES & ISSUES)
    Route::post('/substores-report', [SubStoreController::class, 'make_report']);
    Route::post('/substores-receive-report', [SubStoreController::class, 'receive_report']);
    Route::post('/substores-issue-report', [SubStoreController::class, 'issue_report']);
    Route::post('/substores-report-mail', [SubStoreController::class, 'mail_daily_report']);
    //Part Request Routes

    Route::post('/part-requests', [SubStoreController::class, 'part_requests']);
    Route::post('/part-requests-create', [SubStoreController::class, 'part_requests_create']);
    Route::post('/part-requests-show', [SubStoreController::class, 'part_requests_show']);
    Route::post('/part-requests-update', [SubStoreController::class, 'part_requests_update']);
    Route::post('/part-requests-toggle', [SubStoreController::class, 'part_requests_toggle']);

    //    Legacy parts router
    Route::post('/parts', [PartController::class, 'index']);
    Route::post('/parts-required-purchase', [PartController::class, 'required_purchase']);
    Route::post('/parts-create', [PartController::class, 'store']);
    Route::post('/parts-show', [PartController::class, 'show']);
    Route::post('/parts-toggle', [PartController::class, 'toggleStaus']);
    Route::post('/parts-update', [PartController::class, 'update']);
    Route::post('/parts-upload-photo', [PartController::class, 'update_photo']);
    Route::post('/parts-delete', [PartController::class, 'destroy']);

    //    Legacy Machines router
    Route::post('/machines', [MachineController::class, 'index']);
    Route::post('/machines-create', [MachineController::class, 'store']);
    Route::post('/machines-show', [MachineController::class, 'show']);
    Route::post('/machines-toggle', [MachineController::class, 'toggleStaus']);
    Route::post('/machines-update', [MachineController::class, 'update']);
    Route::post('/machines-delete', [MachineController::class, 'destroy']);
    Route::post('/machines-bulk-store', [MachineController::class, 'store_bulk']);

//    Legacy Common route Color
    Route::post('/colors', [ColorController::class, 'index']);
    Route::post('/colors-create', [ColorController::class, 'store']);
    Route::post('/colors-show', [ColorController::class, 'show']);
    Route::post('/colors-update', [ColorController::class, 'update']);
    Route::post('/colors-delete', [ColorController::class, 'destroy']);
    //    Legacy Common route  size
    Route::post('/styles', [StyleController::class, 'index']);
    Route::post('/styles-create', [StyleController::class, 'store']);
    Route::post('/styles-show', [StyleController::class, 'show']);
    Route::post('/styles-update', [StyleController::class, 'update']);
    Route::post('/styles-delete', [StyleController::class, 'destroy']);
    //    Legacy Common route  size
    Route::post('/sizes', [SizeController::class, 'index']);
    Route::post('/sizes-create', [SizeController::class, 'store']);
    Route::post('/sizes-show', [SizeController::class, 'show']);
    Route::post('/sizes-update', [SizeController::class, 'update']);
    Route::post('/sizes-delete', [SizeController::class, 'destroy']);
    //    Legacy Common route  unit
    Route::post('/units', [UnitController::class, 'index']);
    Route::post('/units-create', [UnitController::class, 'store']);
    Route::post('/units-show', [UnitController::class, 'show']);
    Route::post('/units-update', [UnitController::class, 'update']);
    Route::post('/units-delete', [UnitController::class, 'destroy']);

    //Legacy holidays routes
    Route::post('/holidays', [HolidayController::class, 'index']);
    Route::post('/holidays-create', [HolidayController::class, 'store']);
    Route::post('/holidays-show', [HolidayController::class, 'show']);
    Route::post('/holidays-update', [HolidayController::class, 'update']);
    Route::post('/holidays-delete', [HolidayController::class, 'destroy']);

    //Legacy Item routes here  copy of frontend
    Route::post('/items', [ItemController::class, 'index']);
    Route::post('/items-create', [ItemController::class, 'store']);
    Route::post('/items-show', [ItemController::class, 'show']);
    Route::post('/items-update', [ItemController::class, 'update']);
    Route::post('/items-delete', [ItemController::class, 'destroy']);

    //item Types
    Route::post('/item-types', [ItemController::class, 'item_type_index']);

    //    Legacy sample order request route
    Route::post('/sors', [SorController::class, 'index']);
    Route::post('/sors-create', [SorController::class, 'store']);
    Route::post('/sors-show', [SorController::class, 'show']);
    Route::post('/sors-update', [SorController::class, 'update']);
    Route::post('/sors-delete', [SorController::class, 'destroy']);
    Route::post('/sors-toggle-item-status', [SorController::class, 'toggleitemstatus']);

    //SOR ADMIN ROUTES 
    Route::post('/admin/sors', [SorController::class, 'admin_index']);
    Route::post('/admin/sors-show', [SorController::class, 'admin_show']);
//    Techpacks 
    Route::post('/techpacks', [TechpackController::class, 'index']);
    Route::post('/techpacks-create', [TechpackController::class, 'store']);
    Route::post('/techpacks-show', [TechpackController::class, 'show']);
    Route::post('/techpacks-update', [TechpackController::class, 'update']);
    Route::post('/techpacks-delete', [TechpackController::class, 'destroy']);
    Route::post('/techpacks-toggle-status', [TechpackController::class, 'toggleStatus']);
    Route::post('/techpacks-toggle-item-status', [TechpackController::class, 'toggleitemstatus']);
    Route::post('/techpacks-attachment-delete', [TechpackController::class, 'delete_attachment']);

    //Technical package
    Route::post('/technical-packages', [TechnicalPackageController::class, 'index']);
    Route::post('/technical-packages-all-desc', [TechnicalPackageController::class, 'public_index']);
    Route::post('/technical-package-create', [TechnicalPackageController::class, 'store']);
    Route::post('/technical-package-show', [TechnicalPackageController::class, 'show']);
    Route::post('/technical-package-update', [TechnicalPackageController::class, 'update']);
    Route::post('/technical-package-delete', [TechnicalPackageController::class, 'destroy']);
    Route::post('/technical-package-delete-multiple', [TechnicalPackageController::class, 'destroy_multiple']);
    Route::post('/technical-package-file-delete', [TechnicalPackageController::class, 'delete_file']);

//    CONSUMPTION ROUTES HERE 
    Route::post('/consumptions', [ConsumptionController::class, 'index']);
    Route::post('/consumptions-create', [ConsumptionController::class, 'store']);
    Route::post('/consumptions-show', [ConsumptionController::class, 'show']);
    Route::post('/consumptions-update', [ConsumptionController::class, 'update']);
    Route::post('/consumptions-delete', [ConsumptionController::class, 'destroy']);
    Route::post('/consumptions-toggle-status', [ConsumptionController::class, 'togglestatus']);
    Route::post('/consumptions-attachment-delete', [ConsumptionController::class, 'delete_attachment']);

    //Legacy Sample Types route copy from old
    Route::post('/sors-types', [SampleTypeController::class, 'index']);
    Route::post('/sors-types-create', [SampleTypeController::class, 'store']);
    Route::post('/sors-types-show', [SampleTypeController::class, 'show']);
    Route::post('/sors-types-update', [SampleTypeController::class, 'update']);
    Route::post('/sors-types-delete', [SampleTypeController::class, 'destroy']);
//    for sample department
    Route::post('/sample/sors', [SorController::class, 'index_for_sample_section']);
    Route::post('/sors-togglestatus', [SorController::class, 'togglestatus']);
//    sample store
    Route::post('/sample/sample-stores', [SampleStoreController::class, 'index']);
    Route::post('/sample/sample-stores-create', [SampleStoreController::class, 'store']);
    Route::post('/sample/sample-stores-show', [SampleStoreController::class, 'show']);
    Route::post('/sample/sample-stores-update', [SampleStoreController::class, 'update']);
    Route::post('/sample/sample-stores-increment', [SampleStoreController::class, 'increment']);
    Route::post('/sample/sample-stores-delete', [SampleStoreController::class, 'destroy']);
//    inject from main store
    Route::post('/sample/sample-stores-inject', [SampleStoreController::class, 'inject_from_main_store']);

//    admin router (sample store)
    Route::post('/admin/sample/sample-stores', [SampleStoreController::class, 'admin_index']);
    Route::post('/admin/sample/sample-stores-show', [SampleStoreController::class, 'admin_show']);

    //    Legacy Costing Routes 
    Route::post('/costings', [CostingController::class, 'index']);
    Route::post('/public-costings', [CostingController::class, 'public_index']);
    Route::post('/costings-create', [CostingController::class, 'store']);
    Route::post('/costings-show', [CostingController::class, 'show']);
    Route::post('/costings-update', [CostingController::class, 'update']);
    Route::post('/costings-delete', [CostingController::class, 'destroy']);

    Route::post('/single-costings-item', [CostingController::class, 'single_costings_item']);
    Route::post('/costings-toggle-status', [CostingController::class, 'toggleStatus']);
//    Admin Routes 
    Route::post('/admin/costings', [CostingController::class, 'admin_index']);

    Route::post('/budgets', [BudgetController::class, 'index']);
    Route::post('/public-budgets', [BudgetController::class, 'public_index']);
    Route::post('/budgets-create', [BudgetController::class, 'store']);
    Route::post('/budgets-show', [BudgetController::class, 'show']);
    Route::post('/budgets-update', [BudgetController::class, 'update']);
    Route::post('/budgets-delete', [BudgetController::class, 'destroy']);
    Route::post('/budgets-toggle-status', [BudgetController::class, 'toggleStatus']);

    Route::post('/single-budgets-item', [BudgetController::class, 'single_budget_item']);
    Route::post('/budget-items-with-supplier-and-budget', [BudgetController::class, 'budget_items_via_supplier_budget']);

//    Admin Routes 
    Route::post('/admin/budgets', [BudgetController::class, 'admin_index']);

//    Legacy Designs Routes
    Route::post('/designs', [DesignController::class, 'index']);
    Route::post('/designs-create', [DesignController::class, 'store']);
    Route::post('/designs-show', [DesignController::class, 'show']);
    Route::post('/designs-update', [DesignController::class, 'update']);
    Route::post('/designs-delete', [DesignController::class, 'destroy']);

//    Admin routes for designs
    Route::post('/admin/designs', [DesignController::class, 'admin_index']);
    Route::post('/admin/designs-approve', [DesignController::class, 'admin_design_approve']);

//    Legacy Common route  unit
    Route::post('/purchase-contracts', [PurchaseContractController::class, 'index']);
    Route::post('/public-purchase-contracts', [PurchaseContractController::class, 'public_index']);
    Route::post('/purchase-contracts-create', [PurchaseContractController::class, 'store']);
    Route::post('/purchase-contracts-show', [PurchaseContractController::class, 'show']);
    Route::post('/purchase-contracts-update', [PurchaseContractController::class, 'update']);
    Route::post('/purchase-contracts-delete', [PurchaseContractController::class, 'destroy']);
//    purchases route
    Route::post('/purchases', [PurchaseController::class, 'index']);
    Route::post('/purchases-create', [PurchaseController::class, 'store']);
    Route::post('/purchase-item-add', [PurchaseController::class, 'add_purchase_items']);
    Route::post('/purchases-show', [PurchaseController::class, 'show']);
    Route::post('/purchases-update', [PurchaseController::class, 'update']);
    Route::post('/purchases-delete', [PurchaseController::class, 'destroy']);

    //new PO Routes starts Here 

    Route::post('/pos', [PoController::class, 'index']);
    Route::post('/public-pos', [PoController::class, 'public_index']);
    Route::post('/pos-create', [PoController::class, 'store']);
    Route::post('/pos-show', [PoController::class, 'show']);
    Route::post('/pos-update', [PoController::class, 'update']);
    Route::post('/pos-delete', [PoController::class, 'destroy']);
    Route::post('/pos-delete-multiple', [PoController::class, 'destroy_multiple']);
    Route::post('/pos-file-delete', [PoController::class, 'delete_file']);

//    new workorders routes here 
    Route::post('/workorders', [WorkOrderController::class, 'index']);
    Route::post('/public-workorders', [WorkOrderController::class, 'public_index']);
    Route::post('/workorders-create', [WorkOrderController::class, 'store']);
    Route::post('/workorders-show', [WorkOrderController::class, 'show']);
    Route::post('/workorders-update', [WorkOrderController::class, 'update']);
    Route::post('/workorders-delete', [WorkOrderController::class, 'destroy']);
    Route::post('/workorders-delete-multiple', [WorkOrderController::class, 'destroy_multiple']);

//    Admin Purchases Routes 
    Route::post('/admin/purchases', [PurchaseController::class, 'admin_index']);

    //Bookings route
    Route::post('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings-create', [BookingController::class, 'store']);
    Route::post('/bookings-item-add', [BookingController::class, 'add_booking_items']);
    Route::post('/single-booking-item', [BookingController::class, 'get_single_booking_item']);
    Route::post('/bookings-show', [BookingController::class, 'show']);
    Route::post('/bookings-update', [BookingController::class, 'update']);
    Route::post('/bookings-delete', [BookingController::class, 'destroy']);
    Route::post('/bookings-toggle-status', [BookingController::class, 'toggleStatus']);
    Route::post('/bookings-overview', [BookingController::class, 'bookings_overview']);

    Route::post('/bookings-items-wo-pi-by-supplier', [BookingController::class, 'get_booking_items_by_supplier_id_without_included_pi']);
    Route::post('/booking-items-without-included-pi', [BookingController::class, 'get_booking_items_without_included_pi']);
//  Admin Bookings Routes 
    Route::post('/admin/bookings', [BookingController::class, 'admin_index']);

    //Proforma route
    Route::post('/proformas', [ProformaController::class, 'index']);
    Route::post('/proformas-create', [ProformaController::class, 'store']);
    Route::post('/proformas-create-auto', [ProformaController::class, 'store_auto']);
    Route::post('/proformas-show', [ProformaController::class, 'show']);
    Route::post('/proformas-update', [ProformaController::class, 'update']);
    Route::post('/proformas-delete', [ProformaController::class, 'destroy']);
    Route::post('/proformas-toggle-status', [ProformaController::class, 'toggleStatus']);

//    Admin Bookings Routes 
    Route::post('/admin/proformas', [ProformaController::class, 'admin_index']);

//    Legacy Leftover Routes 
    Route::post('/left-overs', [LeftOverController::class, 'index']);
    Route::post('/left-overs-create', [LeftOverController::class, 'store']);
    Route::post('/left-overs-show', [LeftOverController::class, 'show']);
    Route::post('/left-overs-update', [LeftOverController::class, 'update']);
    Route::post('/left-overs-receive', [LeftOverController::class, 'receive']);
    Route::post('/left-overs-balance', [LeftOverController::class, 'balance']);
    Route::post('/left-overs-balance-details', [LeftOverController::class, 'balance_details']);
    Route::post('/left-overs-balance-issue', [LeftOverController::class, 'issue_item']);
    Route::post('/left-overs-delete', [BookingController::class, 'destroy']);
//    Admin LeftOder Routes 

    Route::post('/admin/left-overs-balance', [LeftOverController::class, 'admin_balance']);

//    Leagacy Store Routes
    Route::post('/receives', [ReceiveController::class, 'index']);
    Route::post('/receives-create', [ReceiveController::class, 'store']);
    Route::post('/receives-show', [ReceiveController::class, 'show']);

//    Admin Receives Routes
    Route::post('/admin/receives', [ReceiveController::class, 'admin_index']);

    //Leagacy Store Routes
    Route::post('/stores', [StoreController::class, 'index']);
    Route::post('/stores-show', [StoreController::class, 'show']);
    Route::post('/stores-summary', [StoreController::class, 'store_summary']);

    // Return Request From Floor   
    Route::post('/store/return-request', [StoreController::class, 'return_request']);
    Route::post('/store/receive-return-request', [StoreController::class, 'receive_return_request']);

//    Stores Admin Routes
    Route::post('/admin/stores', [StoreController::class, 'admin_index']);

//Leagacy Issue Routes
    Route::post('/issues', [IssueController::class, 'index']);
    Route::post('/issues-create', [IssueController::class, 'store']);
    Route::post('/issues-show', [IssueController::class, 'show']);
    Route::post('/issues-update', [IssueController::class, 'update']);
    Route::post('/issues-delete', [IssueController::class, 'destroy']);
//Issues Admin Routes
    Route::post('/admin/issues', [IssueController::class, 'admin_index']);
//Receive and Return From Store Routes 
//Received
    Route::post('/issued-to-me', [ReturnController::class, 'issued_to_me']);
//Return
    Route::post('/returns', [ReturnController::class, 'index']);
    Route::post('/returns-create', [ReturnController::class, 'store']);
    Route::post('/returns-show', [ReturnController::class, 'show']);
    Route::post('/returns-update', [ReturnController::class, 'update']);
    Route::post('/returns-delete', [ReturnController::class, 'destroy']);

//LEGACY LC route
    Route::post('/lcs', [LcController::class, 'index']);
    Route::post('/lcs-create', [LcController::class, 'store']);
    Route::post('/lcs-show', [LcController::class, 'show']);
    Route::post('/lcs-update', [LcController::class, 'update']);
    Route::post('/lcs-delete', [LcController::class, 'destroy']);

    //LEGACY ROUTES FOR LEAVES

    Route::post('/leaves', [LeaveController::class, 'my_leaves']);
    Route::post('/leaves-create', [LeaveController::class, 'store']);
    Route::post('/leaves-show', [LeaveController::class, 'show']);
    Route::post('/leaves-update', [LeaveController::class, 'update']);
    Route::post('/leaves-type-update', [LeaveController::class, 'update_leave_type']);
    Route::post('/leaves-delete', [LeaveController::class, 'destroy']);
    Route::post('/my-leaves-summary-yearly', [LeaveController::class, 'my_leaves_summary_yearly']);
    Route::post('/my-leaves-summary-overview', [LeaveController::class, 'my_leaves_summary_overview']);
    Route::post('/my-leaves-calendar', [LeaveController::class, 'my_leaves_calendar']);

    Route::post('/leaves-actions', [LeaveController::class, 'to_do_action']);
    Route::post('/leaves-toggle', [LeaveController::class, 'toggleStaus']);

    Route::post('/admin-leaves-report-monthly', [LeaveController::class, 'get_admin_report_monthly']);
    Route::post('/admin-leaves-report-yearly', [LeaveController::class, 'get_admin_report_yearly']);
    Route::post('/admin-leaves-actions', [LeaveController::class, 'admin_leaves_actions']);
    Route::post('/admin-leaves-summary', [LeaveController::class, 'admin_summary']);

    Route::post('/get-attendance-data', [LeaveController::class, 'getAttendanceData']);
    Route::post('/get-attendance-monthly', [LeaveController::class, 'getMonthlyAttendanceData']);
    Route::post('/get-payroll-monthly', [LeaveController::class, 'get_monthly_payroll_data']);
    Route::post('/get-attendance-yearly', [LeaveController::class, 'getYearlyAttendanceData']);
    Route::post('/write-attendance-files', [LeaveController::class, 'make_single_csv_from_multiple']);

    //payrolls routes 
    Route::post('/admin/payrolls', [PayrollController::class, 'index']);
    Route::post('/admin/payrolls-create', [PayrollController::class, 'store']);
    Route::post('/admin/payrolls-update', [PayrollController::class, 'update']);

//    users routes
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/profile', [UserController::class, 'profile']);
    Route::post('/profile-update', [UserController::class, 'update_profile']);
    Route::post('/update-profile-picture', [UserController::class, 'update_profile_picture']);
    Route::post('/update-signature', [UserController::class, 'update_signature']);
    Route::post('/mail-signature', [UserController::class, 'mailSign']);

    Route::post('/employees-delete', [EmployeeController::class, 'destroy']);
//     notebooks route
    Route::post('/notebooks', [NotebookController::class, 'index']);
    Route::post('/notebooks-create', [NotebookController::class, 'store']);
    Route::post('/notebooks-show', [NotebookController::class, 'show']);
    Route::post('/notebooks-update', [NotebookController::class, 'update']);
    Route::post('/notebooks-delete', [NotebookController::class, 'destroy']);
    //     terms route
    Route::post('/terms', [TermController::class, 'index']);
    Route::post('/terms-create', [TermController::class, 'store']);
    Route::post('/terms-show', [TermController::class, 'show']);
    Route::post('/terms-update', [TermController::class, 'update']);
    Route::post('/terms-delete', [TermController::class, 'destroy']);
//      roles route
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles-create', [RoleController::class, 'store']);
    Route::post('/roles-show', [RoleController::class, 'show']);
    Route::post('/roles-update', [RoleController::class, 'update']);
    Route::post('/roles-delete', [RoleController::class, 'destroy']);

    Route::get('/get_role_permission', [RoleController::class, 'get_role_permission']);
    Route::post('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications-read', [NotificationController::class, 'read']);
//    settings
    Route::post('/settings-store', [SettingController::class, 'store_bulk_hscode']);
    Route::post('/settings-store-supplier', [SettingController::class, 'store']);

    Route::post('/settings-store-parts', [SettingController::class, 'store_bulk_parts']);
    Route::get('/file-format', [SettingController::class, 'file_format']);

//    POWER ROUTER
//    
    Route::post('/power/reset-balance-operation', [SubstorePowerController::class, 'reset_balance_operation']);
    //subtore power controller
    Route::post('/power/substores', [SubstorePowerController::class, 'index']);
    Route::post('/power/substores/requisitions', [SubstorePowerController::class, 'requisitions']);
    Route::post('/power/substores/parts', [SubstorePowerController::class, 'parts']);
    Route::post('/power/substores/receives', [SubstorePowerController::class, 'receives']);
    Route::post('/power/substores/issues', [SubstorePowerController::class, 'issues']);

//    Substore Access Power control
    Route::post('/power/substores/access', [SubstoreAccessController::class, 'index']);
    Route::post('/power/substores/access-create', [SubstoreAccessController::class, 'store']);
    Route::post('/power/substores/access-show', [SubstoreAccessController::class, 'show']);
    Route::post('/power/substores/access-update', [SubstoreAccessController::class, 'update']);

    //Merchandising power

    Route::post('/power/merchandising', [MerchandisingPowerController::class, 'index']);
    Route::post('/power/merchandising/contracts', [MerchandisingPowerController::class, 'contracts']);
    Route::post('/power/merchandising/techpacks', [MerchandisingPowerController::class, 'techpacks']);
    Route::post('/power/merchandising/sors', [MerchandisingPowerController::class, 'sors']);
    Route::post('/power/merchandising/costings', [MerchandisingPowerController::class, 'costings']);
    Route::post('/power/merchandising/purchases', [MerchandisingPowerController::class, 'purchases']);
    Route::post('/power/merchandising/budgets', [MerchandisingPowerController::class, 'budgets']);
    Route::post('/power/merchandising/bookings', [MerchandisingPowerController::class, 'bookings']);
    Route::post('/power/merchandising/proformas', [MerchandisingPowerController::class, 'proformas']);

    //Teams route
    Route::post('/teams', [TeamController::class, 'index']);
    Route::post('/teams-create', [TeamController::class, 'store']);
    Route::post('/teams-show', [TeamController::class, 'show']);
    Route::post('/teams-update', [TeamController::class, 'update']);
    Route::post('/teams-delete', [TeamController::class, 'destroy']);

    //// employees  route
    Route::post('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees-create', [EmployeeController::class, 'store']);
    Route::post('/employees-show', [EmployeeController::class, 'show']);
    Route::post('/employees-update', [EmployeeController::class, 'update']);
    Route::post('/employees-update-password', [EmployeeController::class, 'change_password']);
    //    suppliers
    Route::post('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers-create', [SupplierController::class, 'store']);
    Route::post('/suppliers-show', [SupplierController::class, 'show']);
    Route::post('/suppliers-update', [SupplierController::class, 'update']);
    //    Departments route
    Route::post('/departments', [DepartmentController::class, 'index']);
    Route::post('/departments-create', [DepartmentController::class, 'store']);
    Route::post('/departments-show', [DepartmentController::class, 'show']);
    Route::post('/departments-update', [DepartmentController::class, 'update']);
//    designations route
    Route::post('/designations', [DesignationController::class, 'index']);
    Route::post('/designations-create', [DesignationController::class, 'store']);
    Route::post('/designations-show', [DesignationController::class, 'show']);
    Route::post('/designations-update', [DesignationController::class, 'update']);
//    companies route
    Route::post('/companies', [CompanyController::class, 'index']);
    Route::post('/companies-create', [CompanyController::class, 'store']);
    Route::post('/companies-show', [CompanyController::class, 'show']);
    Route::post('/companies-update', [CompanyController::class, 'update']);
    //    companies route
    Route::post('/buyers', [BuyerController::class, 'index']);
    Route::post('/buyers-create', [BuyerController::class, 'store']);
    Route::post('/buyers-show', [BuyerController::class, 'show']);
    Route::post('/buyers-update', [BuyerController::class, 'update']);
});

