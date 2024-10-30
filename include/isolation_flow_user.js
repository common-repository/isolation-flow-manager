
/**
 * 切り分けフローの実行処理をするHTMLをコントロールするクラス。
 * データをAPIから取得して動的にHTMLを変更する。サーバとの通信は一切しない。ローカル利用クラス。
 * <pre>
 * 【必要なjs】
 *  ・isolation_flow_editor_canvas.js
 * 【基本的な設計】
 *   HTMLにresultsデータを保存し、HTMLのデータを正とする。
 *   サーバ保存するときはHTMLからデータを取得し、クラス内部の値を更新してから
 *   クラス内のデータをサーバにする。
 * </pre>
 * @class
 * @extends ISFL.IsolationFlowCommon
 */
ISFL.IsolationFlowUser = class extends ISFL.IsolationFlowCommon{
	/**
	 * @constructor
	 * @param {String} id - ルート親HTMLを指定する。sectionタグのid属性で指定。
	 * @param {String} xWpNonce - Wordpressのnonceを設定。
	 * @param {IsolationFlowEditor~flows} data - フローデータ。
	 * @param {Object} [templateIds] - 連想配列でテンプレートのIDを指定する。{user_item_list:'テンプレートscriptタグ(CSSセレクタで指定)'}
	 */
	constructor(id, xWpNonce, data, templateIds) {
		super(id, xWpNonce);
		 //
		this.xWpNonce = xWpNonce;
		this.pagingUnit = 10; //検索結果で表示する件数
		//this.isfl_id = isfl_id;
		
		/**
		 * Flowの実データ
		 * @type {ISFL.IsolationFlowEditor~flow_groups}
		 */
		this.data = data;

		/**
		 * @typedef {Object.<String, Object>} ISFL.IsolationFlowUser~results
		 * @property {Number} result_id - 結果ID
		 * @property {Number} isfl_id - 切り分けフローID
		 * @property {Number} revision - 切り分けフローIDのリビジョン
		 * @property {String} status - 結果のステータス（created, open, resolved）
		 * @property {Array.<Object>} result - 切り分け結果詳細
		 * @property {String} result[].flow_id - FlowID
		 * @property {Number} result[].start_utc_time - フローの開始日時(UTC)。開いた時刻。もしくはnull。
		 * @property {Number} result[].end_utc_time   - フローの終了日時(UTC)。choice選択をして次のフローに遷移した時刻。もしくはnull。
		 * @property {String} result[].choice_id - 選択ID
		 * @property {String} result[].decided_button - クリックしたボタン(open, close, close_forcely)
		 * @property {Array.<Object>} result[].input - 入力項目の値
		 * @property {String} input[].no - 入力No
		 * @property {String} input[].value - 入力値
		 */
		/**
		 * 結果オブジェクト
		 * @type {ISFL.IsolationFlowUser~results}
		 */
		this.results = {result: []};


		//テンプレート設定
		this.setTemplate('user_item_list', '#ISFL_isolation_flow_user_list_item', templateIds);
		this.setTemplate('process_message', '#ISFL_user_flows_process_message', templateIds);
		//this.prepareBtnOnclick();
	}


	/**protected:
	 * IDからFlowを検索して取得する。見つからない場合はnull。
	 * @param {Number} id - Flow IDを指定
	 * @return {ISFL.IsolationFlowEditor~flowData} Flowデータ
	 */
	getFlowDataById(id){
		let flow = this.data.user_flows.flows[id];
		if(typeof flow === "undefined") return null;
		return flow;
	}

	/**public:
	 * HTML上の切り分けフローから、一番最後のFlowIDを取得する。
	 * @return {Number} - Flow ID	 
	 */
	getFlowIdFromHtml(){
		let objLastLi = this.getLastFlowObj();
		if(typeof objLastLi == null) return null;
		let flowId = objLastLi.getAttribute('data-flow_id');
		return Number.parseInt(flowId);
	}

	/**
	 * HTMLから最後のフロー(liタグ)を取得する
	 * @return {Element} - 取得した要素。見つからない場合null。
	 */
	getLastFlowObj(){
		let objTmp = this.findElementByDataId('isolation_flow_user.list');
		let objFlowLi = objTmp.find('> li:last-child');
		if(objFlowLi.length == 0) return null;
		return objFlowLi[0];
	}

	/**protected:
	 * HTMLからフローで押したボタン（'open','close_forcely'）を設定、取得する
	 * @param {Element} objFlowLi - 対象のElement(フローliタグ)。nullの場合、最後のフローを対象にする。
	 * @param {String} [val] - 設定する値。undefのとき設定しない
	 * @return {String} - 取得した結果ステータスの値。
	 */
	decidedButtonValByObj(objFlowLi, val){
		if(objFlowLi === null){
			objFlowLi = this.getLastFlowObj();
		}
		if(!(objFlowLi instanceof Element)) throw new TypeError('objFlowLi must be Element.'); 
		let objInput = objFlowLi.querySelector('[data-id="isolation_flow_user_list_item.val.data-status"]');
		//
		if(typeof val !== 'undefined') objInput.value = val;
		return objInput.value;
	}
	
	/**protected:
	 * HTMLからフローで開始時刻を設定、取得する
	 * @param {Element} objFlowLi - 対象のElement(フローliタグ)。nullの場合、最後のフローを対象にする。
	 * @param {Number} [val] - 設定する値。undefのとき設定しない。nullのとき空文字を設定。
	 * @return {Number} - 取得した結果ステータスの値。空の時null。
	 */
	startUtcTimeValByObj(objFlowLi, val){
		if(objFlowLi === null){
			objFlowLi = this.getLastFlowObj();
		}
		if(!(objFlowLi instanceof Element)) throw new TypeError('objFlowLi must be Element.'); 
		//
		if(val === null) val = "";
		if(typeof val !== 'undefined'){
			objFlowLi.setAttribute('data-start_utc_time', val);
		}
		let ret = objFlowLi.getAttribute('data-start_utc_time');
		if(ret == null || ret == ""){
			ret = null;
		}else{
			ret = parseInt(ret);
		}
		return ret;
	}

	/**protected:
	 * HTMLからフローで終了時刻を設定、取得する
	 * @param {Element} objFlowLi - 対象のElement(フローliタグ)。nullの場合、最後のフローを対象にする。
	 * @param {Number} [val] - 設定する値。undefのとき設定しない
	 * @return {Number} - 取得した結果ステータスの値。空の時null。
	 */
	endUtcTimeValByObj(objFlowLi, val){
		if(objFlowLi === null){
			objFlowLi = this.getLastFlowObj();
		}
		if(!(objFlowLi instanceof Element)) throw new TypeError('objFlowLi must be Element.'); 
		//
		if(val === null) val = "";
		if(typeof val !== 'undefined'){
			objFlowLi.setAttribute('data-end_utc_time', val);
		}
		let ret = objFlowLi.getAttribute('data-end_utc_time');
		if(ret == null || ret == ""){
			ret = null;
		}else{
			ret = parseInt(ret);
		}
		return ret;
	}

	/**protected:
	 * 上部の機能パートのHTMLの備考に値を設定、取得する。
	 * @param {String} [val] - 備考に設定する値。
	 * @return {String} - 取得した値.備考のHTML要素が見つからない時null。
	 */
	resultRemarksFunctionVal(val){
		let objRemarks = this.findElementByDataId('isolation_flow_user_function.val.remarks');
		if(objRemarks.length == 0) return null;
		if(typeof val !== 'undefined') objRemarks.val(val);
		return objRemarks.val();
	}

	/**protected:
	 * 上部の機能パートのHTMLのresults.statusに値を設定、取得する。
	 * @param {String} [val] - 備考に設定する値。
	 * @return {String} - 取得した値statusのHTML要素が見つからない時null。
	 */
	resultStatusFunctionVal(val){
		let objStatus = this.findElementByDataId('isolation_flow_user_function.val.status');
		if(objStatus.length == 0) return null;
		if(typeof val !== 'undefined') objStatus.val(val);
		return objStatus.val();
	}
	
	/**protected:
	 * コマンドのタグへの変換
	 * @param {String} str - 対象文字列
	 * @return {String} - 変換後の文字列
	 */
	convertHtml(str) {
		let self = this;
		str = str.replace(/\{([^\}]+)\}/g, function () {
			let cmd = arguments[1];
			let aryCmd = cmd.match(/([^:]+):([0-9]+)-([0-9]+)/);
			let strChoiceId = aryCmd[2];
			let strInputId = aryCmd[3];
			let value = "";
			switch (aryCmd[1]) {
				case "input":
					value = self.findElementByDataId("isolation_flow_user_list_item_" + strChoiceId + ".input_" + strInputId).val();
					break;
			}
			return value;
		});
		str = str.replace(/\[([^\]]+)\]/g, function () {
			let cmd = arguments[1];
			let aryCmd = cmd.match(/^([^:]+):/);
			let value = "";
			switch (aryCmd[1]) {
				case "http":
				case "https":
					value = "<a href='" + cmd + "' target='_blank'>" + cmd + "</a>";
					break;
				case "image":
					aryCmd = cmd.match(/^image:([0-9]+):(.+)$/);
					let imgWidth = aryCmd[1];
					let imgUrl = aryCmd[2];
					value = "<img src='" + imgUrl + "' style='width:" + imgWidth + "px' alt='no image'>";
			}
			return value;
		});
		str = str.replace(/\n/g, "<br>");
		return str;
	}

	/**protected:
	 * フロー定義の情報を基に、切り分けフローの1つを追加する。
	 * @param {ISFL.IsolationFlowEditor~flowData} json - フロー
	 * @param {Object} [startEndTimes] - 開始終了時刻。
	 * @proprety {Number} startEndTimes.start_utc_time - 開始時刻
	 * @proprety {Number} startEndTimes.end_utc_time - 終了時刻
	 * @return {Element} - 追加したフローの要素（li Element）。
	 */
	addUserFlow(json) {
		let self = this;
		//let html = document.createDocumentFragment();
		//コピー（このやり方が正しいか後で確認）
		let copiedJson = JSON.parse(JSON.stringify(json));
		//文字列変換
		let strQuestion = json["question"];
		copiedJson["quetionHtml"] = this.convertHtml(strQuestion);
		//定義の追加
		copiedJson["DEF_STATUS_BUTTON"] = this.DEF_STATUS_BUTTON;
		//HTML作成
		let strHtml = this.createHtmlFromTemplate('user_item_list', copiedJson);
		//作成した要素を追加
		let objList = this.findElementByDataId('isolation_flow_user.list').append(strHtml);
		let objChild = objList.find('> li:last-child');
		//イベント追加
		objChild.find('button[data-id="isolation_flow_user_list_item.btn_status"]').on('click', function(event) { 
			self.onClickFlowDecide(event); 
		});
		objChild.find('button[data-id="isolation_flow_user_list_item.btn_close_forcely"]').on('click', function(event) { 
			self.onClickHalfEnd(event); 
		});
		objChild.find('button[data-id="isolation_flow_user_list_item.btn_back_flow"]').on('click', function(event) { 
			self.onClickGoBackFlow(event); 
		});
		//スクロールを追加したフローの位置まで移動
		if(objChild.length > 0){
			let objList = this.findElementByDataId('isolation_flow_user.list');
			let scrollHeight = objList[0].scrollHeight;
			objList.scrollTop(scrollHeight + 1000);
		}
		return objChild[0];
	}

	/**protected:
	 * 結果の追加設定(this.results.resultに値を追加する)
	 * @param {Number|String} flow_id - Flow ID(文字型の数字か数値型いずれか)
	 * @param {String} choice_id - 選択したID
	 * @param {Array.<Object>} inputValues - 入力項目の値
	 * @param {String} decidedButton - フローで押したボタン（'open','close_forcely'）
	 * @property {String} inputValues[].no - 入力No
	 * @property {String} inputValues[].value - 入力値
	 */
	createResult(flowId, choiceId, inputValues, decidedButton, startEndTimes){
		if(typeof flowId !== 'number'){
			if(Number.parseInt(flowId) == ''+flowId){
				flowId = Number.parseInt(flowId);
			}else{
				throw new TypeError('flowId must be number.');
			}
		}
		if(typeof choiceId !== 'string') throw new TypeError('choiceId must be number.');
		if(!(inputValues instanceof Array)) throw new TypeError('inputValues must be array.');
		if(typeof decidedButton !== 'string') throw new TypeError('buttonStatus must be number.');
		let flowResult = {flow_id: flowId, choice_id: choiceId, input: inputValues, decided_button:decidedButton, start_utc_time: null, end_utc_time: null};
		//開始終了日時の取得と設定
		if(typeof startEndTimes !== 'undefined'){
			if(startEndTimes.start_utc_time !== null
			 && typeof startEndTimes.start_utc_time !== 'number') throw new TypeError('startEndTimes.start_utc_time must be number.');
			if(startEndTimes.end_utc_time !== null 
			 && typeof startEndTimes.end_utc_time !== 'number') throw new TypeError('startEndTimes.end_utc_time must be number.');
			flowResult['start_utc_time'] = startEndTimes.start_utc_time;
			flowResult['end_utc_time'] = startEndTimes.end_utc_time;
		}
		return flowResult;
	}

	/**protected:
	 * 結果の追加設定をHTML（フローのliタグ内）から値を取得して行う。
	 */
	updateResultsFromHtml(){
		let result = [];
		let objFlowLis = this.findElementByDataId("isolation_flow_user.list");
		if(!objFlowLis) throw new TypeError("data-id=isolation_flow_user.list must be in HTML.");
		let objUserFlows = objFlowLis.find("> li");
		for(let objFlowLi of objUserFlows){
			result.push(this._getResultFromObj(objFlowLi));
		}
		
		//結果result に最後の結果を追記
		this.results.result = result;
		//備考の値を設定する
		let remarks = this.resultRemarksFunctionVal();
		if(remarks != null) this.results.remarks = remarks;
		//ステータスの値を設定する
		let status = this.resultStatusFunctionVal();
		if(status != null) this.results.status = status;

		//更新後処理
		this.afterUpdateResults();
	}

	/**private:
	 * @param {Element} objFlowLi - 対象Flow Li要素
	 * @return {Object} - result結果オブジェクトの１つ
	 */
	_getResultFromObj(objFlowLi){
		if(!(objFlowLi instanceof Element)){
			throw new TypeError('objFlowLi must be Element.');
		}
		let flowId = objFlowLi.getAttribute("data-flow_id");
		if(flowId == null) throw new TypeError('objFlowLi must be li Element of userFLow.');

		//フローで押したボタン
		let decidedButton = this.decidedButtonValByObj(objFlowLi);
		//値を取得
		let inputValues = this.getInputValuesFromObj(objFlowLi);
		let choiceId = null;
		let objSelctedChoice = objFlowLi.querySelector(".ISFL-user-item-choice__selection input:checked");
		if(objSelctedChoice != null){
			choiceId = objSelctedChoice.getAttribute("data-choice_id");
		}
		if(choiceId == null) choiceId = '';
		//開始終了日時
		let startUtcTime = this.startUtcTimeValByObj(objFlowLi);
		let endUtcTime = this.endUtcTimeValByObj(objFlowLi);
		let startEndTimes = {"start_utc_time": startUtcTime, "end_utc_time": endUtcTime};

		//作成
		return this.createResult(flowId, choiceId, inputValues, decidedButton, startEndTimes);
	}


	/**protected:
	 * 指定のフロー結果を削除する
	 * @param {Number} [flowId] - 削除するFlowID。引数を指定しない場合は最後の結果を削除する。
	 * @return {Object} - 削除したフロー結果。配列が0か、指定のflowIDが見つからなかった場合null。
	 */
	removeResult(flowId){
		if(this.results.result.length == 0) return null;
		//引数がない場合は最後の結果を削除する
		if(typeof flowId === 'undefined'){
			return this.results.result.pop();
		}
		//型チェック
		if(typeof flowId !== 'number'){
			if(Number.parseInt(flowId) == ''+flowId){
				flowId = Number.parseInt(flowId);
			}else{
				throw new TypeError('flowId must be number.');
			}
		}
		//FlowIDと一致する結果を探す
		for(let i = 0; i < this.results.result.length; ++i){
			if(this.results.result[i].flow_id == flowId){
				let ret = this.results.result[i];
				this.results.result.slice(i, 1);
				return ret;
			} 
		}
		return null;
	}

	/**protected:
	 * this.updateResultsFromHtml()の処理後に呼ばれる
	 * 派生クラスで実装する。
	 * @return {Promise} - 実行するPromiseを返す
	 */
	afterUpdateResults(){
		return jQuery.Deferred().resolve().promise();
	}

	/**public:
	 * data-idの名前のプレフィックスに合致するデータをすべて取得する
	 * @param {Element} objFlowLi - Flow ID
	 * @return {Array.Object} - Input入力値のユーザが入力した値
	 * @property {String} Returns[].no - Input No
	 * @property {String} Returns[].value - Input入力値
	 */
	getInputValuesFromObj(objFlowLi){
		if(!(objFlowLi instanceof Element)) throw new TypeError("objFlowLi must be Element.");
		let flowId = objFlowLi.getAttribute("data-flow_id");
		let dataIdPrexix = "isolation_flow_user_list_item_" + flowId + ".input_";
		let objTags = objFlowLi.querySelectorAll('[data-id^="'+ dataIdPrexix +'"]');
		
		//値の抽出
		let dataAry = [];
		for(let objTag of objTags){
			let val = objTag.value;
			let name = objTag.getAttribute("name");
			dataAry.push({no: name, value: val});
		}
		return dataAry;
	}

	/**
	 * 全ての情報を表示する
	 */
	displayMainContainer(){
		this.displayUserFlow();
		this.displayFunction();
	}

	/**public:
	 * 最初のフローを表示する
	 */
	displayUserFlow() {
		//ヘッダ部分の表示
		this.displayGroupTitle();
		//フローの表示
		let startFlowId = "" + this.data.user_flows.start_flow_id;
		this.findElementByDataId('isolation_flow_user.list').empty();
		let flowData = this.getFlowDataById(startFlowId);
		this.addUserFlow(flowData);
	}

	/**public:
	 * フローのヘッダを表示（group_title,isfl_id,result_id）。あらかじめthis.dataは設定しておくこと。
	 * @param {ISFL.IsolationFlowUser~results} [results] - 結果オブジェクト。指定しない場合は内部のthis.resultsの値を使用する。
	 */
	displayGroupTitle(results){
		if(typeof results === 'undefined') results = this.results;
		//切り分けタイトルの表示
		let groupTitle = this.data.user_flows.group_title;
		let isflId = this.data.user_flows.isfl_id;
		let resultId = results.result_id;
		this.findElementByDataId('isolation_flow_user.group_title').html(groupTitle);
		this.findElementByDataId('isolation_flow_user.isfl_id').html(isflId);
		this.findElementByDataId('isolation_flow_user.result_id').html(resultId);
	}

	/**
	 * 上部の隠し機能を表示する。(reamrks, functionButton)
	 */
	displayFunction(){
		if(!this.data) return;
		let self = this;
		let remarks = this.results.remarks;
		this.resultRemarksFunctionVal(remarks);
		let status = this.results.status;
		this.resultStatusFunctionVal(status);

		//機能パートのボタンなどを有効にする
		this._disableFunctionButtons(false);
		//備考の保存ボタン
		this.addEventByDataId('user_flows_function.btn_save_remarks', 'click', function(event){
			self.onClickUpdateResultRemarks(event);
		});
	}

	/**
	 * 処理結果をアニメーションポップアップで表示する。
	 * 一定時間たつと自動的に消えるので、alert()のようにOKを押すわずらわしさがない。
	 * @param {String} msg - 表示するメッセージ
	 */
	displayProcessMessage(msg){
		let json = {'msg': msg};
		//HTML作成
		let strHtml = this.createHtmlFromTemplate('process_message', json);
		let objPopupDiv = this.findElementByDataId('user_flows_process_message');
		objPopupDiv.html(strHtml);
	}

	/**public:
	 * フローのリストをクリアして、フローをインポートして表示する。
	 * 結果IDもこのタイミングで取得する。
	 * @param {ISFL.IsolationFlowUser~results} data - 結果データ。
	 */
	import(data){
		let self = this;
		self.results = {results: []};
		self.data = data;
		//フローを表示
		this.displayMainContainer();
	}

	/**protected:
	 * 決定ボタンを押したときの動作。
	 */
	onClickFlowDecide(event) {
		event.preventDefault();
		let obj = event.target;
		let flowId = obj.getAttribute('data-flow_id');
		let btnStatus = obj.getAttribute('data-status');
		//ボタンの親（フローli）を探す
		let objFlowLi = obj.closest('li.ISFL-user-item');
		if(objFlowLi == null) throw new TypeError("Button parent must be 'li.ISFL-user-item'");

		//終了時刻をHTMLに設定する
		this.endUtcTimeValByObj(objFlowLi, Date.now());

		//HTMLの処理
		this.transactFlowDecideToHtml(objFlowLi, btnStatus, Date.now());

		//結果result に値を保存
		this.transactUpdateResultsFromHtml();
	}
	
	/**protected:
	 * 途中終了ボタンを押したときの動作
	 */
	onClickHalfEnd(event){
		event.preventDefault();
		let obj = event.target;
		//場単の直近の親（フローのタグ）を探す
		let objFlowLi = obj.closest('li.ISFL-user-item');
		if(objFlowLi == null) throw new TypeError("Button parent must be 'li.ISFL-user-item'");
		let flowId = objFlowLi.getAttribute('data-flow_id');
		let decidedButton = obj.getAttribute("data-status");
		this.decidedButtonValByObj(objFlowLi, decidedButton);
		//終了時刻をHTMLに設定する
		this.endUtcTimeValByObj(objFlowLi, Date.now());
		//結果result に最後の結果を追記
		this.transactUpdateResultsFromHtml();
		//終了ダイアログ表示
		this.transactFlowEnd(decidedButton);
	}
	
	/**protected:
	 * フローを1つ前に戻すボタンを押したときの動作
	 */
	onClickGoBackFlow(event){
		event.preventDefault();
		this.goBackFlow();
	}

	/**protected:
	 * 備考の保存ボタンを押したときの動作。
	 * HTMLからstatus,remarksの値を取得し内部に保存する。
	 * remarksの値が存在すれば afterOnClickUpdateRemarks() を呼び出す。
	 */
	onClickUpdateResultRemarks(event){
		event.preventDefault();
		let self = this;
		//HTMLからstatusを取得し、内部に保存
		let status = this.resultStatusFunctionVal();
		if(status != null){
			this.results.status = status;
		}
		let remarks = this.resultRemarksFunctionVal();
		if(remarks != null){
			this.results.remarks = remarks;
			this.afterOnClickUpdateRemarks()
			.then(function(){
				self.displayProcessMessage(self.getMsg('OK.SUCCESS'));
			});
		}
	}

	/**propected: 派生クラスでオーバーライドする。
	 * 備考の保存ボタンクリックの後処理。
	 * @return {Promise} - 何かしらの処理をしたPromiseを返す。
	 */
	afterOnClickUpdateRemarks(){
		return jQuery.Deferred().resolve().promise();
	}

	/**public:
	 * フローを1つ前に戻す（HTML上もthis.resultsデータも1つ前の状態にする）
	 */
	goBackFlow(){
		let objUserList = this.findElementByDataId('isolation_flow_user.list');
		let objLis =  objUserList.find('> li');
		if(objLis.length > 1){
			let objLastUserFlow = objLis[objLis.length-1];
			objLastUserFlow.remove();
			this.removeResult();
			//最後のフロー取得
			objLis =  objUserList.find('> li');
			objLastUserFlow = objLis[objLis.length-1];
			let flowId = objLastUserFlow.getAttribute("data-flow_id");
			//ボタンやラジオボタンなどの各種入力項目を使えるようにする。
			this._disableFlowButtons(objLastUserFlow, false);
			//押した情報
			this.decidedButtonValByObj(objLastUserFlow, "");
			//開始終了日時のHTMLへの設定(戻った場合は開始をリセットする)
			this.startUtcTimeValByObj(objLastUserFlow, Date.now());
			this.endUtcTimeValByObj(objLastUserFlow, null);
		}
	}

	/**
	 * フローの決定ボタンを押したときのHTMLの処理だけをする。
	 * 現在のフローにボタンdisabledやdecided_buttonに値を設定などHTML処理をし、
	 * 次のフローをHTML表示。
	 * （Resultの追加などは呼び出し側で実行すること）
	 * @param {Element} objFlowLi - 処理対象のフロー(liタグ)
	 * @param {String} decidedButton - 選択したボタン（ボタンのdata-status属性の値で指定）
	 * @param {Number} [startUtcTime] - フロー処理開始時刻.HTMLに埋め込む。指定しない場合は何もしない。nullのときは空文字を設定。
	 */
	transactFlowDecideToHtml(objFlowLi, decidedButton, startUtcTime) {
		if(!(objFlowLi instanceof Element)) throw new TypeError('objFlowLi must be Element.');
		if(typeof startUtcTime !== 'undefined' && typeof startUtcTime !== 'number') throw new TypeError('startUtcTime must be number.');
		//
		let flowId = objFlowLi.getAttribute("data-flow_id");
		let objSelctedChoice = objFlowLi.querySelector(".ISFL-user-item-choice__selection input:checked");
		//let checkedObj = objFlowLi.querySelector("input[name='isolation_flow_user_item_choice_" + flowId + "']:checked");
		//終了ボタンではなく、しかも結果選択がされていない場合エラー
		if (decidedButton != 'close' && !objSelctedChoice) {
			alert(this.getMsg('HTML.ERR.SELECT_TARGET'));
			return;
		}
		//押したボタンを取得し、hiddenに設定
		this.decidedButtonValByObj(objFlowLi, decidedButton);
		//ボタンやラジオボタンなどの各種入力項目を使えなくする。
		this._disableFlowButtons(objFlowLi, true);

		//ボタンによって処理を分岐
		if(decidedButton != 'close'){
			//値を取得
			let next_flow_id = objSelctedChoice.value;
			//次のフローを追加表示
			let copiedFlowData = JSON.parse(JSON.stringify(this.data.user_flows["flows"][next_flow_id]));
			let objNextFlowLi= this.addUserFlow(copiedFlowData);
			//開始時刻の設定
			if(typeof startUtcTime !== 'undefined'){
				this.startUtcTimeValByObj(objNextFlowLi, startUtcTime);
			}
		}else{
			//終了処理
			this.transactFlowEnd(decidedButton);
		}
	}

	/**protected:
	 * HTMLから値を取得して、内部のthis.resultsに保存する。（備考と指定のフローの保存）
	 * 通常は、フローの決定や途中保存などのボタンがクリックされたときに呼び出す。
	 */
	transactUpdateResultsFromHtml(){
		//結果result に値を保存
		this.updateResultsFromHtml();
	}

	/**protected:
	 * フローの終了処理をする。ダイアログを出して、備考メモと結果ステータスを入力してもらう。
	 */
	transactFlowEnd(decidedButton){
		if(typeof decidedButton !== 'string') throw new TypeError('decidedButton must be string.');
		let self = this;
		let flowId = this.getFlowIdFromHtml();

		//終了のダイアログを表示
		let objDialog = this._createDialog(
			ISFL.Dialog, 
			'#ISFL_dialog_end_flows', 
			{
				title: self.getMsg('TITLE.DIALOG.END_FLOWS'), 
				top: "10%", left: "10%", width: "450px", height: "80%"
			}
		);
		this.setDialog('transactFlowEnd', objDialog);
		//ダイアログのボタンクリックなどのイベントを追加
		objDialog.addEventsFunc(function(dialog){
			//FLow決定の処理
			dialog.findElementByDataId('ISFL_editor_dialog.btn_determin').on('click', function(event){
				let flowId = dialog.findElementByDataId('ISFL_editor_dialog.val.flow_id').val();
				let resultRemarks = dialog.findElementByDataId('ISFL_editor_dialog.val.results.remarks').val();
				let resultStatus = dialog.findElementByDataId('ISFL_editor_dialog.val.results.status').val();
				let sendMail = dialog.findElementByDataId('ISFL_editor_dialog.btn_send_mail').prop('checked');
				let copiedResult = JSON.parse(JSON.stringify(self.results));
				//データ設定
				copiedResult.remarks =resultRemarks;
				copiedResult.status = resultStatus;
				//エラーチェック
				let errMsg = self._checkResult(copiedResult);
				if(errMsg != null){
					alert(errMsg);
					return;
				}
				//切り分け結果を保存
				self.results = copiedResult;
				
				//終了処理
				self.endFlows({"mail_info":{"send_mail": sendMail}})
				.then(function(){
					//処理成功時、ボタンやラジオボタンなどの入力項目を使えなくする。
					self._disableFlowButtons(null, true);
					self._disableFunctionButtons(true);
					dialog.closeModalDialog();
				}).fail(function(){
					//処理失敗時、ボタンを使えるように戻す。
					self._disableFlowButtons(null, false);
					self._disableFunctionButtons(false);
				});

			});
		});
		objDialog.display({"results": self.results, "flow_id": flowId, "decided_button": decidedButton});
	}

	/**private:
	 * Flowの中の入力・選択項目をDisabledにする。
	 * @param {Element} objFlowLi - 対象を指定する。nullのとき最後のフロー(liタグ)を対象。
	 * @param {boolean} disabled - trueのとき使えなくする。
	 */
	_disableFlowButtons(objFlowLi, disabled){
		//対象オブジェクト取得
		if(objFlowLi == null){
			objFlowLi = this.getLastFlowObj();
			if(objFlowLi == null) return;
		}
		//引数チェック
		if(!(objFlowLi instanceof Element)) throw new TypeError('objFlowLi must be Element.');
		if(typeof disabled !== 'boolean') throw new TypeError('disabled must be bool.');
		
		//入力テキストボックスを使えなくする。
		objFlowLi.querySelectorAll(".ISFL-user-item__input input")
		.forEach(function(obj){ obj.disabled = disabled; }); 
		//ボタンを使えなくする
		objFlowLi.querySelectorAll(".ISFL-user-item-choice__buttons button")
		.forEach(function(obj){ obj.disabled = disabled; });
		//ラジオボタンを使えなくする
		objFlowLi.querySelectorAll(".ISFL-user-item-choice__selection input")
		.forEach(function(obj){ obj.disabled = disabled; });
	}

	/**private:
	 * 上部の機能部分の中の入力・選択項目をDisabledにする。
	 * @param {boolean} disabled - trueのとき使えなくする。
	 */
	_disableFunctionButtons(disabled){
		if(typeof disabled !== 'boolean') throw new TypeError('disabled must be bool.');
		//対象オブジェクト取得
		let objFunction = this.findElementByDataId('user_flows_function');
		//ボタンや入力を使えなくする。
		objFunction.find("input,textarea,button").prop('disabled', disabled);
	}

	/**protected:
	 * タイトル部分とフロー部分（履歴の内容で表示）の反映。
	 * 切り分けフローを結果オブジェクトに従って切り分けフロー結果を反映する。
	 * 完了するとresultの結果が画面のフローにすべて入力項目・選択項目ともに反映される。
	 * @param {IsolationFlowUser~results} results
	 */
	_proceedUserFlow(results){
		//タイトル部分の表示
		this.displayGroupTitle(results);
		//フローID取得
		let flowId = "" + this.data.user_flows.start_flow_id;
		//表示をクリアする
		this.findElementByDataId('isolation_flow_user.list').empty();
		//開始のフロー追加
		let flowData = this.getFlowDataById(flowId);
		let objFlowLi = this.addUserFlow(flowData);
		//以降のフローを追加
		for(let line of results['result']){
			let choiceId = line['choice_id'];
			let decidedButton = line['decided_button'];
			//ボタンが押されていない場合もしくは途中終了した場合はbreak。
			if(choiceId == "" || decidedButton == "" || decidedButton == "close_forcely") break;
			let inputValues = line['input'];
			let choice = flowData['choices'].find(element => element['id'] == choiceId);
			//ラジオボタンを選択
			let objRadio = objFlowLi.querySelector('input[data-choice_id="' + choiceId + '"]');
			objRadio.click();
			//入力項目を入力
			for(let input of inputValues){
				if(!input) continue;
				let objInputValue = objFlowLi.querySelector('input[name="' + input.no + '"]');
				if(objInputValue) objInputValue.value = input.value;
			}
			//開始終了日時のHTMLへの設定
			this.startUtcTimeValByObj(objFlowLi, line.start_utc_time);
			this.endUtcTimeValByObj(objFlowLi, line.end_utc_time);
			//ボタンを押していなかった場合、終わり
			if(!decidedButton) break;
			//ボタンを使えないようにする。
			this._disableFlowButtons(objFlowLi, true);
			//次の遷移先のFlowIDが見つからない場合は中断
			if(typeof choice === 'undefined') break;
			let nextFlowId = choice['next_flow_id'];
			//ボタンを押したときのHTML処理
			this.transactFlowDecideToHtml(objFlowLi, decidedButton);
			//次のフローを準備
			flowId = nextFlowId;
			flowData = this.getFlowDataById(flowId);
			objFlowLi = this.getLastFlowObj();
		}
		//コピーしてresultに設定
		this.results = JSON.parse(JSON.stringify(results));
		
		//resultが完結(resolved)していない場合のみ最後のフローのボタンを使えるようにする。
		if(results.status != 'resolved'){
			//最後のフローのボタンを使えるように
			this._disableFlowButtons(null, false);
		}
	}

	/**protected:
	 * 切り分けフロー結果のデータ形式をチェックする。
	 * @return {String} - エラーメッセージ。nullのときエラーなし。
	 */
	_checkResult(results){
		let errMsg = null;
		if(!(results.status == 'created'
		 || results.status == 'open'
		 || results.status == 'resolved')){
			//"flow_idは数字です。"
			errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false, 'OBJ.RESULTS.STATUS', 'TYPE.DEFINED_STRING');
		}else if(typeof results.remarks === 'number'){
			errMsg = this.getMsg('HTML.ERR.PARAM_INVALID', false, 'OBJ.RESULTS.REMARKS', 'TYPE.STRING');
		}
		return errMsg;
	}

	/**protected:
	 * フローの終了処理(派生クラスでオーバーライドする)
	 * @param {Object.<String, Object>} info - メール情報
	 * @property {Object.<String,Object>} info.mail_info - メール情報
	 * @property {boolean} mail_info.send_mail - メール送信するかどうか
	 * @return {Promise} - 実行するPromise。失敗時はrejectにすること。
	 */
	endFlows(info){
		return jQuery.Deferred().resolve().promise();
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
		});
		return obj;
	}

	/**public:
	 * 結果すべてを文字列にする
	 * @param {Object.<String, boolean>} prop - 出力文字列の表示表示の制御。
	 * @property {boolean} [prop.pt_id] - パターンIDを出力するか？
	 * @property {boolean} [prop.title] - タイトルを出力するか？
	 * @property {boolean} [prop.question] - 質問を出力するか？
	 * @property {boolean} [prop.input] - 入力値を出力するか？
	 * @property {boolean} [prop.choice] - 選択値を出力するか？
	 * @return {String} 作成した文字列
	 */
	createResultsString(prop){
		if(!(prop instanceof Object)) throw new TypeError('prop must be Object');
		let ret = "";
		for(let result of this.results.result){
			let str = this._createReesultString(result, prop);
			ret += str;
		}
		return ret;
	}

	/**protected:
	 * 結果の1つresultを文字列化する。
	 * @param {Object.<String, Object>} result - １つのresult
	 * @param {Object.<String, boolean>} prop - 出力文字列の表示表示の制御。
	 * @return {String} 作成した文字列
	 */
	_createReesultString(result, prop){
		if(typeof result.flow_id === 'undefined') throw new TypeError('result must be results.result');
		let str = "";
		let flowId = result.flow_id;
		let flowData = this.getFlowDataById(flowId);
		if(prop.pt_id == true) str += "[" + flowData.pt_id + "] ";
		if(prop.title != false) str += "<" + flowData.title + ">\n";
		if(prop.question != false) str += flowData.question + "\n";
		if(prop.input == true){
			for(let input of flowData.input){
				let val = result.input.find(element => element.no == input.no);
				str += '  ' + input.label + ' => ' + val + '\n';
			}
		}
		if(prop.choice != false){
			let choice = flowData.choices.find(element => element.id == result.choice_id);
			let label = (!choice ? '' : choice.label);
			str += '###' + label + '\n';
		}
		str += '----------------------------\n';
		return str;
	}
};








