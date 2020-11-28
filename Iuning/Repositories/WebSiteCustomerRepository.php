<?php
namespace App\Repositories;

use App\WebSiteCustomer;
use App\Repositories\BaseRepository;

class WebSiteCustomerRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new WebSiteCustomer;
        $this->builder = WebSiteCustomer::query();
        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
    }

    public function update($whereArray,$updateArray)
    {
        $this->model->where($whereArray)->update($updateArray);
        return 1;
    }

    public function findByCustomerId($customer_id)
    {
        return $this->model->where('customer_id',$customer_id)->get()->toArray();
    }

    /**
     * 尋找已分配 等待接收 超時的客戶
     *
     * @return void
     */
    public function findReceiveTimeOut()
    {
        $webSiteCondition = [
            ['process_flag',1],
            ['data_process_due','<=',date("Y-m-d H:i:s")]
        ];
        return $this->basicAndWhere($webSiteCondition,$this->builder)->get()->toArray();
    }

    /**
     * getWaitingData
     * 取得等待分配的web客戶
     *
     * @param  mixed $limit 有輸入則限制數量
     * @return void
     */
    public function getWaitingData($limit=false)
    {
        $builder = $this->builder;
        $builder = $builder->whereNull('process_flag');
        $builder = $builder->orWhere('process_flag',0);
        if($limit){
            $builder = $builder->limit($limit);
        }
        return $builder->get()->toArray();
    }

}
