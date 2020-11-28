<?php
namespace App\Traits;

use App\SalesTarget;
use App\DepartmentEmployee;
use Auth;

trait SalesTargetTrait
{
    public function checkBusinessUserHasSalesTarget()
    {
        $user_id = Auth::user()->id;
        $count = DepartmentEmployee::where('employee_id',$user_id)
                                    // ->where('department_id',1)
                                    ->where('sales_team',1)
                                    ->count();
        return $count;
        // if($count == 0){
        //     $model = new DepartmentEmployee;
        //     $model->row_id = $user_id;
        //     $model->save();
        // }
    }
}
