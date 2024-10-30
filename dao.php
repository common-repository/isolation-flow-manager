<?php 
/*
ISFL_IsolationFlow のDAOクラス

関数名のプレフィックス
find_  ...検索
count_ ...何かの項目でグルーピングした数量を数える
save_  ...更新と挿入
  
resultテーブル：
	status=>'created', 'open', 'resolved'
*/


class ISFL_IsolationFlowDao{
	//最後のエラー。成功してもクリアしないので、これで判断しないこと。
	public $last_error = '';
	//トランザクション制御(トランザクションのネストがあった場合、一番外側だけ実行されるように情報保存する変数)
	private $transaction_info = array();


	/**
	 * もしトランザクションが開始されていなければ開始します。
	 * このメソッドを使用する場合は必ずこのクラスのcommit()/rollback()を使用しなければなりません。
	 * 内部の状態がおかしくなります。
	 */
	protected function begin_transaction_if_required(){
		global $wpdb;
		if(count($this->transaction_info) == 0){
			$wpdb->query("START TRANSACTION"); 
		}
		$this->transaction_info[] = true;
	}

	protected function commit(){
		global $wpdb;
		if(count($this->transaction_info) == 1){
			$wpdb->query("COMMIT"); 
		}
		$ret = array_pop($this->transaction_info);
		if(is_null($ret)){
			throw new Exception('number of transaction must equal number of commit/rollback');
		}
	}

	protected function rollback(){
		global $wpdb;
		if(count($this->transaction_info) == 1){
			$wpdb->query("ROLLBACK"); 
		}
		$ret = array_pop($this->transaction_info);
		if(is_null($ret)){
			throw new Exception('number of transaction must equal number of commit/rollback');
		}
	}

	/**
	 * find検索結果形式に変換した結果を返す
	 * @param array $list 検索結果
	 * @param int $amount 検索総数
	 * @param array $searchkeys 検索キー。offset,limitが設定されていること
	 * @return array array('amount'=>$amount, 'offset'=>$offset, 'limit'=>$limit, 'list'=>$list)
	 */
	protected function _create_find_result(array $list, int $amount, array $searchkeys) : array{
		$offset = $searchkeys['offset'];
		$limit = $searchkeys['limit'];
		if($amount < $offset) $offset = $amount;
		return array('amount'=>$amount, 'offset'=>$offset, 'limit'=>$limit, 'list'=>$list);
	}

	/**
	 * Search Keysのデフォルト値を設定する。
	 * @param array $searchkeys 対象の
	 * @return array デフォルト値を設定した検索キー
	 */
	protected function _default_searchkeys(array $searchkeys): array{
		if(!isset($searchkeys['offset'])) $searchkeys['offset'] = 0;
		if(!is_int($searchkeys['offset']) && ctype_digit($searchkeys['offset'])){
			$searchkeys['offset'] = (int)$searchkeys['offset'];
		}
		if(!is_int($searchkeys['offset']) || $searchkeys['offset'] < 0){
			throw new Exception('$searchkeys[offset] is invalid.');
		}
		if(!isset($searchkeys['limit'])) $searchkeys['limit'] = 30;
		if(!is_int($searchkeys['limit']) && ctype_digit($searchkeys['limit'])){
			$searchkeys['limit'] = (int)$searchkeys['limit'];
		}
		if(!is_int($searchkeys['limit']) || $searchkeys['limit'] < 0){
			throw new Exception('$searchkeys[limit] is invalid.');
		}

		return $searchkeys;
	}

	/**
	 * joinでひとまとめに取得したSQLの結果をキーでまとめる（$keyでgroup by するようなイメージ）
	 * @param array $rows          SQLの結果
	 * @param string $key         集約する項目名。$rows[*][$key]が同じ値のものをひとつにまとめる
	 * @param array $gather_keys  1つに配列化する項目の名前。指定した項目はすべて$ret[*][$gather_name]に連想配列で設定される
	 * @param string $gather_name 1つにした配列を代入する項目名。
	 * @return array まとめた結果を返す([{$key, ..., $gather_name=>{$gather_keysの項目値}}])
	 */
	protected function reshape_result(array $rows, string $key, array $gather_keys, string $gather_name) : array {
		if(!isset($key)) die('不正な引数：$key');
		$ret = array();
		if(count($rows) == 0) return $ret;
		$cur_id = NULL;
		
		//$orderはreturnする配列の1行を表す
		foreach($rows as $line){
			if($cur_id != $line[$key]){
				if(isset($order)) $ret[] = $order;
				$cur_id = $line[$key];
				$order = $line;
				foreach($gather_keys as $condens_key){
					unset($order[$condens_key]);
				}
				$order[$gather_name] = array();
			}
			//1つに配列化するカラムの処理
			$condens_ary = array();
			foreach($gather_keys as $condens_key){
				if(!is_null($line[$condens_key])){
					$condens_ary[$condens_key] = $line[$condens_key];
				}
			}
			if(count($condens_ary) != 0) $order[$gather_name][] = $condens_ary;
		}
		
		//最後の1つを設定する
		$ret[] = $order;
		
		return $ret;
	}

	/**
	 * 指定のキーでDistinctにする。指定のキーの値が一意になった配列が返る。
	 * キーカラムに値が設定されていない場合はその行が無視(削除)される。
	 * @param array $rows 元の対象配列
	 * @param string $key 一意にするためのキーを指定。
	 * @return array キーの値が一意になった配列。[{a:1,b:1},{a:1,b:1}]⇒(結果$key='a')[{a:1,b:1}]
	 */
	private function _reshape_distinct(array $rows, string $key):array {
		$groups = array();
		//指定のキーが全て同じ値の場合はで連想配列を作成。グルーピングする。
		foreach($rows as $line){
			$key_value = $line[$key];
			if(!isset($key_value)) continue;
			$groups[$key_value] = $line;
		}
		//グルーピングしたそれぞれの中を次のグルーピングをする。
		$ret = array();
		foreach($groups as $line){
			$ret[] = $line;
		}
		return $ret;
	}

