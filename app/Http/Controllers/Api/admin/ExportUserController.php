<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\UserExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportUserController extends Controller {
    /**
     * Handle the export.
     *
     * @return \Illuminate\Support\Facades\Response
     */
    public function export()
    {
        return Excel::download(new UserExport, 'users.xlsx');
    }
}
