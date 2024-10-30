

/**
 * @class
 * @extends ISFL.Dialog
 * 画像選択、追加、削除するポップアップ画面を表示・制御するクラス。
 * <pre>
 * 【必要なjs】
 *  ・dialog.js
 * 【使用できるHTMLテンプレート】
 *  ・inc_tmpl_dialog_select_img.php
 * </pre>
 */
ISFL.DialogImageSelect = class extends ISFL.Dialog{
	/**
	 * @constructor 
	 * @param {String} rootHtmlId - ルートのID。
	 * @param {String} xWpNonce - Rest通信するときのナンス。
	 * @param {String} dialogDataId - ダイアログSectionタグを指定。data-id属性で指定。
	 * @param {String} templateSelector - HTMLテンプレートを指定するセレクタ。
	 * @param {Function} callback - 画像を決定したときに呼ばれる。画像のsrcを引数に渡される。
	 * @param {ISLF_Dialog~prop} prop - プロパティ。このクラス用に以下の追加プロパティあり。
	 */
	constructor(rootHtmlId, xWpNonce, dialogDataId, templateSelector, prop){
		if(typeof rootHtmlId !== "string" || typeof xWpNonce !== "string"){
			throw new TypeError("rootHtmlId, xWpNonce must be String.");
		}
		//if(!(prop.determiningCallback instanceof Function) || typeof prop.determiningCallback === "undefined"){
		//	throw new TypeError("callback must be Function.");
		//}
		super(rootHtmlId, xWpNonce, dialogDataId, templateSelector, prop);
		let self = this;
		
		//
		//this.isolationFlowEditor = obj;
		
		/**
		 * 画像選択後に呼ばれるコールバック関数
		 * @callback {Function} ISLF.DialogImageSelect~selectCallback
		 * @param {String} imageSrc - 画像のパス
		 * @param {String} imageId - 画像のID。
		 */
		
		/**
		 * 画像選択後に呼ばれるコールバック関数
		 * @type {ISLF.DialogImageSelect~selectCallback}
		 */
		this.callbackFunc = null;
		
		//テンプレート設定
		this.setTemplate('dialog_img_list', '#ISFL_dialog_img_list', undefined);
		
	}
	
	/**
	 * 画像選択後に呼ばれるコールバック関数を設定する。
	 * @param {ISLF.DialogImageSelect~selectCallback} func - コールバック関数
	 */
	setDeterminingCallback(func){
		this.callbackFunc = func;
	}
	
	/**
	 * 
	 */
	closeModalDialog(){
		this.callbackFunc = null;
		super.closeModalDialog();
	}
	
	/**
	 * 画像選択ボタンをクリックしたときの処理
	 * @param {Object} json - HTMLテンプレートに渡すJson
	 */
	display(json){
		if(this.callbackFunc == null) throw new Error("invoke setDeterminingCallback() and set callback.");
		let self = this;
		this.displayModalDialog(json);
		
		//ページングクリックイベント設定
		this.findElementByDataId('ISFL_editor_dialog.paging.btn_prev').on("click", function(event){
			let page = event.target.getAttribute("data-page");
			self.displayImgList(page);
		});
		this.findElementByDataId('ISFL_editor_dialog.paging.btn_next').on("click", function(event){
			let page = event.target.getAttribute("data-page");
			self.displayImgList(page);
		});
		this.findElementByDataId('ISFL_editor_dialog.paging.page').on("click", function(event){
			let page = event.target.value;
			self.displayImgList(page);
		});
		this.findElementByDataId('ISFL_editor_dialog.paging.page').on('keypress', function(event){
			if( event.keyCode == 13 ){
				let page = event.target.value;
				self.displayImgList(page);
			}
		});
		this.findElementByDataId('ISFL_editor_dialog.paging.btn_find').on("click", function(event){
			self.displayImgList();
		});
		this.findElementByDataId('ISFL_editor_dialog.searchkeys.image_title').on('keypress', function(event){
			if( event.keyCode == 13 ){
				self.displayImgList(1);
			}
		});
		//画像選択の変更時
		this.findElementByDataId('ISFL_editor_dialog.btn_add_image').on('change', function(event){
			self.onChangeImageFile(event);
		});
		//アップロードボタン
		this.findElementByDataId('ISFL_editor_dialog.btn_upload_image').on('click', function(event){
			self.onClickUpload(event);
		});
		//アップロード取消ボタン
		this.findElementByDataId('ISFL_editor_dialog.btn_cancel_upload_image').on('click', function(event){
			self.onClickCancelUpload(event);
		});
		"ISFL_editor_dialog.btn_cancel_upload_image"
		//削除ボタン
		this.findElementByDataId('ISFL_editor_dialog.btn_delete_image').on('click', function(event){
			self.onClickDelete(event);
		});
		//画像決定の処理
		this.findElementByDataId('ISFL_editor_dialog.btn_determin_image').on('click', function(event){
			self.onClickDeterminImage(event);
		});
		
		//最初の表示
		this.displayImgList(1);
	}
	
	/**
	 * 画像一覧HTML表示。検索キーは画面から取得して検索する。
	 * @param {Number} page - 表示するページ
	 * @return {Promise} - 検索リクエストと結果を表示するPromise。
	 */
	displayImgList(page){//次の送信★
		if(typeof page === 'undefined') page = this.findElementByDataId('ISFL_editor_dialog.paging.page').val();
		page = parseInt(page);
		if(Number.isNaN(page) || page < 1) page = 1;
		//ページング情報取得
		let offset = (page-1) * this.pagingUnit;
		let limit = this.pagingUnit;
		//
		let self = this;
		if(this.isSetProcessingFlag()) return null;
		
		//1000ミリ秒間は処理中にして他の処理を受け付けない
		this.setProcessingFlag(1000);
		//
		let imageTitle = this.findElementByDataId("ISFL_editor_dialog.searchkeys.image_title").val();
		//処理中アラート表示
		this.displayAlert();
		//
		return jQuery.ajax({
			url       : self.API_INFO.uriPrefix + "/images",
			method    : 'GET', 
			data      : {
							'offset': offset,
							'limit': limit,
							'image_title': imageTitle,
						},
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-WP-Nonce': self.xWpNonce
			}
		}).then(function (json) {
			//HTML作成
			let strHtml = self.createHtmlFromTemplate('dialog_img_list', json);
			//作成した要素を追加
			let objDiv = self.findElementByDataId(self.dialogDataId, 'ISFL_editor_dialog.float_list');
			objDiv.html(strHtml);
			
			//ページングの書き込み
			self.writePaging("ISFL_editor_dialog.paging", json);
		}).fail(function(data){
			self.transactJsonErrByAlert(data);
		}).always(function(){
			self.hideAlert();
		});
	}
	
	/**
	 * 画像アップロードボタンを押して画像を変更したときのイベント処理
	 */
	onChangeImageFile(event){
		let self = this;
		let objImg = this.findElementByDataId("ISFL_editor_dialog.selected_img")[0];
		if(event.target.files.length == 0) return;
		let filename = event.target.files[0].name;
		let reader = new FileReader();
		reader.onload = function(readerEvent) {
			let objResultImg = new Image();
			objResultImg.onload = function(event){
				let shrinkedImageSrc = self.shrinkImage(objResultImg, 250, 150);
				objImg.src = shrinkedImageSrc;
				objImg.setAttribute("data-filename", filename);
			}
			objResultImg.src = readerEvent.target.result;
		}
		reader.readAsDataURL(event.target.files[0]);
	}
	
	/**
	 * アップロードボタンをクリックされたときの処理
	 */
	onClickUpload(event){
		let objImg = this.findElementByDataId("ISFL_editor_dialog.selected_img")[0];
		let srcWidth = objImg.naturalWidth;
		//画像が選択されているかをチェック
		if(srcWidth == 0){
			alert(this.getMsg('HTML.ERR.SELECT_IMG'));
			return;
		}
		this.uploadImage(objImg);
	}
		
	/**
	 * アップロードキャンセルボタンをクリックされたときの処理
	 */
	onClickCancelUpload(event){
		let objFile = this.findElementByDataId("ISFL_editor_dialog.btn_add_image");
		objFile.val('');
		let objImg = this.findElementByDataId("ISFL_editor_dialog.selected_img")[0];
		objImg.src = '';
	}

	/**
	 * 削除ボタンをクリックされたときの処理
	 */
	onClickDelete(event){
		let objRadio = this.findElements("input[name='ISFL_editor_dialog.val.img_url']:checked")[0];
		if(!objRadio){
			alert(this.getMsg('HTML.ERR.SELECT_IMG'));
			return;
		}
		let src = objRadio.value;
		let attachmentId = objRadio.getAttribute('data-attachment_id');
		//画像を削除する
		this.deleteImage(attachmentId);
	}
	
	/**
	 * 画像を決定して、Modalを閉じる
	 */
	onClickDeterminImage(event){
		let objRadio = this.findElements("input[name='ISFL_editor_dialog.val.img_url']:checked")[0];
		if(!objRadio){
			alert(this.getMsg('HTML.ERR.SELECT_IMG'));
			return;
		}
		let src = objRadio.value;
		let attachmentId = objRadio.getAttribute("data-attachment_id");
		//コールバック実行
		let isSuccess = this.callbackFunc(src, attachmentId);
		if(isSuccess === false) return;
		this.closeModalDialog();
	}
	
	/**
	 * 画像を縮小する
	 * @param {Image} objImg - 画像オブジェクト
	 * @param {Number} maxWidth - 縮小したあとの最大の幅
	 * @param {Number} maxHeight - 縮小したあとの最大の高さ
	 */
	shrinkImage(objImg, maxWidth, maxHeight){
		//シュリンク後の画像の大きさを計算する
		let srcWidth = objImg.naturalWidth;
		let srcHeight = objImg.naturalHeight;
		let scale = 1;
		if(maxWidth < srcWidth) scale = maxWidth / srcWidth;
		if(maxHeight < srcHeight * scale) scale *= maxHeight / (srcHeight * scale);
		
		//縮小する
		let canvas = document.createElement('canvas');
		let ctx = canvas.getContext('2d');
		var dstWidth = srcWidth * scale;
		var dstHeight = srcHeight * scale
		canvas.width = dstWidth;
		canvas.height = dstHeight;
		ctx.drawImage(objImg, 0, 0, srcWidth, srcHeight, 0, 0, dstWidth, dstHeight);
		
		return canvas.toDataURL();
	}

	/**
	 * 画像をサーバにアップロードする。
	 * @param {Image} objImg - 
	 */
	uploadImage(objImg){
		let self = this;
		if(this.isSetProcessingFlag()) return null;
		
		//1000ミリ秒間は処理中にして他の処理を受け付けない
		this.setProcessingFlag(500);

		//処理中画面
		this.displayAlert();

		let imageTitle = this.findElementByDataId("ISFL_editor_dialog.val.image_title").val();
		//送信データ
		let data = {
			'image_title': imageTitle,
			'image': objImg.src.substr(objImg.src.indexOf(',')+1),
			'filename': objImg.getAttribute("data-filename")
		};
		
		//画像をアップロードする
		jQuery.ajax({
			url       : self.API_INFO.uriPrefix + "/images",
			method    : 'POST', 
			data      : JSON.stringify(data),
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': self.xWpNonce
			}
		}).then(function(json) {
			objImg.src = "";
			//500ミリ秒waitしないと処理フラグのせいで次のdisplayImgList()を呼べない。
			return self.createTimeoutPromise(500);
		}).then(function(){
			//画像リストのリフレッシュ
			self.displayImgList(1);
		}).fail(function(data){
			self.transactJsonErrByAlert(data);
		}).always(function(){
			self.hideAlert();
		});
	}
	
	/**
	 * 画像を削除する
	 * @param {String} attachmentId - 削除対象のID
	 */
	deleteImage(attachmentId){
		let self = this;
		if(this.isSetProcessingFlag()) return null;
		
		//1000ミリ秒間は処理中にして他の処理を受け付けない
		this.setProcessingFlag(500);
		
		//処理中画面
		this.displayAlert();
		
		//画像をアップロードする
		jQuery.ajax({
			url       : self.API_INFO.uriPrefix + "/images/" + attachmentId,
			method    : 'DELETE', 
			data      : {},
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-WP-Nonce': self.xWpNonce
			}
		}).then(function(json) {
			//500ミリ秒waitしないと処理フラグのせいで次のdisplayImgList()を呼べない。
			return self.createTimeoutPromise(500, json);
		}).then(function(json){
			//画像リストのリフレッシュ
			self.displayImgList(1);
		}).fail(function(data){
			self.transactJsonErrByAlert(data);
		}).always(function(){
			self.hideAlert();
		});
	}
	

}
