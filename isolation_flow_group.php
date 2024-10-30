<?php 
/**
切り分けフローグループを表すクラス。
グループはひとまとまりのFlow
 */
class ISFL_IsolationFlowGroup{
	const FLOW_DATA_DEFAULT = array(
		'flow_id' => -1,
		'pt_id' => "",
		'revision' => -1,
		'status' => "open",
		'title' => "",
		'group_remarks' => "",
		'question' => "",
		'input' => [ /*{no: "1", type: "text", label: "GWシリアルNo"}*/],
		'choices' => [/*{id: "1", "label": "接続中", "image":"ymi.png", "next_flow_id": 3}*/],
	);
	const FLOW_GROUP_DEFAULT = array(
		'isfl_id' => -1,
		'revision'=> -1,
		'group_title' => '',
		'start_flow_id' => -1,
		'keywords' => [],
		'flows' => array(),
	);
	//
	public $is_debug = false;
	private $json = NULL;

	/**
	 * コンストラクタ
	 * @param array $json FLOWグループJSON 
	 */
	public function __construct(array $json){
		$this->json = $json;
		$this->modify_to_int();
		$this->modify_image();
	}
	
	public function __destruct(){
	}

	/**
	 * getter
	 * @throws Exception 指定の名前以外を指定した場合
	 */
	public function __get($name){
		if($name === 'isfl_id' || $name === 'revision' || $name === 'start_flow_id' 
		|| $name === 'keywords' || $name === 'group_title' || $name === 'group_remarks'){
			return $this->json['user_flows'][$name];
		}
		if($name === 'json') return $this->json;
		//指定以外のプロパティ名はエラーにする
		throw new Exception('Invalid value');
	}
	/**
	 * setter
	 * @throws Exception 指定の名前以外を指定した場合
	 */
	public function __set($name, $value){
		if($name === 'isfl_id' || $name === 'revision' || $name === 'start_flow_id' 
		|| $name === 'group_title' || $name === 'group_remarks'
		){
			$this->json['user_flows'][$name] = $value;
			return;
		}
		//指定以外のプロパティ名はエラーにする
		throw new Exception('Invalid value');
	}

	/**
	 * jsonのうち数値型のカラムを数値型に変換する。
	 * @throw Excpetion 型エラー
	 */
	private function modify_to_int(){
		//user_flow直下のカラムを数値に変換
		$fields = array('isfl_id', 'revision', 'start_flow_id');
		foreach($fields as $field){
			$val = $this->json['user_flows'][$field];
			if(!is_numeric($val)) throw new Exception('json properties type error. must be int:' . $field);
			$this->json['user_flows'][$field] = (int)$val;
		}
		
		//flow直下のカラムを数値に変換
		$fields = array('flow_id', 'revision');
		foreach($this->json['user_flows']['flows'] as &$flow){
			foreach($fields as $field){
				$val = $flow[$field];
				if(!is_numeric($val)) throw new Exception('json properties type error. must be int:' . $field . '=' .$val);
				$flow[$field] = (int)$val;
			}
		}
		//参照渡しにした場合、これをやらないとおかしくなるらしい
		unset($flow);  
	}
	
	/**
	 * jsonのうちimageカラムが""空のとき、削除する。
	 */
	private function modify_image(){
		//flow直下のimageカラムを削除
		$fields = array('isfl_id', 'flow_id', 'revision');
		foreach($this->json['user_flows']['flows'] as &$flow){
			foreach($flow['choices'] as &$choice){
				if(strlen($choice['image']) == 0) unset($choice['image']);
			}
			//念のため
			unset($choice);  
		}
		//参照渡しにした場合、これをやらないとおかしくなるらしい
		unset($flow);  
	}

	/**
	 * キーワードの追加
	 * @param string $value 追加するキーワード値。
	 */
	public function add_keywords(string $value){
		$this->json['user_flows']['keywords'][] = $value;
	}

	/**
	 * グループの参照を取得する（json['user_flows']を返す）
	 * @return array json['user_flows']を返す
	 */
	public function &get_user_flows(): array{
		return $this->json['user_flows'];
	}

