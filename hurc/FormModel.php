<?php

namespace App\Models;

use App\Models\User;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\FundFile;

abstract class FormModel extends Model
{
    use SoftDeletes;

    /**
     * 把form_data 自動轉為陣列
     *
     * @var array
     */
    protected $casts = [
        'form_data' => 'array',
        'contract_data' => 'array',
    ];

    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    protected $appends = ['create_by_user', 'update_by_user', 'status_name', 'excel_row_idx'];

    public function CreatedBy()
    {
        return $this->belongsTo('App\Models\User', 'created_by', 'id');
    }
    public function UpdatedBy()
    {
        return $this->belongsTo('App\Models\User', 'updated_by', 'id');
    }

    public function getCreateByUserAttribute()
    {
        if (isset($this->attributes['created_by'])) {
            return User::find($this->attributes['created_by'])->name;
        }
        return null;
    }

    public function getUpdateByUserAttribute()
    {
        if (isset($this->attributes['updated_by'])) {
            return User::find($this->attributes['updated_by'])->name;
        }
        return null;
    }

    public function getExcelRowIdxAttribute()
    {
        if (isset($this->attributes['created_by'])) {
            $id = $this->attributes['created_by'];
            $sql = "SELECT b.excel_idx FROM users a,user_group b WHERE a.group_id = b.id AND a.id=$id LIMIT 1";
            $md = DB::select(DB::raw($sql));
            if (count($md) > 0)
                return $md[0]->excel_idx;
            else
                return null;
        }
        return null;
    }

    public function getgroupDistrictAttribute()
    {
        if (isset($this->attributes['created_by'])) {
            $id = $this->attributes['created_by'];
            $sql = "SELECT b.city FROM users a,user_group b WHERE a.group_id = b.id AND a.id=$id LIMIT 1";
            $md = DB::select(DB::raw($sql));
            if (count($md) > 0)
                return $md[0]->city;
            else
                return null;
        }
        return null;
    }

    public function getStatusNameAttribute()
    {
        if (isset($this->attributes['status'])) {
            switch ($this->attributes['status']) {
                case '0':
                    return '尚未媒合';
                    break;
                case '1':
                    return '已完成';
                    break;
                case '2':
                    return '已選擇';
                    break;
                case '3':
                    return '已媒合';
                    break;
                case '4':
                    return '已送審';
                    break;
                default:
                    return '未知';
                    break;
            }
        }
        return null;
    }

    /**
     * scopeUnDoneData
     *  拉未完成資料
     * @param  mixed $query
     * @return void
     */
    public function scopeUnDoneData($query)
    {
        return $query->where('status', 0);
    }

    /**
     * scopeDoneData
     * 拉已完成資料
     * @param  mixed $query
     * @return void
     */
    public function scopeDoneData($query)
    {
        return $query->where('status', 1);
    }

    /**
     * scopeChosenData
     * 拉已被選擇資料
     * @param  mixed $query
     * @return void
     */
    public function scopeChosenData($query)
    {
        return $query->where('status', 2);
    }

    /**
     * scopeSelectJSON
     * Select JSON_EXTRACT Something
     * @param  mixed $column => 欄位名稱
     * @param  mixed $keys => 不用加錢字號的JSON Key
     * @param  mixed $alias => 別名
     * @return void
     */
    public function scopeSelectJSON($query, $column, $keys, $alias = false)
    {
        if (!$alias) {
            $alias = $column;
        }
        $sql = sprintf("JSON_UNQUOTE(JSON_EXTRACT(%s, '$.%s')) as %s", $column, $keys, $alias);
        return $query->addSelect(DB::raw($sql));
    }

    /**
     * scopeOrderByStAndUpTime
     * 排序
     * @param  mixed $query
     * @return void
     */
    public function scopeOrderByStAndUpTime($query)
    {
        return $query->orderBy('status', 'asc')->orderBy('updated_at', 'desc');
    }
}
