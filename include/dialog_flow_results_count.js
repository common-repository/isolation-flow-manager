
/**
 * @class
 * @extends ISFL.Dialog
 * 切り分け実行結果を統計表示するポップアップ画面を表示・制御するダイアログクラス。
 * <pre>
 * 【必要なjs】
 *  ・dialog.js
 * 【使用できるHTMLテンプレート】
 *  ・inc_tmpl_dialog_count_flow_results.php
 * </pre>
 */
ISFL.DialogFlowResultsCount = class extends ISFL.Dialog{
	/**
	 * @constructor 
	 * @param {String} rootHtmlId - ルートのID。
	 * @param {String} xWpNonce - Rest通信するときのナンス。
	 * @param {String} dialogDataId - ダイアログSectionタグを指定。data-id属性で指定。
	 * @param {String} templateSelector - HTMLテンプレートを指定するセレクタ。nullのときデフォルト値'#ISFL_editor_dialog_flow_group_list'。
	 * @param {String} listTemplateSelector - 動的にリスト表示するHTMLテンプレートを指定するセレクタ。
	 * @param {ISLF_Dialog~prop} prop - プロパティ。このクラス用に以下の追加プロパティあり。
	 */
	constructor(rootHtmlId, xWpNonce, dialogDataId, templateSelector, listTemplateSelector, prop){
		if(typeof rootHtmlId !== "string" || typeof xWpNonce !== "string"){
			throw new TypeError("rootHtmlId, xWpNonce must be String.");
		}
		//if(!(prop.determiningCallback instanceof Function) || typeof prop.determiningCallback === "undefined"){
		//	throw new TypeError("callback must be Function.");
		//}
		super(rootHtmlId, xWpNonce, dialogDataId, templateSelector, prop);
		let self = this;
		
		//
		if(!listTemplateSelector) listTemplateSelector = '#ISFL_editor_dialog_flow_results_count_list';
		
		//1ページのグラフのデータ数
		this.pagingUnit = 5;

		//ボタンなどにクリックイベントなどを追加する。
		this.addEventsFunc(this.addEvents);

		//テンプレート設定
		this.setTemplate('dialog_list', listTemplateSelector, undefined);
	}

	/**
	 * ボタンクリックなどのイベントを登録する。
	 */
	addEvents(dialog){
		//ページングクリックイベント設定
		dialog.findElementByDataId('ISFL_editor_dialog.paging.btn_prev').on("click", function(event){
			let page = event.target.getAttribute("data-page");
			dialog.displayFlowResultsList(page);
		});
		dialog.findElementByDataId('ISFL_editor_dialog.paging.btn_next').on("click", function(event){
			let page = event.target.getAttribute("data-page");
			dialog.displayFlowResultsList(page);
		});
		dialog.findElementByDataId('ISFL_editor_dialog.paging.page').on("click", function(event){
			let page = event.target.value;
			dialog.displayFlowResultsList(page);
		});
		dialog.findElementByDataId('ISFL_editor_dialog.paging.page').on('keypress', function(event){
			if( event.keyCode == 13 ){
				let page = event.target.value;
				dialog.displayFlowResultsList(page);
			}
		});
		dialog.findElementByDataId('ISFL_editor_dialog.searchkeys.cnt_type').on('change', function(event){
			dialog.onChangeCntKind();
		});
		dialog.findElementByDataId('ISFL_editor_dialog.searchkeys.group_title').on('keypress', function(event){
			if( event.keyCode == 13 ){
				dialog.displayFlowResultsList(1);
			}
		});
		dialog.findElementByDataId('ISFL_editor_dialog.searchkeys.created_date_from').on('keypress', function(event){
			if( event.keyCode == 13 ){
				dialog.displayFlowResultsList(1);
			}
		});
		dialog.findElementByDataId('ISFL_editor_dialog.searchkeys.created_date_to').on('keypress', function(event){
			if( event.keyCode == 13 ){
				dialog.displayFlowResultsList(1);
			}
		});
		//検索ボタン
		dialog.findElementByDataId('ISFL_editor_dialog.paging.btn_find').on('click', function(event){
			dialog.displayFlowResultsList();
		});
		//グラフの種類の値に応じて初期状態を変える
		dialog.onChangeCntKind();

		//初回表示
		dialog.displayFlowResultsList(1);
	}

	/**
	 * グラフの種類のコンボボックスの値が変わったとき。
	 * グラフの種類の値に応じてグラフの時間単位コンボボックスをdisabled/abledを切り返す。
	 */
	onChangeCntKind(){
		let cntKind = this.findElementByDataId('ISFL_editor_dialog.searchkeys.cnt_type').val();
		let objCntUnitSelect = this.findElementByDataId('ISFL_editor_dialog.searchkeys.cnt_created_date_unit');
		//時間単位のコンボボックスの制御
		if(cntKind == 'time'){
			objCntUnitSelect.prop('disabled', false);
		}else{
			objCntUnitSelect.prop('disabled', true);
		}
	}

	/**
	 * 切り分け結果の統計を表示
	 */
	displayFlowResultsList(page){
		if(typeof page === 'undefined') page = this.findElementByDataId('ISFL_editor_dialog.paging.page').val();
		page = parseInt(page);
		if(Number.isNaN(page) || page < 1) page = 1;
		//ページング情報取得
		let offset = (page-1) * this.pagingUnit;
		let limit = this.pagingUnit;
		//
		let self = this;
		let cntKind = this.findElementByDataId('ISFL_editor_dialog.searchkeys.cnt_type').val();
		let cntCreatedDateUnit = this.findElementByDataId('ISFL_editor_dialog.searchkeys.cnt_created_date_unit').val();
		let groupTitle = this.findElementByDataId('ISFL_editor_dialog.searchkeys.group_title').val();
		let createdDateFrom = this.findElementByDataId('ISFL_editor_dialog.searchkeys.created_date_from').val();
		let createdDateTo = this.findElementByDataId('ISFL_editor_dialog.searchkeys.created_date_to').val();
		
		//ステータスデータを配列として取得
		let statuses = this.findElements('input[data-id="ISFL_editor_dialog.searchkeys.statuses"]:checked').map(function(){
			return jQuery(this).val();
		}).get();

		//処理中画面
		this.displayAlert();
		//
		return jQuery.ajax({
			url       : self.API_INFO.uriPrefix + "/flow_results/statistics",
			method    : 'GET', 
			data      : {
							'limit': limit,
							'offset': offset,
							'cnt_kind': cntKind,
							'cnt_created_date_unit': cntCreatedDateUnit,
							'group_title': groupTitle,
							'results_statuses': statuses,
							'created_date_from': createdDateFrom,
							'created_date_to': createdDateTo,
						},
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-WP-Nonce': self.xWpNonce
			}
		}).then(function (json) {
			//HTML作成
			let strHtml = self.createHtmlFromTemplate('dialog_list', json);
			//作成した要素を追加
			let objDiv = self.findElementByDataId('ISFL_editor_modal', 'ISFL_editor_dialog.list');
			objDiv.html(strHtml);
			
			//ページングの書き込み
			self.writePaging("ISFL_editor_dialog.paging", json);
		}).fail(function(data){
			self.transactJsonErrByAlert(data);
		}).always(function(){
			self.hideAlert();
		});
	}
}



