<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\Permission\Models\Permission;

class UserImport implements ToCollection, WithHeadingRow
{
    /**
     * Handle the import.
     *
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Buat pengguna baru
            $user = User::create([
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => Hash::make($row['password']),
                'status' => $row['status'],
                'role_id' => 3, // Tetapkan role_id 3 ('anggota')
            ]);

            // Dapatkan semua permissions dari database dan tetapkan ke pengguna
            $permissions = Permission::all();
            foreach ($permissions as $permission) {
                $user->givePermissionTo($permission->name);
            }
        }
    }
}