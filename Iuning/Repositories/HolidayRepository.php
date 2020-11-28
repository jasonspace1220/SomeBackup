<?php
namespace App\Repositories;

use App\Holiday;

class HolidayRepository extends BaseRepository
{

    public function __construct()
    {
        $this->model = new Holiday;
        $this->builder = Holiday::query();
        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
    }

    /**
     * 新增資料
     *
     * @param  mixed $reqData
     * @return int
     */
    public function createNewData(array $reqData): int
    {
        $result = 0;
        foreach ($reqData as $key => $value) {
            $this->model->$key = $value;
        }
        if ($this->model->save()) {
            $result = 1;
        } else {
            $result = 0;
        }
        return $result;
    }
}
