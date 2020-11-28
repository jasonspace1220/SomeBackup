<?php

namespace App\Repositories\dt;

use App\Customer;
use App\CustomerSalesPlatform;
use App\DataFrom;
use App\Employee;
use App\Platform;
use App\Repositories\dt\BaseDtRepo;
use Auth;
use Lang;

class CustomerRepository extends BaseDtRepo
{
    protected $employee_id_to_name;
    protected $dt_special_col = [
        'action' => 'id',
        'customer_sales_platform' => 'with_effective_customer',
        'city_region' => 'mailing_add_city_id',
        'flag' => 'id',
        'data_from' => 'with_effective_customer',
    ];
    protected $dataFromIdToName; // 客戶來源 ID 對 名稱
    protected $platFormIdToName; // 平台 ID 對 名稱

    public function __construct()
    {
        $this->model = new Customer;
        $this->builder = Customer::query();
        $this->key = $this->model->getKeyName();
        $this->columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($this->model->getTable());
        $this->employee_id_to_name = $this->employeeIdToName();
        $this->dataFromIdToName = DataFrom::IdToNameArray();
        $this->platFormIdToName = Platform::IdToNameArray();
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
            case 'gender':
                $genderLang = Lang::get('customer.gender');
                return $genderLang[$val];
                break;
            case 'customer_sales_platform':
                $ar = [];
                foreach ($row['with_effective_customer'] as $v) {
                    $sales =  $this->employee_id_to_name[$v['sales_id']];
                    if (isset($v['platform'])) {
                        $plat = $this->platFormIdToName[$v['platform']];
                    } else {
                        $plat = "無";
                    }
                    $d = $this->dataFromIdToName[$v['data_from']];
                    $t = "$sales | $d | $plat";
                    array_push($ar, $t);
                }
                return $ar;
                break;
            case 'city_region':
                return $row['mailing_add_city_id'] . $row['mailing_add_region_id'];
                break;
                break;
            case 'flag':
                return '尚未串接';
                break;
            case 'cell_1':
                $ar = [];
                $ar['id'] = $row['id'];
                $ar['cell'] = $val;
                return $ar;
                break;

            case 'last_update_datetime':
                return date('Y-m-d H:i:s', strtotime($val));
                break;

            case 'action':
                $ar = [];
                $ar['id'] = $val;
                $ar['phone'] = [
                    $row['cell_1'],
                    $row['cell_2'],
                    $row['cell_3'],
                ];

                $ar['giveup'] = $this->CSProwId($row);
                return $ar;
                break;
            default:
                return $val;
                break;
        }
        return $val;
    }


    /**
     * 回傳業務平台資料的row_id
     */
    protected function CSProwId($row)
    {
        foreach ($row['with_effective_customer'] as $k => $v) {
            if ($v['sales_id'] == Auth::user()->id) {
                return $v['row_id'];
            }
        }
    }

    //查詢 範圍
    public function SpacialQuery()
    {
        $ids = CustomerSalesPlatform::select('customer_id')->findActiveCustomerBySales(Auth::user()->id)->get()->toArray();
        $customer_ids = [];
        foreach ($ids as $key => $value) {
            array_push($customer_ids, $value['customer_id']);
        }
        return $this->builder->whereIn('id', $customer_ids);
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
        if (isset($attributeArray, $this->dt_special_col)) {
            $attributeArray = array_merge($attributeArray, $this->dt_special_col);
        }
        $result = [];
        $result['draw'] = $draw;
        $result['recordsTotal'] = $totalCount;
        $result['recordsFiltered'] = $filterCount;
        $result['data'] = [];
        // dd($query->toSql(),$query->getBindings());
        // dd($query->with('withEffectiveCustomer')->get()->toArray());
        foreach ($query->with('withEffectiveCustomer')->get()->toArray() as $val) {
            $data = [];
            foreach ($attributeArray as $key => $attribute) {
                $value = $this->dtSetResult($key, $val[$attribute], $val);
                $data[$key] = $value;
            }
            array_push($result['data'], $data);
        }
        return $result;
    }
}
