<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Bank;

class CommonController extends Controller {

    public function countries() {
        return Country::all();
    }

    public function currencies() {
        return Currency::all();
    }

    public function banks() {
        return Bank::all();
    }

    public function store(Request $request) {
        return "Created Success";
    }

    public function show(Request $request) {
        return "Showing Success";
    }

    public function update(Request $request) {
        return "Updated Success";
    }

    public function destroy(Request $request) {
        return "Deleted Success";
    }

}
