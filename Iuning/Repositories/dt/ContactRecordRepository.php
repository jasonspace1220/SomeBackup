<?php
namespace App\Repositories\dt;

use App\Customer;
use App\CustomerSalesPlatform;
use App\Contact;
use App\DataFrom;
use App\Employee;
use App\Platform;
use App\DepartmentEmployee;

use App\Repositories\dt\BaseDtRepo;
use Auth;
use Lang;
use DB;
use App\Traits\BasePermission;

class ContactRecordRepository extends BaseDtRepo
{
    use BasePermission;
    protected $isPersonal;
    protected $canSeePhoneDept;

    protected $employee_id_to_name;
    protected $dataFromIdToName;
    protected $platFormIdToName;

    protected $todayPersonal; // 用來標記這次查詢是不是 今天 個人用

    protected $mainColumnPrefix = 'c';
    protected $dt_special_col = [
        'action' => 'c.row_id',
        'customer_name' => 'cus.name',
        'customer_phone' => 'cus.cell_1',
        'last_contact_datetime' => 'cus.last_contact_datetime',
        'process_status' => 'c.row_id',
        'sales_from_plat' => 'c.customer_id',
    ];

    public function __construct()
    {
        //判斷 個人 OR 部門
        if(strpos(request()->url(),'contact/reservation/personal')){
            $this->isPersonal = true;
        }else{
            $this->isPersonal = false;
        }
        $this->todayPersonal = false;

        $this->model = new Contact;
        $this->builder = DB::table('contact')->from("contact as c")
                        ->leftJoin('customer as cus','cus.id','=','c.customer_id')
                        ->leftJoin(DB::raw("(select * from customer_sales_platform where effective = 1 and giveup_date is null and receive_date is not null) as csp"),'csp.customer_id','=','c.customer_id');

        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
        $this->employee_id_to_name = $this->employeeIdToName();
        $this->dataFromIdToName = DataFrom::IdToNameArray();
        $this->platFormIdToName = Platform::IdToNameArray();
        // $this->canSeePhoneDept = $this->userSeeCellPhoneByDept(); //使用者部門是否可看手機
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

    protected function customerIdToName()
    {
        $data = Customer::select('id', 'name')->get();
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
            case 'action' :
                $ar = [];
                $ar['customer_id'] = $row['customer_id'];
                $ar['row_id'] = $row['row_id'];

                if(isset($row['contact_time_ed']) && $this->todayPersonal){
                    $ar['gray'] = true;
                }else{
                    $ar['gray'] = false;
                }

                return $ar;
            break;

            case 'customer_phone':
                $result = [];
                $result['customer_id'] = $row['customer_id'];
                if($this->canSeePhoneDept){
                    $result['phone'] = $val;
                }else{
                    $canSee = $this->userManagingCustomer($row['customer_id']);
                    if($canSee){
                        $result['phone'] = $val;
                    }else{
                        $result['phone'] = '**********';;
                    }
                }
                return $result;
            break;

            case 'sales_from_plat':
                $res = '';
                $sales = explode(',',$row['sales']);
                $pf = explode(',',$row['platform']);
                $df = explode(',',$row['datafrom']);

                foreach ($sales as $k => $v) {
                    if($v != 'N'){
                        $s = ($v == 'N') ? "" : $this->employee_id_to_name[$v];
                        $p = ($pf[$k] == "N") ? "" : $this->platFormIdToName[$pf[$k]];
                        $d = ($df[$k] == "N") ? "" : $this->dataFromIdToName[$df[$k]];
                        $res .= $s." | ".$p." | ".$d."<br>";
                    }else{
                        $res = "";
                    }
                }
                return $res;
            break;

            case 'process_status':
                return '尚未串接';
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
        $halfYear = date('Y-m-d H:i:s', strtotime('-6 month')); //半年的資料
        $this->builder->where('c.create_datetime', '>=', $halfYear );
        $this->builder->where('c.create_by', Auth::user()->id);
        $this->builder->whereNull('c.deleted_at');

        if(isset($this->allSearch[0]) && strpos($this->allSearch[0], '@@') !== false){
            $searchVal = explode('@@',$this->allSearch[0]);
            $startDay = $searchVal[0];
            $endDay = $searchVal[1];
            $this->builder->whereBetween('c.contact_date',[$startDay,$endDay]);
        }
        return $this->builder;
    }

    //欄位搜尋資料 組合 where 條件
    public function TableApiSearch($query, $searchParamsArray)
    {
        foreach ($searchParamsArray as $val) {
            if ($val['col'] != 'chkbox' && $val['col'] != 'action') {
                $query = $query->where($val['col'], 'LIKE', '%' . $val['val'] . '%');
            }
        }
        return $query;
    }

    // -- 組合DataTables要吃的陣列格式 - //
    public function FormatArray($query, $draw, $totalCount, $filterCount, $attributeArray)
    {
        $this->canSeePhoneDept = $this->userSeeCellPhoneByDept(); //使用者部門是否可看手機

        $selectArray = [];

        if (isset($attributeArray, $this->dt_special_col)) {
            foreach ($attributeArray as $key => $value) {
                $k = $this->mainColumnPrefix.'.'.$value;
                array_push($selectArray,"$k as $key");
            }
            foreach ($this->dt_special_col as $key => $value) {
                array_push($selectArray,"$value as $key");
            }
            $attributeArray = array_merge($attributeArray, $this->dt_special_col);
        }

        $this->builder->select($selectArray);
        $this->builder->addSelect(DB::raw('GROUP_CONCAT(COALESCE(csp.sales_id,"N")) as sales'));
        $this->builder->addSelect(DB::raw('GROUP_CONCAT(COALESCE(csp.platform,"N")) as platform'));
        $this->builder->addSelect(DB::raw('GROUP_CONCAT(COALESCE(csp.data_from,"N")) as datafrom'));
        $this->builder->groupBy('c.row_id');

        $result = [];
        $result['draw'] = $draw;
        $result['recordsTotal'] = $totalCount;
        $result['recordsFiltered'] = $filterCount;
        $result['data'] = [];
        foreach ($query->get() as $val) {
            $data = [];
            $val = collect($val)->toArray();
            foreach ($attributeArray as $key => $attribute) {
                $value = $this->dtSetResult($key, $val[$key],$val);
                $data[$key] = $value;
            }
            array_push($result['data'], $data);
        }
        return $result;
    }
}