	/**
	 * 指定のFlowデータの参照を取得する。
	 * @param int|string $flow_id 検索対象のFlow ID
	 * @return array 見つかった場合、対象のFlowデータの参照を返す。見つからない場合NULL。
	 * @throws TypeError 引数の型がおかしい
	 */
	public function &get_flow_data($flow_id):?array {
		if(gettype($flow_id) === 'string'){
			$flow_id = (int)$flow_id;
		}else if(gettype($flow_id) !== 'integer'){
			throw new TypeError('$flow_id must be int');
		}
		$flow_id = "" . $flow_id;
		static $ret_null;
		$ret_null = NULL; //array();
		if(!isset($this->json['user_flows']['flows'][$flow_id])) return $ret_null;
		return $this->json['user_flows']['flows'][$flow_id];
	}

	/**
	 * 指定のFlowデータのinputのnoの、データを取得する。
	 * @param int|string $flow_id 検索対象のFlow ID
	 * @param string $no inputのnoを指定する。
	 * @return array inputのうちの1行の参照を返す。見つからない場合NULL。
	 */
	public function &get_input($flow_id, string $no):?array {
		$flow_data = &$this->get_flow_data($flow_id);
		static $ret;
		$ret_null = NULL; //array();
		if(is_null($flow_data)) return $ret;
		//noを探す
		foreach($flow_data['input'] as &$line){
			if($line['no'] === $no) return $line;
		}
		return $ret;
	}

	/**
	 * 指定のFlowデータのchoicesのidの、データを取得する。
	 * @param int|string $flow_id 検索対象のFlow ID
	 * @param string $id choicesのidを指定する。
	 * @return array choicesのうち指定idの1行の参照を返す。見つからない場合NULL。
	 */
	public function &get_choice($flow_id, string $id):?array {
		$flow_data = &$this->get_flow_data($flow_id);
		static $ret_null;
		$ret_null = NULL; //array();
		if(is_null($flow_data)) return $ret_null;
		//noを探す
		foreach($flow_data['choices'] as &$line){
			if($line['id'] === $id) return $line;
		}
		return $ret_null;
	}

	/**
	 * Flow IDの一覧を取得する。
	 * @return array すべてのflow_idを配列に詰めて返す。
	 */
	public function get_flow_ids():array {
		$ret = array();
		foreach($this->get_user_flows()['flows'] as $flow_id => $flow){
			$ret[] = $flow_id;
		}
		return $ret;
	}

	/**
	 * Jsonで設定した値が正しいかチェックする。
	 * input.noが一意かなど、書式チェック以外のチェックをおなう。
	 * 返り値のエラー配列の様式：{'flow[2].input[0].label': array('err code', array(key1, key2))}
	 * @param f
	 * @return array チェック結果。エラー書式（{field:[ [errCode, [key]] ]}）
	 */
	public function validate():array {
		//一意性のチェック
		$errors = $this->validate_unique();
		if(count($errors) != 0) return $errors;
		//キーチェック
		$errors = $this->validate_key();
		if(count($errors) != 0) return $errors;
		//存在チェック
		$errors = $this->validate_existince();
		if(count($errors) != 0) return $errors;
		//無限ループチェック
		$result = $this->check_flows_inifinite_loop();
		if($result > 0){
			$errors["flows['$result']"] = array(array('ERR.INFINITE_LOOP'));
		}else if($result == -1){
			$errors["flows"] = array(array('ERR.TOO_MANY_HIERARCHY'));
		}
		return $errors;
	}

