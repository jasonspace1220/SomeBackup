<?php
namespace App\Traits;

use Cache;
use Carbon\Carbon;
use App\Employee;
use App\CustomerQueueSales;

trait CacheUserOnline {

    /**
     * middlewareCheck
     * 檢查如果使用者在線上，
     * 則刷新其Cache時間
     *
     * @param  mixed $user_id
     * @return void
     */
    public function middlewareCheck($user_id)
    {
        if (Cache::has('user-is-online-' . $user_id)){
            $data = Cache::get('user-is-online-' . $user_id);
            $this->setUserOnlineById($user_id,$data);
        }
    }

    /**
     * cacheExpiresAt
     * 回傳Cache的ExpireTime
     *
     * @return void
     */
    public function cacheExpiresAt()
    {
        return Carbon::now()->addMinutes(config('params.online_timeout'));
    }

    /**
     * setUserOnline setting cache to record user is online
     *
     * @param  int $user_id
     * @return void
     */
    public function setUserOnlineById($user_id,$data)
    {
        Cache::put('user-is-online-' . $user_id, $data, $this->cacheExpiresAt());
    }

    /**
     * showAllOnlineUser
     * 顯示在線上的使用者
     *
     * @return void
     */
    public function showAllOnlineUser()
    {
        $result = [];
        $users = Employee::all();
        foreach ($users as $user) {
            if (Cache::has('user-is-online-' . $user->id)){
                $data = Cache::get('user-is-online-' . $user->id);
                $result[$user->name] = $data;
            }
        }
        return $result;
    }

    public function checkUserById($user_id)
    {
        if (Cache::has('user-is-online-' . $user_id)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 刪除
     *
     * @return void
     */
    public function deleteCacheById($user_id)
    {
        if (Cache::has('user-is-online-' . $user_id)){
            Cache::pull('user-is-online-' . $user_id);
        }
    }


    /**
     * checkSalesQueueStatus
     * 檢查業務在線排班狀況
     *
     * @return void
     */
    public function getSalesInQueueCount()
    {
        $this->refreshQueueStatus();
        return count($this->getSalesInQueue());
    }

    /**
     * refreshQueueStatus
     * 刷新業務住列的狀態
     *
     * @return void
     */
    public function refreshQueueStatus()
    {
        $salesInQueue = $this->getSalesInQueue();

        foreach ($salesInQueue as $key => $v) {
            if (!Cache::has('user-is-online-' . $v['sales_id'])){
                //如果在Cache中並沒有這個sales代表這業務被caceh timeout了，
                //已下線
                $customerQueueSales = CustomerQueueSales::find($v['sales_id']);
                $customerQueueSales->status = 0;
                $customerQueueSales->save();
            }
        }
    }

    /**
     * getSalesInQueue
     * 在佇列中排隊的業務
     *
     * @return void
     */
    public function getSalesInQueue()
    {
        $lastAssignTimeout = date("Y-m-d H:i:s",strtotime("-15 minutes",strtotime(date("Y-m-d H:i:s"))));
        // return CustomerQueueSales::where('status',1)->where('last_assign_datetime','>=',$lastAssignTimeout)->get()->toArray();
        return CustomerQueueSales::where('status',1)->where(function($query) use($lastAssignTimeout){
            $query->where('last_assign_datetime','<=',$lastAssignTimeout)
                   ->orWhereNull('last_assign_datetime');
        })->orderBy('join_datetime','asc')->get()->toArray();
    }

    /**
     * updateLastAssignTime
     * 更新業務最後被分配客戶時間
     *
     * @param  mixed $sales_id
     * @return void
     */
    public function updateLastAssignTime($sales_id)
    {
        CustomerQueueSales::where('sales_id',$sales_id)->where('status',1)->update([
            'last_assign_datetime' => date("Y-m-d H:i:s")
        ]);
    }
}