/**
 * サーバと通信し、データの取得や結果の保存などの処理も行う切り分けフロー実行クラス。
 * <pre>
 * 【必要なjs】
 *  ・isolation_flow_editor_canvas.js
 * </pre>
 * @class
 * @extends ISFL.IsolationFlowUser
 */
ISFL.IsolationFlowUserExec = class extends ISFL.IsolationFlowUser{
	/**
	 * @constructor
	 * @param {String} id - HTMLのノードID（切り分けフロー）
	 * @param {String} xWpNonce - WPのNonce
	 * @param {IsolationFlowEditor~flows} data - フローデータ。最初からデータを設定しない場合はnull。
	 * @param {Objcet} templateIds - 
	 */
	constructor(id, xWpNonce, data, templateIds) {
		super(id, xWpNonce, data, templateIds);

		//テンプレート設定
		this.setTemplate('dialog_flow_group_list', '#ISFL_editor_dialog_flow_group_list', undefined);

		/**
		 * 結果オブジェクトをサーバに保存するかどうか。
		 */
		this.canSaveResult = true;
	}
	
	/**
	 * サーバに切り分け結果の保存するかどうかのフラグを設定する。
	 */
	setCanSaveResult(bl){
		if(!(typeof bl === 'boolean')) throw new TypeError('bl must be boolean.');
		this.canSaveResult = bl;
	}


	/**
	 * フローのリストをクリアして、フローをロードして開始する。
	 * 結果IDもこのタイミングで取得する。
	 * @param {Number} isflId - 切り分けフローID
	 */
	importFromServerByIsflId(isflId) {
		let self = this;
		self.findElementByDataId('isolation_flow_user.list').empty();
		//フローのロード(フローの最初から切り分け開始）
		this._requestGetFlowGroup(isflId);
	}

	/**
	 * フローのリストをクリアして、フローをロードして開始する。
	 * 結果IDもこのタイミングで取得する。
	 * @param {Number} resultId - 結果ID
	 */
	importFromServerByResultId(resultId) {
		let self = this;
		self.findElementByDataId('isolation_flow_user.list').empty();
		//途中まで実行したフローで開始
		this._requestFlowResultById(resultId);
	}
	
	/**override:
	 * 故障切り分け結果をサーバに保存する。
	 * @return {Promise} - サーバリクエスト（切り分け結果保存）
	 */
	afterUpdateResults(){
		let self = this;
		return this.requestSaveResult()
		.fail(function(data){
			//エラーの場合はフローをロールバックする
			self.goBackFlow();
		});
	}

	/**override:
	 * @return {Promise} - サーバリクエスト（切り分け結果保存）
	 */
	afterOnClickUpdateRemarks(){
		return this.requestSaveResult();
	}

	/**
	 * resultをサーバに保存する
	 * @param {Object.<String, Object>} info - 
	 * @return {Promise} - サーバリクエスト（切り分け結果保存）
	 */
	endFlows(info){
		let self = this;
		return this.requestSaveResult(info);
	}

	/**
	 * 切り分けフローをサーバから取得する
	 * @param {Number} isflId - 切り分けフローID
	 * @return {Promise} flow_results新規生成、flow_group取得リクエストと結果の設定をするProcess。
	 */ 
	_requestGetFlowGroup(isflId){
		let self = this;
		let data = {isfl_id: isflId};
		//
		return jQuery.ajax({
			url       : self.API_INFO.uriPrefix + "/flow_results",
			method    : 'POST', 
			data      : JSON.stringify(data),
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': this.xWpNonce
			}
		}).then(function(flowResult){
			self.results = flowResult;
			let revision = flowResult['revision'];
			return jQuery.ajax({
				url       : self.API_INFO.uriPrefix + "/flow_groups/" + isflId + '/' + revision,
				method    : 'GET', 
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': self.xWpNonce
				}
			});
		}).then(function (json1) {
			//console.log(json1);
			self.data = json1;	
			//最初のフローを表示する
			self.displayMainContainer();
		}).fail(function (data) {
			//console.log(data.responseJSON);
			self.transactJsonErrByAlert(data);
		});
	}
	
	/**
	 * 結果IDのフロー結果を取得し、設定する
	 * @param {Number} resultId - 切り分けフロー結果ID
	 * @return {Promise} flow_results取得、flow_group取得リクエストと結果の設定をするProcess。
	 */
	_requestFlowResultById(resultId){
		let self = this;
		let objResult = null;
		return jQuery.ajax({
			url       : self.API_INFO.uriPrefix + "/flow_results",
			method    : 'GET', 
			data      : {
							'result_id': resultId
						},
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-WP-Nonce': self.xWpNonce
			}
		}).then(function (json) {
			if(json['amount'] == 0){
				alert('Unexpected Error.');
				return;
			}
			objResult = json['list'][0];
			let isfl_id = objResult['isfl_id']; 
			let revision = objResult['revision']; 
		//	self.closeModalDialog();
			return jQuery.ajax({
				url       : self.API_INFO.uriPrefix + "/flow_groups/" + isfl_id + '/' + revision,
				method    : 'GET', 
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': self.xWpNonce
				}
			});
		}).then(function (json1) {
			//console.log(json1);
			self.data = json1;
			//切り分け結果情報を元にフローを先に進める
			let results =objResult;
			
			//切り分け結果のサーバ保存を中止
			self.setCanSaveResult(false);
			try{
				//故障切り分け結果を反映する
				self._proceedUserFlow(results);
				//上部機能部分を表示する。
				self.displayFunction();
			}finally{
				self.setCanSaveResult(true);
			}
		}).fail(function(data){
			self.transactJsonErrByAlert(data);
		});
	}


	/**
	 * サーバにFlow結果を保存する。
	 * @param {Object.<String, Object>} [info] - メール情報
	 * @return {Promise} サーバ保存リクエスト処理のPrimiseを返す。
	 */
	requestSaveResult(info){
		if(!this.canSaveResult){
			return jQuery.Deferred().resolve({'status':'success'}).promise();
		}
		let self = this;
		let results = this.results;
		let data = {"results": results};
		if(typeof info !== 'undefined') data['mail_info'] = info.mail_info;
		//処理を後で書く
		return jQuery.ajax({
			url       : self.API_INFO.uriPrefix + "/flow_results/"+results.result_id,
			method    : 'POST', 
			data      : JSON.stringify(data),
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': this.xWpNonce
			}
		}).fail(function (data) {
			//console.log(data.responseJSON);
			self.transactJsonErrByAlert(data);
		});
	}
	
};
