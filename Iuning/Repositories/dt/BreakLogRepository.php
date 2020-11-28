<?php
namespace App\Repositories\dt;

use App\BreakLog;
use App\BreakType;
use App\Employee;
use App\Repositories\dt\BaseDtRepo;
use Lang;

class BreakLogRepository extends BaseDtRepo
{
    protected $employee_id_to_name;
    protected $break_type_id_to_name;
    protected $dt_special_col = ['action' => 'row_id', 'chkbox' => 'row_id'];

    protected $timeing_col = [
        'apply_date',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
    ];
    public function __construct()
    {
        $this->model = new BreakLog;
        $this->builder = BreakLog::query();
        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
        $this->employee_id_to_name = $this->employeeIdToName();
        $this->break_type_id_to_name = BreakType::idToName();
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
    // protected function breakTypeIdToName()
    // {
    //     $data = BreakType::select('id', 'name')->get();
    //     $result = [];
    //     foreach ($data as $value) {
    //         $result[$value->id] = $value->name;
    //     }
    //     return $result;
    // }
/**
 * 特殊欄位對應值處理
 *
 * @param  mixed $key = column name
 * @param  mixed $val = sql query result
 * @return string|int 欄位想要顯示的資料
 */
    public function dtSetResult($key, $val, $row)
    {
        switch ($key) {
            case 'employee_id':
            case 'vic_employee_id':

                if (isset($this->employee_id_to_name[$val])) {
                    return $this->employee_id_to_name[$val];
                } else {
                    return $val;
                }
                break;
            case 'break_type':

                if (isset($this->break_type_id_to_name[$val])) {
                    return $this->break_type_id_to_name[$val];
                } else {
                    return $val;
                }
                break;
            case 'verify_st':
                if (is_null($val)) {
                    return Lang::get("break_log.verify_st.null");
                }
                return Lang::get("break_log.verify_st.$val");
                break;

            case 'action':
                $ar = [];
                $ar['id'] = $val;
                $ar['break_verify_id'] = $row->row_id;
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
        return $this->builder->where('employee_id', resolve('User')['id']);
    }

    //欄位搜尋資料 組合 where 條件
    public function TableApiSearch($query, $searchParamsArray)
    {
        $searchParamsArray = $this->preProccessSearch($searchParamsArray);

        $query = $query->where(function($q) use ($searchParamsArray){
            foreach ($searchParamsArray as $val) {
                if($this->checkHasChinese($val['val'])){
                    //有中文
                    if(!in_array($val['col'],$this->timeing_col )){
                        $q = $q->orWhere($val['col'], 'LIKE', '%' . $val['val'] . '%');
                    }
                }else{
                    $q = $q->orWhere($val['col'], 'LIKE', '%' . $val['val'] . '%');
                }
            }
        });
        return $query;
    }

    /**
     * 搜尋前處理
     *
     * @param  mixed $searchParamsArray
     * @return void
     */
    public function preProccessSearch($searchParamsArray)
    {
        $newSearchParamsArray = [];
        foreach ($searchParamsArray as $arr) {
            switch ($arr['col']) {
                case 'employee_id':
                case 'vic_employee_id':
                    //待補
                    break;

                default:
                    array_push($newSearchParamsArray,$arr);
                    break;
            }
        }
        return $newSearchParamsArray;
    }

}
