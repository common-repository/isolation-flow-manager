

/**
 * ISFL.IsolationFlowEditorのダイアログ画面を表示・制御する基底クラス。
 * ダイアログ表示中にCanvasを操作可能にする機能もある。
 * <pre>
 * 【必要なjs】
 *  ・IsolationFlowCommon.js
 * 
 * 【ダイアログHTML】'ISFL_editor_modal'は任意。
 * 	&lt;input type="checkbox" id="ISFL_editor_modal_switch" data-id="ISFL_editor_modal_switch" class="ISFL-editor-modal-switch">
 * 	&lt;section data-id="ISFL_editor_modal" class="ISFL-editor-modal-overlay">
 * 		&lt;div class="ISFL-editor-modal-content">
 * 			&lt;header>
 * 				&lt;label for="ISFL_editor_modal_switch" class="icon-window-close">&lt;/label>
 * 				&nbsp;&nbsp;
 * 				&lt;span data-id="ISFL_editor_modal.title">&lt;/span>
 * 			&lt;/header>
 * 			&lt;div data-id="ISFL_editor_modal.body" class="ISFL-editor-modal-content--body">
 * 			&lt;/div>
 * 		&lt;/div>
 * 	&lt;/section>
 *   
 * </pre>
 */
ISFL.Dialog = class extends ISFL.IsolationFlowCommon{
	/**
	 * @constructor 
	 * @param {String} rootHtmlId - ルートのHTMLを指定。ID属性で指定。
	 * @param {String} xWpNonce - 通信時のナンス。
	 * @param {String} dialogDataId - ダイアログを指定する。data-id属性で指定。
	 * @param {String} templateSelector - 
	 * @param {ISFL.Dialog~prop} prop - プロパティ
	 */
	constructor(rootHtmlId, xWpNonce, dialogDataId, templateSelector, prop){
		if(typeof rootHtmlId === "undefined"){
			throw new TypeError("rootHtmlId must be String.");
		}
		super(rootHtmlId, xWpNonce);
		
		//パラメタチェック
		if(typeof dialogDataId !== "string") throw new TypeError("dialogDataId must be String.");
		if(typeof templateSelector === "undefined") templateSelector = '#ISFL_editor_dialog_img_list';
		if(typeof prop === "undefined") throw new TypeError("prop must have value.");
		if(typeof prop.title !== "string") throw new TypeError("prop.title must be string.");
		if(typeof prop.closingCallback !== "undefined" && !(prop.closingCallback instanceof Function)){
			throw new TypeError("prop.closingCallback must be Function.");
		}
		
		/**
		 * ダイアログのsectionタグのdata-id。この値に"_switch"を付けたdata-id属性がcheckboxになっていること。
		 * @type {String}
		 */
		this.dialogDataId = dialogDataId;
		
		/**
		 * ダイアログのswitch用checkbox。data-id属性で指定。
		 * @type {String}
		 */
		this.dialogSwitchDataId = dialogDataId + "_switch";
		
		/**
		 * @typedef {Object.<String, Object>} ISFL.Dialog~prop
	 	 * @property {Number} prop.title - ダイアログのタイトル
	 	 * @property {Number} [prop.width] - ダイアログの幅
		 * @property {Number} [prop.height] - ダイアログの高さ
		 * @property {Number} [prop.top] - ダイアログの表示位置（ウィンドウの上からの位置）
		 * @property {Number} [prop.left] - ダイアログの表示位置（ウィンドウの左からの位置）
		 * @property {Number} [prop.right] - ダイアログの表示位置（ウィンドウの右からの位置）
		 * @property {bool}   [prop.hideCloseButton] - 閉じるボタンを隠すかどうか
		 * @property {Function} [prop.closingCallback] - 閉じるときに呼び出すコールバック
		 */
		/**
		 * プロパティ。
		 * @type {Dialog~prop}
		 */
		this.prop = prop;
		
		/**
		 * 閉じるボタンを押したときのコールバック関数。引数は、this。
		 * @type {Function}
		 */
		this.closingCallback = prop.closingCallback;
		
		/**
		 * クリックなどのイベントの設定用コールバック関数。引数は、this。
		 * @type {Function}
		 */
		this.eventCallback = function(objDialog){};
		
		/**
		 * ドラッグ移動するための情報を保存しておく領域
		 */
		this.objDragInfo = {stage: null, mouseDownPos: null};
		
		//テンプレート設定
		this.setTemplate('content', templateSelector, undefined);
		
	}
		
	/**
	 * ダイアログを閉じる時に呼ばれるコールバック関数を設定する。
	 * @param {Function} func - コールバック関数。引数は、ダイアログ自身this。
	 */
	setClosingCallback(func){
		this.closingCallback = func;
	}
	
	/**
	 * イベント追加。{@link Dialog#displayModalDialog}内で呼ばれ、ボタン等のクリックイベント設定するための関数。
	 * @param {Function} eventCallback - 内部でclick等のイベント登録をする。引数はthis。
	 */
	addEventsFunc(eventCallback){
		this.eventCallback = eventCallback;
	}
	
	/** protected:
	 * モーダルダイアログを表示。
	 * @param {Object} json - テンプレートに渡すデータJson
	 */
	displayModalDialog(json){
		let prop = this.prop;
		let title = this.prop.title;
		let templateName = 'content';
		let self = this;
		let objModal = this.findElementByDataId(this.dialogDataId)
			.find(".ISFL-editor-modal-content");
		//CSSデフォルト
		objModal.css("width" , "70%");
		objModal.css("height", "70%");
		objModal.css("top"   , "20%");
		objModal.css("left"  , "20%");
		
		//closeボタンを表示
		objModal.find(".icon-window-close").css("display", "");
		
		//プロパティによるCSS設定
		if(typeof prop !== "undefined"){
			if(typeof prop.width !== "undefined") objModal.css("width", prop.width);
			if(typeof prop.height !== "undefined") objModal.css("height", prop.height);
			if(typeof prop.top !== "undefined") objModal.css("top", prop.top);
			if(typeof prop.left !== "undefined") objModal.css("left", prop.left);
			if(typeof prop.right !== "undefined") objModal.css("right", prop.right);
			if(typeof prop.hideCloseButton !== "undefined" && prop.hideCloseButton){
				objModal.find(".icon-window-close").css("display", "none");
			}
		}
		
		//閉じるボタンに関数を設定する。
		objModal.find(".icon-window-close").off("click");
		objModal.find(".icon-window-close").on("click", function(event){ 
			event.preventDefault();
			self.closeModalDialog(); 
		});
		
		//タイトルをクリックし、ダイアログをドラッグできるようにする
		this._addDragEvent(objModal);
		
		//ダイアログ表示用のradioにチェック入れる
		this.findElementByDataId(this.dialogSwitchDataId).prop('checked', true);
		
		//HTML作成
		let html = this.createHtmlFromTemplate(templateName, json);
		this.findElementByDataId('ISFL_editor_modal.title').html(title);
		this.findElementByDataId('ISFL_editor_modal.body').html(html);
		
		//イベント登録
		this.eventCallback(this);
	}
	
	/** private:
	 * タイトルをクリックするとドラッグできるようにイベント追加する。
	 * @param {Element} objModal - JQuery要素オブジェクト。ダイアログ。
	 */
	_addDragEvent(objModal){
		let self = this;
		let objHeader = objModal.find("header")[0];
		if(!objHeader) throw new Error("header tag dose not exist.");
		objHeader = jQuery(objHeader);
		//マウスアップしたときの動作
		let funcMouseMove = function(event){
			let info = self.objDragInfo;
			if(info.mouseDownPos == null) return;
			event.preventDefault();
			let dx = info.mouseDownPos.x - event.clientX;
			let dy = info.mouseDownPos.y - event.clientY;
			objModal.offset({
				top: info.mouseDownPos.scrollTop - dy, 
				left: info.mouseDownPos.scrollLeft - dx 
			});
			return false;
		};
		//マウスアップしたときの動作
		let funcMouseUp = function(event){
			self.objDragInfo.mouseDownPos = null;
			objBody.off("mouseup");
			objBody.off("mousedown");
		};
		//
		let objBody = jQuery(document.body);
		objHeader.off("mousedown");
		objHeader.on("mousedown", function(event){
			let info = self.objDragInfo;
			let offset = objModal.offset();
			//マウスダウンした位置を保存
			info.mouseDownPos = {
				x: event.clientX, y: event.clientY, 
				scrollLeft: offset.left, scrollTop: offset.top
			};
			//マウス移動時の処理を追加
			objBody.on("mousemove", funcMouseMove);
			objBody.on("mouseup", funcMouseUp);
		});
	}
	
	/** public
	 * 子クラスで実装
	 * @param {Object} json - テンプレートに渡すデータJson
	 */
	display(json){
		this.displayModalDialog(json);
	}
	
	/** protected:
	 * Dialogを隠す。非表示にする。
	 */
	hideModalDialog(){
		//ダイアログ表示用のradioにチェックはずす
		this.findElementByDataId(this.dialogSwitchDataId).prop('checked', false);
	}
	
	/**
	 * Modalダイアログを閉じる
	 */
	closeModalDialog(){
		//Canvasクリック関数の解除
		//this.setModalCanvasClick(null);
		
		try{
			//閉じるボタンを押したときののコールバック
			if(this.closingCallback) this.closingCallback(this);
		}finally{
			//CanvasをOverlayの上に表示を解除
			this.moveCanvasOntoOverlay(false);
			//ダイアログを隠す
			this.hideModalDialog();
		}
	}
	
	/**
	 * Modalダイアログ表示時にCanvasをOverlayの上に表示する。
	 */
	moveCanvasOntoOverlay(isOverlay){
		let objCanvasDiv = this.findElements("#" + this.canvasId);
		if(typeof isOverlay === "undefined") isOverlay = true;
		if(isOverlay){
			//Overlayの上に表示する
			objCanvasDiv.addClass("ISFL-editor-modal-overlay--front");
		}else{
			//Overlayの上に表示を解除する
			objCanvasDiv.removeClass("ISFL-editor-modal-overlay--front");
		}
	}

}








