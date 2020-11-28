<?php
namespace App\Repositories;

use App\Repositories\Interfaces\BaseRepositoryInterface;

abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * Eloqument query builder
     */
    protected $builder;
    /**
     * model instance
     */
    protected $model;
    /**
     * columns of model table
     */
    protected $columns;
    /**
     * PK for models
     */
    protected $key;


    public function renewBuilder()
    {
        $this->builder = $this->model->query();
    }
    /**
     * find
     *
     * @param  mixed $id
     * @param  mixed $trashed
     * @return void
     */
    public function find($id, $trashed = false)
    {
        if ($trashed) {
            return $this->builder->withTrashed()->findOrFail($id);
        }
        return $this->builder->findOrFail($id);
    }

    public function basicStore($reqData)
    {
        foreach ($reqData as $key => $value) {
            if(in_array($key, $this->columns)){
                $this->model->$key = $value;
            }
        }
        if($this->model->save()){
            // return $this->model->id;
            $primaryKey = $this->model->primaryKey;
            return $this->model->$primaryKey;
        }else{
            return -1;
        }
    }

    public function basicAndWhere($condition_array,$builder)
    {
        $builder->where($condition_array);
        return $builder;
    }
}
