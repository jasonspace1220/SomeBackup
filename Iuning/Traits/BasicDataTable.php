<?php
namespace App\Traits;

/**
 * 這是一個寄生在Model用的DataTable Api Trait
 */

trait BasicDataTable
{
    /*
    如果要有特殊欄位的話，在Model中塞入這個 var
     EX:
     public $dt_special_col = [
        'action' => 'row_id',
    ];
    */


    /**
     * 有特殊搜尋範圍時用 (在Model中複寫)
     *
     * @param  mixed $query
     * @return void
     */
    public function scopeSpacialQuery($query)
    {
        return $query;
    }

    public function scopeTableApiSearch($query, $searchParamsArray)
    {
        foreach ($searchParamsArray as $val) {
            if ($val['col'] == 'create_time') {
                $ar = explode('@@', $val['val']);
                //specail proccess
                $query = $query->whereBetween($val['col'], [$ar[0] . " 00:00:00", $ar[1] . " 23:59:59"]);
            } else {
                $query = $query->where($val['col'], 'LIKE', '%' . $val['val'] . '%');
            }
            // instr chatIndex
        }
        return $query;
    }
    // ---- Order By, Offset,limit ---- //
    public function scopeTableApiOOL($query, $orderBy, $ascDesc, $offset, $limit)
    {
        return $query->orderBy($orderBy, $ascDesc)->offset($offset)->limit($limit);
    }
    // ---- Count Api Run Result ---- //
    public function scopeTableApiCount($query, $num)
    {
        if ($num > 0) {
            return $query->count();
        } else {
            // $n = new self();
            $n = $this->spacialQuery();
            return $n->count();
        }
    }
    // -- 組合DataTables要吃的陣列格式 - //
    public function scopeFormatArray($query, $draw, $totalCount, $filterCount, $attributeArray)
    {
        if(isset($attributeArray,$this->dt_special_col)){
            $attributeArray = array_merge($attributeArray,$this->dt_special_col);
        }
        $result = [];
        $result['draw'] = $draw;
        $result['recordsTotal'] = $totalCount;
        $result['recordsFiltered'] = $filterCount;
        $result['data'] = [];
        foreach ($query->get() as $val) {
            $data = [];
            foreach ($attributeArray as $key => $attribute) {
                $value = $this->dtSetResult($key,$val[$attribute]);
                $data[$key] = $value;
                // $data[$key] = $val[$attribute];
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
    public function dtSetResult($key,$val)
    {
        return $val;
    }
    // --- 分解DataTables的請求參數 -- //
    public function scopeBreakDownRequestQuery($query, $requestQuery, $columns)
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

    public function getTableColumns()
    {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }

    // - 把所有上面Function直接run一遍 - //
    public function scopeRunDataTables($query, $requstQuery, $columns = false, $attributeArray = false)
    {
        if (!$columns) {
            $columns = $this->getTableColumns();
        }
        if (!$attributeArray) {
            $attributeArray = [];
            foreach ($columns as $val) {
                $attributeArray[$val] = $val;
            }
        }
        //儲存搜尋欄位&值
        $analisQuery = $query->BreakDownRequestQuery($requstQuery, $columns);

        $query = $query->spacialQuery();
        //有下搜尋
        if (count($analisQuery['searchParamsArray']) > 0) {
            $query = $query->tableApiSearch($analisQuery['searchParamsArray']);
        }

        //篩選後數量，這要在拉分頁和排序前先做，不然數字不對
        $filterCount = $query->tableApiCount(count($analisQuery['searchParamsArray']));
        //開始拉分頁和排序資料
        $query = $query->tableApiOOL($analisQuery['orderBy'][0], $analisQuery['orderBy'][1], $analisQuery['start'], $analisQuery['length']);
        //資料總數量
        $totalCount = $query->tableApiCount(0);
        $result = $query->formatArray($analisQuery['draw'], $totalCount, $filterCount, $attributeArray);
        return $result;
    }
}