/**
 * @class
 * 切り分けフローを選択するポップアップ画面を表示・制御するクラス。
 * <pre>
 * 【必要なjs】
 *  ・dialog.js
 * 【使い方】
 ISFL.DialogSampleList = class extends ISFL.DialogListBase{
	displayList(page, textName){
		let self = this;
		let title = this.findElementByDataId('title');
		//
		return jQuery.ajax({
			url       : self.API_INFO.uriPrefix + "/images?title=" + title,
			method    : 'GET', ・・・
		}).fail(function(data){
			self.transactJsonErrByAlert(data);
		});
	}
 }
let dialog = new ISFL.DialogSampleList(
	'#ISFL_isolation_flow_user', "<?php echo wp_create_nonce( 'wp_rest' ); ?>", 
	'ISFL_editor_modal', '#ISFL_dialog_select_image', 
	{
		'title': '<?= $msg('HTML.DIALOG.TITLE.SELECT_RESULT') ?>'
	}
);
dialog.set(function(dialog){
	let imageId = dialog.findElementByDataId('image_id').val();
	dialog.closeModalDialog();
	return true;
});
 * </pre>
 */
ISFL.DialogListBase = class extends ISFL.Dialog{
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
		if(!listTemplateSelector) throw new TypeError('listTemplateSelector must have value.');
		
		/**
		 * 切り分けフロー選択後に呼ばれるコールバック関数
		 * @callback {Function} ISLF.DialogListBase~selectCallback
		 * @param {Dialog} dialog - ダイアログ。このクラスを渡す。
		 * @return {bool} - falseのときダイアログは閉じない。それ以外は閉じる。
		 */
		
		/**
		 * 選択決定後に呼ばれるコールバック関数
		 * @type {ISLF.DialogListBase~selectCallback}
		 */
		this.callbackFunc = null;
		
		//ボタンなどにクリックイベントなどを追加する。
		this.addEventsFunc(this.addEvents);

		//テンプレート設定
		this.setTemplate('dialog_list', listTemplateSelector, undefined);
		
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

		//すべてのテキストボックスにリターンキーのイベントを追加する
		let objTexts = self.findElementByDataId('ISFL_editor_dialog.paging').find('input[type="text"]');
		for(let objText in objTexts){
			objText.on('keypress', function(event){
				let dataId = event.target.getAttribute('data-id');
				if( event.keyCode == 13 ){
					self.displayFlowGroupList(1, dataId);
				}
			});
		}
		
		//決定ボタンの処理
		self.findElementByDataId('ISFL_editor_dialog.btn_determin').on('click', function(event){
			let promise = self.onClickDetermin(event);
			if(!promise) return;
			promise.then(function (json) {
				//HTML作成
				let strHtml = self.createHtmlFromTemplate('dialog_list', json);
				//作成した要素を追加
				let objDiv = self.findElementByDataId(self.dialogDataId, 'ISFL_editor_dialog.float_list');
				objDiv.html(strHtml);
				
				//ページングの書き込み
				self.writePaging("ISFL_editor_dialog.paging", json);
			}).fail(function(data){
				self.transactJsonErrByAlert(data);
			});
		});

		//初回表示
		self.displayFlowGroupList(1);
	}

	/** protected: 
	 * @param {Event} event - イベントオブジェクト
	 */
	onClickDetermin(event){
		if(!this.callbackFunc(this)) return;
		this.closeModalDialog();
	}

	/** protected abstract 
	 * リスト表示。派生クラスで実装すること。
	 * @param {Number} page - 表示するページ番号（1～）
	 * @param {String} [textDataId] - リターンキーを押したテキストのdata-id属性値。テキストボックスではなかった場合はundefined。
	 * @return {Promise} - リストテンプレートに反映させるJsonを取得するPromise。nullの場合、リストのHTMLには反映させない。
	 */
	displayList(page, textName){
		
	}

}

