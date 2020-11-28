<?php
namespace App\Repositories;

use App\BookMark;
use App\Repositories\BaseRepository;
use Auth;
use App\Customer;

class BookMarkRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new BookMark;
        $this->builder = BookMark::query();
        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
    }

    public function countUserBookMarkName($name,$user_id)
    {
        return $this->builder->where(['name'=>$name,'create_by'=>$user_id])->count();
    }

    public function delete($id)
    {
        $m = BookMark::find($id);
        $m->CustomerBookMark()->delete();
        $m->delete();
    }

    public function show($id)
    {
        return $this->model->find($id)->toArray();
    }

    public function update($data,$row_id)
    {
        return $this->model->find($row_id)->update($data);
    }

    public function updateCustomerLastContact($customer_id)
    {
        Customer::find($customer_id)->update([
            'last_contact_datetime' => date("Y-m-d H:i:s")
        ]);
    }
}