/**
 * @class
 * CSV作成の汎用クラス。項目を設定するとCSV文字列を作ってくれる。
 * addField()で項目を設定し、make()でCSVを作成。
 * <pre>
 * </pre>
 */
ISFL.MakeCountCsv = class {
	constructor(){
		/**
		 * Jsonを渡すとフィールドに設定すべき項目値を１つ返す関数
		 * @callback ISFL.MakeCountCsv~funcGetField
		 * @param {Array} line - 入力Json。
		 * @return {String} CSVのフィールド値を返す。
		 */

		/**
		 * フィールド値を返す関数を保存しておく関数配列
		 * @type {Array.<ISFL.MakeCountCsv~funcGetField>}
		 */
		this.decoratorFields = [];

		/**
		 * フィールド名を保存しておく配列
		 * @type {Array.<String>}
		 */
		this.names = [];
	}

	/**
	 * 項目の定義を追加する。項目名と、値を取り出す関数を設定する。
	 * @param {String} name - 項目名。CSVのタイトル部分に出力される。
	 * @param {ISFL.MakeCountCsv~funcGetField} func - 値を返す関数。
	 */
	addField(name, func){
		if(typeof name !== 'string'){
			throw new TypeError('name must be String or String.');
		}
		if(!(func instanceof Function)){
			throw new TypeError('func must be Function or String.');
		}
		this.names.push(name);
		this.decoratorFields.push(func);
	}	

	/**
	 * CSVを作成する。
	 * @param {Array.<Object>} list - CSVを作成する基のデータ
	 * @return {String} - 作成したCSV
	 */
	make(list){
		let ret = '';
		let fieldCnt = this.decoratorFields.length;
		if(fieldCnt == 0) throw new Error('You should add fields before make().No fields exist.');
		//ヘッダ
		for(let i = 0; i < fieldCnt; ++i){
			ret += this.names[i];
			if(i != fieldCnt-1){
				ret += ',';
			}else{
				ret += "\r\n";
			}
		}
		//値
		for(let line of list){
			for(let i = 0; i < fieldCnt; ++i){
				let field = this.decoratorFields[i];
				ret += field(line);
				if(i != fieldCnt-1){
					ret += ',';
				}else{
					ret += "\r\n";
				}
			}
		}
		return ret;
	}

	/**
	 * 階層を指定して値を取得する関数。
	 * @param {String} str - jsonの階層を記述。Json階層(例："result[0].input[0].no")を指定すると値を取得する関数を作って追加する。
	 * @return {Function} - 作成したフィールド値作成関数を返す
	 */
	funcVal(str){
		let func = null;
		if(typeof str !== 'string') throw new TypeError('str must be String.');
		eval("func = function(line){ return line."+ str +"; }");
		return func;
	}

	/**
	 * 結果にダブルクォートを付ける関数。
	 * @param {Function} func - jsonの階層を記述。Json階層(例："result[0].input[0].no")を指定すると値を取得する関数を作って追加する。
	 * @return {Function} - 作成したフィールド値作成関数を返す
	 */
	funcQuoteDeco(func){
		if(!(func instanceof Function)) throw new TypeError('func must be Functio.');
		return function(line){ return JSON.stringify(String(func(line))); };
	}

	/**
	 * 条件を満たした数をカウントする関数。
	 * @param {String} strLoop - カウントする配列をJson階層で指定
	 * @param {Function} funcIf - カウントする条件。strLoopで指定した配列の要素を引数にした関数。booleanを返す。
	 * @return {Function} - 作成した関数
	 */
	funcCountIf(strLoop, funcIf){
		if(typeof strLoop !== 'string') throw new TypeError('strLoop must be String.');
		if(!(funcIf instanceof Function)) throw new TypeError('func must be Functio.');
		let funcGetList = null;
		eval("funcGetList = function(line){ return line."+ strLoop +"; }");
		return function(obj){
			let list = funcGetList(obj);
			let cnt = 0;
			for(let line of list){
				if(funcIf(line)) ++cnt;
			}
			return cnt;
		}
	}
	
	/**
	 * 数を足し合わせる関数。
	 * @param {String} strLoop - カウントする配列をJson階層で指定
	 * @param {Function} funcGetNum - 合計する値。strLoopで指定した配列の要素を引数にした関数。Numberを返す。
	 * @return {Function} - 作成した関数
	 */
	funcSum(strLoop, funcSum){
		if(typeof strLoop !== 'string') throw new TypeError('strLoop must be String.');
		if(!(funcSum instanceof Function)) throw new TypeError('func must be Functio.');
		let funcGetList = null;
		eval("funcGetList = function(line){ return line."+ strLoop +"; }");
		return function(obj){
			let list = funcGetList(obj);
			let sum = 0;
			for(let line of list){
				sum += funcSum(line);
			}
			return sum;
		}
	}

	/**
	 * Resultsのinput.valueが指定の条件を満たした数を数える関数。
	 * @param {Number} flow_id - フローID
	 * @param {String} inputNo - input.noの値。
	 * @param {Function} funcValueIf - input.valueのカウント条件関数（return boolean）。
	 *            引数にinput.valueが渡されるのでそれを元に判定する。
	 * @return {Function} - 作成した関数
	 */
	funcCountIfInputValueIs(flow_id, inputNo, funcValueIf){
		return this.funcCountIf('result', function(line){
			if(line.flow_id != flow_id) return false;
			for(let input of line.input){
				if(input.no != inputNo) return false;
				if(funcValueIf(input.value)) return true;
			}
			return false;
		});
	}
	
	/**
	 * Resultsのinput.valueを取得する関数。クォート付き。
	 * @param {Number} flow_id - フローIDを指定。
	 * @param {String} inputNo - input.noの値を指定。
	 * @return {Function} - 作成した関数。出力はクォートがつく。
	 */
	funcInputValueByNo(flow_id, inputNo){
		let ret = function(results){
			for(let result of results.result){
				if(result.flow_id != flow_id) continue;
				for(let input of result.input){
					if(input.no == inputNo) return input.value;
				}
			}
			return "";
		};
		return this.funcQuoteDeco(ret);
	}
	
}







