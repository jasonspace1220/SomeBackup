<?php
namespace App\Repositories;

use App\Contact;
use App\Repositories\BaseRepository;
use Auth;
use App\Customer;

class ContactRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new Contact;
        $this->builder = Contact::query();
        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
    }

    public function getRecordByCustomer($customer_id)
    {
        $builder = $this->builder;
        $builder->with(['ContactResult','ContactWay','Employee']);
        $builder->where('customer_id',$customer_id);
        return $builder->get()->toArray();
    }

    public function delete($row_id)
    {
        return $this->model->find($row_id)->delete();
    }

    public function show($row_id)
    {
        return $this->model->find($row_id)->toArray();
    }

    public function update($data,$row_id)
    {
        if(isset($data['contact_time_ed'])){
            $this->updateCustomerLastContact($data['customer_id']);
        }
        return $this->model->find($row_id)->update($data);
    }

    public function updateCustomerLastContact($customer_id)
    {
        Customer::find($customer_id)->update([
            'last_contact_datetime' => date("Y-m-d H:i:s")
        ]);
    }
}
