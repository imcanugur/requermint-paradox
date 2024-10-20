<?php

namespace App\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait Repository
{
    protected Model $model;

    protected ?string $tenantId = null;

    protected bool $isTenantId;

    public function initialize(Model $model, bool $isTenantId = false): void
    {
        $this->model = $model;
        $this->isTenantId = $isTenantId;

        $this->tenantId = \request()->header('X-Tenant-Id') ?? '';

        if (! Str::isUuid($this->tenantId)) {
            abort(403, 'Invalid Tenant ID. Must be a valid UUID.');
        }
    }

    /**
     * @param  array  $filters
     *                          - Simple key-value pairs (e.g., ['status' => 'active'])
     *                          - Date range filters (e.g., ['created_at' => ['from' => '2024-01-01', 'to' => '2024-12-31']])
     *                          - In array filters (e.g., ['category_id' => ['in' => [1, 2, 3]]])
     *                          - Combined filters (e.g., ['status' => 'active', 'created_at' => ['from' => '2024-01-01', 'to' => '2024-12-31'], 'category_id' => ['in' => [1, 2, 3]]])
     *                          - ...
     */
    public function getAll(int $page = 1, int $perPage = 10, array $filters = [])
    {
        $query = $this->model->orderBy('created_at', 'desc');

        if ($this->isTenantId) {
            $query->where('tenant_id', $this->tenantId);
        }

        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                if (isset($value['from']) && isset($value['to'])) {
                    $query->whereBetween($key, [$value['from'], $value['to']]);
                } elseif (isset($value['in'])) {
                    $query->whereIn($key, $value['in']);
                }
            } else {
                $query->where($key, $value);
            }
        }

        //  return $query->paginate($perPage, ['*'], 'page', $page);
        return $query->get();
    }

    public function getById(string $id): ?Model
    {
        $query = $this->model->where('id', $id);

        if ($this->isTenantId) {
            $query->where('tenant_id', $this->tenantId);
        }

        return $query->first();
    }

    public function findByIds(array $ids)
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    public function create(array $data): Model
    {
        if ($this->isTenantId) {
            $data['tenant_id'] = $this->tenantId;
        }

        return $this->model->create($data);
    }

    public function update(string $id, array $data): ?Model
    {
        $model = $this->getById($id);
        if (! $model) {
            return null;
        }

        if ($this->isTenantId && $model->tenant_id !== $this->tenantId) {
            abort(403, 'Tenant ID mismatch.');
        }

        $model->update($data);

        return $model;
    }

    public function delete(string $id): bool
    {
        $model = $this->getById($id);
        if (! $model || ($this->isTenantId && $model->tenant_id !== $this->tenantId)) {
            return false;
        }

        $model->delete();

        return true;
    }

    public function search(array $search)
    {
        $query = $this->model->newQuery();

        foreach ($search as $key => $value) {
            $query->where($key, 'like', "%{$value}%");
        }

        return $query->get();
    }
}
