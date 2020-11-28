<?php
namespace App\Repositories;

use App\CustomerSalesPlatform;
use App\Repositories\BaseRepository;
use Auth;

class CustomerSalesPlatformRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new CustomerSalesPlatform;
        $this->builder = CustomerSalesPlatform::query();
        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
    }

    public function store($reqData)
    {
        foreach ($reqData as $key => $value) {
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

    public function update($whereArray,$reqData)
    {
        $this->model->where($whereArray)->update($reqData);
        return 1;
    }

    /**
     * 找到業務正在經營中的客戶的資料
     *
     * @param  mixed $sales_id
     * @param  mixed $customer_id
     * @return void
     */
    public function getDataSalesCustomer($sales_id,$customer_id)
    {
        $this->renewBuilder();
        return $this->builder->where([
            'customer_id' => $customer_id,
            'sales_id' => $sales_id,
            'effective' => 1
        ])->whereNotNull('receive_date')
        ->whereNull('giveup_date')
        ->get()
        ->toArray();
    }

    /**
     * 更新業務接收客戶日期
     *
     * @param  mixed $customer
     * @param  mixed $sales
     * @return void
     */
    public function updateReceive($row_id)
    {
        // $this->model->findWaitReceiveCustomer($sales)
        //             ->where('customer_id',$customer)
        //             ->update([
        //                 'receive_date' => date("Y-m-d H:i:s")
        //             ]);
        $this->model->where('row_id',$row_id)
                    ->update([
                        'receive_date' => date("Y-m-d H:i:s")
                    ]);
    }

    /**
     * 取得業務等待接收客戶
     *
     * @return void
     */
    public function getUserReceive()
    {
        return $this->builder->with('Customer')->findWaitReceiveCustomer(Auth::user()->id)->get()->toArray();
    }

    /**
     * 業務正在經營中的客戶們
     *
     * @param  mixed $sales_id
     * @param  mixed $customer_id
     * @return void
     */
    public function findSalesCustomer($sales_id,$customer_id)
    {
        return $this->builder
            ->findActiveCustomerBySales($sales_id)
            ->where('customer_id',$customer_id)
            ->get()
            ->toArray();
    }

    /**
     * 檢查客戶是否有等待中的狀態
     *
     * @param  mixed $customer_id
     * @return void
     */
    public function checkCustomerWaiting($customer_id)
    {
        $count = $this->builder->findWaitReceiveCustomerByCustomer($customer_id)->count();
        if($count > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * findInServiceByCustomer
     * 用客戶ID找經營中資料
     *
     * @param  mixed $customer_id
     * @return void
     */
    public function findInServiceByCustomerIds($customer_ids)
    {
        return (new CustomerSalesPlatform)->findInServiceByCustomerIds($customer_ids)->get()->toArray();
    }
    /**
     * findWaitReceiveByCustomerIds
     * 用客戶ID找經營中資料
     *
     * @param  mixed $customer_id
     * @return void
     */
    public function findWaitReceiveByCustomerIds($customer_ids)
    {
        return (new CustomerSalesPlatform)->findWaitReceiveByCustomerIds($customer_ids)->get()->toArray();
    }

    /**
     * 取得客戶最近被放棄的資料(3筆)
     *
     * @param  mixed $id
     * @return void
     */
    public function getGiveUpData($id)
    {
        return (new CustomerSalesPlatform)->getGiveUpDataByCustomerId($id)->orderBy('giveup_date','desc')->limit(3)->get()->toArray();
    }


    // public function edit($id)
    // {
    //     return $this->builder->with(['CustomerEmployed','CustomerSelfEmployed'])->where('id',$id)->get()->toArray();
    // }

    // public function update($reqData,$id)
    // {
    //     $model = $this->builder->find($id);
    //     foreach ($reqData as $key => $value) {
    //         $model->$key = $value;
    //     }
    //     if($model->save()){
    //         return $model->id;
    //     }else{
    //         return -1;
    //     }
    // }

    /* -------------------------------------------------------------------------- */

}
