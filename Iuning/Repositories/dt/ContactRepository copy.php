<?php
namespace App\Repositories\dt;

use App\Customer;
use App\CustomerSalesPlatform;
use App\Contact;
use App\DataFrom;
use App\Employee;
use App\Platform;

use App\Repositories\dt\BaseDtRepo;
use Auth;
use Lang;
use DB;
use App\Traits\BasePermission;

class ContactRepository extends BaseDtRepo
{
    use BasePermission;
    protected $isPersonal;
    protected $canSeePhoneDept;

    protected $employee_id_to_name;
    protected $customer_id_to_name;
    protected $dt_special_col = [
        'action' => 'row_id',
        'customer_name' => 'customer_id',
        'customer_phone' => 'customer_phone',
        'last_contact_datetime' => 'last_contact_datetime',
        'process_status' => 'row_id',
        'sales_from_plat' => 'sales_from_plat'
    ];

    public function __construct()
    {
        //判斷 個人 OR 部門
        if(strpos(request()->url(),'contact/reservation/personal')){
            $this->isPersonal = true;
        }else{
            $this->isPersonal = false;
        }

        $this->model = new Contact;
        // $this->builder = DB::table('contact');
        $this->builder = Contact::query();
        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
        $this->employee_id_to_name = $this->employeeIdToName();
        $this->customer_id_to_name = $this->customerIdToName();
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
            case 'customer_name' :
                if(isset($this->customer_id_to_name[$val])){
                    $d = $this->customer_id_to_name[$val];
                }else{
                    $d = null;
                }
                return $d;
            break;

            case 'customer_phone':
                if($this->canSeePhoneDept){
                    return $val;
                }else{
                    $canSee = $this->userManagingCustomer($row['customer_id']);
                    if($canSee){
                        return $val;
                    }
                    return '**********';
                }
            break;

            case 'sales_from_plat':
                $res = $val;
                if(count($val) > 1){
                    $res = implode("<br>",$val);
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
        $this->builder->where('create_datetime', '>=', $halfYear );

        $today = date("Y-m-d");
        if(isset($this->allSearch[0])){
            switch ($this->allSearch[0]) {
                case 'today':
                    $this->builder->whereBetween('next_contact_date',[$today,$today]);
                    break;
                case 'before':
                    $this->builder->where('next_contact_date', '<', $today);
                    break;
                case 'after':
                    $this->builder->where('next_contact_date', '>', $today);
                    break;
                default:
                    break;
            }
        }

        if($this->isPersonal){
            return $this->builder->where('create_by', Auth::user()->id);//個人
        }else{
            //部門 (未完成)
            return $this->builder;
        }
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

        if (isset($attributeArray, $this->dt_special_col)) {
            $attributeArray = array_merge($attributeArray, $this->dt_special_col);
        }


        $result = [];
        $result['draw'] = $draw;
        $result['recordsTotal'] = $totalCount;
        $result['recordsFiltered'] = $filterCount;
        $result['data'] = [];
        // dd($query->toSql());

        // $times = [];
        // $times['a'] = microtime(true);
        // $query->select('row_id','customer_id','next_contact_date')->get();
        // $data = $query->get()->toArray();
        // $data = DB::select("SELECT
        //                         *
        //                     FROM
        //                         `contact`
        //                     WHERE
        //                         `create_datetime` >= '2020-01-01 00:00:00' AND `create_by` = 3
        //                         AND `create_datetime` >= '2020-01-01 00:00:00'
        //                         AND `create_by` = 3
        //                         AND `create_datetime` >= '2020-01-01 00:00:00'
        //                         AND `create_by` = 3
        //                     ORDER BY
        //                         `customer_id`
        //                     DESC
        //                     LIMIT 10 OFFSET 0");
        // $md = DB::table('contact');
        // $md = $md->where('create_datetime','>=','2020-01-01 00:00:00');
        // $md = $md->where('create_by','3');
        // $md = $md->orderBy('customer_id','DESC');
        // $md = $md->limit(10);
        // $md = $md->offset(0);
        // $data = $md->get();

        // $times['c'] = microtime(true);
        // $times['c-a'] = $times['c']-$times['a'];
        // dd($times,collect($data[0])->toArray());

        foreach ($query->get() as $val) {
            $data = [];
            foreach ($attributeArray as $key => $attribute) {
                $value = $this->dtSetResult($key, $val[$attribute],$val);
                $data[$key] = $value;
            }
            array_push($result['data'], $data);

        }
        // $times['c'] = microtime(true);
        // $times['c-a'] = $times['c']-$times['a'];
        // dd($times);
        return $result;
    }
}
