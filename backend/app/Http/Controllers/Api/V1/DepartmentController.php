<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Http\Resources\V1\DepartmentResource;

use App\Models\Department;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentController extends Controller {
  public function __construct() {
    // index→viewAny, store→create, update→update, destroy→delete
    $this->authorizeResource(Department::class, 'department');
  }

/**
     * GET /api/v1/departments — all role.
     */
  public function index(): AnonymousResourceCollection {
    return DepartmentResource::collection(
      Department::query()->orderBy('name')->get()
    );
  }


/**
     * POST /api/v1/departments — admin only.
     */
  public function store(StoreDepartmentRequest $request):JsonResponse {
    $department = Department::create($request->validated());

    return response()->json([
      'success' => true,
      'message' => 'Department created successfully',
      'data' => new DepartmentResource($department),
    ], 201);
  }

  /**
   * GET /api/v1/departments/{id} - all role
   */
  public function show(Department $department): JsonResponse {
    return response()->json([
      'success' => true,
      'message' => 'Department detail',
      'data' => new DepartmentResource($department)
    ]);
  }

  /**
     * PUT /api/v1/departments/{id} — admin only.
     */
  public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse {
    $department->update($request->validated());

    return response()->json([
      'success' => true,
      'message' => 'Department updated successfully',
      'data' => new DepartmentResource($department->fresh())
    ]);
  }

   /**
     * DELETE /api/v1/departments/{id} — admin only.
     */
   public function destroy(Department $department): JsonResponse {
    $department->delete();

    return response()->json([
      'success' => true,
      'message' => 'Department deleted successfully'
    ]);
   }

}
