<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AcademicYearController extends Controller
{
    /**
     * Menampilkan halaman manajemen Tahun Akademik.
     */
    public function index()
    {
        return view('academic-year.index');
    }

    /**
     * API: Mengambil semua data Tahun Akademik.
     */
    public function fetchAll()
    {
        try {
            $academicYears = DB::table('academic_years')
                ->select(
                    'id',
                    'year',
                    'semester',
                    'name', // <-- TAMBAHKAN INI
                    'start_date',
                    'end_date',
                    'is_active'
                )
                ->orderBy('year', 'desc')
                ->orderBy('semester', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Academic Year data loaded successfully.',
                'data' => $academicYears
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching academic years: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load data.',
                'data' => []
            ], 500);
        }
    }

    /**
     * API: Menyimpan data Tahun Akademik baru.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'year' => [
                    'required',
                    'string',
                    'max:9',
                    'regex:/^\d{4}\/\d{4}$/', // e.g., 2023/2024
                    Rule::unique('academic_years')->where(function ($query) use ($request) {
                        return $query->where('semester', $request->semester);
                    })
                ],
                'semester' => 'required|in:odd,even',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'is_active' => 'required|boolean',
            ], [
                'year.unique' => 'The combination of Year and Semester already exists.'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // <-- TAMBAHKAN BLOK INI: Generate 'name' otomatis -->
            $name = $request->year . ' - ' . ucfirst($request->semester);
            // <-- SELESAI BLOK TAMBAHAN -->

            DB::beginTransaction();

            // Jika 'is_active' = true, nonaktifkan semua yang lain
            if ($request->is_active == 1) {
                DB::table('academic_years')->update(['is_active' => false]);
            }

            DB::table('academic_years')->insert([
                'name' => $name, // <-- TAMBAHKAN INI
                'year' => $request->year,
                'semester' => $request->semester,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => (bool) $request->is_active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Academic Year created successfully.'
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error(
                'Error creating academic year: ' . $e->getMessage()
            );
            return response()->json([
                'success' => false,
                'message' => 'Error saving data.' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Menampilkan detail satu Tahun Akademik.
     */
    public function show($id)
    {
        try {
            $academicYear = DB::table('academic_years')->find($id);

            if (!$academicYear) {
                return response()->json(['success' => false, 'message' => 'Data not found.'], 404);
            }

            return response()->json(['success' => true, 'message' => 'Data loaded.', 'data' => $academicYear]);
        } catch (Exception $e) {
            Log::error('Error fetching academic year ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading data.'], 500);
        }
    }

    /**
     * API: Memperbarui data Tahun Akademik.
     */
    public function update(Request $request, $id)
    {
        try {
            if (!DB::table('academic_years')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Data not found.'], 404);
            }

            $validator = Validator::make($request->all(), [
                'year' => [
                    'required',
                    'string',
                    'max:9',
                    'regex:/^\d{4}\/\d{4}$/',
                    Rule::unique('academic_years')->where(function ($query) use ($request) {
                        return $query->where('semester', $request->semester);
                    })->ignore($id)
                ],
                'semester' => 'required|in:odd,even', // <-- SAYA PERBAIKI (dari Odd,Even)
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'is_active' => 'required|boolean',
            ], [
                'year.unique' => 'The combination of Year and Semester already exists.'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // <-- TAMBAHKAN BLOK INI: Generate 'name' otomatis -->
            $name = $request->year . ' - ' . ucfirst($request->semester);
            // <-- SELESAI BLOK TAMBAHAN -->

            DB::beginTransaction();

            // Jika 'is_active' = true, nonaktifkan semua yang lain
            if ($request->is_active == 1) {
                DB::table('academic_years')->where('id', '!=', $id)->update(['is_active' => false]);
            }

            DB::table('academic_years')->where('id', $id)->update([
                'name' => $name, // <-- TAMBAHKAN INI
                'year' => $request->year,
                'semester' => $request->semester,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => (bool) $request->is_active,
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Academic Year updated successfully.']);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating academic year ID ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating data.'], 500);
        }
    }

    /**
     * API: Menghapus data Tahun Akademik.
     */
    public function destroy($id)
    {
        try {
            if (!DB::table('academic_years')->where('id', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Data not found.'], 404);
            }

            // Tambahkan logika: jangan biarkan menghapus tahun akademik yang aktif
            $isActive = DB::table('academic_years')->where('id', $id)->value('is_active');
            if ($isActive) {
                return response()->json(['success' => false, 'message' => 'Cannot delete the active academic year.'], 400);
            }

            DB::beginTransaction();
            DB::table('academic_years')->where('id', $id)->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Academic Year deleted successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting academic year ID ' . $id . ': ' . $e->getMessage());
            // Tangani foreign key constraint
            if ($e->getCode() == 23000) {
                return response()->json(['success' => false, 'message' => 'Cannot delete. This academic year is in use by other data.'], 500);
            }
            return response()->json(['success' => false, 'message' => 'Error deleting data.'], 500);
        }
    }
}
