<?php
namespace App\Traits;

use App\Holiday;
use Lang;

trait BreakLogTrait
{

    public $DAYHOURS = 8;

    public function countLeaveDays($start, $end, $breakTypeId, $workTimeArray)
    {
        $startWorkTime = $workTimeArray['wst'];
        $endWorkTime = $workTimeArray['wet'];
        $workBreakStartTime = $workTimeArray['wbst'];
        $workBreakEndTime = $workTimeArray['wbet'];
        if(strtotime($end)-strtotime($start) <= 0){
            return[
                'leaveDaysTooSmall',Lang::get('error_msg.leaveDaysTooSmall')
            ];
        }
        //每天休息小時數
        $workBreakHours = (int)$this->countDiff($workBreakEndTime, $workBreakStartTime);
        if (date("Y-m-d", strtotime($start)) == date("Y-m-d", strtotime($end))) {
            //如果請假是單天
            $totalBreakDayHours = $this->countOneDaysHours($start,$end,$workTimeArray,$workBreakHours);
        } else {
            //如果不是請單天的假
            //六日的日期陣列
            $holidayArray = $this->satSunDate($start, $end);
            //六日的日期陣列 排除補班日
            $holidayArray = $this->excludeMakeUpDay($holidayArray);
            //特殊假日陣列
            $spHolidayArray = Holiday::getBetweenHolidayArray($start, $end);

            $holidayArray = array_merge($holidayArray, $spHolidayArray);
            //檢查起始日和最後日期不能在假日上
            $SEnotAtHoliday = $this->checkSEnotAtHoliday(date("Y-m-d", strtotime($start)), date("Y-m-d", strtotime($end)), $holidayArray);
            if ($SEnotAtHoliday !== true) {
                return ['onHoliday', $SEnotAtHoliday];
            }
            $swt = date("Y-m-d $startWorkTime:00");
            $ewt = date("Y-m-d $endWorkTime:00");
            //每日工時
            $this->DAYHOURS = (int)$this->countDayHours($startWorkTime, $endWorkTime) - $workBreakHours;
            //請假(天數) days
            $breakDays = $this->countBreakDays($start, $end) - 1;
            //請假天數總小時 hours
            $totalBreakDayHours = $breakDays * $this->DAYHOURS;

            //第一天和最後一天的時差 hours
            // $st_diff = $this->countDiff($start,$swt);
            // $ed_diff = $this->countDiff($ewt,$end);
            // $totalBreakDayHours = $totalBreakDayHours - $st_diff - $ed_diff;

            //補班日計算
            $makeUpDayArray =  Holiday::getBetweenMakeUpDayArray($start, $end);
            $totalMakeUpDayHours = $this->DAYHOURS * count($makeUpDayArray);

            $totalBreakDayHours =  $totalBreakDayHours + $totalMakeUpDayHours;
            //第一天和最後一天的工時
            $st_diff = $this->startDayHours($start,$workTimeArray,$workBreakHours);
            $ed_diff = $this->endDayHours($end,$workTimeArray,$workBreakHours);
            $totalBreakDayHours = $totalBreakDayHours + $st_diff + $ed_diff;
            //==排除一般的六日
            //範圍內六日的天數總和*工時
            // $normalSatSunTotalHours = count($holidayArray) * $this->DAYHOURS;
            $normalSatSunTotalHours = $this->sumSatSun($start, $end) * $this->DAYHOURS;
            //排除特殊假日時數
            $spTotalHours = count($spHolidayArray) * $this->DAYHOURS;

            $totalBreakDayHours = $totalBreakDayHours - $normalSatSunTotalHours - $spTotalHours;
        }
        //==特殊假別 要扣或加
        // $breakType = BreakType::find($breakTypeId);
        return round($totalBreakDayHours / $this->DAYHOURS, 3);
    }

    /**
     * 輸入上下班時間 算出一天工時
     *
     * @param  mixed $s
     * @param  mixed $e
     * @return void
     */
    private function countDayHours($s, $e)
    {
        return (strtotime(date("Y-m-d $e:00")) - strtotime(date("Y-m-d $s:00"))) / (60 * 60);
    }

    /**
     * 計算請假的天數
     *
     * @param  mixed $s
     * @param  mixed $e
     * @return void
     */
    private function countBreakDays($s, $e)
    {
        $s = date("Y-m-d 00:00:00", strtotime($s));
        $e = date("Y-m-d 00:00:00", strtotime($e));
        return (strtotime($e) - strtotime($s)) / (60 * 60 * 24);
    }

    /**
     * 算 時 差距
     *
     * @param  mixed $t1
     * @param  mixed $t2
     * @return void
     */
    private function countDiff($t1, $t2)
    {
        $t1 = date('2000-01-01 H:i:00', strtotime($t1));
        $t2 = date('2000-01-01 H:i:00', strtotime($t2));
        return (strtotime($t1) - strtotime($t2)) / (60 * 60);
    }

