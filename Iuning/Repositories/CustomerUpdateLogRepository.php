<?php
namespace App\Repositories;

use App\CustomerUpdateLog;
use App\Repositories\BaseRepository;

class CustomerUpdateLogRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new CustomerUpdateLog;
        $this->builder = CustomerUpdateLog::query();
        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
    }

    public function store($reqData)
    {
        foreach ($reqData as $key => $value)
        {
            if(in_array($key,$this->columns)){
                $this->model->$key = $value;
            }
        }
        if($this->model->save()){
            return $this->model->id;
        }else{
            return -1;
        }
    }
}
