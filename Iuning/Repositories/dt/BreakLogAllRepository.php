<?php
namespace App\Repositories\dt;

use App\BreakLog;
use App\BreakType;
use App\Employee;
use App\Repositories\dt\BaseDtRepo;
use DB;
use Lang;

class BreakLogAllRepository extends BaseDtRepo
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
        return $this->builder->where('start_date', '>=', date('Y-m-d'))->where('verify_st', 1);
    }

    //欄位搜尋資料 組合 where 條件
    public function TableApiSearch($query, $searchParamsArray)
    {
        $searchParamsArray = $this->preProccessSearch($searchParamsArray);

        $query = $query->where(function ($q) use ($searchParamsArray) {
            foreach ($searchParamsArray as $val) {
                switch ($val['col']) {
                    case 'employee_id':
                    case 'vic_employee_id':
                        $ids = Employee::select(DB::raw("GROUP_CONCAT(id) as ids"))->where('name', 'LIKE', '%' . $val['val'] . '%')->get()->toArray();
                        if(isset($ids[0]['ids'])){
                            $ids_array = explode(',',$ids[0]['ids']);
                            $q = $q->whereIn($val['col'], $ids_array);
                        }
                        break;
                    case 'apply_date':
                    case 'start_date':
                    case 'end_date':
                        $col = $val['col'];
                        $q = $q->where(DB::raw("YEAR($col)"), $val['val']);
                        break;
                    case 'department':
                        $data = DepartmentEmployee::select(DB::raw("GROUP_CONCAT(employee_id) as employee_id"))->where('department_id', $val['val'])->groupBy('department_id')->get()->toArray();
                        $q = $q->whereIn('employee_id', explode(',', $data[0]['employee_id']));
                        break;
                    default:
                        $q = $q->where($val['col'], 'LIKE', '%' . $val['val'] . '%');
                        break;
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
                // case 'employee_id':
                // case 'vic_employee_id':
                    //待補
                    // break;

                default:
                    array_push($newSearchParamsArray, $arr);
                    break;
            }
        }
        return $newSearchParamsArray;
    }

}
