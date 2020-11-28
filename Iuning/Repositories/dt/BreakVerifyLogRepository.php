<?php
namespace App\Repositories\dt;

use App\BreakVerifyLog;
use App\Employee;
use Lang;
use App\Repositories\dt\BaseDtRepo;

class BreakVerifyLogRepository extends BaseDtRepo
{
    protected $employee_id_to_name;
    protected $dt_special_col = ['action' => 'break_id','chkbox' => 'row_id'];

    public function __construct()
    {
        $this->model = new BreakVerifyLog;
        $this->builder = BreakVerifyLog::query();
        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
        $this->employee_id_to_name = $this->employeeIdToName();
    }

    protected function employeeIdToName()
    {
        $data = Employee::select('id', 'name')->get();
        $result = [];
        foreach ($data as $value) {
            $result[$value->id] = $value->name;
        }
        return $result;
    }
/**
 * 特殊欄位對應值處理
 *
 * @param  mixed $key = column name
 * @param  mixed $val = sql query result
 * @return string|int 欄位想要顯示的資料
 */
    public function dtSetResult($key, $val,$row)
    {
        switch ($key) {
            case 'verify_employee_id':
                if (isset($this->employee_id_to_name[$val])) {
                    return $this->employee_id_to_name[$val];
                } else {
                    return $val;
                }
                break;
            case 'verify_st':
                return Lang::get("break_verify_log.verify_st.$val");
            break;
            case 'action':
                $ar = [];
                $ar['id'] = $val;
                $ar['break_verify_id'] = $row->row_id;
                if($row->verify_st == 0){
                    $ar['edit'] = true;
                }else{
                    $ar['edit'] = false;
                }
                return $ar;
            break;
            default:
                return $val;
                break;
        }
        return $val;
    }
    //查詢 範圍
    public function SpacialQuery()
    {
        return $this->builder->where('verify_employee_id',resolve('User')['id'])->where('verify_st',0);
    }

    //欄位搜尋資料 組合 where 條件
    public function TableApiSearch($query, $searchParamsArray)
    {
        foreach ($searchParamsArray as $val) {
            if($val['col'] != 'chkbox' && $val['col'] != 'action'){
                $query = $query->where($val['col'], 'LIKE', '%' . $val['val'] . '%');
            }
        }
        return $query;
    }
}
