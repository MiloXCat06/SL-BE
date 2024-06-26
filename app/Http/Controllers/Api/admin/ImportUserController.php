<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ImportUserController extends Controller
{
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()]);
        }

        try {
            $filePath = $request->file('file')->getPathname();
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $columns = range('A', $worksheet->getHighestDataColumn());
            $headerRow = 1;

            $columnMapping = [
                'name' => null,
                'email' => null,
                'password' => null,
                'status' => null,
            ];

            // Map columns based on header
            foreach ($columns as $column) {
                $cellValue = strtolower($worksheet->getCell($column . $headerRow)->getValue());
                if (in_array($cellValue, array_keys($columnMapping))) {
                    $columnMapping[$cellValue] = $column;
                }
            }

            // Check if all required columns are found
            if (in_array(null, $columnMapping)) {
                return response()->json(['error' => 'Salah satu atau lebih kolom yang diperlukan tidak ditemukan dalam file.']);
            }

            $dataStartRow = 2;
            $dataEndRow = $worksheet->getHighestDataRow();

            $usersData = [];

            for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
                $user = [];
                $isRowEmpty = true;
                foreach ($columnMapping as $key => $column) {
                    $value = $worksheet->getCell($column . $row)->getValue();
                    if ($key == 'password') {
                        $value = Hash::make($value); // Hash password before storing
                    }
                    $user[$key] = $value;
                    if (!empty($value)) {
                        $isRowEmpty = false;
                    }
                }
                if ($isRowEmpty) {
                    // Skip empty rows
                    continue;
                }
                // Check if all required data is present
                if (array_search(null, $user, true) !== false) {
                    return response()->json(['error' => "Data tidak lengkap pada baris $row. Semua kolom harus diisi."]);
                }
                $usersData[] = $user;
            }

            foreach ($usersData as $data) {
                $user = User::create($data);

                // Assign role to the user
                $role = Role::findByName('anggota');
                $user->assignRole($role);

                // Assign permissions based on the role
                $permissions = $role->permissions;
                $user->syncPermissions($permissions);
            }

            return response()->json(['success' => 'Data pengguna berhasil di-import!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan dalam mengimpor data pengguna: ' . $e->getMessage()]);
        }
    }
}
