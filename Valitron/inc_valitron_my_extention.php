<?php
/*
Valitronのルール追加：
・my.dateAfterWith (日付が他のフィールドより後)
　$v->rule('my.dateAfterWith', 'user_flows.date', 'パラメタ名');
  指定のパラメタ名の日付より後の日付かどうかをチェック

・my.arrayLengthBetween （配列の数）
  $v->rule('my.arrayLengthBetween', 'user_flows.flows.*.input', 0, 10);
  配列の数が0～40の間かどうかをチェック

・my.arrayStrLength （文字列配列の合計文字列桁数）
  $v->rule('my.arrayStrLength', 'user_flows.keywords', 0, 40);
  配列内が文字列で、文字列を文字数をすべて合計したときに0～40文字の間かをチェック

・my.jsonStrLength （PHPの連想配列をjson文字列にしたときの文字列長）
  $v->rule('my.jsonStrLength', 'user_flows.keywords', 0, 40);
  json文字列に変換したときに0～40文字の間かをチェック
  

*/

//オリジナルルールの追加(日付が他のフィールドより後)
Valitron\Validator::addRule('my.dateAfterWith', function($field, $value, $params, $fields) {
	$p = $fields[$params[0]];
	if(!isset($p)) return false;
	$vtime = ($value instanceof \DateTime) ? $value->getTimestamp() : strtotime($value);
	$ptime = ($p instanceof \DateTime) ? $p->getTimestamp() : strtotime($p);
	return $vtime >= $ptime;
}, 'must be date after field "%s"');
//オリジナルルールの追加（配列の数）
Valitron\Validator::addRule('my.arrayLengthBetween', function($field, $value, $params, $fields) {
	$min_num = $params[0];
	$max_num = $params[1];
	if(!isset($value)) return true;
	if(!is_array($value)) return false;
	$array_num = count($value);
	if($array_num >= $min_num && $array_num <= $max_num) return true;
	return false;
}, 'must be length of Array between %d and %d');
//オリジナルルールの追加（文字列配列の合計文字列桁数）
Valitron\Validator::addRule('my.arrayStrLength', function($field, $value, $params, $fields) {
	$min_num = $params[0];
	$max_num = $params[1];
	if(!isset($value)) return true;
	if(!is_array($value)) return false;
	$len = 0;
	foreach($value as $element){
		$len += mb_strlen($element);
	}
	if($len >= $min_num && $len <= $max_num) return true;
	return false;
}, 'must be cumulative of params length between %d and %d');
//オリジナルルールの追加（PHPの連想配列をjson文字列にしたときの文字列長）
Valitron\Validator::addRule('my.jsonStrLength', function($field, $value, $params, $fields) {
	$min_num = $params[0];
	$max_num = $params[1];
	if(!isset($value)) return true;
	if(!is_array($value)) return false;
	//JSON文字列に変換
	$str_json = json_encode($value , JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	$len = mb_strlen($str_json);
	if($len >= $min_num && $len <= $max_num) return true;
	return false;
}, 'must be cumulative of json length between %d and %d');

?>