	/**
	 * カラム名でグルーピングして返す。
	 * $primary_keyでグルーピングした結果を、さらにカラム名に__がついているものでグルーピング。
	 * __は複数指定可能。結果は$primary_keyの値をキーにした連想配列。
	 * [{a:1, b__c:2, b__d:3, c:11}, {a:1, b__c:4, b__d:5, c:11}, {a:1, b__c:4, b__d:7, c:11}]
	 * (結果:$primary_key='a', $group_ids=['b__c']): {1: {a:1, c:11, b:[{c:2, d:3}, {c:4, d:5}]}}
	 * @param array $rows 元データ
	 * @param string $primary_key データの中で主キーとなるカラム名。
	 * @param array $group_ids カラム名__がついているもののうち主キーとなるカラム名。同じ値は1行になる。
	 * @return array グルーピングした結果。
	 */
	protected function reshape_result_by_name(array $rows, string $primary_key, array $group_ids): array{
		if(!isset($primary_key)) die('不正な引数：$primary_key');
		$ret = array();
		if(count($rows) == 0) return $ret;

		//各グループキーを分解(group_name:__より前の部分、column_name:__より後ろの部分)
		$group_info = array();
		foreach($group_ids as $key){
			$pos = strpos($key, '__');
			//グルーピング対象ではない値
			if($pos === false){
				$group_fields[$key] = $val;
				continue;
			}
			//__より前の文字列
			$group_name = substr($key, 0, $pos);
			//__より後ろの文字列
			$name = substr($key, -(strlen($key) - $pos -2));
			$group_info[$key] = array('name'=>$key, 'group_name'=>$group_name, 'column_name'=>$name);
		}

		//まずidでグルーピング
		$gid = $primary_key;
		foreach($rows as $line){
			$id = $line[$primary_key];
			if(!isset($ret[$id])) $ret[$id] = array();
			$ret[$id][] = $line;
		}

		//グルーピングした中でさらに名前__でグルーピング
		foreach($ret as $i => $group){
			$group_fields = array();
			foreach($group as $line){
				$fields = array();
				//1行のデータ内でまとめる
				foreach($line as $key => $val){
					$pos = strpos($key, '__');
					//グルーピング対象ではない値
					if($pos === false){
						$group_fields[$key] = $val;
						continue;
					}
					//__より前の文字列
					$group_name = substr($key, 0, $pos);
					//__より後ろの文字列
					$name = substr($key, -(strlen($key) - $pos -2));
					//グルーピングの値の設定
					if(!isset($fields[$group_name])) $fields[$group_name] = array();
					
					$fields[$group_name][$name] = $val;
				}
				//グルーピング１つにデータを追加
				foreach($fields as $group_name => $ary_val){
					if(!isset($group_fields[$group_name])) $group_fields[$group_name] = array();
					$group_fields[$group_name][] = $ary_val;
				}
			}
			//グループキーの値で一意にする
			foreach($group_info as $info){
				$group_name = $info['group_name'];
				$group_lines = $group_fields[$group_name];
				//グループの主キーで一意にする。
				$group_fields[$group_name] = $this->_reshape_distinct($group_lines, $info['column_name']);
			}
			//グルーピングし直した値で詰めなおす
			$ret[$i] = $group_fields;
		}
		
		return $ret;
	}

	/**
	 * 入力文字列について、likeのエスケープをする。
	 * @param string $val - 対象文字列
	 * @return string - エスケープ後の文字列
	 */
	protected function _escape_like(string $val):string {
		$ret = preg_replace("/([%_])/", '\\\\$1', $val);
		return $ret;
	}

	/**
	 * flowテーブルのレコードを削除
	 * @param array $serachkeys 削除対象検索キー
	 *      ({isfl_id:必須, revision, max_revision:これ以下の値を削除,
	 *        has_not_result:結果と紐づかないもの(設定値はtrueのみ)})
	 */
	public function delete_group_flows(array $searchkeys): bool{
		global $wpdb;
		$isfl_id = $searchkeys['isfl_id'];
		if(!isset($isfl_id)) throw new Exception('$searchkeys[isfl_id] must have value.');
		//
		$table_result = $wpdb->prefix . 'isfl_result';
		$table_flow = $wpdb->prefix . 'isfl_flow';
		$where = '';
		$where_ary = array();
		//
		$where .= 'and isfl_id = %d ';
		$where_ary[] = $isfl_id;
		if(isset($searchkeys['revision'])){
			$where .= 'and revision = %d ';
			$where_ary[] = $searchkeys['revision'];
		}
		if(isset($searchkeys['has_not_result'])){
			if(!isset($searchkeys['max_revision'])){
				throw new Exception('$searchkeys: max_revision must be set, if you use has_not_result.');
			}
			$where .= "and revision not in(select distinct revision from 
				$table_result r1 where r1.isfl_id = isfl_id) ";
		}
		if(isset($searchkeys['max_revision'])){
			$where .= 'and revision <= %d ';
			$where_ary[] = $searchkeys['max_revision'];
		}
		
		if(strlen($where) != 0){
			$where = 'where ' . substr($where, 3);
		}