    /**
     * 計算時間範圍內 星期六和星期日的總天數
     *
     * @param  mixed $st
     * @param  mixed $ed
     * @return void
     */
    private function sumSatSun($st, $ed)
    {
        $num = 0;
        $start = new \DateTime($st);
        $end = new \DateTime($ed);
        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($start, $interval, $end);
        foreach ($period as $dt) {
            if ($dt->format('N') == 7) {
                $num++;
            }
            if ($dt->format('N') == 6) {
                $num++;
            }
        }
        return $num;
    }

    /**
     * 回傳六日的日期
     *
     * @param  mixed $st
     * @param  mixed $ed
     * @return array
     */
    private function satSunDate($st, $ed)
    {
        $ed = date("Y-m-d H:i:s", strtotime('+1 day', strtotime($ed)));
        $start = new \DateTime($st);
        $end = new \DateTime($ed);
        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($start, $interval, $end);
        $ar = [];
        foreach ($period as $dt) {
            if ($dt->format('N') == 7) {
                array_push($ar, $dt->format("Y-m-d"));
            }
            if ($dt->format('N') == 6) {
                array_push($ar, $dt->format("Y-m-d"));
            }
        }
        return $ar;
    }

    /**
     * 排除掉六日日期陣列中與補班日重疊之日期
     *
     * @param  mixed $holidayArray
     * @return void
     */
    public function excludeMakeUpDay($holidayArray)
    {
        $makeUpDayArray = Holiday::getMakeUpDatesAfterNow();
        foreach ($makeUpDayArray as $v) {
            $k = array_search($v,$holidayArray);
            if($k !== false){
                unset($holidayArray[$k]);
            }
        }
        return $holidayArray;
    }
    /**
     * 檢查起始和最後日期不在假日上用
     *
     * @param  mixed $start
     * @param  mixed $end
     * @param  mixed $holidayArray
     * @return void
     */
    private function checkSEnotAtHoliday($start, $end, $holidayArray)
    {
        // if(!in_array($start,$holidayArray) && !in_array($end,$holidayArray) ){
        //     return true;
        // }else{
        //     return false;
        // }
        if (in_array($start, $holidayArray)) {
            return $start;
        } else if (in_array($end, $holidayArray)) {
            return $end;
        } else {
            return true;
        }
    }

    /**
     * 取得上下班時間和休息時間
     *
     * @param  mixed $user
     * @return array
     */
    public function getWorkTime($user): array
    {
        if ($user['job_type'] == 0) {
            return [
                'wst' => config('params.work_start_time'),
                'wet' => config('params.work_end_time'),
                'wbst' => config('params.work_break_start_time'),
                'wbet' => config('params.work_break_end_time'),
            ];
        } else {
            return [
                'wst' => config('params.night_work_start_time'),
                'wet' => config('params.nigh_work_end_time'),
                'wbst' => config('params.night_work_break_start_time'),
                'wbet' => config('params.night_break_end_time'),
            ];
        }
    }

    /**
     * 取得起始日工時
     *
     * @param  datetime $start = 休假起始日時
     * @param  mixed $workTimeArray 上下班時間
     * @param  mixed $workBreakHours 休息時間(Hour)
     * @return void
     */
    public function startDayHours($start,array $workTimeArray,$workBreakHours):int
    {
        //下班時間-申請時間
        $sHours = $this->countDiff($workTimeArray['wet'] , $start);
        if($this->countDiff($workTimeArray['wbst'] , $start) >= 0){
            $sHours = $sHours-$workBreakHours;
        }
        return (int)$sHours;
    }

    /**
     * 取得結束日工時
     *
     * @param  mixed $end
     * @param  mixed $workTimeArray
     * @param  mixed $workBreakHours
     * @return int
     */
    public function endDayHours($end,$workTimeArray,$workBreakHours):int
    {
        //申請時間-上班時間
        $eHours = $this->countDiff($end,$workTimeArray['wst']);
        if($this->countDiff($end,$workTimeArray['wbet']) >= 0){
            $eHours = $eHours-$workBreakHours;
        }
        return (int)$eHours;
    }

    /**
     * 計算單天小時數 (Hours)
     *
     * @param  mixed $start
     * @param  mixed $end
     * @param  mixed $workTimeArray
     * @param  mixed $workBreakHours
     * @return void
     */
    public function countOneDaysHours($start,$end,$workTimeArray,$workBreakHours)
    {
        $A = $this->countDiff($workTimeArray['wbst'] , $start);
        $B = $this->countDiff($end,$workTimeArray['wbet'] );

        $total = $this->countDiff($end,$start);

        if($A >= 0 && $B >= 0){
            $total = $total - $workBreakHours;
        }
        return $total;
    }
}
