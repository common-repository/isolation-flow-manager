

/**
 * HTMLの描画やボタンの操作などをコントロールするクラス。
 * HTMLデザインを3つの層に分け、制御する。
 * Entire層(切り分けIDや切り分けタイトルなどがある場所（保存するボタンなどは範疇外）)、
 * Canvas層（Canvas描画）、
 * プロパティウィンドウ層。
 * <pre>
 * 【必要なjs】
 *  ・isolation_flow_editor_canvas.js
 *  ・dialog_image_select.js
 * </pre>
 */
ISFL.IsolationFlowEditor = class extends ISFL.IsolationFlowCommon{
	/**
	 * @param {String} id - HTML上のルートとなるタグを指定。CSSセレクタで指定。	(例："#test")
	 * @param {String} xWpNonce - Nonceを指定
	 * @param {String} canvasId - フローを描画するCanvasタグをid属性で指定(例："test")
	 * @param {Object<String, String>} templateIds - HTMLテンプレート。handlebarsのscriptタグの設定
	 */
	constructor(id, xWpNonce, canvasId, templateIds){
		super(id, xWpNonce);
		let self = this;
		//this.displayId = id;
		
		/**
		 * CanvasオブジェクトのID
		 * @type {String}
		 */
		this.canvasId = canvasId;
		
		/**
		 * Modal表示時にCavasをクリックしたときに呼ばれるコールバック関数
		 * @type {ISFL.IsolationFlowEditor~funcModalCanvasClick}
		 */
		this.funcModalCanvasClick = null;
		
		/**
		 * Modal表示時にCavasをクリックしたときに呼ばれるコールバック関数
		 * @callback ISFL.IsolationFlowEditor~funcModalCanvasClick
		 * @param {Number} flowId - Flow ID。
		 * @return {bool} クリックしたFlow（四角）を選択状態の描画をする場合はtrue。falseのときは描画は何もしない。
		 */
		 
		/**
		 * プロパティウィンドウのデータのdata-idのプレフィックス
		 * @type {String}
		 */
		this.propValueDataIdPrefix = "ISFL_editor_prop.val.";
		
		/**
		 * データのステータス。トランザクションの管理に使用。
		 * trueのとき、トランザクション中。データを保管し、rollback()すると変更を戻せる。
		 * @type {bool}
		 */
		this.dataIsTransaction = false;
		
		/**
		 * データのバックアップ。トランザクションの管理に使用。
		 * トランザクション開始時にデータを保管。トランザクションを開始していないときはnull。
		 * @type {Object}
		 */
		this.dataBackup = null;
		
		/**
		 * 最初のrevisionを保存しておく
		 * @type {Number}
		 */
		this.dataRevision = null;
		
		//テンプレート設定
		this.setTemplate('operator_prop', '#ISFL_editor_prop', templateIds);
		this.setTemplate('dialog_select_next_flow', '#ISFL_editor_dialog_select_next_flow', templateIds);
		this.setTemplate('dialog_processing', '#ISFL_editor_dialog_processing', templateIds);
		//this.setTemplate('dialog_select_img', '#ISFL_dialog_select_img', templateIds);
		//this.setTemplate('dialog_img_list', '#ISFL_dialog_img_list', templateIds);
		this.setTemplate('prop_user_list_item', '#ISFL_editor_prop_user_list_item', templateIds);
		
		/**
		 * フローグループ
		 * @typedef {Object} ISFL.IsolationFlowEditor~flow_groups
		 * @property {ISFL.IsolationFlowEditor~entireData} user_flows - ユーザフロー
		 */
		/**
		 * フローグループのメイン部分(Entireデータ)
		 * @typedef {Object} ISFL.IsolationFlowEditor~entireData
		 * @proprety {Number} isfl_id - 切り分けフローID
		 * @property {Number} revision - リビジョン
		 * @property {Number} start_flow_id - 開始Flow ID
		 * @property {String} group_title - グループタイトル
		 * @property {Array.<String>} keywords - キーワード
		 * @property {String} remarks - 切り分けフローの備考。
		 * @property {Object.<String, ISFL.IsolationFlowEditor~flowData>} flows - キーはFlowID、値はフロー
		 */
		/**
		 * 切り分けフローの１つ
		 * @typedef {Object} ISFL.IsolationFlowEditor~flowData
		 * @property {Number} flow_id - Flow ID
		 * @property {Number} revision - リビジョン
		 * @property {String} status - ステータス
		 * @property {String} pt_id - パターンID（任意。一意）
		 * @property {String} title - フローのタイトル
		 * @property {String} question - 質問
		 * @property {Array.<Object>} input - 入力項目
		 * @property {String} input[].no - 項番
		 * @property {String} input[].type - 入力項目のタイプ。text
		 * @property {String} input[].label - 表示名。
		 * @property {Array.<Object>} choices - 結果の選択
		 * @property {String} choices[].id - 項番
		 * @property {String} choices[].label - 表示名
		 * @property {String} choices[].next_flow_id - 次の遷移先フローID。
		 * @property {String} [choices[].image] - 画像のURL。
		 * @property {String} [choices[].attachment_id] - 画像ID。
		 */
		/**
		 * Flowのデータ
		 * @typedef {Object.<String, Object>} ISFL.IsolationFlowEditor~flows
		 * @property {Object.<String, Object>} user_flows - 配下にデータを保持。キーはFlowId。
		 * @property {Number} user_flows.isfl_id - FlowID
		 * @property {Number} user_flows.revision - リビジョン
		 * @property {Number} user_flows.start_flow_id - 開始FlowID
		 * @property {Object.<String, IsolationFlowEditor~flowData>} user_flows.flows - キーはflow_id、値は切り分けフロー。
		 */
		/**
		 * Flowの実データ
		 * @type {ISFL.IsolationFlowEditor~flow_groups} 
		 */
		this.data = this._createDummyFlowGroups();

		//データの初期化
		this.remake(this.data);
		
		//フロー描画Canvasの生成
		this.objEditorCanvas = new ISFL.IsolationFlowEditorCanvas(canvasId, this.data, 
			function(id){ return self.onClickFlowOnCanvas(id); }
		);
		//タイトルを設定
		this.objEditorCanvas.setTitleNameUnreffrencedFlow(this.getMsg('OBJ.CANVAS.TITLE.UNREFFERENCED_FLOWS'));
		
		//データが設定されていればCanvas更新。
		if(this.data){
			this.redraw(this.data);
			this.updateCanvas();
		}
	}
	
	/** public:
	 * データをインポートする。
	 * 成功するとalert()を出すので呼び出し先で出さないように注意。
	 * @param {ISFL.IsolationFlowEditor~flows} data - フローデータ。
	 */
	import(data){
		try{
			this.remake(data);
			//データチェックする。
			let errMsg = this.checkDataAll();
			if(errMsg != null){
				this.remake(this._createDummyFlowGroups());
				alert(errMsg);
				return;
			}
			this.redraw(data);
			this.updateCanvas();
			//
			alert(this.getMsg('OK.SUCCESS' ));
		}catch(ex){
			alert(ex.message);
		}
	}

	/** protected:
	 * データを再構築する。データを設定し、初期化する。データチェックは行わない。
	 * メソッド内では this.redraw() と this.updateCanvas() は実行しないので必要に応じて使用者が実行。
	 * @param {ISFL.IsolationFlowEditor~flows} data - フローデータ。
	 */
	remake(data){
		this.data = data;
		this.dataRevision = this.getRevision();
		this.dataIsTransaction = false;
		this.dataBackup = null;
		//Entire層の表示
		this.displayEntire();
		//プロパティウィンドウをクリアする。
		this.clearPorperty();
	}
	
	/** protected:
	 * Entire層の表示。Canvasやプロパティウィンドウは描画しない。
	 */
	displayEntire(){
		let isflId = this.data.user_flows.isfl_id;
		if(isflId == 0) isflId = "New";
		let keywords = this.data.user_flows.keywords;
		if(keywords instanceof Array) keywords = keywords.join(',');
		this.findElementByDataId('ISFL_editor_entire.val.isfl_id')
			.val(isflId);
		this.findElementByDataId('ISFL_editor_entire.val.group_title')
			.val(this.data.user_flows.group_title);
		this.findElementByDataId('ISFL_editor_entire.val.keywords')
			.val(keywords);
		this.findElementByDataId('ISFL_editor_entire.val.group_remarks')
			.val(this.data.user_flows.group_remarks);
	}

	/**public:
	 * {@link ISFL.IsolationFlowEditorCanvas}#updateを呼び出す。
	 * Canvasを描画して、Canvasに溜め込んだ変更を反映する。
	 * this.dataをCanvasに溜め込むには事前に this.redraw() を実行する必要がある。
	 */
	updateCanvas(){
		this.objEditorCanvas.update();
	}

	/**protected:
	 * フローグループの初期データ（ダミー）を作る。
	 * @return {ISFL.IsolationFlowEditor~flow_groups} 
	 */
	_createDummyFlowGroups(){
		return {//初期値。ダミーの値。
			"user_flows": { 
				"isfl_id": 0,
				"revision": 0,
				"start_flow_id": 1,
				"group_title": "Input your title",
				"keywords": [],
				"group_remarks": "",
				"flows": {"1":{ 
					"flow_id": 1,
					"pt_id": "pt-1",
					"revision": 1,
					"status": "open",
					"title": "dummy",
					"question": "",
					"input":[],
					"choices" : []
				}}
			}
		};
	}

	/**protected:
	 * トランザクションが開始されているかどうか？
	 * @return {bool} trueのとき開始されている。
	 */
	_isTransaction(){
		return this.dataIsTransaction;
	}
	
	/**protected:
	 * Flowデータのトランザクションを開始する。
	 * あくまでブラウザ上のJS内のもので、サーバ側のDBデータなどに対しては何もしない。
	 */
	_beginTransaction(){
		if(this._isTransaction()) throw new Error("already begin transaction!");
		this.dataIsTransaction = true;
		
		//データをコピーして設定
		this.dataBackup = this.data;
		this.data = JSON.parse(JSON.stringify(this.data));
	}
	
	/**protected:
	 * Flowデータをコミットする。
	 * データだけなのでCanvasは更新しない。
	 */
	_commitTransaction(){
		if(!this._isTransaction()) throw new Error("Not began transaction!");
		this.dataIsTransaction = false;
		this.dataBackup = null;
	}
	
	/**protected:
	 * Flowデータをトランザクション開始前に戻す。
	 * データだけなのでCanvasは更新しない。
	 */
	_rollbackTransaction(){
		if(!this._isTransaction()) throw new Error("Not began transaction!");
		this.dataIsTransaction = false;
		this.data = this.dataBackup;
		this.dataBackup = null;
	}
	
	
	/**protected:
	 * ダイアログクラスを作成する
	 * @param {Class} DialogClass - 生成するダイアログクラス。nullの場合、Dialogが指定される。
	 * @param {String} templateSelector - HTMLテンプレートを指定するセレクタ
	 * @param {Object.<String, Object>} prop - プロパティ。{@link ISFL.Dialog}参照。
	 */
	_createDialog(DialogClass, templateSelector, prop){
		if(DialogClass == null) DialogClass = ISFL.Dialog;
		//if(!(DialogClass instanceof ISFL.Dialog)) throw new TypeError("DialogClass must be ISLF.Dialog class.");
		let self = this;
		let obj = new DialogClass(this.rootHtmlId, this.xWpNonce, 'ISFL_editor_modal', templateSelector, prop);
		obj.setClosingCallback(function(clDialog){
			self.setModalCanvasClick(null);
			self.moveCanvasOntoOverlay(false);
		});
		return obj;
	}
	
	
	/**protected:
	 * IDからFlowを検索して取得する。見つからない場合はnull。
	 * @param {Number} id - Flow IDを指定
	 * @return {ISFL.IsolationFlowEditor~flowData} Flowデータ。見つからない場合null。
	 */
	getFlowDataById(id){
		if(isNaN(id)) throw new TypeError('id must be number.');
		let flow = this.data.user_flows.flows[id];
		if(typeof flow === "undefined") return null;
		return flow;
	}
	
	/**protected:
	 * フローデータをthis.dataに設定する。キー名はflowData.flow_idを設定。
	 * @param {ISFL.IsolationFlowEditor~flowData} flowData - 設定するインスタンス
	 */
	setFlowData(flowData){
		if(!(flowData instanceof Object)) throw new TypeError("flowData must be Object.");
		if(this.getFlowDataById(flowData.flow_id) == null) throw new TypeError("flowData.flow_id dose not exists in this.data.");
		this.data.user_flows.flows[flowData.flow_id] = flowData;
	}
	
	/**protected:
	 * パターンIDからFlowを検索して取得する。見つからない場合はnull。
	 * @param {String} ptId - パターンID。空文字の場合もnullを返す。
	 * @return {ISFL.IsolationFlowEditor~flowData|null} - 一致するフローデータ。見つからない場合null。 
	 */
	getFlowDataByPtId(ptId){
		if(ptId == '') return null;
		let flows = this.data.user_flows.flows;
		for(let flowId in flows){
			let flow = flows[flowId];
			if(flow["pt_id"] == ptId) return flow;
		}
		
		return null;
	}
	
	/**protected:
	 * this.data内の最大のflow_idを取得する。
	 * @return {Number} 最大のFlow ID
	 */
	getMaxFlowId(){
		let flows = this.data.user_flows.flows;
		let maxId = 0;
		for(let flowId in flows){
			if(Number.parseInt(flowId) > maxId) maxId = Number.parseInt(flowId);
		}
		return maxId;
	}
	
	/**public:
	 * 現在のthis.dataのRevisionを取得する。
	 * @return {Number} this.dataのrevisionを返す。
	 */
	getRevision(){
		return this.data.user_flows.revision;
	}

	/**public:
	 * 現在のthis.dataのuser_flowsを取得する。
	 * @return {ISFL.IsolationFlowEditor~entireData} Entireデータ
	 */
	getUserFlows(){
		return this.data.user_flows;
	}
	
	/**public:
	 * Flowのjsonをサーバに保存する。自身のthis.dataにサーバからの情報を設定しなおす。
	 * @return {Promise|null} - 他が処理中の場合はnull。Promiseが返るので後処理を繋げること。this.dataが最新に更新される
	 */
	requestSaveFlowGroups(){
		let self = this;
		if(!this.data){
			alert("unexpected error. this.data has no values.");
			return null;
		}
		//プロパティの編集中ではないか？(コミットしてない値は保存されないので警告)
		if(this.checkPropValueUpdated()){
			alert(this.getMsg('HTML.WARN.EDIT.PROPERY_NOT_COMMIT'));
			return null;
		}
		//保存して良いか確認
		if(!confirm(this.getMsg('HTML.WARN.EDIT.CONFIRM_SAVE_FLOW_GROUP'))) return null;
		if(this.isSetProcessingFlag()) return null;
		//Entire層をthis.dataに更新
		if(!this.updateEntire()){
			alert(this.getMsg('HTML.ERR.HAS_OCCURED'));
			return null;
		}

		//1000ミリ秒間は処理中にして他の処理を受け付けない
		this.setProcessingFlag(1000);
		
		//URIパラメタを作成
		let uriParam = "";
		if(this.data.user_flows.isfl_id != 0) uriParam = "/" + this.data.user_flows.isfl_id;

		//フローをサーバに保存する
		return jQuery.ajax({
			url       : self.API_INFO.uriPrefix + "/flow_groups" + uriParam,
			method    : 'POST', 
			data      : JSON.stringify(this.data),
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': self.xWpNonce
			}
		}).then(function (json) {console.log(json);
			self.import(json.group);
			return json;
		}).fail( function(data){
			self.transactJsonErrByAlert(data);
		});
	}
	
	/**public:
	 * Flowのjsonをサーバから取得する。自身のthis.dataにサーバからの情報を設定しなおす。
	 * @param {Number} isflId - 
	 * @param {Number} revision - 
	 * @return {Promise|null} - 他が処理中の場合はnull。Promiseが返るので後処理を繋げること。this.dataが最新に更新される
	 */
	requestGetFlowGroups(isflId, revision){
		if(typeof isflId !== 'number') throw new TypeError('isflId must be number.');
		if(typeof revision !== 'number') throw new TypeError('revision must be number.');
		let self = this;
		//プロパティの編集中ではないか？(コミットしてない値は保存されないので警告)
		if(this.checkPropValueUpdated()){
			//変更を無視して良いか確認
			if(!confirm(this.getMsg('HTML.WARN.EDIT.CONFIRM_CHANGE_FLOW_GROUP'))) return null;
		}
		if(this.isSetProcessingFlag()) return null;
		
		//1000ミリ秒間は処理中にして他の処理を受け付けない
		this.setProcessingFlag(1000);

		//フローをサーバに保存する
		return jQuery.ajax({
			url       : self.API_INFO.uriPrefix + "/flow_groups/"+isflId+"/"+revision,
			method    : 'GET', 
			data      : {"isfl_id": isflId},
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': self.xWpNonce
			}
		}).then(function (json) {console.log(json);
			self.import(json);
			return json;
		}).fail( function(data){
			self.transactJsonErrByAlert(data);
		});
	}
	
	/**
	 * Entire層のinput値をクラス内のthis.dataに反映させる。
	 * @return {bool} 失敗時false
	 */
	updateEntire(){
		let entireData = {};
		entireData.group_title = this.findElementByDataId('ISFL_editor_entire.val.group_title').val();
		entireData.group_remarks = this.findElementByDataId('ISFL_editor_entire.val.group_remarks').val();
		entireData.keywords = this.findElementByDataId('ISFL_editor_entire.val.keywords').val();
		entireData.keywords = entireData.keywords.split(',');
		//妥当性チェック
		let errMsg = this._checkEntireData(entireData);
		if(errMsg != null){
			alert(errMsg);
			return false;
		}
		//データ設定
		this.data.user_flows.group_title = entireData.group_title;
		this.data.user_flows.group_remarks = entireData.group_remarks;
		this.data.user_flows.keywords = entireData.keywords;
		return true;
	}

	/** private:
	 * Entire層のデータをチェックする。
	 * @param {Object} entireData - Entireデータ
	 * @return {String} エラーなしの場合null。エラーの場合はメッセージ
	 */
	_checkEntireData(entireData){
		let errMsg = null;
		if(typeof entireData.group_title !== 'string' 
		|| entireData.group_title.length > 40 || entireData.group_title.length == 0){
			//"group_titleは文字列";
			errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false, 'OBJ.GROUP_TITLE', 'TYPE.STRING');
		}else if(!(entireData.keywords instanceof Array)){
			//キーワード
			errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false, 'OBJ.KEYWORDS', 'TYPE.ARRAY');
		}else if(entireData.keywords.length > 3){
			//キーワード
			errMsg = this.getMsg('HTML.ERR.ARRAY_TOO_BIG', false, 'OBJ.KEYWORDS');
		}
		//キーワード１つずつをチェック
		for(let key in entireData.keywords){
			if(key.length > 7) errMsg = this.getMsg('HTML.ERR.STRING_TOO_BIG', false, 'OBJ.KEYWORDS');
		}
		return errMsg;
	}

	/** private:
	 * JsonのFlowデータを妥当性チェックし、エラーの場合はエラーメッセージ
	 * を返す。
	 * @param {Object} flowData - １つのFlowデータ
	 * @return {String} エラーなしの場合null。エラーの場合はメッセージ
	 */
	_checkFlowData(flowData){
		let errMsg = null;
		if(typeof flowData === "undefined"){
			errMsg = "エラー";
		}else if(!(typeof flowData.flow_id === "number")){
			//"flow_idは数字です。"
			errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false, 'OBJ.FLOW_ID', 'TYPE.NUMBER');
		}else if(flowData.revision != null && !(typeof flowData.revision === "number")){
			//"revisionは数字です。";
			errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false, 'OBJ.FLOW.REVISION', 'TYPE.NUMBER');
		}else if(!(typeof flowData.pt_id === "string")){
			//"pt_idは空でない文字列です。";
			errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false, 'OBJ.FLOW.PT_ID', 'TYPE.STRING');
		}else if(flowData.status != "close" && flowData.status != "open"){
			//"statusは指定の文字列です。";
			errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false, 'OBJ.FLOW.STATUS', 'TYPE.STRING');
		}else if(!(typeof flowData.title === "string") || flowData.title.length == 0){
			errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false, 'OBJ.FLOW.TITLE', 'TYPE.STRING');
		}else if(!(typeof flowData.question === "string") || flowData.question.length == 0){
			//"questionは空でない文字列です。";
			errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false, 'OBJ.FLOW.QUESTION', 'TYPE.STRING');
		}else if(!(flowData.input instanceof Array)) {
			//"inputは配列です";
			errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false, 'OBJ.INPUT', 'TYPE.ARRAY');
		}else if(!(flowData.choices instanceof Array)){
			//"choicesは配列です";
			errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false, 'OBJ.CHOICES', 'TYPE.ARRAY');
		}else if(flowData.status == "close" && flowData.choices.length != 0){
			//"statusがcloseのときchoicesは設定しないでください。";
			errMsg = this.getMsg('HTML.ERR.RELATION.STATUS_AND_CHOICES', false);
		}
		if(errMsg != null) return errMsg;
		
		//choiceのチェック。同じFlow IDは存在してはならない。
		errMsg = this._checkFlowDataChoices(flowData.choices);
		if(errMsg != null) return errMsg;
		
		//choicesの無限ループチェック
		if(!this._checkDataFlowInfiniteLoop(flowData)){
			return this.getMsg('HTML.ERR.LOOPING_INFINITELY', false);
		}
		
		//inputデータを妥当性チェックする
		let inputNoHolder = {};
		for(let input of flowData.input){
			//値の妥当性チェック
			if(typeof input.no === "undefined" || isNaN(input.no)){
				errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false ,'OBJ.INPUT.NO', 'TYPE.NUMBER');
			}else if(input.type != "text" && input.type != "select"){
				errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false ,'OBJ.INPUT.TYPE', 'TYPE.DEFINED_STRING');
			}else if(typeof input.label === "undefined" || input.label.length == 0){
				errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false ,'OBJ.INPUT.LABEL', 'TYPE.STRING');
			}else if(typeof inputNoHolder[input.no] !== "undefined"){
				errMsg = this.getMsg('HTML.ERR.DUPLICATED', false ,'OBJ.INPUT.NO');
			}
			if(errMsg != null) break;
			
			//Noの重複チェックのために保存
			inputNoHolder[input.no] = true;
		}
		
		return errMsg;
	}
	
	/** private:
	 * FLowDataのchoicesの項目だけの妥当性チェック
	 * @param {Array.<Object>} aryChoices - 妥当性チェック対象のchoices
	 * @return {String} - エラーメッセージ
	 */
	_checkFlowDataChoices(aryChoices){
		//choiceのチェック。同じFlow IDは存在してはならない。
		let countMap = {};
		let errMsg = null;
		for(let choice of aryChoices){
			if(typeof countMap[choice.next_flow_id] === "undefined"){
				countMap[choice.next_flow_id] = 1;
			}else{
				++countMap[choice.next_flow_id];
			}
			if(countMap[choice.next_flow_id] > 1){
				errMsg = this.getMsg('HTML.ERR.UNIQUE.next_flow_id', false);
				break;
			}
			//値の妥当性チェック
			if(typeof choice.id === "undefined" || isNaN(choice.id)){
				errMsg = "choice.id must be number.";
			}else if(typeof choice.label === "undefined" || typeof choice.label !== "string"){
				errMsg = "choice.label must be string.";
			}else if(choice.label.length == 0){
				errMsg = this.getMsg('HTML.ERR.SET_PARAM', false, 'OBJ.CHOICES.LABEL');
			}else if(typeof choice.next_flow_id === "undefined" || isNaN(choice.next_flow_id)){
				errMsg = "choice.next_flow_id must be number.";
			}else if(this.getFlowDataById(choice.next_flow_id) == null){
				//"choice.next_flow_id dose not exist.";
				errMsg = this.getMsg('HTML.ERR.SET_PARAM', false, 'OBJ.CHOICES.NEXT_FLOW_ID');
			}
			if(errMsg != null) break;
		}
		return errMsg;
	}
	
	/**protected:
	 * Flowが無限ループしていないかをチェック
	 * @param {Object} flowData - 追加、もしくは更新されるFlowデータ
	 * @param {Number} [stock]  - 内部で使用のため指定しない。チェックのためにnext_flow_idを保存しておくストック。
	 * @return {bool} 無限ループする場合false。
	 */
	_checkDataFlowInfiniteLoop(flowData, stock){
		if(typeof stock === "undefined") stock = flowData.flow_id;
		for(let choice of flowData.choices){
			if(stock == choice.next_flow_id) return false;
			let next_flowData = this.getFlowDataById(choice.next_flow_id);
			if(next_flowData == null) continue;
			if(!this._checkDataFlowInfiniteLoop(next_flowData, stock)) return false;
		}
		return true;
	}

	/** public:
	 * 内部のデータthis.dataについて全てのチェックを行う。
	 * _checkEntireData(),_checkFlowData()
	 * @return {String|null} - エラーメッセージ。エラーなしの場合null。
	 */
	checkDataAll(){
		let entireData = this.getUserFlows();
		let errMsg = null;
		errMsg = this._checkEntireData(entireData);
		if(errMsg != null) return errMsg;
		for(let flowId in entireData.flows){
			let flowData =  entireData.flows[flowId];
			errMsg = this._checkFlowData(flowData);
			if(errMsg != null) return errMsg;
		}
		return null;
	}
	
	/**protected:
	 * 新規でFlowデータを作る。
	 * @param {Object} objValues - Flowデータに設定する値。必須項目はtitle、question。flow_idがない場合は新規にflow_idを発行する。
	 * @return {Object} - １つのFlowデータ。 flow_idがない場合は IDを発行する。
	 * @exception {TypeError} - 妥当性チェックエラーの場合
	 */
	createNewFlowJson(objValues){
		let flowId = objValues["flow_id"];
		if(typeof flowId === "undefined" || flowId == "") flowId = this.getMaxFlowId() + 1
		//値の生成
		let json = {
			"flow_id": flowId,
			"revision": this.dataRevision + 1,
			"pt_id": objValues["pt_id"],
			"status": "open",
			"title": objValues["title"],
			"question": objValues["question"],
			"input":[],
			"choices" : [],
		};
		
		//必須でない値の設定
		//if(typeof objValues["revision"] !== "undefined") json.revision = objValues["revision"];
		if(typeof objValues["status"] !== "undefined")   json.status = objValues["status"];
		if(typeof objValues["input"] !== "undefined"){
			let copiedArray = JSON.parse(JSON.stringify(objValues["input"]));
			//"$"が付いたキーは削除する
			this.removeKeysFromObjectArray(copiedArray, ["$*"]);
			json.input = copiedArray;
		}
		if(typeof objValues["choices"] !== "undefined"){
			let copiedArray = JSON.parse(JSON.stringify(objValues["choices"]));
			//"$"が付いたキーは削除する
			this.removeKeysFromObjectArray(copiedArray, ["$*"]);
			json.choices = copiedArray;
		}
		
		//妥当性チェック
		let errMsg = this._checkFlowData(json);
		if(errMsg != null) throw new TypeError(errMsg);
		
		return json;
	}
	
	/**protected:
	 * ローカルでフロー新規追加更新する。this.dataが更新される。サーバーには保存しない。
	 * @param {Object} flowData - 追加する1つの新規フローのデータ。flow_idが割り当てられて返る。
	 * @param {Number} parentFlowId - このFlowの次の遷移先にflowData.flow_idを追加する
	 * @param {Number} index        - parentFlowIdの choices[index].next_flow_id = flowData.flow_id を設定する。
	 * @return {String} エラーメッセージ。エラーなしの場合null。
	 */
	addFlow(flowData, parentFlowId, index){
		//データの妥当性チェック
		let errMsg = null;
		if(typeof flowData === "undefined"){
			errMsg = "flowData must have value.";
		}else if(this.getFlowDataById(flowData.flow_id) != null){
			errMsg = "flowData.flow_id must be unique.";
		}else if(this.getFlowDataByPtId(flowData.pt_id) != null){
			errMsg = "flowData.pt_id must be unique.";
		}
		if(typeof parentFlowId === "undefined") errMsg = "parentFlowId must have value.";
		if(typeof index === "undefined") errMsg = "index must have value.";
  		if(errMsg != null) return errMsg;
		index = Number.parseInt(index);

		//妥当性チェック
		errMsg = this._checkFlowData(flowData);
		if(errMsg != null) return errMsg;
		
		//親のFlowデータを取得
		let parentFlowData = this.getFlowDataById(parentFlowId);
		
		//親のFlowデータに追加
		parentFlowData.choices[index].next_flow_id = flowData.flow_id;
		
		//this.dataにFlowデータを追加
		this.data.user_flows.flows[flowData.flow_id] = flowData;
		
		return null;
	}
	
	/**protected:
	 * ローカルでFlowを更新する。this.dataが更新される。サーバーには保存しない。
	 * @param {Object} p_flowData - 更新するFlowデータ
	 * @return {String} エラーメッセージ。エラーがない場合はnull。
	 */
	updateFlow(p_flowData){
		//データの妥当性チェック
		let errMsg = null;
		if(typeof p_flowData === "undefined"){
			errMsg = "p_flowData must have value.";
		}else if(this.getFlowDataById(p_flowData.flow_id) == null){
			errMsg = "p_flowData.flow_id dose not exist.";
		}else{
			//基本的な妥当性チェック
			errMsg = this._checkFlowData(p_flowData);
		}
		if(errMsg != null) return errMsg;
		
		//更新
		let flowData = this.createNewFlowJson(p_flowData); //this.getFlowDataById(p_flowData.flow_id);
		//flowData.revision = this.dataRevision + 1;
		this.data.user_flows.revision = this.dataRevision + 1;
		//フローデータを既存の物と置き替え
		this.setFlowData(flowData);
		
		return null;
	}
	
	/**protected:
	 * Modalが開いているときにCancasがクリックされたときに呼ばれるコールバック関数を設定する。
	 * @param {Function} callback - コールバック関数
	 */
	setModalCanvasClick(callback){
		if(callback != null && !(callback instanceof Function)) throw new TypeError("arugue is invalid.");
		this.funcModalCanvasClick = callback;
	}
	
	/**public:
	 * 現在のプロパティウィンドウに表示しているFlow IDを取得する。
	 * @return {String} 現在のFlow ID。値がない場合はnull。
	 */
	getCurrentPropFlowId(){
		let flowId = this.findElementByDataId("ISFL_editor_prop.val.flow_id").val();
		if(typeof flowId === "undefined" || flowId == "") return null;
		return flowId;
	}
	
	/**protected:
	 * 指定のdata-id名の配下のタグの値を収集してJsonを作成する。
	 * 属性：data-idをキー名にし、Elementのvalue値とdata-originalの値を抽出する。
	 * 同じ階層のdata-originalの値は、キー名の先頭に"$"をつけたプロパティに設定する。
	 * @param {String} parentDataId - 親data-id
	 * @param {String} dataIdPrefix - 子孫data-id（前方一致）
	 * @return {Array.<Object>}
	 */
	getValuesFromElements(parentDataId, dataIdPrefix){
		let objTags = this.findElementsByDataIdPrefix(parentDataId, dataIdPrefix);
		
		//値の抽出
		let dataAry = {};
		for(let objTag of objTags){
			let orgValue = objTag.getAttribute("data-original");
			let name = objTag.getAttribute("data-id");
			name = name.substr(dataIdPrefix.length);
			let aryNames = name.match(/^([a-zA-Z0-9_]+)(\[([0-9]+)\]){0,1}(\.(.+)){0,1}$/);
			if(aryNames == null) continue;
			let name1 = aryNames[1];
			let name1Index = aryNames[3];
			let name2 = aryNames[5];
			let value = objTag.value;
			//値を設定
			if(typeof name1Index === "undefined"){
				//配列ではないので設定変更
				dataAry[name] = value;
				
				//data-originalの値を設定
				if(orgValue != null) dataAry["$" + name] = orgValue;
			}else{
				let index = Number.parseInt(name1Index);
				//配列の場合
				if(typeof dataAry[name1] === "undefined") dataAry[name1] = {};
				let dataAry1 = dataAry[name1];
				if(typeof dataAry1[index] === "undefined") dataAry1[index] = {};
				dataAry1[index][name2] = value;
				
				//data-originalの値を設定
				if(orgValue != null) dataAry1[index]["$" + name2] = orgValue;
			}
		}
		return dataAry;
	}
	
	/**public:
	 * プロパティウィンドウ内の値を収集してFlowデータのJsonを作成する。
	 * data-original属性の値は先頭に"$"を付けた名前で保存される。
	 * @return {IsolationFlowEditor~flowData}
	 */
	getPropValuesFromWindow(){
		const NAME_PREFIX = "ISFL_editor_prop.val.";
		//let objTags = this.findElementsByDataIdPrefix("ISFL_editor_prop", NAME_PREFIX);
		let ret = {
			"flow_id": null,
			"revision": null,
			"pt_id": null,
			"status": null,
			"title": null,
			"question": null,
			"input":[],
			"input_length": null,
			"choices" : [],
			"choices_length": null
		};
		
		//値の抽出
		let dataAry = this.getValuesFromElements("ISFL_editor_prop", NAME_PREFIX);
		dataAry["flow_id"] = Number.parseInt(dataAry["flow_id"]);
		dataAry["revision"] = Number.parseInt(dataAry["revision"]);
		
		//値の設定(スカラー値のみ)
		for(let name in ret){
			if(ret[name] instanceof Array) continue;
		
			//値が抽出できれば設定する
			if(typeof dataAry[name] !== "undefined"){
				ret[name] = dataAry[name];
			}
			name = "$" + name;
			if(typeof dataAry[name] !== "undefined"){
				ret[name] = dataAry[name];
			}
		}
		
		//値の設定(choices配列)
		for(let name in ret){
			if(!(ret[name] instanceof Array)) continue;
			if(typeof dataAry[name] === "undefined") continue;
			//
			let dataAry1 = dataAry[name];
			let data1 = Array(dataAry1.length);
			for(let index1 in dataAry1){
				let data = dataAry1[index1];
				data1[index1] = data;
			}
			ret[name] = data1;
		}
		
		return ret;
	}
	
	/**protected:
	 * プロパティウィンドウ内のHTMLタグを取得する。data-idの名前で検索する。
	 * @param {String} strDataIdName - 名前。例えば"flow_id"の場合、data-id="ISFL_editor_prop.val.flow_id"を検索する。
	 *                        名前のプレフィックスは、this.propValueDataIdPrefix の設定値。 
	 * @return {jQuery.Element} - 結果。
	 */
	findPropElementByDataName(strDataIdName){
		let objTag = this.findElementByDataId("ISFL_editor_prop", this.propValueDataIdPrefix + strDataIdName);
		if(objTag.length != 1){
			throw new Error(this.propValueDataIdPrefix + strDataIdName + " is not unique.");
		}
		return objTag;
	}
	
	/**protected:
	 * プロパティウィンドウとthis.dataにの指定のフィールドに値を設定する。
	 * @param {String} strDataIdName - 取得するFlowデータの階層を指定（例："choices[2].next_flow_id"）
	 * @param {} val - フィールドに設定する値。
	 */
	setPropValue(strDataIdName, val){
		let objTag = this.findPropElementByDataName(strDataIdName);
		let flowId = this.getCurrentPropFlowId();
		let flowData = this.getFlowDataById(flowId);
		//データを設定。
		this.bindData(flowData, strDataIdName, val);
		//プロパティウィンドウに値を設定
		objTag.val(val);
	}
	
	/**protected:
	 * プロパティウィンドウ内の値が変更されているかをチェックする。
	 * @return {bool} trueのとき変更されている。
	 */
	checkPropValueUpdated(){
		//値を取得。
		let objTags = this.findElementsByDataIdPrefix("ISFL_editor_prop", this.propValueDataIdPrefix);
		//
		for(let objTag of objTags){
			let originalVal = objTag.getAttribute("data-original");
			let curVal = objTag.value;
			if(originalVal != curVal) return true;
		}
		return false;
	}
	
	/**protected:
	 * 結線の選択や★の表示を外す。クリアする。CanvasのUpdateは行わない。
	 */
	unselectConnection(){
		//すべての星を削除
		this.objEditorCanvas.removeAllStarsOnRects();
		
		//すべての結合線の選択状態を解除
		this.objEditorCanvas.unselectConnection();
	}
	
	/**protected:
	 * 親と子の間の結線を選択状態にし、子に星を付ける。CanvasのUpdateは行わない。
	 * @param {Number} flowId - 親のFlowID
	 * @param {Number} nextId - 子のFlowID
	 */
	selectConnection(flowId, nextId){
		//次の遷移先を示す星を表示
		this.objEditorCanvas.displayStarOn(nextId);
		
		//次の遷移先との結合線を選択状態にする
		this.objEditorCanvas.selectConnection(flowId, nextId);
	}
	
	/**protected:
	 * プロパティのプレビューの表示ボタンをクリックしたときの処理
	 * @param {Event} event 
	 */
	onClickDisplayPropPreview(event){
		let self = this;
		let objPropDiv = this.findElementByDataId('ISFL_editor_prop');
		objPropDiv.addClass('ISFL-editor-prop--preview-on');
		let objPreviewDiv = this.findElementByDataId('ISFL_editor_prop.preview');
		
		//プロパティ上のデータを取得してフローデータに加工
		let prop = this.getPropValuesFromWindow();
		let data = {
			"user_flows": { 
				"isfl_id": 1,
				"revision": 1,
				"group_title": "",
				"start_flow_id": prop.flow_id,
				"flows": {}
			}
		};
		data.user_flows.flows["" + prop.flow_id] = prop;
		let test = new ISFL.IsolationFlowUser('#ISFL_editor_prop_preview', this.xWpNonce, data, 
			{user_item_list: '#ISFL_isolation_flow_user_list_item'});
		test.displayUserFlow();
	}

	/**protected:
	 * プロパティのプレビューを隠すボタンをクリックした時の処理
	 * @param {Event} event 
	 */
	onClickHidePropPreview(event){
		let objPropDiv = this.findElementByDataId('ISFL_editor_prop');
		objPropDiv.removeClass('ISFL-editor-prop--preview-on');
	}

	/**protected:
	 * プロパティのプレビューを更新ボタンをクリックした時の処理
	 * @param {Event} event 
	 */
	onClickUpdatePropPreview(event){
		this.onClickHidePropPreview(event);
		this.onClickDisplayPropPreview(event);
	}

	/**protected:
	 * 画像選択ボタンをクリックしたときの処理
	 * @param {Event} event 
	 */
	onClickSelectImage(event){
		let self = this;
		let objDialog = this.getDialog('onClickSelectImage');
		if(typeof objDialog === 'undefined'){
			objDialog = this._createDialog(
				ISFL.DialogImageSelect, 
				'#ISFL_dialog_select_img', 
				{
					title: self.getMsg('TITLE.DIALOG.SELECT_IMAGE'), 
					top: "10%", left: "10%", height: "80%"
				}
			);
			this.setDialog('onClickSelectImage', objDialog);
		}
		
		//決定時の処理
		objDialog.setDeterminingCallback(function(srcImage){
			srcImage = srcImage.replace(/^http[s]{0,1}\:\/\/[^\/]+/i, '');
			let objTextArea = self.findElementByDataId("ISFL_editor_prop.val.question")[0];
			let text = "[image:80:" + srcImage + "]";
			self.insertToTextArea(objTextArea, text);
		});
		
		//ダイアログ表示
		objDialog.display({});
	}
	
	/**protected:
	 * 入力項目挿入ボタンをクリックしたときの処理
	 * @param {Event} event 
	 */
	onClickSelectInputLabel(event){
		let self = this;
		let objDialog = this.getDialog('onClickSelectInputLabel');
		if(typeof objDialog === 'undefined'){
			objDialog = this._createDialog(
				ISFL.Dialog, 
				'#ISFL_editor_dialog_select_input_no', 
				{
					title: self.getMsg('TITLE.DIALOG.SELECT_INPUT_LABEL'), 
					top: "10%", left: "10%", width: "450px", height: "80%"
				}
			);
			this.setDialog('onClickSelectInputLabel', objDialog);
		}
		
		//ボタン等クリック時の処理
		objDialog.addEventsFunc(function(dialog){
			dialog.findElementByDataId('ISFL_editor_dialog.btn_determin_input')
			.on('click', function(event){
				let objRadio = dialog.findElements("input[name='ISFL_editor_dialog.val.input.no']:checked")[0];
				if(!objRadio){
					alert(self.getMsg('HTML.ERR.SELECT_TARGET'));
					return;
				}
				//選択したID等の取得と挿入文字列作成
				let flowId = objRadio.getAttribute('data-flow_id');
				let no = objRadio.getAttribute('data-no');
				let text = "{input:" + flowId + "-" + no + "}"
				
				//TextAreaオブジェクト
				let objTextArea = self.findElementByDataId("ISFL_editor_prop.val.question")[0];
				
				//テキストを挿入する
				self.insertToTextArea(objTextArea, text);
				dialog.closeModalDialog();
			});
		});
		
		//Json作成
		let json = {flows: []};
		for(let flow_id in this.data.user_flows.flows){
			let flow = this.data.user_flows.flows[flow_id];
			json.flows.push({
				"flow_id": flow.flow_id,
				"pt_id": flow.pt_id,
				"title": flow.title,
				"input": flow.input
			});
		}
		
		//コピー
		let copiedJson = JSON.parse(JSON.stringify(json));
		
		//ダイアログ表示
		objDialog.display(copiedJson);
	}
	
	/**protected:
	 * リンク入力ボタンをクリックしたときの処理
	 * @param {Event} event 
	 */
	onClickInputLink(event){
		let url = prompt(this.getMsg('HTML.DESC.INPUT_URL'));
		if(url == null) return;
		let objTextArea = this.findElementByDataId("ISFL_editor_prop.val.question")[0];
		let text = "[" + url + "]";
		this.insertToTextArea(objTextArea, text);
	}
	
	/**protected:
	 * Canvas上でFlowオブジェクトをクリックしたときの処理。
	 * @param {Number} id - クリックされたFlow ID
	 * @return {bool} trueのときFlowを選択状態に描画する。
	 */
	onClickFlowOnCanvas(id){
		if(this.funcModalCanvasClick == null){
			//モーダルが表示されていない、通常のCanvasクリックの場合
			if(this.checkPropValueUpdated()){
				if(!confirm(this.getMsg('HTML.WARN.EDIT.COMFIRM_DISCARD_CHANGE'))) return false;
			}
			//新たなプロパティウィンドウを開く
			this.displayPorperty(id);
			return true;
		}else{
			//モーダルが表示されていて、callback関数が設定されている場合
			return this.funcModalCanvasClick(id);
		}
	}
	
	/**protected:
	 * 更新ボタンをクリックされたときの処理。
	 * @param {Event} event 
	 */
	onClickUpdateBtn(event){
		let currentFlowId = this.getCurrentPropFlowId();
		//更新処理 
		let newflowData = this.getPropValuesFromWindow();
		let errMsg = this.updateFlow(newflowData);
		if(errMsg != null){
			alert(errMsg);
			return;
		}
		//ローカルデータのコミット処理
		this._commitTransaction();
		this.redraw(this.data);
		alert(this.getMsg('OK.SUCCESS.BUT_NOT_SAVED_TO_SERVER', false));
		//プロパティを再表示
		this.displayPorperty(currentFlowId); 
	}
	
	/**protected:
	 * プロパティウィンドウのキャンセルボタンがクリックされたときの処理。
	 * @param {Event} event 
	 */
	onClickCancelBtn(event){
		if(this.checkPropValueUpdated()){
			if(!confirm(this.getMsg('HTML.WARN.EDIT.COMFIRM_DISCARD_CHANGE'))) return;
		}
		//元に戻す処理 
		this._rollbackTransaction();
		this.redraw(self.data);
		//プロパティを再表示
		let currentFlowId = this.getCurrentPropFlowId();
		this.displayPorperty(currentFlowId); 
	}
	
	/**protected:
	 * 結果の入力項目の削除ボタンを押されたときのイベント処理
	 * @param {Event} event - イベントオブジェクト
	 */
	onClickDelInputValue(event){
		let obj = event.target;
		let delTargetNo = obj.getAttribute("data-input_no");
		if(typeof delTargetNo === "undefined"){//プログラムミスの可能性大
			throw new Error("Unexpeted error. attribute no do not exist. ");
			return;
		}
		let flowId = this.getCurrentPropFlowId();
		let flowData = this.getFlowDataById(flowId);
		
		//選択した選択を削除する
		let copiedJson = this.getPropValuesFromWindow();
		let inputIndex = copiedJson["input"].findIndex(function(data){ return data.no == delTargetNo;});
		
		//this.dataから削除
		flowData.input.splice(inputIndex, 1);
		//プロパティウィンドウから削除
		copiedJson["input"].splice(inputIndex, 1);
		
		//HTML表示
		this.writePorperty(copiedJson);
	}
	
	/**protected:
	 * 結果の入力項目の追加ボタンを押されたときのイベント処理
	 * @param {Event} event - イベントオブジェクト
	 */
	onClickAddInputValue(event){
		let obj = event.target;
		let flowId = this.getCurrentPropFlowId();
		let flowData = this.getFlowDataById(flowId);
		
		//プロパティウィンドウからデータを取得
		let copiedJson = this.getPropValuesFromWindow();
		let no = copiedJson["input"].length + 1;
		let newInput = {no: no, type: "text", label: ""};
		
		//念のため、this.dataとプロパティウィンドウの要素数が一致するかチェック
		if(copiedJson["input"].length != flowData.input.length) throw new Error("array length not match.");
		
		//this.dataのchoicesにデータを1つ追加
		flowData.input.push(newInput);
		
		//プロパティウィンドウに追加
		copiedJson["input"].push(newInput);
		
		//HTML表示
		this.writePorperty(copiedJson);
	}
	
	/**protected:
	 * 結果の選択項目のラジオボタンを押されたときのイベント処理
	 * @param {Event} event 
	 */
	onClickChoiceSelection(event){
		let obj = event.target;
		let index = obj.getAttribute("data-choice-index");
		let flowId = this.getCurrentPropFlowId();
		let nextId = this.findPropElementByDataName("choices[" + index + "].next_flow_id").val();
		//let nextId = this.findElementByDataId(this.propValueDataIdPrefix + "choices[" + index + "].next_flow_id").val();
		
		//星と結線の選択状態をクリアする
		this.unselectConnection();
		
		//次の遷移先との結合線を選択状態にする
		this.selectConnection(flowId, nextId);
		
		//Canvas更新
		this.updateCanvas();
	}
	
	/**protected:
	 * 結果の選択項目の追加ボタンを押されたときのイベント処理
	 * @param {Event} event 
	 */
	onClickAddChoicesLine(event){
		let obj = event.target;
		let flowId = this.getCurrentPropFlowId();
		let flowData = this.getFlowDataById(flowId);
		
		//プロパティウィンドウからデータを取得
		let copiedJson = this.getPropValuesFromWindow();
		let id = copiedJson["choices"].length + 1;
		let newChoice = {"id": id, "label": "", "next_flow_id": ""};
		
		//念のため、this.dataとプロパティウィンドウの要素数が一致するかチェック
		if(copiedJson["choices"].length != flowData.choices.length) throw new Error("array length not match.");
		
		//this.dataのchoicesにデータを1つ追加
		flowData.choices.push(newChoice);
		
		//プロパティウィンドウに追加
		copiedJson["choices"].push(newChoice);
		
		//HTML表示
		this.writePorperty(copiedJson);
	}
	
	/**protected:
	 * 結果の選択項目の削除ボタンを押されたときのイベント処理
	 * @param {Event} event 
	 */
	onClickDelChoicesLine(event){
		let obj = event.target;
		let objChecked = this.findElements("input[name='ISFL_editor_prop.choice_selection']:checked");
		if(objChecked.length == 0){
			alert(this.getMsg('HTML.ERR.SELECT_TARGET', false));
			return;
		}
		let choiceId = objChecked.attr("data-flow_id");
		let flowId = this.getCurrentPropFlowId();
		let flowData = this.getFlowDataById(flowId);
		
		//選択した選択を削除する
		let copiedJson = this.getPropValuesFromWindow();
		let choiceIndex = copiedJson["choices"].findIndex(function(data){ return data.id == choiceId;});
		
		//this.dataから削除
		flowData.choices.splice(choiceIndex, 1);
		//プロパティウィンドウから削除
		copiedJson["choices"].splice(choiceIndex, 1);
		
		//HTML表示
		this.writePorperty(copiedJson);
	}
	
	/**protected:
	 * 次の遷移先のradioボタンをクリックされたときの処理
	 * @param {Event} event 
	 */
	onClickNextChoiceId(event){
		let self = this;
		let obj = event.target;
		let nextId = obj.value;
		let index = obj.getAttribute("data-choice-index");
		
		//ラジオボタンを選択する
		obj.parentElement.click();

		//Canvasの位置情報を取得する
		let canvasRect = this.objEditorCanvas.getCanvasBoundingRect();
		let intLeft = canvasRect.right + 10;
		
		//Modalダイアログを表示する（Canavasの右隣に表示）
		let json = {choices_index: index, current_next_flow_id: nextId};
		let objDialog = this.getDialog('onClickNextChoiceId');
		if(typeof objDialog === 'undefined'){
			objDialog = this._createDialog(
				ISFL.Dialog, 
				'#ISFL_editor_dialog_select_next_flow', 
				{
					title: self.getMsg('TITLE.DIALOG.SELECT_NEXT_FLOW'), 
					width: "300px", height: "500px", left: ''+intLeft+"px", top: "50px",
				}
			);
			
			//イベント追加
			objDialog.addEventsFunc(function(dialog){
				//CanvasをOverlayの上に表示
				self.moveCanvasOntoOverlay();
				
				//Canvasがクリックされたときの動作（ModalにクリックしたFlowIDを入力する）
				self.setModalCanvasClick(function(id){
					dialog.findElementByDataId("ISFL_editor_dialog.next_flow_id").val(id);
					return false;
				});
				
				//イベント追加
				dialog.findElementByDataId("ISFL_editor_dialog.next_flow_id.btn_save")
				.on("click", function(event){
					//入力した値をModalから抜き取ってFlowデータに保存する
					if(!self._updateNextChoiceIdModal(index)) return;
					dialog.closeModalDialog();
				});
				dialog.findElementByDataId("ISFL_editor_dialog.next_flow_id.btn_cancel")
				.on("click", function(event){
					//ダイアログを隠す（取消ボタンを押したときと同じ処理）
					dialog.closeModalDialog();
				});
			});
			
			//ダイアログを設定
			this.setDialog('onClickNextChoiceId', objDialog);
		}
		
		//ダイアログ表示
		objDialog.display(json);
		
	}
	
	/**protected:
	 * 選択の画像削除ボタンをクリックした時の処理。
	 * @param {Event} event 
	 */
	onClickDeleteChoiceImage(event){
		let obj = event.target;
		let flowId = this.getCurrentPropFlowId();
		let flowData = this.getFlowDataById(flowId);
		let index = parseInt(obj.getAttribute('data-index'));
		if(isNaN(index)){
			//なぜか子要素のiconがevent.targetになるときがある。その対策。
			obj = obj.parentElement;
			index = parseInt(obj.getAttribute('data-index'));
		}
		
		//プロパティウィンドウからデータを取得
		let copiedJson = this.getPropValuesFromWindow();
		
		//プロパティウィンドウに追加
		delete copiedJson.choices[index].image;
		delete copiedJson.choices[index].attachment_id;
		
		//HTML表示
		this.writePorperty(copiedJson);
	}
	
	/**protected:
	 * 選択の画像追加ボタンをクリックした時の処理。
	 * @param {Event} event 
	 */
	onClickAddChoiceImage(event){
		let obj = event.target;
		let flowId = this.getCurrentPropFlowId();
		let flowData = this.getFlowDataById(flowId);
		let index = parseInt(obj.getAttribute('data-index'));
		if(isNaN(index)){
			//なぜか子要素のiconがevent.targetになるときがある。その対策。
			obj = obj.parentElement;
			index = parseInt(obj.getAttribute('data-index'));
		}
		
		//プロパティウィンドウからデータを取得
		let copiedJson = this.getPropValuesFromWindow();
		
		//画像選択ダイアログ作成
		let self = this;
		let objDialog = this.getDialog('onClickAddChoiceImage');
		if(typeof objDialog === 'undefined'){
			objDialog = this._createDialog(
				ISFL.DialogImageSelect, 
				'#ISFL_dialog_select_img', 
				{
					title: self.getMsg('TITLE.DIALOG.SELECT_IMAGE'), 
					top: "10%", left: "10%", height: "80%",
				}
			);
			
			//ダイアログを設定
			this.setDialog('onClickAddChoiceImage', objDialog);
		}
		
		//画像決定時の処理
		objDialog.setDeterminingCallback(function(srcImage, attachmentId){
			//プロパティウィンドウに追加
			copiedJson.choices[index].image = srcImage;
			copiedJson.choices[index].attachment_id = attachmentId;
			
			//HTML表示
			self.writePorperty(copiedJson);
		});
		
		//ダイアログ表示
		objDialog.display({});
	}
	
	/** private:
	 * 次のFlow IDを選択するModalダイアログで、入力した値をthis.dataに保存する。
	 * プロパティウィンドウの値も変更する。処理中ポップアップは呼び出し側でPromiseに追加することでCloseすること。
	 * @param {Number} index - プロパティウィンドウでクリックされたnext_flow_idの要素番号（0～）。この要素のIDを変更する。
	 * @return {bool} - trueの場合成功。falseの場合エラーあり。
	 */
	_updateNextChoiceIdModal(index){
		let currentFlowId = this.getCurrentPropFlowId();
		let choicesIndex = this.findElementByDataId("ISFL_editor_dialog.choices_index").val();
		choicesIndex = Number.parseInt(choicesIndex);
		let creationType = this.findElements("input[name='ISFL_editor_dialog.flow_creation_type']:checked").val();
		let nextId = this.findElementByDataId("ISFL_editor_dialog.next_flow_id").val();
		if(typeof creationType === "undefined"){
			alert(this.getMsg('HTML.ERR.SELECT_TARGET', false));
			return null;
		}
		
		//処理
		if(creationType == "select"){
			//既存のフローIDを選択する
			//データの中にFlowIdが存在するかチェックする
			if(this.getFlowDataById(nextId) == null){
				alert(this.getMsg('HTML.ERR.NOT_FOUND_FLOW_ID', false));
				return false;
			}
			//無限ループチェック
			let flowData = this.getPropValuesFromWindow();
			flowData.choices[choicesIndex].next_flow_id = nextId;
			if(!this._checkDataFlowInfiniteLoop(flowData)){
				alert(this.getMsg('HTML.ERR.LOOPING_INFINITELY', false));
				return false;
			}
		}else{
			//フローを新規作成する
			//値を取得する
			let objValues = this.getValuesFromElements("ISFL_editor_modal", "ISFL_editor_dialog.val.");
			if(!objValues["title"] || !objValues["question"]){
				alert(this.getMsg('HTML.ERR.REQUIRED_PARAMS', false));
				return false;
			}
			if(objValues["pt_id"].length > 0){
				if(this.getFlowDataByPtId(objValues["pt_id"]) != null){
					alert(this.getMsg('HTML.ERR.UNIQUE.PT_ID', false));
					return false;
				}
			}
			
			//Flowデータの生成
			let flowData = this.createNewFlowJson(objValues);
			nextId = flowData.flow_id;
			
			//データに追加
			let errMsg = this.addFlow(flowData, currentFlowId, choicesIndex);
			if(errMsg != null){
				alert(errMsg);
				return false;
			}
		}
		
		//表示処理---------------
		//プロパティウィンドウとthis.dataに値設定
		this.setPropValue("choices[" + choicesIndex + "].next_flow_id", nextId);
		//再描画する
		this.redraw(this.data);
		return true;
	}
	
	
	/**protected:
	 * プロパティを表示する。
	 * このときthis._beginTransaction()も開始する。トランザクションの開始はこのメソッドが担当するので
	 * 他では呼び出さないこと。commit, rollbackはプロパティウィンドウ内のボタンが担当し実行する。
	 * @param {String} id - FlowデータのID
	 */
	displayPorperty(id){
		//トランザクション開始
		if(this._isTransaction()){
			this._rollbackTransaction();
			this.redraw(this.data);
		}
		this._beginTransaction();
	
		//遷移線の選択をクリア
		this.unselectConnection();
		
		//データ取得
		let json = this.getFlowDataById(id);
		let copiedJson = JSON.parse(JSON.stringify(json));
		
		//HTML表示
		this.writePorperty(copiedJson);
	}
	
	/**protected:
	 * プロパティウィンドウを表示する。
	 * @param {ISFL.IsolationFlowEditor~flowData} json - Flowデータ（コピーされたデータで変更されてもいいもの）。nullの時は空のウィンドウ表示。
	 */
	writePorperty(json){
		let self = this;
		//nullの時は何もないウィンドウを表示して終了
		if(json == null){
			this.findElementByDataId('ISFL_editor_prop').html("");
			return;
		}
		let copiedJson = json;
		let currentFlowId = json.flow_id;
		
		//inputとchoices要素の数を追加
		json["input_length"] = json["input"].length;
		json["choices_length"] = json["choices"].length;
		
		//data-originalの値が設定されていない場合はjsonに同じ値を追加する
		for(let name in json){
			if(json[name] instanceof Array){
				let data1 = json[name];
				for(let d of data1){
					for(let key in d){
						if(key.substr(0, 1) == "$") continue;
						if(typeof d["$" + key] === "undefined"){
							d["$" + key] = d[key];
						}
					}
				}
			}else{
				if(name.substr(0, 1) == "$") continue;
				if(typeof json["$" + name] === "undefined"){
					json["$" + name] = json[name];
				}
			}
		}
		
		//HTML作成
		let strHtml = this.createHtmlFromTemplate('operator_prop', copiedJson);
		//作成した要素を追加
		let objDiv = this.findElementByDataId('ISFL_editor_prop')
		objDiv.html(strHtml);
		
		//クリックイベントを追加
		this.findElementByDataId('ISFL_editor_prop.btn_prop_preview').on('click', function(event){
			//プレビューボタン処理 
			self.onClickDisplayPropPreview(event); 
		});
		this.findElementByDataId('ISFL_editor_prop.btn_hide_prop_preview').on('click', function(event){
			//プレビュー隠すボタン処理 
			self.onClickHidePropPreview(event); 
		});
		this.findElementByDataId('ISFL_editor_prop.btn_update_prop_preview').on('click', function(event){
			//プレビュー更新ボタン処理 
			self.onClickUpdatePropPreview(event); 
		});
		this.findElementByDataId('ISFL_editor_prop.btn_select_question_image').on('click', function(event){
			//画像選択ボタン処理 
			self.onClickSelectImage(event); 
		});
		this.findElementByDataId('ISFL_editor_prop.btn_select_question_input').on('click', function(event){
			//入力項目追加ボタン処理 
			self.onClickSelectInputLabel(event); 
		});
		this.findElementByDataId('ISFL_editor_prop.btn_input_question_link').on('click', function(event){
			//リンク入力ボタン処理 
			self.onClickInputLink(event); 
		});
		this.findElementByDataId('ISFL_editor_prop.btn_add_input_line').on('click', function(event){
			//追加ボタン処理 
			self.onClickAddInputValue(event); 
		});
		this.findElementByDataId('ISFL_editor_prop.btn_del_input_line').on('click', function(event){
			//削除ボタン処理 
			self.onClickDelInputValue(event); 
		});
		
		this.findElementByDataId('ISFL_editor_prop.btn_choice_selection').on('click', function(event){ 
			self.onClickChoiceSelection(event); 
		});
		this.findElementByDataId('ISFL_editor_prop.btn_add_choices_line').on('click', function(event){
			//追加ボタン処理 
			self.onClickAddChoicesLine(event); 
		});
		this.findElementByDataId('ISFL_editor_prop.btn_del_choices_line').on('click', function(event){
			//削除ボタン処理 
			self.onClickDelChoicesLine(event); 
		});
		//更新ボタン
		this.findElementByDataId('ISFL_editor_prop.btn_update_flow').on('click', function(event){
			self.onClickUpdateBtn(event);
		});
		//キャンセルボタン
		this.findElementByDataId('ISFL_editor_prop.btn_rollback_flow').on('click', function(event){
			//キャンセル処理
			self.onClickCancelBtn(event);
		});
		//次の遷移先Flowのinputのイベント追加
		this.findElements(
			"input[data-id^='" + this.propValueDataIdPrefix + "choices'][data-id$='.next_flow_id']"
		).on('click', function(event){
			//次の遷移先選択処理 
			self.onClickNextChoiceId(event); 
		});
		//次の遷移先の画像削除のイベント追加
		this.findElementByDataId("ISFL_editor_prop.btn_delete_choice_image").on('click', function(event){
			//次の遷移先選択処理 
			self.onClickDeleteChoiceImage(event); 
		});
		//次の遷移先の画像追加のイベント追加
		this.findElementByDataId("ISFL_editor_prop.btn_add_choice_image").on('click', function(event){
			//次の遷移先選択処理 
			self.onClickAddChoiceImage(event); 
		});
	}
	
	/**
	 * プロパティウィンドウをクリアする。
	 */
	clearPorperty(){
		//トランザクション開始
		if(this._isTransaction()){
			this._rollbackTransaction();
		}
		//HTMLクリア
		this.findElementByDataId('ISFL_editor_prop').html("");
	}

	/**protected:
	 * 入力項目挿入ボタンをクリックしたときの処理
	 * @return {Dialog} 表示したダイアログ
	 */
	displayProcessintDialog(){
		let self = this;
		let objDialog = this.getDialog('displayProcessintDialog');
		if(typeof objDialog === 'undefined'){
			objDialog = this._createDialog(
				ISFL.Dialog, 
				'#ISFL_editor_dialog_processing', 
				{
					title: self.getMsg('HTML.DESC.PROCESSING'), 
					top: "40%", left: "40%", width: "20%", height: "20%"
				}
			);
			this.setDialog('displayProcessintDialog', objDialog);
		}
		objDialog.display({});
		return objDialog;
	}

	/**protected:
	 * Modalダイアログ表示時にCanvasをOverlayの上に表示する。
	 * @param {bool} [isOverlay] - trueのときCanvasを触れるように表示。デフォルトtrue。
	 */
	moveCanvasOntoOverlay(isOverlay){
		let objCanvasDiv = this.findElements("#" + this.canvasId);
		if(typeof isOverlay === "undefined") isOverlay = true;
		if(isOverlay){
			//edior-section自体を上にもっていかないとうまく表示できない
			this.rootHtmlElement.css('z-index', '99999');
			//Overlayの上に表示する
			objCanvasDiv.addClass("ISFL-editor-modal-overlay--front");
		}else{
			//edior-sectionの上表示を解除
			this.rootHtmlElement.css('z-index', '');
			//Overlayの上に表示を解除する
			objCanvasDiv.removeClass("ISFL-editor-modal-overlay--front");
		}
	}
	
	/** protected: 
	 * canvasの再構築、再描画を行う。このクラス内のthis.dataは変更しない。
	 * @param {ISFL.IsolationFlowEditor~flows} data - フローデータ
	 */
	redraw(data){
		this.objEditorCanvas.remake(this.data);
		
		//現在のFlowを選択選択状態にする
		let currentFlowId = this.getCurrentPropFlowId();
		if(currentFlowId != null){
			this.objEditorCanvas.selectFlow(currentFlowId);
		}
		
		//Canvas更新
		this.updateCanvas();
	}
}
