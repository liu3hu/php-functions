<?php
	/**
     * 将数字转换为大写金额
     * @param  mix $ns 数字金额
     * @return string  转换后的大写金额
     */
    function cny($ns) {
		static $cnums = array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖"), 
		$cnyunits = array("圆","角","分"), 
		$grees = array("拾","佰","仟","万","拾","佰","仟","亿"); 
		list($ns1,$ns2) = explode(".",$ns,2); 
		$ns2 = array_filter(array($ns2[1],$ns2[0])); 

		$arr=array(array(str_split($ns1),$grees),array('',$cnyunits));
		foreach ($arr as $k => $v_arr) {
			if($k==1)$v_arr[0]=$ret;
			$ul = count($v_arr[1]); 
			$xs = array(); 
			foreach (array_reverse($v_arr[0]) as $x) { 
				$l = count($xs); 
				if($x!="0" || !($l%4)) {
					$n=($x=='0'?'':$x).($v_arr[1][($l-1)%$ul]); 
				}
				else{
					$n=is_numeric($xs[0][0]) ? $x : ''; 
				}
				array_unshift($xs, $n); 
			} 
			if($k==0){
				$ret = array_merge($ns2,array(implode("", $xs), "")); 
			}
		}
		$ret = implode("",array_reverse($xs)); 
		$r=str_replace(array_keys($cnums), $cnums,$ret); 

		preg_match_all("/./u", $r, $r_arr);
		$rr='';
		$prev_letter='';
		foreach ($r_arr[0] as $k1 => $v1) {
			if(!($v1==$prev_letter && $prev_letter=='零')){
				$rr.=$v1;
			}
			$prev_letter=$v1;
		}
		return $rr;
	}
?>