<?php
namespace App\Traits;

use App\Employee;
use App\DepartmentEmployee;
use Auth;
use App\Inject\SalesTarget;
use App\CustomerSalesPlatform;
use DB;

trait BasePermission {

    /**
     * 檢查是否有任何主管權限
     *
     * @return void
     */
    public function checkLeaderPermission()
    {
        return DepartmentEmployee::employeeIsAnyLeader(Auth::user()->id);
    }
    /**
     * 檢查是否有對應部門
     *
     * @return void
     */
    public function checkDepartPermission($depart_name,$apiUser=false)
    {
        if($apiUser){
            // $employee = Employee::
        }else{
            return Auth::user()->checkDepartByName($depart_name);
        }
    }

    /**
     * 檢查是否有業務全縣
     *
     * @return void
     */
    public function chekcSalesPermission()
    {
        $count = DepartmentEmployee::where('employee_id',Auth::user()->id)->where('sales_team',1)->count();
        if($count > 0){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 檢查是否可以分配客戶
     *
     * @return void
     */
    public function chekcCustomerAssignPermission($user_id)
    {
        $count = DepartmentEmployee::where('employee_id',$user_id)->where('customer_assign',1)->count();
        if($count > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 檢查業務能否接收客戶
     *
     * @param  mixed $sales_id
     * @return void
     */
    public function checkSalesCanReceiveCus($sales_id)
    {
        if($this->chekcCustomerAssignPermission($sales_id)){
            $salesTarget = new SalesTarget;
            $salesMax = $salesTarget->salesMaxLimit($sales_id);
            $salesServicing = $salesTarget->salesHasCustomer($sales_id);
            $c = $salesMax - $salesServicing;
            if($c > 0){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 判斷能否查看 搜尋客戶後 手機
     *
     * 專案 資訊  管理
     * 2    6     7
     *
     * @param  mixed $user_id
     * @return void
     */
    public function checkCanSeeCustomerCellPhone($user_id)
    {
        $count = DepartmentEmployee::where('employee_id',$user_id)->whereIn('department_id',[
            2,6,7
        ])->count();

        if($count > 0){
            return true;
        }else{
            return false;
        }
    }

    public function checkCanSeeCustomerCellPhoneNew($customer_id)
    {
        if($this->userAlwaysSeeCellPhoneAuth()){
            return true;
        }

        if($this->userManagingCustomer($customer_id)){
            return true;
        }
        return false;
    }

    public function userSeeCellPhoneByDept()
    {
        $ar = ['專案部','資訊部','管理部'];
        if(isset(Auth::user()->department_name)){
            $dn = explode(',',Auth::user()->department_name);
            foreach ($dn as $v) {
                if(in_array($v,$ar)){
                    return true;
                }
            }
        }
        return false;
    }

    public function userManagingCustomer($customer_id)
    {
        $csp = CustomerSalesPlatform::findInServiceByCustomer($customer_id)->where('sales_id',Auth::user()->id)->count();
        if($csp > 0){
            return true;
        }
        return false;
    }

}
