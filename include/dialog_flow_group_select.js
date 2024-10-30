
/**
 * @class
 * @extends ISFL.Dialog
 * 切り分けフローを選択するポップアップ画面を表示・制御するクラス。
 * <pre>
 * 【必要なjs】
 *  ・dialog.js
 * 【使用できるHTMLテンプレート】
 *  ・inc_tmpl_dialog_select_flow_group.php
 * </pre>
 */
ISFL.DialogFlowGroupsSelect = class extends ISFL.Dialog{
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
		if(!listTemplateSelector) listTemplateSelector = '#ISFL_editor_dialog_flow_group_list';
		
		/**
		 * 切り分けフロー選択後に呼ばれるコールバック関数
		 * @callback {Function} ISLF.DialogFlowGroupSelect~selectCallback
		 * @param {String} isflId - 切り分けフローID
		 * @param {String} revision - 切り分けフローリビジョン
		 */
		
		/**
		 * 選択決定後に呼ばれるコールバック関数
		 * @type {ISLF.DialogFlowGroupsSelect~selectCallback}
		 */
		this.callbackFunc = null;
		
		//ボタンなどにクリックイベントなどを追加する。
		this.addEventsFunc(this.addEvents);

		//テンプレート設定
		this.setTemplate('dialog_flow_group_list', listTemplateSelector, undefined);
		
	}

	/** public:
	 * 決定選択後に呼ばれるコールバック関数を設定する。
	 * @param {ISLF.DialogImageSelect~selectCallback} func - コールバック関数
	 */
	setDeterminingCallback(func){
		this.callbackFunc = func;
	}
	
	/**
	 * ボタンクリックなどのイベントを登録する。
	 */
	addEvents(dialog){
		let self = this;
		
		//ページングクリックイベント設定
		self.findElementByDataId('ISFL_editor_dialog.paging.btn_prev').on("click", function(event){
			let page = event.target.getAttribute("data-page");
			self.displayFlowGroupList(page);
		});
		self.findElementByDataId('ISFL_editor_dialog.paging.btn_next').on("click", function(event){
			let page = event.target.getAttribute("data-page");
			self.displayFlowGroupList(page);
		});
		self.findElementByDataId('ISFL_editor_dialog.paging.page').on("click", function(event){
			let page = event.target.value;
			self.displayFlowGroupList(page);
		});
		self.findElementByDataId('ISFL_editor_dialog.paging.page').on('keypress', function(event){
			if( event.keyCode == 13 ){
				let page = event.target.value;
				self.displayFlowGroupList(page);
			}
		});
		self.findElementByDataId('ISFL_editor_dialog.paging.btn_find').on('keypress', function(event){
			if( event.keyCode == 13 ){
				let page = event.target.value;
				self.displayFlowGroupList(page);
			}
		});
		self.findElementByDataId('ISFL_editor_dialog.searchkeys.group_title').on('keypress', function(event){
			if( event.keyCode == 13 ){
				self.displayFlowGroupList(1);
			}
		});
		self.findElementByDataId('ISFL_editor_dialog.searchkeys.keywords').on('keypress', function(event){
			if( event.keyCode == 13 ){
				self.displayFlowGroupList(1);
			}
		});
		self.findElementByDataId('ISFL_editor_dialog.searchkeys.group_remarks').on('keypress', function(event){
			if( event.keyCode == 13 ){
				self.displayFlowGroupList(1);
			}
		});
		//検索ボタン
		self.findElementByDataId('ISFL_editor_dialog.paging.btn_find').on("click", function(event){
			self.displayFlowGroupList();
		});
		//FLow決定の処理
		self.findElementByDataId('ISFL_editor_dialog.btn_determin_flow_group').on('click', function(event){
			self.onClickDetermin(event);
		});
		
		//初回表示
		self.displayFlowGroupList(1);
	}

	/**
	 * @param {Event} event - イベントオブジェクト
	 */
	onClickDetermin(event){
		let obj = this.findElements('input[name="ISFL_editor_dialog.val.isfl_id"]:checked');
		if(obj.length == 0){
			alert(this.getMsg('HTML.ERR.SELECT_TARGET'));
			return;
		}
		let isSuccess = this.callbackFunc(obj.val(), obj.attr('data-revision'));
		if(isSuccess === false) return;
		this.closeModalDialog();
	}

	/**
	 * Flowグループリスト表示
	 */
	displayFlowGroupList(page){
		if(typeof page === 'undefined') page = this.findElementByDataId('ISFL_editor_dialog.paging.page').val();
		page = parseInt(page);
		if(Number.isNaN(page) || page < 1) page = 1;
		//ページング情報取得
		let offset = (page-1) * this.pagingUnit;
		let limit = this.pagingUnit;
		//
		let self = this;
		let groupTitle = this.findElementByDataId('ISFL_editor_dialog.searchkeys.group_title').val();
		let keywords = this.findElementByDataId('ISFL_editor_dialog.searchkeys.keywords').val();
		let groupRemarks = this.findElementByDataId('ISFL_editor_dialog.searchkeys.group_remarks').val();
		
		//処理中アラート表示
		this.displayAlert();

		return jQuery.ajax({
			url       : self.API_INFO.uriPrefix + "/flow_groups",
			method    : 'GET', 
			data      : {
							'offset' : offset,
							'limit' : limit,
							'group_title': groupTitle,
							'keywords': keywords,
							'group_remarks': groupRemarks,
						},
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-WP-Nonce': self.xWpNonce
			}
		}).then(function (json) {
			//HTML作成
			let strHtml = self.createHtmlFromTemplate('dialog_flow_group_list', json);
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

