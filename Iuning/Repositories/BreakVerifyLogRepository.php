<?php
namespace App\Repositories;

use App\BreakVerifyLog;
use App\Repositories\BaseRepository;
use App\BreakLog;

class BreakVerifyLogRepository extends BaseRepository
{
    protected $dt_special_col = ['action' => 'row_id'];

    public function __construct()
    {
        $this->model = new BreakVerifyLog;
        $this->builder = BreakVerifyLog::query();
        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
    }

    public function updateStatus($reqData)
    {
        $model = $this->builder->find($reqData['id']);
        $model->verify_st = $reqData['verify_st'];
        $model->verify_memo = $reqData['verify_memo'];
        $model->verify_datetime = date("Y-m-d H:i:s");
        $model->save();
        // $this->builder->find($reqData['id'])->update([
        //     'verify_st' => $reqData['verify_st'],
        //     'verify_memo' => $reqData['verify_memo'],
        //     'verify_datetime' => date("Y-m-d H:i:s"),
        // ]);
        $break_id = $model->break_id;
        $this->updateBreakLogStatus($break_id);
        return true;
    }

    public function updateStatusMultiple($reqData)
    {
        foreach ($reqData['id'] as $key => $value) {
            $model = new BreakVerifyLog;
            $model = $model->find($value);
            $model->verify_st = $reqData['verify_st'];
            $model->verify_memo = $reqData['verify_memo'];
            $model->verify_datetime = date("Y-m-d H:i:s");
            $break_id = $model->break_id;
            $model->save();
            $this->updateBreakLogStatus($break_id);
        }
        return true;
    }

    public function updateBreakLogStatus($break_id)
    {
        $data = $this->model->select('verify_st')->where('break_id',$break_id)->distinct()->get()->toArray();
        if(count($data) == 1){
            if($data[0]['verify_st'] != 0){
                $breakLog = new BreakLog;
                $breakLog = $breakLog->find($break_id);
                $breakLog->verify_st = $data[0]['verify_st'];
                $breakLog->save();
            }
        }else{
            $verify_st_array = [];
            foreach ($data as $value) {
                array_push($verify_st_array,$value['verify_st']);
            }
            if(in_array(2,$verify_st_array)){
                //肯定是被駁回了 ... 吧
                $breakLog = new BreakLog;
                $breakLog = $breakLog->find($break_id);
                $breakLog->verify_st = 2;
                $breakLog->save();
            }
        }
    }

    /**
     * 用BreakLog id 找資料
     *
     * @param  mixed $breakLog_id
     * @return void
     */
    public function findByBreakId($breakLog_id)
    {
        return $this->builder->with('employee')->where('break_id',$breakLog_id)->get();
    }
}