	/**
	 * 一意になっているかのチェック。対象は、flow.flow_id, flow.input.no,flow.choices.id,flow.choices.next_flow_id
	 * @return array エラー書式。1つのプロパティで複数のエラーが発生した場合、後勝ち。
	 */
	protected function validate_unique(): array{
		$ERR_MSG = array('ERR.DEPULICATED_PARAM'); 
		//返り値のエラー情報
		$errors = array();
		
		//グループ情報
		$user_flows = $this->get_user_flows();

		$map_flow_id = array();
		foreach($user_flows['flows'] as $key => $flow){
			//flow.flow_idの一意性
			$flow_id = $flow['flow_id'];
			//一意ではない場合
			if(isset($map_flow_id[$flow_id])){
				$errors["flows['$flow_id'].flow_id"] = array($ERR_MSG, array('OBJ.FLOW.FLOW_ID'));
			}
			//一意性確認のため保存する
			$map_flow_id[$flow_id] = true;

			//flow.input.noの一意性
			$map = array();
			$i = 0;
			foreach($flow['input'] as $input){
				$no = $input['no'];
				//一意ではない場合
				if(isset($map[$no])){
					$errors["flows['$flow_id'].input[$i].no"] = array($ERR_MSG, array('OBJ.INPUT.NO'));
				}
				//一意性確認のため保存する
				$map[$no] = true;
				++$i;
			}
			
			//flow.choices.idの一意性
			$map = array();
			$i = 0;
			foreach($flow['choices'] as $choice){
				$id = $choice['id'];
				//一意ではない場合
				if(isset($map[$id])){
					$errors["flows['$flow_id'].choices[$i].id"] = array($ERR_MSG, array('OBJ.CHOICES.ID'));
				}
				//一意性確認のため保存する
				$map[$id] = true;
				++$i;
			}
			
			//flow.choices.next_flow_idの一意性
			$map = array();
			$i = 0;
			foreach($flow['choices'] as $choice){
				$next_flow_id = $choice['next_flow_id'];
				//一意ではない場合
				if(isset($map[$next_flow_id])){
					$errors["flows['$flow_id'].choices[$i].next_flow_id"] = array($ERR_MSG, array('OBJ.CHOICES.NEXT_FLOW_ID'));
				}
				//一意性確認のため保存する
				$map[$next_flow_id] = true;
				++$i;
			}
		}
		
		return $errors;
	}

	/**
	 * Flowsの連想配列キーと内部のFlow.flow_idが一致するかをチェックする。
	 * @return array エラー書式。1つのプロパティで複数のエラーが発生した場合、後勝ち。
	 */
	protected function validate_key(): array{
		$ERR_MSG = array('ERR.NOT_MATCH_PARAM', 
			array('OBJ.FLOW_ID_KEY', 'OBJ.FLOW.FLOW_ID')
		); 
		//返り値のエラー情報
		$errors = array();

		//グループ情報
		$user_flows = $this->get_user_flows();

		//連想配列のキーと内部のflow_idが一致するかチェック
		foreach($user_flows['flows'] as $flow_id => $flow){
			if((int)$flow_id != $flow['flow_id']){
				$errors["flows['$flow_id']"] = array($ERR_MSG);
			}
		}

		return $errors;
	}

	/**
	 * 値が存在するかをチェックする。start_flow_id, next_flow_id
	 * @return array エラー書式。1つのプロパティで複数のエラーが発生した場合、後勝ち。
	 */
	protected function validate_existince(): array{
		$ERR_MSG = array('ERR.NOT_FOUND'); 
		//返り値のエラー情報
		$errors = array();
		
		//グループ情報
		$user_flows = $this->get_user_flows();

		//start_flow_idが存在するかのチェック
		$start_flow_id = $user_flows['start_flow_id'];
//error_log('vali===>'.print_r($user_flows, true));
		$flow = $this->get_flow_data($start_flow_id);
		if(is_null($flow)){
			$errors["start_flow_id"] = array($ERR_MSG);
		}

		//next_flow_idが存在するかのチェック
		$map = array();
		foreach($user_flows['flows'] as $flow_id => $flow){
			//flow.flow_id
			$flow_id = $flow['flow_id'];

			//チェック
			$i = 0;
			foreach($flow['choices'] as $choice){
				$flow = $this->get_flow_data($choice['next_flow_id']);
				if(is_null($flow)){
					$errors["flows['$flow_id'].choices[$i].next_flow_id"] = array($ERR_MSG);
				}
				++$i;
			}
		}
		return $errors;
	}

