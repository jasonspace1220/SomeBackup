<?php
namespace App\Repositories\dt;

use App\BreakLog;
use App\BreakType;
use App\DepartmentEmployee;
use App\Employee;
use App\Repositories\dt\BaseDtRepo;
use DB;
use Lang;

class BreakLogDepartRepository extends BaseDtRepo
{
    protected $employee_id_to_name;
    protected $break_type_id_to_name;
    protected $dt_special_col = ['action' => 'row_id', 'chkbox' => 'row_id', 'department' => 'employee_id'];

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
        $this->break_type_id_to_name = $this->breakTypeIdToName();
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
    protected function breakTypeIdToName()
    {
        $data = BreakType::select('id', 'name')->get();
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
            case 'department':
                $ar = [];
                foreach (DepartmentEmployee::employeeWithDepartment($val) as $v) {
                    array_push($ar, $v->department->name);
                }
                return implode(',', $ar);
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
    //查詢 範圍 (同部門的所有人)
    public function SpacialQuery($user_id = false)
    {
        if ($user_id) {
            $all_vic = DepartmentEmployee::getVic($user_id, true);
        } else {
            $all_vic = DepartmentEmployee::getVic(resolve('User')['id'], true);
        }
        $vic_ids = [];
        foreach ($all_vic as $key => $value) {
            array_push($vic_ids, $value->employee_id);
        }
        return $this->builder->whereIn('employee_id', $vic_ids);
    }

    //欄位搜尋資料 組合 where 條件
    public function TableApiSearch($query, $searchParamsArray)
    {
        $searchParamsArray = $this->preProccessSearch($searchParamsArray);
        $query = $query->where(function ($q) use ($searchParamsArray) {
            foreach ($searchParamsArray as $val) {
                switch ($val['col']) {
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
                case 'vic_employee_id':
                    //待補
                    $ids = Employee::select('id')->where('name', 'LIKE', '%' . $arr['val'] . '%')->get()->toArray();
                    $id_str = [];
                    foreach ($ids as $key => $value) {
                        array_push($id_str, $value['id']);
                    }
                    $ar = [
                        'col' => $arr['col'],
                        'val' => implode(',', $id_str),
                    ];
                    array_push($newSearchParamsArray, $ar);
                    break;
                // case 'break_type':
                //     $type_id = BreakType::select('id')->where('name','LIKE','%'.$arr['val'].'%')->get()->toArray();
                //     $id_str = [];
                //     foreach ($type_id as $key => $value) {
                //         array_push($id_str,$value['id']);
                //     }
                //     $ar = [
                //         'col' => $arr['col'],
                //         'val' => implode(',',$id_str)
                //     ];
                //     array_push($newSearchParamsArray,$ar);
                // break;

                default:
                    array_push($newSearchParamsArray, $arr);
                    break;
            }
        }
        return $newSearchParamsArray;
    }
}
