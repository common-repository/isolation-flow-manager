
/** 名前空間宣言 */
var ISFL = ISFL || {};
ISFL.X = class{
	constructor(x){
		this.a=x;
	}
}


/**
 * 共通基底クラス 
 * 
 * @class 
 */
ISFL.IsolationFlowCommon = class{
	/**
	 * @constructor
	 */
	constructor(id, xWpNonce){
		this.xWpNonce = xWpNonce;
		this.rootHtmlId = id;
		this.rootHtmlElement = jQuery(this.rootHtmlId);
		
		//テンプレート（コンパイル結果を保存しておく場所）
		this.templates = {};
		//処理中の場合にIDを設定する（setTimeout()のID）
		this.processingFlagId = null;
		
		/**
		 * 最初のrevisionを保存しておく
		 * @type {Number}
		 */
		this.dialogs = {};

		//ステータスの表示文字列の定義
		eval('this.DEF_STATUS_BUTTON = ' + this.getMsg('DEF.STATUS_BUTTON.JS_ARRAY', false) + ';');
		//this.DEF_STATUS_BUTTON = {open: "決定", close:"終了"};
		//API情報の設定
		this.API_INFO = ISFL.API_INFO;
		
		/**
		 * ページング時の1ページのデータ数
		 * @type {Number}
		 **/
		this.pagingUnit = 30;
	}


	/** 
	 * メッセージテキストの取得。
	 * @param {String} name - メッセージ名。
	 * @param {Bool} [isEscape] - HTMLエスケープするかどうか。
	 * @param {String} [param0Key] - プレースホルダ{0}をこれで置換。メッセージキーで指定。
	 * @param {String} [param1Key] - プレースホルダ{1}をこれで置換。メッセージキーで指定。
	 */
	getMsg(name, isEscape, param0Key, param1Key){
		if(typeof isEscape === 'undefined') isEscape = true;
		let msg = ISFL.IsolationFlowCommon.MESSAGES;
		if(typeof msg === 'undefined') throw 'MESSAGES is not defined. Maybe rental_goods_manager_common_def.js is not inclueded.';
		let str = msg[name];
		if(str == '') throw "Message ID is not found.";
		if(typeof param0Key !== 'undefined'){
			let val = this.getMsg(param0Key);
			str = str.replace('{0}', val);
		}
		if(typeof param1Key !== 'undefined'){
			let val = this.getMsg(param1Key);
			str = str.replace('{1}', val);
		}
		if(!isEscape) return str;
		return str.replace(/[&'`"<>]/g, function(match) {
			return {
			'&': '&amp;',
			"'": '&#x27;',
			'`': '&#x60;',
			'"': '&quot;',
			'<': '&lt;',
			'>': '&gt;',
			}[match]
		});
	}

	/**
	 * 定義（DEFINITIONS）の中の名前の定義配列を取得する。
	 * @param {String} name - 定義名
	 * @return {Array} - 指定の定義の配列 
	 */
	getDef(name){
		let msg = ISFL.IsolationFlowCommon.MESSAGES;
		if(typeof msg === 'undefined') throw 'MESSAGES is not defined. Maybe rental_goods_manager_common_def.js is not inclueded.';
		let defs = msg['DEFINITIONS'];
		if(defs == '') throw "Message DEFINITIONS is not found.";
		let ret = defs[name];
		if(typeof ret === 'undefined') throw "Message DEFINITIONS[" + name + "]  is not found.";
		return ret;
	}

	/** HTMLのテンプレート(handlebars)を設定する。idからテンプレートを取得しコンパイルしてthis.templatesに設定する。
	 * @param {String} templateName  - テンプレート名。
	 * @param {String} defaultTemplateId - デフォルトのテンプレートのID属性を指定。
	 * @param {String} templateIds   - 指定するテンプレートをCSSセレクタで指定。
	 */
	setTemplate(templateName, defaultTemplateId, templateIds){
		templateIds = !templateIds ? {} : templateIds;
		let tagId = !templateIds[templateName] ? defaultTemplateId : templateIds[templateName];
		let objTag = document.querySelector(tagId);
		if(objTag){
			this.templates[templateName] = Handlebars.compile(objTag.innerHTML);
		}
	}

	/**HTMLのテンプレート(handlebars)を適用してHTMLを作成する。
	 * jsonにDEFINITIONS(ISFL.IsolationFlowCommon.MESSAGES.DEFINITIONS)を追加してからhadlebarsを呼び出す。
	 * @param {String} templateName  - テンプレート名。
	 * @param {Array} json - テンプレートに送るデータ。
	 * @return {String} - テンプレートを適用した結果。HTML文字列。
	 */
	createHtmlFromTemplate = function(templateName, json){
		if(this.templates[templateName]){
			json['DEFINITIONS'] = ISFL.IsolationFlowCommon.MESSAGES.DEFINITIONS;
			return this.templates[templateName](json);
		}
		return '';
	}

	/**HTML内の要素の検索。
	 * data-idを指定して要素を取得する。
	 * @param {String} dataId        - data-idの名前
	 * @param {String} [childDataId] - 省略可。子data-id。dataId配下のdata-idを検索し返す。
	 * @return {Element} 結果要素(jQuery)
	 */
	findElementByDataId(dataId, childDataId){
		let objTag =  this.rootHtmlElement.find('[data-id="' + dataId + '"]');
		if(!objTag || typeof childDataId === 'undefined') return objTag;
		return objTag.find('[data-id="' + childDataId + '"]');
	}
	
	/**HTML内の要素の検索。
	 * data-idを指定して要素を取得する。
	 * @param {String} parentDataId - 親data-id。完全一致。nullのときは親は指定しない。
	 * @param {String} dataIdPrefix  - data-idの名前。前方一致。
	 * @return {Element} 結果要素
	 */
	findElementsByDataIdPrefix(parentDataId, dataIdPrefix){
		let objParentTag = this.rootHtmlElement;
		if(parentDataId != null) objParentTag = this.findElementByDataId(parentDataId);
		let objTags =  objParentTag.find('[data-id^="' + dataIdPrefix + '"]');
		return objTags;
	}

	/**HTML内の要素の検索。
	 * @param {String} selector - cssセレクタ
	 * @return {Element} 結果要素(jQuery)
	 */
	findElements(selector){
		return this.rootHtmlElement.find(selector);
	}

	/**HTML内の要素にイベントを付加する。既存イベントを削除してから付加。data-idで要素を指定する。要素が見つからない場合は何もしない。
	 * @param {String} dataId      - data-idの名前
	 * @param {String} eventName   - イベント名（例：click, keypress）。
	 * @param {Function} func      - イベント発生時の処理。
	 */
	addEventByDataId(dataId, eventName, func){
		let objTag =  this.findElementByDataId(dataId);
		if(objTag.length == 0) return;
		objTag.off(eventName);
		objTag.on(eventName, func);
	}

	/**
	 * ページング処理。検索結果データを渡すとHTMLに情報を埋め込む。
	 *    この関数を使ってページングのHTMLを処理する場合は以下のタグ属性(data-*)の構成にする。
	 *    以下の「section_name1.paging」はデータIDのプレフィックスで、各自で自由にカスタマイズ可能。
	 *    プレフィックスでページング処理する対象を決めている。後半の文字は固定文字列なので注意。
	 *    &lt;a data-id="section_name1.paging.btn_prev" data-page="1"> prev &lt;/a>
	 *    &lt;input type="text" data-id="section_name1.paging.page" name="paging.page" value="1" style="width:50px;">
	 *     / &lt;span data-id="section_name1.paging.max_page">&lt;/span>
	 *    &lt;a data-id="section_name1.paging.btn_next" data-page="1"> next &lt;/a>
	 *
	 * @param {string} pagingDataId - ページング対象のタグをdata-idで指定。（例:"section_name1.paging"）
	 * @param {array} searchResultJson - 検索結果形式のJson。検索結果形式({amount:5, offset:0, limit:10, list:[...]})である必要あり。
	 * @note HTMLは次の構成である必要がある。
	 */
	writePaging(pagingDataId, searchResultJson){
		let amount = searchResultJson['amount'];
		let offset = searchResultJson['offset'];
		let limit = searchResultJson['limit'];
		//ページングを計算
		let page = Math.floor(offset / limit) + 1;
		let maxPage = Math.floor((amount-1) / limit) + 1;
		if(amount == 0){
			//検索結果なし（0件）
			page = 1;
		}else if(offset == amount){
			//検索結果はあるが、ページが件数を超えている
			page = maxPage + 1;
		}
		let prevPage = page < 2 ? 1 : page-1;
		let nextPage = page+1 > maxPage ? maxPage : page+1;
		
		//HTMLにページング情報を埋め込み
		this.findElementByDataId(pagingDataId + ".max_page").html(maxPage);
		this.findElementByDataId(pagingDataId + ".page").val(page);
		this.findElementByDataId(pagingDataId + ".btn_prev").attr("data-page", prevPage);
		this.findElementByDataId(pagingDataId + ".btn_next").attr("data-page", nextPage);
	}

	/**
	 * 処理中フラグを解除する。
	 */
	cancelProcessingFlag(){
		 clearTimeout(this.processingFlagId);
		 this.processingFlagId = null;
	}

	/**
	 * 処理中フラグを立てる。
	 * @param {int} msec 処理中フラグを立てておく時間を指定
	 */
	setProcessingFlag(msec){
		let self = this;
		this.processingFlagId = setTimeout(function(){self.cancelProcessingFlag();}, msec);
		return this.processingFlagId;
	}

	/**
	 * 処理中かどうか。
	 * @rerurn {bool} 処理中フラグが立っている場合true
	 */
	isSetProcessingFlag(){
		return this.processingFlagId != null;
	}

	/**
	 * タブを移動するアニメーションをする。
	 * @param {bool} isPrev 戻る動きをする
	 * @param {String} displayTab 対象のタブをcssセレクタで指定
	 */
	changeSliderTab(isPrev, displayTabDataId){
		//現在のタブ
		let activeTab = this.findElements('.slider-tab--active');
		//クラスのクリア
		let reservationTab = this.findElements('.slider-tab');
		reservationTab.removeClass('slider-tab--active');
		reservationTab.removeClass('slider-tab--right-side');
		reservationTab.removeClass('slider-tab--slide-in-right');
		reservationTab.removeClass('slider-tab--slide-in-left');
		
		if(isPrev){
			//現在のタブのアニメーション設定
			activeTab.addClass('slider-tab--right-side');
			activeTab.addClass('slider-tab--slide-in-right');
			//次に移動するタブにアニメーション設定
			this.findElementByDataId(displayTabDataId).addClass('slider-tab--active');
			this.findElementByDataId(displayTabDataId).addClass('slider-tab--slide-in-right');
		}else{
			//現在のタブにアニメーション設定
			activeTab.addClass('slider-tab--slide-in-left');
			//次に移動するタブにアニメーション設定
			this.findElementByDataId(displayTabDataId).addClass('slider-tab--active');
			this.findElementByDataId(displayTabDataId).addClass('slider-tab--slide-in-left');
		}
	}

	/**
	 * エラー処理をする。
	 * @param {array} response エラーレスポンスJson
	 */
	transactJsonErrByAlert(response){
		if(response.status >= 499 || response.status == 0){
			console.log(response);
			alert(this.getMsg('ERR.UNEXPECTED') + ': status=' + response.status);
			return;
		}
		
		let json = response.responseJSON;
		let jsonCode = (!json ? '' : json.code);
		let jsonMessage = (!json ? '' : json.message);
		if(response.status == 400){
			let fields = json['errors']['fields'];
			let errorsStr = '';
			let i = 0;
			for(let field in fields){
				errorsStr += fields[field].join(',') + '\n';
				++i;
				if(i >= 5){
					errorsStr += '...and more';
					break;
				}
			}
			alert(this.getMsg('ERR.ERR_OCCURED') + '[' + jsonCode + ']\n ' + jsonMessage
				+ '\n' + errorsStr);
			return;
		}
		
		let msg = '';
		if(response.status == 401){
			msg = this.getMsg('ERR.UNAUTHORIZED');
		}else if(response.status == 403){
			msg = this.getMsg('ERR.ACCESS_ERROR');
		}else{
			msg = this.getMsg('ERR.ERR_OCCURED');
		}
		//アラートを出す
		alert(msg + '[' + jsonCode + ']\n ' + jsonMessage);
	}

	/**
	 * エラー処理をする。フィールドエラーの場合はHTMLに書き込む。その他のエラーはalertで警告。
	 *    HTMLに、「プレフィックス+'err.'+フィールド名」のdata-idを用意しておくこと。そこにエラーメッセージが書かれる。
	 * @param {int} status HTTPステータス
	 * @param {array} response エラーレスポンスJson
	 * @param {string} dataIdPrefix エラーを書き込むHTMLのdata-idのプレフィックス。設定しない場合""として処理。
	 */
	transactJsonErrByHTML = function(status, response, dataIdPrefix){
		let self = this;
		dataIdPrefix = (!dataIdPrefix ? "" : dataIdPrefix);
		let objErrs = this.findElements('[data-id^="' + dataIdPrefix + 'err."]');
		//エラーのタグをクリア
		objErrs.empty();
		
		//成功の場合
		if(status == 200){
			this.findElementByDataId(dataIdPrefix + 'message').html(this.getMsg('OK.SUCCESS', false));
			return;
		}
		
		//フィールドエラー以外の場合
		if(response.status != 400){
			this.transactJsonErrByAlert(response);
			return;
		}
		
		//エラー
		this.findElementByDataId(dataIdPrefix + 'message').html(this.getMsg('ERR.ERR_OCCURED', false) + response.responseJSON.message);
		
		//フィールドエラー表示
		let errors = response.responseJSON.errors.fields;
		for(field in errors){
			let obj = this.findElementByDataId(dataIdPrefix + 'err.' + field);
			if(obj) obj.html(errors[field].join(' / '));
		}
		
	}
	
	/**
	 * Json式で階層のデータを指定し、値を取得したり、値を設定する。
	 * @param {Object} json      - データ。この中のデータを検索する。
	 * @param {String} strFormat - パス文字列(例："choices[0].name")
	 * @param {String|Number} [val] - 設定する値。
	 * @return {Object} 指定のパスの値を返す。パスが見つからない場合はnull。
	 */
	bindData(json, strFormat, val){
		let cmd = "json";
		if(strFormat.startsWith("[")){
			cmd += strFormat;
		}else{
			cmd += "." + strFormat;
		}
		if(typeof val !== "undefined") cmd += "=" + val;
		try{
			let ret = eval(cmd);
			return ret;
		}catch(e){
			return null;
		}
	}
	
	/**
	 * aryObjの各配列要素から、指定のkeysのキー名があれば削除する。keysには後ろに"*"を指定でき、
	 * その場合は前方一致で見つかったキーを削除する。
	 * @param {Array.<Object>} aryObj - 連想配列の配列
	 * @param {Array.<String>} keys   - 削除対象のキー名。完全一致か、前方一致(例："no*")を指定できる。
	 */
	removeKeysFromObjectArray(aryObj, keys){
		for(let line of aryObj){
			for(let srcKey in line){
				for(let key of keys){
					if(key.endsWith("*") && srcKey.startsWith(key.substr(0, key.length-1))){
						delete line[srcKey];
					}else if(srcKey == key){
						delete line[srcKey];
					}
				}
			}
		}
	}
	
	/**
	 * テキストエリアのカーソルの位置に文字列を追加する。
	 * @param {Textarea} objTextArea - 
	 * @param {replacement} replacement - 
	 */
	insertToTextArea(objTextArea, replacement){
		if(!(objTextArea.type === "textarea")){
			throw new TypeError("objTextArea must be Textarea.");
		}
		let strTextArea = objTextArea.value;
		let allLen      = strTextArea.length;
		let startPos    = objTextArea.selectionStart;
		let first   = strTextArea.substr(0, startPos);
		let latter  = strTextArea.substr(startPos, allLen);
		strTextArea = first + replacement + latter;
		objTextArea.value = strTextArea;
	}
	
	/**
	 * テキストをファイルとしてダウンロードさせる。
	 * @param {String} filename - ダウンロードするときのファイル名
	 * @param {String} content -　ダウンロードファイルの内容
	 * @param {boolean} [appendBom] - ファイルの先頭にBOMを追加するか？
	 */
	handleDownload(filename, content, appendBom) {
		let bom = "";
		if(appendBom === true) bom = new Uint8Array([0xEF, 0xBB, 0xBF]);
		let blob = new Blob([bom, content ], { "type" : "text/plain" });
	
		if (window.navigator.msSaveBlob) {
			// IEとEdge
			window.navigator.msSaveBlob(blob, filename);
		}else {
			// それ以外のブラウザ
			// Blobオブジェクトを指すURLオブジェクトを作る
			let objectURL = window.URL.createObjectURL(blob);
			// リンク（<a>要素）を生成し、JavaScriptからクリックする
			let link = document.createElement("a");
			try{
				document.body.appendChild(link);
				link.href = objectURL;
				link.download = filename;
				link.click();
			}finally{
				document.body.removeChild(link);
			}
		}
	}


	
	/**
	 * ダイアログオブジェクトを取得する
	 * @param {String} name - 取得するダイアログ名
	 * @return {ISFL.Dialog} - 指定のダイアログ
	 */
	getDialog(name){
		return this.dialogs[name];
	}
	
	/**
	 * ダイアログオブジェクトを保存する
	 * @param {String} name - ダイアログ名
	 * @param {ISFL.Dialog} objDialog - 保存するダイアログ
	 */
	setDialog(name, objDialog){
		if(!(objDialog instanceof ISFL.Dialog)){
			throw new TypeError('objDialog must be ISLF.Dialog.');
		}
		this.dialogs[name] = objDialog;
	}
	
	/**
	 * 指定の時間を待つPromiseを作成する。(jQuery.Deferred)
	 * @param {Number} msec - ミリ秒で待つ時間を指定
	 * @param {Onject} [data] - 次のPromiseに渡すデータ。
	 * @return {Promise} - 指定の時間を待つPromise。
	 */
	createTimeoutPromise(msec, data){
		if(typeof msec !== 'number') throw new TypeError('msec must be number.');
		let d = new jQuery.Deferred();
		setTimeout(function(){
			d.resolve(data);
		}, msec);
		return d.promise();
	}
	
	/**
	 * HTML画面上にアラート表示（ダイアログみたいな感じだがDialogの上に表示）
	 * @param {String} [msg] - 表示するメッセージ。省略した場合、処理中を表示。
	 * @param {boolean} [useProcessingAnime] - trueのとき、処理中アニメーションを表示する。
	 * @see hideAlert()
	 */
	displayAlert(msg, useProcessingAnime){
		const ID = 'ISFL_commonjs_alert_____temp';
		if(typeof msg === 'undefined') msg = this.getMsg('HTML.DESC.PROCESSING');
		if(typeof useProcessingAnime === 'undefined') useProcessingAnime = true;
		let objDiv = document.getElementById(ID);
		//ない場合はDiv作成
		if(objDiv == null){
			//オーバーレイするHTML
			objDiv = document.createElement('div');
			objDiv.style.cssText = 'display: none; position: fixed; position: absolute; top: 0; left: 0;'
				+ 'background-color: rgba(255,255,255, 0.3); z-index:99999999;'
				+ 'overflow-y: auto; width: 100%; height: 120%; text-align: center; vertical-align: middle;';
			objDiv.setAttribute('id', ID);
			document.body.appendChild(objDiv);
		}
		//メッセージを記述
		let inner = '<div style="position:fixed; top:30%; left:40%; background-color:white; '
			+ 'padding:20px; border:double black; text-align:left; width:200px; height:70px;">'
			+ msg;
		if(useProcessingAnime) inner += '<span class="ISFL-display-alert-processing"></span>';
		inner += '</div>';
		objDiv.innerHTML = inner;
		//表示
		objDiv.style.display = "block";
	}

	/**
	 * アラートを隠す。
	 */
	hideAlert(){
		const ID = 'ISFL_commonjs_alert_____temp';
		let objDiv = document.getElementById(ID);
		if(objDiv == null) return;
		document.body.removeChild(objDiv);
	}
	
}