	/**
	 * Flowsの無限ループチェック。
	 * @return int 0:問題なし。正の整数:問題のFlowID。-1:階層が多すぎる。-2:next_flow_idが見つからない。
	 */
	public function check_flows_inifinite_loop() : int {
		$flow_id = $this->json['user_flows']['start_flow_id'];
		$flow_data = $this->get_flow_data($flow_id);
		try{
			if(is_null($flow_data)) throw new Exception('next_flow_id.not_found');
			//無限ループチェック（エラー時は例外発生）
			$this->check_infinite_loop($flow_data);
			return 0;
		}catch ( Exception $ex ) {
			if($ex->getMessage() === 'too_many_hierarchy'){
				return -1;
			}else if($ex->getMessage() === 'next_flow_id.not_found'){
				return -2;
			}else{
				return $ex->getMessage();
			}
		}
	}

	/**
	 * １つのフローについて無限ループになっていないかをチェックする。
	 * 対象のFlow IDと同じIDがループをたどったときにぶつかるかをチェックする。
	 * 50階層以上もぐった場合はエラーにする。
	 * @param array $target_flow_data 対象Flowデータ。
	 * @param array $hierarchy 今まで通ってきたFlowIDを保存。同じIDがあれば無限ループ。
	 * @return bool trueのとき問題なし。falseのとき無限ループの可能性
	 * @throws Exception 'next_flow_id.not_found'-次のFlowIDが見つからない場合、'too_many_hierarchy'-階層が多すぎる、それ以外は問題のFlowIDが設定される。
	 */
	protected function check_infinite_loop(array $target_flow_data, 
	array $hierarchy=array()) : bool{
		if(count($hierarchy) > 40) throw new Exception('too_many_hierarchy');
		$flow_id = $target_flow_data['flow_id'];
		//自身のFlowIDと対象IDが一致しないかをチェック
		if(isset($hierarchy[$flow_id])) throw new Exception($flow_id);
		$hierarchy[$flow_id] = true;
		//次のFlowをチェックしていく
		foreach($target_flow_data['choices'] as $choice){
			$next_flow_id = $choice['next_flow_id'];
			$flow_data = $this->get_flow_data($next_flow_id);
			//次のFlowデータが見つからない場合、例外
			if(is_null($flow_data)) throw new Exception('next_flow_id.not_found');
			//次のFlowデータチェック
			if(!$this->check_infinite_loop($flow_data, $hierarchy)){
				throw new Exception($flow_id);
			}
		}
		return true;
	}