		//レコード取得
		$sql = "delete from $table_flow $where";
		$sql = $wpdb->prepare($sql , $where_ary);
		$result = $wpdb->query($sql, ARRAY_A);
		if($result === false){
			$this->last_error = $wpdb->last_error;
			return false;
		}
		return true;
	}

	/**
	 * FlowGroupを登録する。
	 * @param ISFL_IsolationFlowGroup $p_group 保存対象。isfl_id=0のときinsert。それ以外は更新。
	 * @return int 成功時：0、警告時：1:更新なのに変更(差分)データなし、エラー時：-1:更新(isfl_id<>0)なのに元データがない、-3:DBエラー、-4:予期せぬエラー
	 */
	public function save_group_class($p_group): int{
		if(!($p_group instanceof ISFL_IsolationFlowGroup)){
			throw new TypeError('$p_group must be ISFL_IsolationFlowGroup.');
		}
		$isErr = true;
		$isfl_id = $p_group->isfl_id;
		$revision = $p_group->revision;

		//トランザクション開始
		$this->begin_transaction_if_required();
		try{
			//DBのデータとの差分を計算
			if($isfl_id === 0){
				//新規
				$current_revision = 0; 
			}else{
				//更新時はDBの値との差分を計算
				//DBのグループ情報を取得する
				$db_group = $this->get_group_class($isfl_id);
				if(is_null($db_group)){
					$this->last_error = 'update command, but isfl_id dose not exist.';
					return -1;
				}
				//DBとの差分を計算
				$diff = $p_group->check_diff($db_group);
				if(count($diff['flows']) == 0 && count($diff['entire']) == 0) return 1;
				if(count($diff) == 0) return 1;
				$current_revision = $db_group->revision; 
			}
			$diff = array('flows' => $p_group->get_flow_ids());
			
			//DBと引数グループとの差分から保存対象を作成
			$next_revision = $current_revision + 1;
			$save_data = $p_group->get_user_flows();
			$save_data['revision'] = $next_revision;
			$save_data['flows'] = array(); 
			foreach($diff['flows'] as $flow_id){
				$flow = $p_group->get_flow_data($flow_id);
				$flow['revision'] = $next_revision;
				//保存対象のFlowとして追加
				$save_data['flows'][] = $flow;
			}

			//Groupテーブル
			$result = $this->save_group($current_revision, $save_data);
			if($result === false){
				$this->last_error = 'save_group() failed.';
				return -3;
			}
			//insertだった場合は値が返ってきてるのでそれを設定。
			$isfl_id = $save_data['isfl_id'];

			//Flowテーブル
			foreach($save_data['flows'] as $flow){
				$result = $this->insert_flow($isfl_id, $flow);
				if($result === false){
					$this->last_error = 'insert_flow() failed.';
					return -3;
				}
			}

			//成功時
			$isErr = false;
			$this->commit();
			
			//成功したらisfl_idを保存する（insertの場合はDB値が返ってくる）
			$p_group->isfl_id = $save_data['isfl_id'];

			return 0;
		}finally{
			if($isErr){
				$this->last_error .= '  ' . $wpdb->last_error;
				$this->rollback();
			}
		}
		return -4;
	}

	/**
	 * groupテーブルに保存。isfl_id=0のときinsert。それ以外は更新。
	 * @param int $revision 更新対象のリビジョン（楽観的ロックのために必要）
	 * @param array $data Groupデータ。insertの場合$data['isfl_id']にDBの値が設定される。
	 * @return bool 成功時：true、エラー時：false
	 */
	public function save_group(int $revision, array& $group) : bool {
		global $wpdb;
		if(!is_numeric($group['isfl_id'])){
			throw new Exception('isfl_id must be numeric.isfl_id='. $group['isfl_id']);
		}
		if($revision >= $group['revision']){
			throw new Exception('$group["revision"] must be bigger than $revision.');
		}
		//GroupテーブルDB保存
		$table_group = $wpdb->prefix . 'isfl_group';
		$table_flow = $wpdb->prefix . 'isfl_flow';
		$data = array(
			'isfl_id'      =>$group['isfl_id'],
			'created_date' =>date_i18n("Y-m-d H:i:s"),
			'revision'     =>$group['revision'],
			'group_title'  =>$group['group_title'],
			'group_remarks'=>$group['group_remarks'],
			'start_flow_id'=>$group['start_flow_id'],
		);
		$fields = "created_date, revision, group_title, group_remarks, start_flow_id, keywords";
		$keywords = ',' . implode(',', $group['keywords']) . ',';
		$data['keywords'] = $keywords;
		
		//SQL作成
		if($data['isfl_id'] === 0){
			$sql = "insert into $table_group ($fields) values(%s, %d, %s, %s, %d, %s)";
			$values = array($group['created_date'], $data['revision'], 
				$group['group_title'], $group['group_remarks'], 
				$data['start_flow_id'], $data['keywords']);
		}else{
			$sql = "update $table_group set revision = %d, group_title= %s, 
				group_remarks=%s, start_flow_id=%s, keywords=%s 
				where isfl_id=%d and revision=%d ";
			$values = array($data['revision'], $group['group_title'], $group['group_remarks'],
				$data['start_flow_id'], $data['keywords'], $data['isfl_id'], $revision);
		}
		//保存
		$sql = $wpdb->prepare($sql , $values);
		$result = $wpdb->query($sql);
		$insert_id = $wpdb->insert_id;//挿入したレコードのキー値
		if($result === false){
			$this->last_error = $wpdb->last_error;
			return false;
		}else if($result !== 1){
			//1行も影響を受けなかった場合
			return false;
		}
		//insertの場合、idを取得
		if($group['isfl_id'] === 0){
			$group['isfl_id'] = $insert_id;
		}
		return true;
	}

	/**
	 * １つのFlowの登録
	 * @param int $isfl_id FlowグループID
	 * @param array $flow_data FLowデータ
	 * @return bool 成功時：true、エラー時：false
	 */
	public function insert_flow(int $isfl_id, array $flow_data) : bool {
		global $wpdb;
		$isErr = true;
		$flow_id = $flow_data['flow_id'];
		$revision = $flow_data['revision'];
		//トランザクション開始
		$this->begin_transaction_if_required();
		try{
			//FlowテーブルDB保存
			$table_name = $wpdb->prefix . 'isfl_flow';
			$data = array(
				'isfl_id'=>$isfl_id,
				'flow_id'=>$flow_id,
				'pt_id'=>$flow_data['pt_id'],
				'created_date'=>date_i18n('Y-m-d H-i-s.v'),
				'revision'=>$revision,
				'status'=>$flow_data['status'],
				'title'=>$flow_data['title'],
				'question'=>$flow_data['question'],
			);
			$result = $wpdb->insert($table_name, $data);
			if($result === false){
				$this->last_error = $wpdb->last_error;
				return false;
			}

			//inputテーブル
			foreach($flow_data['input'] as $line){
				$result = $this->insert_flow_input($isfl_id, $flow_id, $revision, $line);
				if($result === false){
					return false;
				}
			}

			//choicesテーブル
			foreach($flow_data['choices'] as $line){
				$result = $this->insert_flow_choice($isfl_id, $flow_id, $revision, $line);
				if($result === false){
					return false;
				}
			}

			//成功時
			$isErr = false;
			$this->commit();
			return true;
		}finally{
			if($isErr){
				$this->last_error = $wpdb->last_error;
				$this->rollback();
			}
		}
		return true;
	}
	
	/**
	 * inputテーブルへの保存
	 * @param int $flow_id Flow ID
	 * @param int $revision リビジョン
	 * @param array   $input 入力データ
	 * @return bool 成功時：true、エラー時：false
	 */
	public function insert_flow_input(int $isfl_id, int $flow_id, int $revision, array $input)
	: bool{
		global $wpdb;
		//inputテーブル
		$table_name = $wpdb->prefix . 'isfl_input';
		$data = array(
			'isfl_id' =>$isfl_id, 
			'flow_id'=>$flow_id,
			'revision'=>$revision,
			'no'=>$input['no'],
			'type'=>$input['type'],
			'label'=>$input['label'],
		);
		$result = $wpdb->insert($table_name, $data);
		if($result === false){
			$this->last_error = $wpdb->last_error;
			return false;
		}
		return true;
	}

	/**
	 * choicesテーブルへの保存
	 * @param int $flow_id Flow ID
	 * @param int $revision リビジョン
	 * @param array   $input 入力データ
	 * @return bool 成功時：true、エラー時：false
	 */
	public function insert_flow_choice(int $isfl_id, int $flow_id, int $revision, array $choice)
	: bool{
		global $wpdb;
		//choiceテーブル
		$table_name = $wpdb->prefix . 'isfl_choices';
		$data = array(
			'isfl_id' =>$isfl_id, 
			'flow_id' =>$flow_id,
			'revision'=>$revision,
			'id'      =>$choice['id'],
			'label'   =>$choice['label'],
			'attachment_id'   =>$choice['attachment_id'],
			'next_flow_id'=>$choice['next_flow_id'],
		);
 		$result = $wpdb->insert($table_name, $data);
		if($result === false){
			$this->last_error = $wpdb->last_error;
			return false;
		}
		return true;
	}


	
	/**
	 * resultテーブルに保存。result_id=0のときinsert。それ以外は更新。
	 * {user_id:int, isfl_id:int, revision:int, status:string, result:array, remarks:string, result_id:int}
	 * 連動してデータの整合性がおかしくなることもないので、このテーブルは楽観的ロックなし。
	 * @param array $flow_result resultデータ。insertの場合$data['result_id']にDBの値が設定される。
	 * @return bool 成功時：true、エラー時：false　groupテーブルにisfl_idが存在しない場合もfalse。
	 */
	public function save_flow_results(array &$flow_result) : bool {
		global $wpdb;
		if(!is_numeric($flow_result['result_id'])){
			throw new Exception('isfl_id must be numeric. result_id='. $flow_result['result_id']);
		}
		$status = $flow_result['status'];
		if(!($status == 'created' || $status == 'open' || $status == 'resolved')){
			throw new Exception("status must be in 'creaetd','open','resolved'. actually $status");
		}
		if(!is_array($flow_result['result'])){
			throw new Exception("result must be array.");
		}
		//GroupテーブルDB保存
		$table_group = $wpdb->prefix . 'isfl_group';
		$table_result = $wpdb->prefix . 'isfl_result';
		
		//JSON文字列に変換
		$result = json_encode($flow_result['result'] , JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES );

		//SQL作成
		if($flow_result['result_id'] === 0){
			$sql = "insert into $table_result (
				 isfl_id, revision, status, result, remarks, created_date, user_id)
				 values(
				  (select isfl_id from $table_group grp where grp.isfl_id=%d), 
				  (select revision from $table_group grp where grp.isfl_id=%d),
				  %s, %s, %s, %s, %d)";
			$values = array(
				$flow_result['isfl_id'], 
				$flow_result['isfl_id'], 
				$flow_result['status'],
				$result, 
				$flow_result['remarks'],
				date_i18n('Y-m-d H-i-s.v'),
				$flow_result['user_id'],
			);
		}else{
			$sql = "update $table_result set status=%s, result=%s, remarks=%s, user_id=%s
				where result_id=%d and isfl_id=%d and revision=%d";
			$values = array(
				$flow_result['status'],
				$result, 
				$flow_result['remarks'],
				$flow_result['user_id'],
				$flow_result['result_id'],
				$flow_result['isfl_id'], 
				$flow_result['revision'],
			);
			/* どのユーザでもどの結果も更新できるようにしたのでコメントアウト
			//ユーザ指定の場合（通常は指定。管理者の時のみ指定なし）
			if(!is_null($flow_result['user_id'])){
				$sql .= ' and user_id=%d';
				$values[] = $flow_result['user_id'];
			}*/
		}
		//保存
		$sql = $wpdb->prepare($sql , $values);
		$result = $wpdb->query($sql);
		$insert_id = $wpdb->insert_id;//挿入したレコードのキー値
		if($result === false){
			$this->last_error = $wpdb->last_error;
			return false;
		}else if($result !== 1){
			//1行も影響を受けなかった場合(ありえないが2行以上影響受けた場合含む)
			//同じ値に更新された場合$resultは0を返すので、チェックできない。
			//return false;
		}
		//insertの場合、idを取得
		if($flow_result['result_id'] === 0){
			$flow_result['result_id'] = $insert_id;
		}
		return true;
	}

	/**
	 * グループの検索。
	 * @param array $searchkeys 検索キー。{offset, limit, group_title, keywords[], isfl_id}
	 *       revisionの指定はできない（groupテーブルのrevitionには現在のrivision（=最大値）が設定されている）。
	 * @return array 検索結果形式
	 */
	public function find_group(array $searchkeys){
		global $wpdb;
		$searchkeys = $this->_default_searchkeys($searchkeys);
		$offset = $searchkeys['offset'];
		$limit = $searchkeys['limit'];
		//
		$table_group = $wpdb->prefix . 'isfl_group';
		$where = '';
		$where_ary = array();
		if(isset($searchkeys['group_title'])){
			$where .= 'and grp.group_title like %s ';
			$where_ary[] = '%'. $this->_escape_like($searchkeys['group_title']) .'%';
		}
		if(isset($searchkeys['group_remarks'])){
			$where .= 'and grp.group_remarks like %s ';
			$where_ary[] = '%'. $this->_escape_like($searchkeys['group_remarks']) .'%';
		}
		if(isset($searchkeys['keywords'])){
			$where .= 'and grp.keywords like %s ';
			$where_ary[] = '%,'. $this->_escape_like($searchkeys['keywords']) .',%';
		}
		if(isset($searchkeys['isfl_id'])){
			$where .= 'and grp.isfl_id = %d ';
			$where_ary[] = $searchkeys['isfl_id'];
		}
		
		if(strlen($where) != 0){
			$where = 'where ' . substr($where, 3);
		}
		//全件数の取得
		$sql = "select count(grp.isfl_id) as cnt from $table_group grp $where";
		$sql = $wpdb->prepare($sql , $where_ary);
		$amount = $wpdb->get_results($sql, ARRAY_A)[0];
		$amount = isset($amount) ? $amount['cnt'] : 0;

		//レコード取得
		$sql = "select grp.* from $table_group grp
			 $where order by grp.isfl_id limit $offset,$limit ";
		$sql = $wpdb->prepare($sql , $where_ary);
		$rows = $wpdb->get_results($sql, ARRAY_A);
		$this->last_error = $wpdb->last_error;
		//keywordの設定
		foreach($rows as &$line){
			//カンマ区切りを配列に変換
			$keywords = $line['keywords'];
			if($keywords == ',,'){
				$ary = array();
			}else{
				$ary = explode(',', substr($keywords, 1, strlen($keywords)-2));
			}
			$line['keywords'] = $ary;
		}
		//
		return $this->_create_find_result($rows, $amount, $searchkeys);
	}

	/**
	 * １つのグループのFlowを取得
	 * @param int $isfl_id グループのIDを指定
	 * @param int $revision 検索するリビジョン。NULLのとき最新を取得。flow_idでグルーピングして、
	 * 	　　　　　　　指定したリビジョン以下のうち最大のリビジョンのレコードを取得する。
	 * @return array 指定のグループ情報に所属するFlowデータ。見つからない場合要素数0の配列。
	 */
	public function get_group_flows(int $isfl_id, ?int $revision=NULL) :array{
		global $wpdb;
		//if(is_null($revision)) $revision = 9999999;//mysqlの最大値
		$table_group = $wpdb->prefix . 'isfl_group';
		$table_flow = $wpdb->prefix . 'isfl_flow';
		$table_input = $wpdb->prefix . 'isfl_input';
		$table_choices = $wpdb->prefix . 'isfl_choices';
		$table_posts = $wpdb->prefix . 'posts';
		$where_ary = array($isfl_id);
		//
		$sql = "select flow.flow_id, flow.revision, flow.created_date, flow.pt_id, 
		flow.status, flow.title, flow.question,
		input.no as input__no, input.type as input__type, input.label as input__label, 
		choices.id as choices__id, choices.label as choices__label, 
		choices.next_flow_id as choices__next_flow_id, choices.attachment_id as choices__attachment_id,
		pst.guid as choices__image
		from $table_flow flow 
		left outer join $table_input input 
		on flow.isfl_id=input.isfl_id and flow.flow_id=input.flow_id and flow.revision=input.revision 
		left outer join $table_choices choices 
		on flow.isfl_id=choices.isfl_id and flow.flow_id=choices.flow_id and flow.revision=choices.revision
		left outer join $table_posts pst on pst.id=choices.attachment_id
		where flow.isfl_id=%d ";
		if(is_null($revision)){
			$sql .= " and flow.revision = (
				select revision from $table_group g1 where g1.isfl_id = flow.isfl_id)";
		}else{
			$sql .= " and flow.revision = %d";
			$where_ary[] = $revision;
		} 
		$sql .= " order by flow.flow_id, input__no, choices__id";
		
		//SQL文作成
		$sql = $wpdb->prepare($sql , $where_ary);
		//SQL実行
		$rows = $wpdb->get_results($sql, ARRAY_A);
		if($rows === false){
			$this->last_error = $wpdb->last_error;
			return array();
		}
		$ret = $this->reshape_result_by_name($rows, 'flow_id', array('input__no', 'choices__id'));
		return $ret;
	}

	/**
	 * グループ情報（完全）を取得する。
	 * @param int $isfl_id 取得する対象のID
	 * @param int $revision 通常は指定しない。過去のflowsを取得したい時のみ指定する。
	 *             取得対象のリビジョン。NULLのとき最新のリビジョンを検索。
	 *             リビジョンを指定した場合、flowsのみが検索される。start_flow_idは最新の値になるので注意。
	 * @return ISFL_IsolationFlowGroup グループ情報。見つからない場合はNULL
	 */
	public function get_group_class(int $isfl_id, ?int $revision=NULL) :?ISFL_IsolationFlowGroup{
		global $wpdb;
		$ret = array();

		//グループ検索キー作成
		$searchkey = array('isfl_id'=>$isfl_id);
		
		//グループテーブル
		$result = $this->find_group($searchkey);
		if($result['amount'] == 0) return NULL;
		$group = $result['list'][0];
		require_once 'isolation_flow_group.php';
		$ret['user_flows'] = ISFL_IsolationFlowGroup::FLOW_GROUP_DEFAULT;
		$ret['user_flows']['isfl_id'] = $isfl_id;
		$ret['user_flows']['revision'] = $group['revision'];
		$ret['user_flows']['group_title'] = $group['group_title'];
		$ret['user_flows']['group_remarks'] = $group['group_remarks'];
		$ret['user_flows']['start_flow_id'] = $group['start_flow_id'];
		$ret['user_flows']['keywords'] = $group['keywords'];
		
		//フロー
		$rows = $this->get_group_flows($isfl_id, $revision);
		if(count($rows) == 0) return NULL;
		foreach($rows as $line){
			$flow_id = $line['flow_id'];
			$ret['user_flows']['flows'][''.$flow_id] = $line;
		}

		//オブジェクト作成
		$obj = new ISFL_IsolationFlowGroup($ret);
		return $obj;
	}
	
	/**
	 * Flow結果の検索。
	 * @param array $searchkeys 検索キー。{offset, limit, keywords, isfl_id, 
	 *     result_id, user_id, revision, remarks, group_title
	 *     results.status, results.statuses, results.remarks, order_by:array}
	 *     order_byはソート順の指定。連想配列でカラム名=>bool(trueのときasc)
	 *     カラム名に指定できるのは、{result_id,status,user_id,isfl_id}。
	 * @return array 検索結果形式
	 */
	public function find_flow_results(array $searchkeys): array{
		global $wpdb;
		$searchkeys = $this->_default_searchkeys($searchkeys);
		$offset = $searchkeys['offset'];
		$limit = $searchkeys['limit'];
		//
		$table_group = $wpdb->prefix . 'isfl_group';
		$table_result = $wpdb->prefix . 'isfl_result';
		$table_users = $wpdb->prefix . 'users';
		$where = '';
		$where_ary = array();
		if(isset($searchkeys['user_id'])){
			$where .= 'and rst.user_id = %d ';
			$where_ary[] = $searchkeys['user_id'];
		}
		if(isset($searchkeys['user_name'])){
			$where .= "and rst.user_id in(select id from $table_users where $table_users.display_name like %s) ";
			$where_ary[] = '%'.$searchkeys['user_name'].'%';
		}
		if(isset($searchkeys['result_id'])){
			$where .= 'and rst.result_id = %d ';
			$where_ary[] = $searchkeys['result_id'];
		}
		if(isset($searchkeys['group_title'])){
			$where .= "and rst.isfl_id in(select isfl_id from $table_group grp
				where grp.group_title like %s ) ";
			$where_ary[] = '%'. $this->_escape_like($searchkeys['group_title']) .'%';
		}
		if(isset($searchkeys['keywords'])){
			$where .= "and rst.isfl_id in(select isfl_id from $table_group grp
				where grp.keywords like %s ) ";
			$where_ary[] = '%,'. $this->_escape_like($searchkeys['keywords']) .',%';
		}
		if(isset($searchkeys['remarks'])){
			$where .= "and rst.isfl_id in(select isfl_id from $table_group grp
				where grp.remarks like %s ) ";
			$where_ary[] = '%'. $this->_escape_like($searchkeys['remarks']) .'%';
		}
		if(isset($searchkeys['isfl_id'])){
			$where .= 'and rst.isfl_id = %d ';
			$where_ary[] = $searchkeys['isfl_id'];
		}
		if(isset($searchkeys['revision'])){
			$where .= 'and rst.revision = %d ';
			$where_ary[] = $searchkeys['revision'];
		}
		if(isset($searchkeys['results.remarks'])){
			$where .= 'and rst.remarks like %s ';
			$where_ary[] = '%'. $this->_escape_like($searchkeys['results.remarks']) .'%';
		}
		if(isset($searchkeys['results.status'])){
			$where .= 'and rst.status = %s ';
			$where_ary[] = $searchkeys['status'];
		}
		if(isset($searchkeys['results.statuses'])){
			if(is_array($searchkeys['results.statuses']) && count($searchkeys['results.statuses']) > 0){
				$in = '';
				foreach($searchkeys['results.statuses'] as $line){
					$in .= ',%s';
					$where_ary[] = $line;
				}
				$where .= 'and rst.status in (' . substr($in, 1) . ') ';
			}
		}
		if(isset($searchkeys['created_date_from']) || isset($searchkeys['created_date_to'])){
			if(empty($searchkeys['created_date_from'])) $searchkeys['created_date_from'] = '1970-01-01';
			if(empty($searchkeys['created_date_to'])) $searchkeys['created_date_to'] = '9999-12-30';
			$where .= 'and (rst.created_date >= %s and rst.created_date < DATE_ADD(%s,INTERVAL 1 DAY) ) ';
			$where_ary[] = $searchkeys['created_date_from'];
			$where_ary[] = $searchkeys['created_date_to'];
		}
		//ソートの作成
		$order_by = 'order by rst.result_id desc';
		if(isset($searchkeys['order_by'])){
			if(!(is_array($searchkeys['order_by']) && count($searchkeys['order_by'])>0)){
				throw new Exception('order_by must be array and length > 0.');
			}
			$order_by = '';
			foreach($searchkeys['order_by'] as $column => $bl){
				if(!($column == 'result_id' || $column == 'status' || $column == 'user_id' || $column == 'isfl_id')){
					throw new Exception('order_by must be in result_id,status,user_id,isfl_id.');
				}
				$order_by .= ", rst.$column " . ($bl ? 'asc' : 'desc');
			}
			$order_by = 'order by ' . substr($order_by, 1);
		}
		if(strlen($where) != 0){
			$where = 'where ' . substr($where, 3);
		}

		//全件数の取得
		$sql = "select count(rst.isfl_id) as cnt from $table_result rst $where";
		$sql = $wpdb->prepare($sql , $where_ary);
		$amount = $wpdb->get_results($sql, ARRAY_A)[0];
		$amount = isset($amount) ? $amount['cnt'] : 0;
		//レコード取得
		$sql = "select rst.*, grp.group_title, user.display_name as user_name 
			from $table_result rst
			 left outer join $table_group grp 
			  on grp.isfl_id=rst.isfl_id
			 left outer join $table_users user
			  on user.id=rst.user_id
			 $where $order_by limit $offset,$limit";
		$sql = $wpdb->prepare($sql , $where_ary);
		$rows = $wpdb->get_results($sql, ARRAY_A);
		$this->last_error = $wpdb->last_error;
		//resultをJSON文字列から連想配列に変換
		foreach($rows as &$line){
			$line['result'] = json_decode($line['result'] , true);
		}
		//
		return $this->_create_find_result($rows, $amount, $searchkeys);
	}
	

	/**
	 * 切り分け結果の統計数値の取得。数の多い順に並べる。
	 * この関数はreturnは1行1つの項目を返すように設計する。そうしないとページングの
	 * 整合性が取れなくなる。例えば日毎、isfl_id毎に結果数をlimit 5でまとめた場合、
	 * 結果の日付が7日間あると7日間分を１つのisfl_idでまとめて返却しないとグラフがおかしくなるが。
	 * 5日間分のデータで1ページを作って、2ページにisfl_id=1が2行とisfl_id=2が3行のように混ざって返却される。
	 * こうなるとグラフを作るときに整合性がとれない。
	 * @param array $searchkeys 検索キー。{offset, limit, 
	 *     cnt_kind{統計の種類:user|time|isfl_id},keywords, 
	 *     group_title, results.statuses, (cnt_created_date_unit|created_date_from)}
	 * @return array 検索結果形式{chart_kind{グラフの種類:bar-h,line-time}, cnt_kind, max_cnt:最大値, ...あとは検索結果形式}
	 */
	public function count_flow_results(array $searchkeys) : array{
		global $wpdb;
		$searchkeys = $this->_default_searchkeys($searchkeys);
		$offset = $searchkeys['offset'];
		$limit = $searchkeys['limit'];

		//条件
		$table_group = $wpdb->prefix . 'isfl_group';
		$table_result = $wpdb->prefix . 'isfl_result';
		$table_users = $wpdb->prefix . 'users';
		//%%Yなどの置き替えを必ずやるためにプレースホルダーを強引に使わせる
		$where = 'and 1=%d ';
		$where_ary = array(1);
		//条件句の作成
		if(isset($searchkeys['group_title'])){
			$where .= "and rst.isfl_id in(select isfl_id from $table_group grp
				where grp.group_title like %s ) ";
			$where_ary[] = '%'. $this->_escape_like($searchkeys['group_title']) .'%';
		}
		if(isset($searchkeys['keywords'])){
			$where .= "and rst.isfl_id in(select isfl_id from $table_group grp
				where grp.keywords like %s ) ";
			$where_ary[] = '%,'. $this->_escape_like($searchkeys['keywords']) .',%';
		}
		if(isset($searchkeys['results.statuses'])){
			if(is_array($searchkeys['results.statuses']) && count($searchkeys['results.statuses']) > 0){
				$in = '';
				foreach($searchkeys['results.statuses'] as $line){
					$in .= ',%s';
					$where_ary[] = $line;
				}
				$where .= 'and rst.status in (' . substr($in, 1) . ') ';
			}
		}
		$created_date_format = '%%Y-%%m-%%d';
		if(isset($searchkeys['cnt_created_date_unit'])){
			$unit = $searchkeys['cnt_created_date_unit'];
			if($unit == 'hour'){ $created_date_format = '%%Y-%%m-%%d %%H';}
			else if($unit == 'day'){ $created_date_format = '%%Y-%%m-%%d'; }
			else if($unit == 'month'){ $created_date_format = '%%Y-%%m'; }
			else if($unit == 'year'){ $created_date_format = '%%Y'; }
			else{ throw new Exception('$searchkeys[cnt_created_date_unit] is invalid.'); }
		}
		if(isset($searchkeys['created_date_from']) || isset($searchkeys['created_date_to'])){
			if(empty($searchkeys['created_date_from'])) $searchkeys['created_date_from'] = '1970-01-01';
			if(empty($searchkeys['created_date_to'])) $searchkeys['created_date_to'] = '9999-12-30';
			$where .= 'and (rst.created_date >= %s and rst.created_date < DATE_ADD(%s,INTERVAL 1 DAY) ) ';
			$where_ary[] = $searchkeys['created_date_from'];
			$where_ary[] = $searchkeys['created_date_to'];
		}
		//
		if(strlen($where) != 0){
			$where = 'where ' . substr($where, 3);
		}

		//
		$from = " from (select date_format(created_date, '$created_date_format') as date_term, 
		created_date, isfl_id, revision, status, user_id from $table_result) rst
		left outer join $table_group grp 
		 on grp.isfl_id=rst.isfl_id 
		left outer join $table_users user
		 on user.id=rst.user_id";

		//グルーピング
		$group_by = '';
		$order_by = '';
		$kind = $searchkeys['cnt_kind'];
		$sql = '';
		if($kind == 'user'){
			$sql = "select user.id as y_axis_id, user.display_name as y_axis_name, count(rst.isfl_id) as cnt $from
				$where group by user.id, user.display_name order by cnt desc,user.display_name";
		}else if($kind == 'time'){
			//時間単位の抽出は難しいの別関数で実装
			$select = "select rst.date_term as x_axis_name, count(rst.date_term) as cnt ";
			return $this->_count__by_time($select, $from, $where, $where_ary, $searchkeys);
		}else if($kind == 'isfl_id'){
			$sql = "select rst.isfl_id as y_axis_id, grp.group_title as y_axis_name, count(rst.isfl_id) as cnt $from
				$where group by rst.isfl_id, grp.group_title order by cnt desc,rst.isfl_id";
		}else if($kind == 'results_status'){
			$sql = "select '*' as y_axis_id, rst.status as y_axis_name, count(rst.status) as cnt $from
				$where group by rst.status order by cnt desc,rst.status";
		}else{
			throw new Error('$searchkeys[cnt_kind] is invalid.');
		}

		//全件数の取得
		$sql_cnt = "select count(*) as cnt, max(cnt) as max_cnt from ($sql) tmp";
		if(count($where_ary) > 0){
			$sql_cnt = $wpdb->prepare($sql_cnt , $where_ary);
		}
		$tmp = $wpdb->get_results($sql_cnt, ARRAY_A)[0];
		$amount = isset($tmp) ? $tmp['cnt'] : 0;
		$max_cnt = isset($tmp) ? $tmp['max_cnt'] : 0;
		
		//SQL実行
		$sql .= " limit $offset,$limit";
		if(count($where_ary) > 0){
			$sql = $wpdb->prepare($sql , $where_ary);
		}
		$rows = $wpdb->get_results($sql, ARRAY_A);
		$this->last_error = $wpdb->last_error;
		//
		$ret = $this->_create_find_result($rows, $amount, $searchkeys);
		$ret['cnt_kind'] = $kind;
		$ret['chart_kind'] = 'bar-h';
		$ret['max_cnt']  = $max_cnt;
		return $ret;
	}

	/** 横軸を日時でグラフを作る場合の共有サブ関数(汎用的)。
	 * 時間Chartのとき専用のサブ関数。上の関数から呼び出し、結果を返す
	 * @param string $select SQLのカラム選択部分（必要なカラムはx_axis_name(date_term日時を切り捨てた形式のcreated_time),cnt）（limit, offsetを入れないこと）
	 *                       (例)"select rst.date_term as x_axis_name, count(rst.date_term) as cnt "
	 * @param string $from SQLのFrom文字列（必要なカラムはdate_term）
	 *                       (例)" from (select date_format(created_date, '%%Y-%%m-%%d') as date_term, created_date, isfl_id from $table_result) rst left outer join $table_group grp on grp.isfl_id=rst.isfl_id "
	 * @param string $where SQLのWhere文字列（最低1つはプレースホルダを使っていること。最悪"and 1=%d"のようなダミーを入れる）
	 * @param array $where_ary SQLのWhere部分のプレースホルダの値。$whereと一致させる。
	 * @param array $searchkeys 検索条件
	 * @return array 検索結果形式{chart_kind{グラフの種類:bar-h,line-time}, cnt_kind, max_cnt:最大値, ...あとは検索結果形式}
	 */
	protected function _count__by_time($select, string $from, string $where, array $where_ary, array $searchkeys): array{
		global $wpdb;
		$limit = $searchkeys['limit'];
		$offset = $searchkeys['offset'];
		$cnt_kind = $searchkeys['cnt_kind'];
		//全件数の取得
		$sql_cnt = "select max(x_axis_name) as max_created_date, 
			min(x_axis_name) as min_created_date, 
			max(cnt) as max_cnt 
			from ($select $from $where group by x_axis_name) tmp";
		if(count($where_ary) > 0){
			$sql_cnt = $wpdb->prepare($sql_cnt , $where_ary);
		}
		$tmp = $wpdb->get_results($sql_cnt, ARRAY_A)[0];
		//レコードがない場合は0件で返す
		if(!isset($tmp)){
			$ret = $this->_create_find_result(array(), 0, $searchkeys);	
			$ret['cnt_kind'] = $cnt_kind;
			$ret['chart_kind'] = 'line-time';
			$ret['max_cnt']  = 0;
			return $ret;
		}
		//max_cnt取得
		$max_cnt = $tmp['max_cnt'];
		//日時の差を計算
		$time_from = null;
		$time_to = null;
		//amount件数計算のための事前準備（$amount=$unitの間隔で等分した数）
		$unit = $searchkeys['cnt_created_date_unit'];
		$amount = null;
		$amount_func = null; //全てのunit数を計算する関数
		$date_format = null;
		$date_unit = null;
		if($unit == 'year'){ 
			$date_pattern = '%y';
			$date_format = 'Y';
			$date_unit = 'years';
			$amount_func = function($interval){ return $interval->y + 1; };
		}else if($unit == 'month'){
			$date_pattern = '%m';
			$date_format = 'Y-m';
			$date_unit = 'months';
			$amount_func = function($interval){ return $interval->y*12 + $interval->m + 1; };
		}else if($unit == 'day'){
			$date_pattern = '%d';
			$date_format = 'Y-m-d';
			$date_unit = 'days';
			$amount_func = function($interval){ return $interval->days + 1; };
		}else if($unit == 'hour'){
			$date_pattern = '%h';
			$date_format = 'Y-m-d H';
			$date_unit = 'hours';
			$amount_func = function($interval){ return $interval->days*24 + $interval->h + 1; };
		}else{
			throw new Exception('$searchkeys[created_date_unit] is invalid.');
		}
		//最大と最小を作成
		$max_created_date = DateTime::createFromFormat($date_format, $tmp['max_created_date']);
		$min_created_date = DateTime::createFromFormat($date_format, $tmp['min_created_date']);
		//最大の日時と最小の日時の差分を計算
		$interval = $max_created_date->diff($min_created_date);
		//日時$unitの間隔で箱を作っておく
		$amount = $amount_func($interval);
		$time_ary = array();
		for($i=$offset; $i < $amount && $i < $offset + $limit; ++$i){
			$clone_max = clone $max_created_date;
			$time = $clone_max->modify("-$i $date_unit");
			$time_str = $time->format($date_format);
			$time_ary[$time_str] = array('x_axis_name'=>$time_str, 'cnt'=>0);
		}
		if(count($time_ary) == 0){
			$ret = $this->_create_find_result(array(), $amount, $searchkeys);	
			$ret['cnt_kind'] = $cnt_kind;
			$ret['chart_kind'] = 'line-time';
			$ret['max_cnt']  = $max_cnt;
			return $ret;
		}

		//SQL実行
		if(count($where_ary) != 0) $where .= ' and';
		$where .= ' (date_term >= %s and date_term <= %s) ';
		$where_ary[] = array_pop(array_keys($time_ary));
		$where_ary[] = array_shift(array_keys($time_ary));
		$sql = "$select $from
			$where group by x_axis_name order by x_axis_name";
		$sql = $wpdb->prepare($sql , $where_ary);
		$rows = $wpdb->get_results($sql, ARRAY_A);
		$this->last_error = $wpdb->last_error;
		//結果の入れ替え（値がない時間も0件でレコードを作成した状態にする）
		foreach($rows as $line){
			$date_term = $line['x_axis_name'];
			if(array_key_exists($date_term, $time_ary)) 
				$time_ary[$date_term] = $line;
		}

		//結果の作成
		$ret = $this->_create_find_result(array_values($time_ary), $amount, $searchkeys);
		$ret['cnt_kind'] = $cnt_kind;
		$ret['chart_kind'] = 'line-time';
		$ret['max_cnt']  = $max_cnt;
		return $ret;
	}


	/**
	 * Flow結果の検索。
	 * @param array $searchkeys 検索キー。{offset, limit}
	 * @return array 検索結果形式{id, title, img_src}
	 */
	public function find_image(array $searchkeys){
		global $wpdb;
		$searchkeys = $this->_default_searchkeys($searchkeys);
		$offset = $searchkeys['offset'];
		$limit = $searchkeys['limit'];
		//
		$table_posts = $wpdb->prefix . 'posts';
		$where = '';
		$where_ary = array();
		if(isset($searchkeys['image_title'])){
			$where .= 'and pst.post_title like %s ';
			$where_ary[] = 'ISFL::%' . $this->_escape_like($searchkeys['image_title']) . '%';
		}
		if(isset($searchkeys['guid'])){
			$where .= 'and pst.guid like %s ';
			$where_ary[] = '%' . $this->_escape_like($searchkeys['guid']) . '%';
		}
		$where .= "and pst.post_title like 'ISFL::%' and post_type ='attachment' ";
		if(strlen($where) != 0){
			$where = 'where ' . substr($where, 3);
		}

		//全件数の取得
		$sql = "select count(pst.id) as cnt from $table_posts pst $where";
		$sql = $wpdb->prepare($sql , $where_ary);
		$amount = $wpdb->get_results($sql, ARRAY_A)[0];
		$amount = isset($amount) ? $amount['cnt'] : 0;

		//レコード取得
		$sql = "select pst.id as attachment_id, pst.post_title as image_title, pst.guid as img_url from $table_posts pst
				 $where $order_by limit $offset,$limit";
		$sql = $wpdb->prepare($sql , $where_ary);
		$rows = $wpdb->get_results($sql, ARRAY_A);
		$this->last_error = $wpdb->last_error;
		//
		return $this->_create_find_result($rows, $amount, $searchkeys);
	}
}


?>