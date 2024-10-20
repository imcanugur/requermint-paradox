<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait Controller
{
    protected $service;

    protected function success($result, $message = 'Success', $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $result,
            'message' => $message,
        ];

        return response()->json($response, $code);
    }

    protected function error($message = 'Error', array $errors = [], $code = 500): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function pagination(Request $request): array
    {
        return $request->validate([
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1|max:50',
        ]) + [
            'page' => 1,
            'perPage' => 10,
        ];
    }

    public function index(Request $request)
    {
        $validated = $this->pagination($request);
        $records = $this->service->getAll($validated['page'], $validated['perPage'], $this->filters($request));

        return $this->success($records, 'Record retrieved successfully.');
    }

    /**
     * @param  array  $filters
     *                          - Simple key-value pairs (e.g., ['status' => 'active'])
     *                          - Date range filters (e.g., ['created_at' => ['from' => '2024-01-01', 'to' => '2024-12-31']])
     *                          - In array filters (e.g., ['category_id' => ['in' => [1, 2, 3]]])
     *                          - Combined filters (e.g., ['status' => 'active', 'created_at' => ['from' => '2024-01-01', 'to' => '2024-12-31'], 'category_id' => ['in' => [1, 2, 3]]])
     *                          - ...
     */
    public function filters(Request $request)
    {
        return []; // $request->only(['category_id', ..]);
    }

    public function show(string $id)
    {
        $record = $this->service->getById($id);

        if (! $record) {
            return $this->error('Record not found.', [], 404);
        }

        return $this->success($record, 'Record retrieved successfully.');
    }

    public function destroy(string $id)
    {
        $deleted = $this->service->delete($id);

        if (! $deleted) {
            return $this->error('Record not found.', [], 404);
        }

        return $this->success([], 'Record deleted successfully.');
    }

    public function search(Request $request)
    {
        $search = collect($request->all())->filter(function ($value, $key) {
            return in_array($key, ['name', 'title']) || Str::endsWith($key, 'description'); // ['name', 'title', ...]
        })->toArray();

        if (! $search) {
            return response()->json(['message' => 'Search query is required'], 400);
        }
        $record = $this->service->search($search);

        return $this->success($record, 'Record retrieved successfully.');
    }
}
