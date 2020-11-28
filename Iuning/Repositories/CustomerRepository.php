<?php

namespace App\Repositories;

use App\Customer;
use App\Repositories\BaseRepository;
use Auth;

class CustomerRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new Customer;
        $this->builder = Customer::query();
        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
    }

    public function store($reqData)
    {
        foreach ($reqData as $key => $value) {
            $this->model->$key = $value;
        }
        if ($this->model->save()) {
            return $this->model->id;
        } else {
            return -1;
        }
    }

    public function edit($id)
    {
        // $sales_id = Auth::user()->id;

        $data = $this->builder
            ->with(['withEffectiveCustomer', 'CustomerEmployed', 'CustomerSelfEmployed', 'WebSiteCustomer'])
            ->where('id', $id)
            ->get()
            ->toArray();

        return $data;
    }

    public function update($reqData, $id)
    {
        $model = $this->builder->find($id);
        foreach ($reqData as $key => $value) {
            $model->$key = $value;
        }
        if ($model->save()) {
            return $model->id;
        } else {
            return -1;
        }
    }

    // public function getRelation($relation_id)
    // {
    //     $this->renewBuilder();
    //     $data = $this->builder->where('relation_id',$relation_id)->get();
    // }

    // public function search($reqData)
    // {
    //     $data = $this->proccessDateSearch($reqData);
    //     foreach ($data as $key => $value) {
    //         $this->builder->where($key,'LIKE','%'.$value.'%');
    //     }
    //     return $this->builder->with('CustomerSalesPlatform')->get()->toArray();
    // }
    public function search($reqData)
    {
        $sales_id = Auth::user()->id;
        $data = $this->proccessDateSearch($reqData);
        foreach ($data as $key => $value) {
            $this->builder->where($key, 'LIKE', '%' . $value . '%');
        }
        return $this->builder->with('withEffectiveCustomer')->get()->toArray();
    }


    public function proccessDateSearch($data)
    {
        $data = $this->dateSelectCondition('create_date_start', 'create_date_end', 'first_create_datetime', $data);
        $data = $this->dateSelectCondition('update_date_start', 'update_date_end', 'last_update_datetime', $data);
        $data = $this->dateSelectCondition('contact_date_start', 'contact_date_end', 'last_contact_datetime', $data);
        return $data;
    }
    public function dateSelectCondition($start, $end, $key, $data)
    {
        if (isset($data[$start]) && isset($data[$end])) {
            $s = date('Y-m-d 00:00:00', strtotime($data[$start]));
            $e = date('Y-m-d 23:59:59', strtotime($data[$end]));
            $this->builder->whereBetween($key, [$s, $e]);
        }
        unset($data[$start]);
        unset($data[$end]);
        return $data;
    }


    /**
     * 取得手機或Email重複的Customer
     *
     * @param  mixed $phone
     * @param  mixed $email
     * @return void
     */
    public function getCustomerRepeat(array $phone, $email)
    {
        return $this->builder->whereIn('cell_1', $phone)
            ->orWhereIn('cell_2', $phone)
            ->orWhereIn('cell_3', $phone)
            ->orWhere('email', $email)
            ->get()
            ->toArray();
    }

    /**
     * 取得手機在資料庫中出現的數量
     *
     * @param  mixed $cell
     * @return void
     */
    public function checkCell($cell)
    {
        return $this->scopeFindByPhone($cell)->get()->toArray();
    }

    public function scopeFindByPhone($phone)
    {
        return $this->builder->where('cell_1', $phone)
            ->orWhere('cell_2', $phone)
            ->orWhere('cell_3', $phone);
    }
}
