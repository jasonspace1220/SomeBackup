<?php
namespace App\Repositories\dt;

use App\Repositories\Interfaces\BaseDataTable;

abstract class BaseDtRepo implements BaseDataTable
{
    protected $allSearch = [];
/* Main Control Function-------------------------------------------------------------------------- */
    public function runDt(array $reqData)
    {
        $attributeArray = [];
        foreach ($this->columns as $val) {
            $attributeArray[$val] = $val;
        }
        //儲存搜尋欄位&值
        $analisQuery = $this->BreakDownRequestQuery($reqData, $this->columns);
        //有搜尋的欄位參數
        $searchArray = $analisQuery['searchParamsArray'];
        $searchCount = count($searchArray);
        //特殊查詢
        $query = $this->SpacialQuery();
        //有下搜尋
        if ($searchCount > 0) {
            $query = $this->TableApiSearch($query, $searchArray);
        }
        //篩選後數量，這要在拉分頁和排序前先做，不然數字不對
        $filterCount = $this->TableApiCount($query, $searchCount);

        //開始拉分頁和排序資料
        $query = $this->TableApiOOL($query, $analisQuery['orderBy'][0], $analisQuery['orderBy'][1], $analisQuery['start'], $analisQuery['length']);

        //資料總數量
        $totalCount = $this->TableApiCount($query, 0);

        $result = $this->FormatArray($query, $analisQuery['draw'], $totalCount, $filterCount, $attributeArray);

        return $result;
    }
/* -------------------------------------------------------------------------- */

// --- 分解DataTables的請求參數 -- //
    public function BreakDownRequestQuery($requestQuery, $columns)
    {
        $searchParamsArray = [];
        foreach ($requestQuery as $key => $value) {
            switch ($key) {
                case 'start':
                    $start = $value;
                    break;
                case 'length':
                    $length = $value;
                    break;
                case 'draw':
                    $draw = $value;
                    break;
                case 'order':
                    $orderBy[0] = $columns[$value[0]['column']];
                    $orderBy[1] = $value[0]['dir'];
                    break;
                case 'columns':
                    //如果欄位搜尋有值
                    foreach ($value as $col) {
                        if (!is_null($col['search']['value'])) {
                            $ar = [];
                            $ar['col'] = $col['data'];
                            $ar['val'] = $col['search']['value'];
                            array_push($searchParamsArray, $ar);
                        }
                    }
                    break;
                case 'search' :
                    if(isset($value['value'])){
                        array_push($this->allSearch,$value['value']);
                    }
                break;
                default:
                    break;
            }
        }
        $result = [
            'start' => $start,
            'length' => $length,
            'draw' => $draw,
            'orderBy' => $orderBy,
            'searchParamsArray' => $searchParamsArray,
        ];
        return $result;
    }

//特殊查詢
    public function SpacialQuery()
    {
        return $this->builder;
    }
//欄位搜尋資料 組合 where 條件
    public function TableApiSearch($query, $searchParamsArray)
    {
        // foreach ($searchParamsArray as $val) {
        //     $query = $query->where($val['col'], 'LIKE', '%' . $val['val'] . '%');
        // }
        $query = $query->where(function($q) use ($searchParamsArray){
            foreach ($searchParamsArray as $val) {
                $q = $q->orWhere($val['col'], 'LIKE', '%' . $val['val'] . '%');
            }
        });
        return $query;
    }
// ---- Count Api Run Result ---- //
    public function TableApiCount($query, $num)
    {
        if ($num > 0) {
            return $query->count();
        } else {
            // return $this->model->count();
            return $this->SpacialQuery()->count();
        }
    }
// ---- Order By, Offset,limit ---- //
    public function TableApiOOL($query, $orderBy, $ascDesc, $offset, $limit)
    {
        return $query->orderBy($orderBy, $ascDesc)->offset($offset)->limit($limit);
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
        foreach ($query->get() as $val) {
            $data = [];
            foreach ($attributeArray as $key => $attribute) {
                $value = $this->dtSetResult($key, $val[$attribute],$val);
                $data[$key] = $value;
            }
            array_push($result['data'], $data);
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
        // switch ($key) {
        //     case 'action':
        //         $action = [
        //             'row_id' => $val,
        //             'edit' => false,
        //         ];
        //         return $action;
        //         break;

        //     default:
        //         return $val;
        //         break;
        // }
        return $val;
    }


    /**
     * 檢查字串是否含有中文
     *
     * @param  mixed $str
     * @return void
     */
    public function checkHasChinese($str)
    {
        if(preg_match('/[\x{4e00}-\x{9fa5}]/u', $str)>0){
            //含中文
            return true;
        }else{
            return false;
        }
    }
}
