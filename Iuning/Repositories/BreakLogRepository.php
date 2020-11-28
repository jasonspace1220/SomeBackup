<?php
namespace App\Repositories;

use App\BreakLog;
use App\Repositories\BaseRepository;

class BreakLogRepository extends BaseRepository
{
    protected $dt_special_col = ['action' => 'row_id'];

    public function __construct()
    {
        $this->model = new BreakLog;
        $this->builder = BreakLog::query();
        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
    }

    public function updateStatus($reqData)
    {
        return $this->builder->find($reqData['id'])->update([
            'verify_st' => $reqData['verify_st'],
            'verify_memo' => $reqData['verify_memo'],
            'verify_datetime' => date("Y-m-d H:i:s"),
        ]);
    }

    public function updateStatusMultiple($reqData)
    {
        foreach ($reqData['id'] as $key => $value) {
            BreakLog::find($value)->update([
                'verify_st' => $reqData['verify_st'],
                'verify_memo' => $reqData['verify_memo'],
                'verify_datetime' => date("Y-m-d H:i:s"),
            ]);
        }
        return true;
    }
}