/**
 * @class
 * @extends ISFL.Dialog
 * 切り分け実行結果の統計情報をCSVダウンロードするポップアップ画面を表示・制御するダイアログクラス。
 * <pre>
 * 【必要なjs】
 *  ・dialog.js
 * 【使用できるHTMLテンプレート】
 *  ・inc_tmpl_dialog_download_results_count.php
 * </pre>
 */
ISFL.DialogDownloadResultsCount = class extends ISFL.Dialog{
	/**
	 * @constructor 
	 * @param {String} rootHtmlId - ルートのID。
	 * @param {String} xWpNonce - Rest通信するときのナンス。
	 * @param {String} dialogDataId - ダイアログSectionタグを指定。data-id属性で指定。
	 * @param {String} templateSelector - HTMLテンプレートを指定するセレクタ。nullのときデフォルト値'#ISFL_editor_dialog_flow_group_list'。
	 * @param {String} listTemplateSelector - 動的にリスト表示するHTMLテンプレートを指定するセレクタ。このダイアログクラスでは使用しないが他のダイアログクラスに合わせて引数を用意してはいる。
	 * @param {ISLF_Dialog~prop} prop - プロパティ。このクラス用に以下の追加プロパティあり。
	 */
	constructor(rootHtmlId, xWpNonce, dialogDataId, templateSelector, listTemplateSelector, prop){
		if(typeof rootHtmlId !== "string" || typeof xWpNonce !== "string"){
			throw new TypeError("rootHtmlId, xWpNonce must be String.");
		}
		super(rootHtmlId, xWpNonce, dialogDataId, templateSelector, prop);
		let self = this;
		
		//
		if(!listTemplateSelector) listTemplateSelector = '#ISFL_editor_dialog_download_results_count_list';
		
		/**
		 * 1ページのグラフのデータ数
		 * @type {Number}
		 */
		this.pagingUnit = 100;

		//ボタンなどにクリックイベントなどを追加する。
		this.addEventsFunc(this.addEvents);

		//テンプレート設定
		//this.setTemplate('dialog_list', listTemplateSelector, undefined);
	}

	/**
	 * ボタンクリックなどのイベントを登録する。
	 */
	addEvents(dialog){
		//検索ボタン
		dialog.findElementByDataId('ISFL_editor_dialog.paging.btn_find').on('click', function(event){
			dialog.requestResutls();
		});
		//エクスポートボタン
		dialog.findElementByDataId('ISFL_editor_dialog.btn_export').on('click', function(event){
			let isBom = dialog.findElementByDataId('ISFL_editor_dialog.searchkeys.csv_bom').prop("checked");
			let csvStr = dialog.findElementByDataId('ISFL_editor_dialog.val.csv').val();
			dialog.handleDownload("download.csv", csvStr, isBom);
		});
	}

	display(json){
		//ユーザ情報を設定したjsonを渡す
		json['userRole'] =  this.userRole;
		super.display(json);
	}

	/**
	 * CSV作成クラスを作成する。CSV項目も設定されているのでmake()するだけでCSVファイル作成可能。
	 * @return {ISFL.MakeCountCsv} - CSV作成クラス
	 */
	makeCsvFunc(){
		let self = this;
		let defStatuses = self.getDef('RESULTS_STATUSES');
		let addField1 = this.findElementByDataId('ISFL_editor_dialog.searchkeys.add_field1').val();
		let addField2 = this.findElementByDataId('ISFL_editor_dialog.searchkeys.add_field2').val();
		let addField3 = this.findElementByDataId('ISFL_editor_dialog.searchkeys.add_field3').val();
		let aryAddFields = [addField1, addField2, addField3];
		//
		let ret = new ISFL.MakeCountCsv();
		//[results]
		ret.addField(this.getMsg('OBJ.RESULTS.RESULT_ID'), ret.funcVal('result_id'));
		ret.addField(this.getMsg('OBJ.ISFL_ID')          , ret.funcVal('isfl_id'));
		ret.addField(this.getMsg('OBJ.RESULTS.STATUS')   , ret.funcQuoteDeco(
			function(line){
				return defStatuses[line.status];
			})
		);
		ret.addField(this.getMsg('OBJ.RESULTS.CREATED_DATE'), ret.funcQuoteDeco(ret.funcVal('created_date')));
		ret.addField(this.getMsg('OBJ.RESULTS.TIME')     , ret.funcSum('result', function(line){
			if(line.end_utc_time==null || line.start_utc_time==null) return 0;
			return Math.floor((line.end_utc_time - line.start_utc_time)/1000);
		}));
		ret.addField(this.getMsg('OBJ.RESULTS.REMARKS')  ,  ret.funcQuoteDeco(ret.funcVal('remarks')));
		ret.addField(this.getMsg('OBJ.FLOW.FLOW_ID')+'*', ret.funcCountIf('result', function(line){
			return true;
		}));
		//追加項目
		for(let field of aryAddFields){
			if(!field) continue;
			let ary = field.split('-');
			if(ary.length != 2) continue;
			ret.addField(this.getMsg('OBJ.RESULTS.RESULT.INPUT.VALUE') + field , 
				ret.funcInputValueByNo(ary[0], ary[1]));
		}
		return ret;
	}


	/**
	 * 切り分け結果を取得する。最大5回繰り返し、データをマージした結果をCSVにし、表示する。
	 */
	requestResutls(){
		//ページング情報取得
		let page = 1;
		let offset = (page-1) * this.pagingUnit;
		let limit = this.pagingUnit;
		//
		let self = this;
		let results_statuses = this.findElementByDataId('ISFL_editor_dialog.searchkeys.results_statuses').val();
		let groupTitle = this.findElementByDataId('ISFL_editor_dialog.searchkeys.group_title').val();
		let createdDateFrom = this.findElementByDataId('ISFL_editor_dialog.searchkeys.created_date_from').val();
		let createdDateTo = this.findElementByDataId('ISFL_editor_dialog.searchkeys.created_date_to').val();
		
		//ステータスデータを配列として取得
		let statuses = this.findElements('input[data-id="ISFL_editor_dialog.searchkeys.statuses"]:checked').map(function(){
			return jQuery(this).val();
		}).get();

		//処理中画面
		this.displayAlert();
		
		//
		let csvData = [];
		let amount = 0;
		return jQuery.ajax({
			url       : self.API_INFO.uriPrefix + "/flow_results",
			method    : 'GET', 
			data      : {
							'limit': limit,
							'offset': offset,
							'user_only': false,
							'group_title': groupTitle,
							'results_statuses': statuses,
							'created_date_from': createdDateFrom,
							'created_date_to': createdDateTo,
						},
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-WP-Nonce': self.xWpNonce
			}
		}).then(function(json) {
			csvData = json.list;
			amount = json['amount'];
			let cnt = Math.floor(amount/limit) + 1;
			let promise = self.createTimeoutPromise(500);
			//最大5回までリクエストする
			for(let i = 0; i < cnt && i < 5; ++i){
				promise = promise.then(function(json) {
					if(typeof json !== 'undefined'){
						csvData = csvData.concat(json.list);
					}
					offset += limit;
					return jQuery.ajax({
						url       : self.API_INFO.uriPrefix + "/flow_results",
						method    : 'GET', 
						data      : {
										'limit': limit,
										'offset': offset,
										'group_title': groupTitle,
										'results_statuses': statuses,
										'created_date_from': createdDateFrom,
										'created_date_to': createdDateTo,
									},
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded',
							'X-WP-Nonce': self.xWpNonce
						}
					});
				});
			}
			return promise;
		}).then(function(){
			//結果取得できた件数
			let strCsvResult = csvData.length + ' / ' + amount;
			self.findElementByDataId('ISFL_editor_dialog.val.strCsvResult').html(strCsvResult);
			//CSV生成関数取得
			let makeCsvFunc = self.makeCsvFunc();
			let csvStr = "";
			csvStr = makeCsvFunc.make(csvData);
			//textareaにCSVを設定
			self.findElementByDataId('ISFL_editor_dialog.val.csv').val(csvStr);
		}).fail(function(data){
			self.transactJsonErrByAlert(data);
		}).always(function(){
			self.hideAlert();
		});
	}
}