	/**
	 * 自身と引数を比較して差分を返す(revisionの値はチェックしない)
	 * @param ISFL_IsolationFlowGroup $p_group 比較対象のFlowグループ
	 * @return array 差分情報[entire:{start_flow_id:int,group_title:string,keywords:array}, flows:[flow_id:int]]]が返る。
	 *     flowsは配列で値が一致しないflow_idが設定される。差分なしの場合は要素0の配列。
	 */
	public function check_diff($p_group): array{
		if(!($p_group instanceof ISFL_IsolationFlowGroup)){
			throw new TypeError('$p_group must be ISFL_IsolationFlowGroup.');
		}
		$ret = array('flows'=>array(), 'entire'=>array());
		$user_flows = &$this->json['user_flows'];
		//グループタイトルをチェック
		if($this->group_title !== $p_group->group_title){
			$ret['entire']['group_title'] = $this->group_title . '';
		}
		//グループ備考をチェック
		if($this->group_remarks !== $p_group->group_remarks){
			$ret['entire']['group_remarks'] = $this->group_remarks . '';
		}
		//開始Flow IDをチェック
		if($this->start_flow_id !== $p_group->start_flow_id){
			$ret['entire']['start_flow_id'] = $this->start_flow_id;
		}
		//キーワードをチェック
		if(count($this->keywords) !== count($p_group->keywords) 
		|| count(array_diff($this->keywords,$p_group->keywords)) > 0){
			$ret['entire']['keywords'] = $this->keywords;
		}

		//Flowを一つずつチェック
		$debug_msg = '';//デバッグのための変数
		foreach($user_flows['flows'] as $flow_id => $flow){
			$debug_msg = "flows['$flow_id'].";
			$target_flow = &$p_group->get_flow_data($flow_id);
			//Flowが見つからない場合エラー
			if(is_null($target_flow)){
				$ret['flows'][] = $flow_id;
				if($this->is_debug) error_log('check_diff[ErrField]:'.$debug_msg);
				continue;
			}

			//チェック
			$is_err = false;
			$keys = array('flow_id', 'status','title','question');
			foreach($keys as $key){
				if($flow[$key] !== $target_flow[$key]){
					$is_err = true;
					$debug_msg .= 'field:' . $key;
					break;
				}
			}

			//inputフィールドの確認
			$keys = array('no', 'type', 'label');
			foreach($flow['input'] as $line){
				$target_line = &$p_group->get_input($flow_id, $line['no']);
				//数が一致するか
				if(is_null($target_line) || count($line) !== count($target_line)){
					$is_err = true;
					$debug_msg .= 'input[' . $line['no'] .'].cnt:'. count($line);
					break;
				}
				//各項目の値が一致するか
				foreach($keys as $key){
					if($line[$key] !== $target_line[$key]){
						$is_err = true;
						$debug_msg .= 'input[' . $line['no'] .']:' . $key;
						break;
					}
				}
			}
			//inputの数が一致するかチェック
			if(count($flow['input']) !== count($target_flow['input'])){
				$is_err = true;
				$debug_msg .= 'input.count:' . count($flow['input']);
			}
			
			//choicesフィールドの確認
			$keys = array('id', 'label', 'image', 'next_flow_id');
			foreach($flow['choices'] as $line){
				$target_line = &$p_group->get_choice($flow_id, $line['id']);
				//数が一致するか
				if(is_null($target_line) || count($line) !== count($target_line)){
					$is_err = true;
					$debug_msg .= 'choices[' . $line['id'] .'].cnt:'. count($line);
					break;
				}
				//各項目の値が一致するか
				foreach($keys as $key){
					if($line[$key] !== $target_line[$key]){
						$is_err = true;
						$debug_msg .= 'choices[' . $line['id'] .']:' . $key
						. '::'.$line[$key] .'!=='. $target_line[$key].';';
					}
				}
			}
			
			//choicesの数が一致するかチェック
			if(count($flow['choices']) !== count($target_flow['choices'])){
				$is_err = true;
				$debug_msg .= 'choices.count:' . count($flow['choices']);
			}
			//エラーチェック
			if($is_err){
				$ret['flows'][] = $flow_id;
				if($this->is_debug) error_log('check_diff[ErrField]:'.$debug_msg);
			}
		}

		return $ret;
	}

	public function check_revision(){

	}

	/**
	 * 切り分け結果データからメッセージ文字列を作成する。
	 * @param array $result - 切り分け結果
	 * @param bool [$is_simple] - メッセージを省略形にするか？
	 * @return string 作成したメッセージ
	 */
	public function make_result_msg(array $result, bool $is_simple=true) : string {
		$msg = 'isfl_id:'.$result['isfl_id']."\n"
			. 'status:'.$result['status']."\n"
			. 'title:'.$this->group_title."\n\n";
		foreach($result['result'] as $line){
			$flow_id = $line['flow_id'];
			$flow_data = $this->get_flow_data($flow_id);
			$choice = $this->get_choice($flow_id, $line['choice_id']);
			$msg .= "=====================\n";
			if($is_simple){
				$msg .= $flow_data['title'];
			}else{
				$msg .= $flow_data['question'];
			}
			$msg .= "\n=>";
			$msg .= $choice['label'] . "\n\n";
		}
		//備考欄
		$msg .= "[-------------------------]\n". $result['remarks'];

		return $msg;
	}
}

?>