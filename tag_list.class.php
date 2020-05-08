<?php
    /**
     * @class tag_list
     * @author zero (zero@nzeo.com)
     * @brief 꼬리표 목록 출력
     * @version 0.1
     **/

    class tag_list extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 제목
            $title = $args->title;
			
            // 시간
			$hours = preg_match('/[^0-9]/', $args->hours)?'':$args->hours;
            
			// 출력된 목록 수
            $list_count = (int)$args->list_count;
            if(!$list_count) $list_count = 20;
            $list_count ++;

            // 대상 모듈 (mid_list는 기존 위젯의 호환을 위해서 처리하는 루틴을 유지. module_srl로 위젯에서 변경)
            $oModuleModel = &getModel('module');
            if($args->mid_list) {
                $mid_list = explode(",",$args->mid_list);
                if(count($mid_list)) {
                    $module_srl = $oModuleModel->getModuleSrlByMid($mid_list);
                } else {
                    $site_module_info = Context::get('site_module_info');
                    if($site_module_info) {
                        $margs->site_srl = $site_module_info->site_srl;
                        $oModuleModel = &getModel('module');
                        $output = $oModuleModel->getMidList($margs);
                        if(count($output)) $mid_list = array_keys($output);
                        $module_srl = $oModuleModel->getModuleSrlByMid($mid_list);
                    }
                }
            } else $module_srl = explode(',',$args->module_srls);
            if($args->period) {
                $before_month_month_day = $this->convertDatetoDay( date("n") == 1 ? date("Y") - 1 : date("Y"),  date("n") == 1 ? 12 :  date("n") - 1);
                $m = date("n");
                $y = date("Y");
                if(date("j") < $args->period) {
                    $day = $before_month_month_day + date("j") - $args->period + 1;
                    $m = $m - 1;
                    if($m < 1) {
                        $m = 12;
                        $y = $y - 1;
                    }
                } else {
                    $day = date("j") - $args->period + 1;
                }
                $widget_info->date_from = $y."-".sprintf("%02d", $m)."-".sprintf("%02d", $day);
                $widget_info->period = $args->period;
                $args->regdate = $y.sprintf("%02d", $m).sprintf("%02d", $day).date("His");
            }
            $args->module_srl = implode(",",$module_srl);
            $args->list_count = $list_count;
			$args->order_target = 'regdate';
			$output = executeQueryArray('widgets.tag_list.getTagList', $args);

            $widget_info->title = $title;

            if(count($output->data)) {
                $tags = array();
                $max = 0;
                $min = 99999999;
                foreach($output->data as $key => $val) {
					if($hours&&$val->regdate<date('YmdHis', strtotime('-'.$hours.' hours'))) continue;
                    $tag = trim($val->tag);
                    if(!$tag) continue;
                    $count = $val->count;
                    if($max < $count) $max = $count;
                    if($min > $count) $min = $count;
                    $tags[] = $val;
                    if(count($tags)>=20) continue;
                }

                $mid2 = $min+(int)(($max-$min)/2);
                $mid1 = $mid2+(int)(($max-$mid2)/2);
                $mid3 = $min+(int)(($mid2-$min)/2);

                foreach($tags as $key => $item) {
                    if($item->count > $mid1) $rank = 1;
                    elseif($item->count > $mid2) $rank = 2;
                    elseif($item->count > $mid3) $rank = 3;
                    else $rank= 4;
                    $tags[$key]->rank = $rank;
                }
                shuffle($tags);
            }
            $widget_info->tag_list = $tags;


            Context::set('widget_info', $widget_info);

            // 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            Context::set('colorset', $args->colorset);

            // 템플릿 파일을 지정
            $tpl_file = 'tags';

            // 템플릿 컴파일
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }
		
        function convertDatetoDay($year, $month) { 
            $numOfLeapYear = 0; // 윤년의 수 

            // 전년도까지의 윤년의 수를 구한다. 
            for($i = 0; $i < $year; $i++) { 
                if($this->isLeapYear($i)) $numOfLeapYear++; 
            } 

            // 전년도까지의 일 수를 구한다. 
            $toLastYearDaySum = ($year-1) * 365 + $numOfLeapYear; 

            // 올해의 현재 월까지의 일수 계산 
            $thisYearDaySum = 0; 
            //                        1,  2,  3,  4,  5,  6,  7,  8,  9, 10, 11, 12 
            $endOfMonth = array(1 => 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31); 

            for($i = 1; $i < $month; $i++) { 
                $thisYearDaySum += $endOfMonth[$i]; 
            } 

            // 윤년이고, 2월이 포함되어 있으면 1일을 증가시킨다. 
            if ($month > 2 && $this->isLeapYear($year)) $thisYearDaySum++; 

            if($this->isLeapYear($year)) $endOfMonth[2] = 29;

            return $endOfMonth[$month];
        }
        function isLeapYear($year) { 
            if ($year % 4 == 0 && $year % 100 != 0 || $year % 400 == 0) return true; 
            else return false;
        } 
    }
?>
