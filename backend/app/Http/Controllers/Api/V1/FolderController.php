<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Folder\StoreFolderRequest;
use App\Http\Requests\Folder\UpdateFolderRequest;
use App\Http\Resources\V1\BreadcrumbResource;
use App\Http\Resources\V1\FolderResource;
use App\Models\Folder;
use App\Services\Folder\DeleteFolderSubtreeService;
use App\Services\Folder\DetectCycleService;
use App\Services\Folder\GetBreadcrumbsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Folder::class, 'folder');
    }

    /**
     * GET /api/v1/folders?parent_id=
     */
    public function index(Request $request): JsonResponse
    {
        $query = Folder::query()->orderBy('name');

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->integer('parent_id'));
        } else {
            $query->whereNull('parent_id');
        }

        return response()->json([
            'success' => true,
            'message' => 'Folders retrieved',
            'data'    => FolderResource::collection($query->get()),
        ]);
    }

    public function store(StoreFolderRequest $request): JsonResponse
    {
        $folder = Folder::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Folder created successfully',
            'data'    => new FolderResource($folder),
        ], 201);
    }

    /**
     * Detail + direct children (explicitly loaded).
     */
    public function show(Folder $folder): JsonResponse
    {
        $folder->load('children.creator');

        return response()->json([
            'success' => true,
            'message' => 'Folder detail',
            'data'    => new FolderResource($folder),
        ]);
    }

    /**
     * Rename and/or move. A move is cycle-checked first.
     */
    public function update(
        UpdateFolderRequest $request,
        Folder $folder,
        DetectCycleService $cycleDetector
    ): JsonResponse {
        $targetParentId = $request->validated('parent_id');

        // Is this a move? (target parent differs from current)
        $isMoving = $targetParentId !== null
            && (int) $targetParentId !== $folder->parent_id;

        if ($isMoving && $cycleDetector->wouldCauseCycle($folder->id, (int) $targetParentId)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot move a folder into itself or any of its descendant subfolders.',
            ], 422);
        }

        $folder->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Folder updated successfully',
            'data'    => new FolderResource($folder->fresh()),
        ]);
    }

    public function destroy(Folder $folder, DeleteFolderSubtreeService $deleter): JsonResponse
    {
        $deleter->execute($folder);

        return response()->json([
            'success' => true,
            'message' => 'Folder and its subtree deleted successfully',
        ]);
    }

    /**
     * Root → this folder chain, in a single CTE query.
     */
    public function breadcrumbs(Folder $folder, GetBreadcrumbsService $breadcrumbs): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Breadcrumbs retrieved',
            'data'    => BreadcrumbResource::collection(
                $breadcrumbs->execute($folder->id)
            ),
        ]);
    }
}
