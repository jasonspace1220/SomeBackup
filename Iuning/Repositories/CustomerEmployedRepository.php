<?php
namespace App\Repositories;

use App\CustomerEmployed;
use App\Repositories\BaseRepository;

class CustomerEmployedRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new CustomerEmployed;
        $this->builder = CustomerEmployed::query();
        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
    }

    public function store($reqData)
    {
        foreach ($reqData as $key => $value) {
            $this->model->$key = $value;
        }
        if($this->model->save()){
            return $this->model->id;
        }else{
            return -1;
        }
    }
}
