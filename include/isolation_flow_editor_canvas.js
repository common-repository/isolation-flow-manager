//import ISFL_IsolationFlowCommon from "isolation_common";
//import createjs from "createjs.min";




/**
 * @class
 * フローを描画するクラス。CreateJsを使用。結線については独自のプロパティを追加していることに注意。
 * 
 * <pre>
 * 【必要なjs】
 *  createjs.js
 *
 * 【階層】
 *    Stage
 *      ┗Container(参照ありのFlow階層)
 *        .name = null
 *        ┗Rect(フロー)
 *          .name = "flow11"
 *        ┗Container(結合線) 
 *          .name = "conn-flow1-flow11"
 *          .ISFL_objStrokeFill 独自プロパティ。色を変更するためのGraphicsコマンドと.setStyle()メソッドが保存されている。
 *        ┗Container(子孫のフロー)
 *          ┗Rect(フロー)
 *            .name = "flow15"
 *       :
 *      ┗Container(参照なしのFlow階層)
 *        .name = null
 *        ┗Rect(フロー)
 *          .name = "flow11"
 *       :
 * </pre>
 */
ISFL.IsolationFlowEditorCanvas = class{
	/**
	 * @param {String} id - Canvasを表示するdivオブジェクトのid属性を指定。
	 * @param {ISFL.IsolationFlowEditor~flow_groups} data - Flowデータ。
	 * @param {Function} funcCallback - 
	 */
	constructor(id, data, funcCallback){
		if(typeof data === "undefined") throw new TypeError("data must be flow data.");
		if(!(funcCallback instanceof Function)) throw new TypeError("funcCallback must be Function.");
		this.objDisplayDiv = document.getElementById(id);
		this.canvasId = id + "_canvas";
		this.objCanvas = this._createCanvas(this.canvasId, this.objDisplayDiv.clientWidth, this.objDisplayDiv.clientHeight);
		this.objDisplayDiv.appendChild(this.objCanvas);
		
		/**
		 * フォントタイプ
		 * @type {String}
		 */
		this.font = "'ＭＳ 明朝'";
		
		/**
		 * フォントの大きさ(px)
		 * @type {Number}
		 */
		this.fontSize = 12;
		
		/**
		 * 参照されないFlowのタイトル名。
		 */
		this.titleNameUnrefferenced = "Unrefferenced Flows";

		/**
		 * フローのRect背景の色定義
		 * @type {Object.<String, String>}
		 * @proprey {String} OPEN - フローが「継続」の場合の背景色
		 * @proprey {String} CLOSE - フローが「終了」の場合の背景色
		 */
		this.flowColors = {
			OPEN: "#58FA58",
			CLOSE: "#4169e1",
		};

		/**
		 * フロー、線などの描画サイズ定義
		 * @type {Object.<String, Number>}
		 * @proprey {String} FLOW_WIDTH - フローの横幅
		 * @proprey {String} FLOW_HEIGHT - フローの縦幅
		 * @proprey {String} FLOWS_LINE_HEIGHT - フロー間の縦の幅
		 * @proprey {String} UNREF_FLOWS_MARGIN_Y - 参照されないフロー間の縦の幅
		 * @proprey {String} ARROW_WIDTH - 線につける矢印の横幅
		 * @proprey {String} CONNECTION_TEXT_MAX_LINE_NUM - 線に描画するテキストの最大行数
		 */
		this.size ={
			FLOW_WIDTH : 100,
			FLOW_HEIGHT : 50,
			FLOWS_LINE_HEIGHT : 100,
			UNREF_FLOWS_MARGIN_Y : 10,
			ARROW_WIDTH : 5,
			CONNECTION_TEXT_MAX_LINE_NUM : 2,
		};
		
		
		/**
		 * FlowのRectをクリックされたときに呼び出されるコールバック関数
		 * @type {IsolationFlowEditorCanvas~onClickFlow}
		 */
		this.funcCallback = funcCallback;
		
		/**
		 * FlowのRectをクリックされたときに呼び出されるコールバック関数
		 * @callback IsolationFlowEditorCanvas~onClickFlow
		 * @param {String} id - クリックされたFlow ID
		 * @return {bool} 選択の処理を続けるかどうか。trueの場合、Rectが選択状態の描画になる。
		 */
		
		/**
		 * CanvasのStageの情報
		 * @type {Object}
		 * @property {Stage} stage - CreatejsのStageオブジェクト
		 * @property {Number} mouseDownPos.x - クリックした時点でのカーソルのx座標
		 * @property {Number} mouseDownPos.y - クリックした時点でのカーソルのy座標
		 * @property {Number} mouseDownPos.scrollLeft - クリックした時点でのCanvasスクロールの左位置
		 * @property {Number} mouseDownPos.scrollTop  - クリックした時点でのCanvasスクロールの上位置
		 */
		this.objCanvasStageInfo =  {stage: null, mouseDownPos: null};
		
		/**
		 * Flowのデータと描画オブジェクト。
		 * IDをキー、データはdrawDataで保存。
		 * @type {Object.<String, IsolationFlowEditorCanvas~drawData>}
		 * @property {IsolationFlowEditorCanvas~drawData} this[id] - drawData
		 */
		this.drawData = {};
		
		/**
		 * 2つのFlowを結ぶ線の情報
		 * @type {Array.<Object>}
		 * @property {Number} index - 横の位置(0～)
		 * @property {Container} parent - 親
		 * @property {IsolationFlowEditorCanvas~drawData}  childDrawData - 子の情報
		 * @property {String}  label     - 結線上に記述するテキスト
		 * @property {String}  parentId  - 親のFlow ID
		 * @property {String}  childId   - 子のFlow ID
		 */
		this.drawConnectionData = [];
		
		/**
		 * Flow IDと描画オブジェクトContainerをまとめたHolder。
		 * Containerには、Flow IDのRectが先頭に保存されている。
		 * @typedef {Object} IsolationFlowEditorCanvas~drawData
		 * @property {String} id      - Flow ID
		 * @property {Container} container - Flow（配下を含めた）
		 * @property {Number}  width  - 子も含めた全体の横幅
		 * @property {Number}  height - 子も含めた全体の縦幅
		 */
		
		/**
		 * 選択されたオブジェクト。選択されていない場合はnull。
		 * @type {IsolationFlowEditorCanvas~drawData}
		 */
		this.selectedInfo = null;
		//
		this.remake(data);
	}
	
	remake(data){
		const WIDTH = this.size.FLOW_WIDTH;
		const MARGIN = this.size.UNREF_FLOWS_MARGIN_Y;
		
		//Stageのクリア
		if(this.objCanvasStageInfo.stage != null){
			this.objCanvasStageInfo.stage.removeAllChildren();
		}
		this.objCanvasStageInfo.stage = null;
		
		//リセット
		this.drawData = {};
		this.drawConnectionData = [];
		this.selectedInfo = null;
		
		//Stage作成
		let stage = this._createStage(this.canvasId);
		this.objCanvasStageInfo = {stage: stage, mouseDownPos: null};
		
		//図形作成
		let result = this._createFlows(data, 1);
		let shape = result.container;
		shape.x += WIDTH * 1.2;
		shape.y += MARGIN;
		stage.addChild(shape);
		
		//曲がった結合線の追加
		for(let info of this.drawConnectionData){
			shape = this._addConnection(info.parent, info.childDrawData.container, info.index, info.label);//info);
		}
		
		//参照されていないFlowの表示
		shape = this._createNonRefFlows(data);
		shape.y += MARGIN;
		stage.addChild(shape);
		
		//大きさを計算する
		let entireRect = stage.getBounds();

		//描画されたオブジェクトが元のCanvasより大きい場合は大きくする
		if(entireRect.width > this.objCanvas.getAttribute("width")){
			this.objCanvas.setAttribute("width", entireRect.width + 50); // stage.getBounds().right
		}
		if(entireRect.height > this.objCanvas.getAttribute("height")){
			this.objCanvas.setAttribute("height", entireRect.height + 50);
		}
	}
	
	/** private:
	 * Canvasを作成する
	 * @param {String} id - DOM Canvas要素のid属性の値を指定
	 * @param {Number} width - 描画の幅
	 * @param {Number} height - 描画の高さ
	 * @return {Element} Canvasオブジェクト 
	 */
	_createCanvas(id, width, height){
		let objCanvas = document.createElement("canvas");
		objCanvas.setAttribute("width", width);
		objCanvas.setAttribute("height", height);
		objCanvas.setAttribute("id", id);
		
		//キャンバスのドラッグのために
		let self = this;
		objCanvas.addEventListener("mousedown", function(event){
			let info = self.objCanvasStageInfo;
			//マウスダウンした位置を保存
			info.mouseDownPos = {
				x: event.clientX, y: event.clientY, 
				scrollLeft: self.objDisplayDiv.scrollLeft, scrollTop: self.objDisplayDiv.scrollTop
			};
		});
		objCanvas.addEventListener("mousemove", function(event){
			let info = self.objCanvasStageInfo;
			if(info.mouseDownPos == null) return;
			event.preventDefault();
			let dx = info.mouseDownPos.x - event.clientX;
			let dy = info.mouseDownPos.y - event.clientY;
			self.objDisplayDiv.scrollLeft = info.mouseDownPos.scrollLeft + dx;
			self.objDisplayDiv.scrollTop = info.mouseDownPos.scrollTop + dy;
			return false;
		});
		objCanvas.addEventListener("mouseup", function(event){
			self.objCanvasStageInfo.mouseDownPos = null;
		});
		
		return objCanvas;
	}
	
	/**
	 * 参照されないFlowのCanvas上のタイトル名。
	 */
	setTitleNameUnreffrencedFlow(name){
		this.titleNameUnrefferenced = name;
	}
	
	/** private:
	 * Stageオブジェクトを作成する
	 * @param {String} id - DOM Canvas要素をid属性で指定する
	 * @return {Stage} - ステージオブジェクト
	 */
	 _createStage(canvasId){
		let stage = new createjs.Stage(canvasId);
		/*let self = this;
		
		let mouseDownPos = null;
		//キャンバスのドラッグのために
		stage.addEventListener("stagemousedown", function(event){
			let info = self.objCanvasStageInfo;
			//マウスダウンした位置を保存
			info.mouseDownPos = {clientX: event.clientX,
				x: info.stage.mouseX, y: info.stage.mouseY, 
				scrollLeft: self.objDisplayDiv.scrollLeft, scrollTop: self.objDisplayDiv.scrollTop
			};
		});
		stage.addEventListener("stagemousemove", function(event){
			event.preventDefault();
			let info = self.objCanvasStageInfo;
			if(info.mouseDownPos == null) return;
			let dx = info.mouseDownPos.x - info.stage.mouseX;
			let dy = info.mouseDownPos.y - info.stage.mouseY;
			self.objDisplayDiv.scrollLeft = info.mouseDownPos.scrollLeft + dx;
			self.objDisplayDiv.scrollTop = info.mouseDownPos.scrollTop + dy;
			return false;
		});
		stage.addEventListener("stagemouseup", function(event){
			self.objCanvasStageInfo.mouseDownPos = null;
		});
		*/
		//
		return stage;
	}
	
	/** private:
	 * すべてのフローを描画するcontainerを作る。
	 * すべての結線は作らない（位置がずれるので）。
	 * 既に作図済みのFlowへの結線は、後で this.drawConnectionData を使用して結線する。
	 * @param {ISFL.IsolationFlowEditor~flow_groups} data - フローデータ
	 * @param {Number} id - FlowIDを指定する
	 * @return {Container} 作成されたcontainer
	 * @property {Returns.Container} container - 指定ID配下すべて描画したもの
	 * @property {Returns.Number}    width  - 横幅
	 * @property {Returns.Number}    height - 縦幅
	 * @return {Container} 指定のFlowIDのContainerを作成して返す。
	 */
	_createFlows(data, id){
		const WIDTH = this.size.FLOW_WIDTH;
		const HEIGHT = this.size.FLOW_HEIGHT;
		const LINE_HEIGHT = this.size.FLOWS_LINE_HEIGHT;
		let retWidth = 0;
		let ret = this._createFlow(0, data.user_flows.flows[id]);
		//
		let flow = data.user_flows.flows[id];
		let maxHeight = HEIGHT;
		for(let i = 0; i < flow.choices.length; ++i){
			let objChoice = flow.choices[i];
			let choiceId = objChoice.next_flow_id;
			if(choiceId == '') continue;
			//既に存在するIDの場合は、そちらに直線を結ぶため、情報を保存
			if(typeof this.drawData[choiceId] !== "undefined"){
				this.drawConnectionData.push({index: i, parent: ret, childDrawData: this.drawData[choiceId],
					label: objChoice.label,
					parentId: id, childId: choiceId});
				continue;
			}
			let result = this._createFlows(data, choiceId);
			let childContainer = result.container;
			childContainer.x += retWidth;
			childContainer.y += LINE_HEIGHT;
			ret.addChild(childContainer);
			
			//親へのコネクション線を描く
			let shape = this._addConnection(ret, childContainer, i, objChoice.label);
			ret.addChild(shape);
			//
			retWidth += result.width;
			if(result.height > maxHeight) maxHeight = result.height
		}
		
		if(retWidth == 0){
			//子要素がない場合
			retWidth += WIDTH * 1.2;
		}
		maxHeight += LINE_HEIGHT;
		let info = this._addDrawData(id, ret, retWidth, maxHeight); //{id: id, container: ret, width: retWidth, height: maxHeight};
		//描画Containerを保存
		//this.drawData[id] = info;
		return info;
	}
	
	/**
	 * drawDataを作成して、this.drawData に追加する。{IsolationFlowEditorCanvas~drawData}
	 * @param {Number} flowId - FlowID
	 * @param {Container} container - Flowを含むContainer
	 * @param {Number} width  - 全体の横幅
	 * @param {Number} height - 全体の縦幅
	 * @return {IsolationFlowEditorCanvas~drawData} 作成したdrawDataを返す。
	 */
	_addDrawData(flowId, container, width, height){
		//if(typeof flowId !== "string") throw new TypeError("flowId must be Number.");
		if(!(container instanceof createjs.Container)) throw new TypeError("container must be Container.");
		if(typeof width !== "number") throw new TypeError("width must be Number.");
		if(typeof height !== "number") throw new TypeError("height must be Number.");
		//データ作成
		let info = {id: flowId, container: container, width: width, height: height};
		//データの追加
		this.drawData[flowId] = info;
		return info;
	}
	
	/** private:
	 * Flow間を結ぶ線分を親に追加する。親の下辺の真ん中から子の上辺の真ん中へ線を記述。
	 * 線のnameは、"conn-"+親FlowID+子FlowID (例："conn-flow-4-flow-102")。
	 * index * WIDTH * 1.2の分だけ直線を結び、そのあとはべーじぇ曲線で終点（子）に結ぶ。
	 * @param {Container} objParentContainer - 親のコンテナ。この(0,0)の位置に親のFlowの四角がある。
	 * @param {Container} objChildContainer  - 子のコンテナ。この(0,0)の位置に子のFlowの四角がある。
	 * @param {Number}    index              - 子の横の位置。
	 * @param {String}    label              - 直線の上に文字列を表示
	 * @return {Shape} 結線のShape
	 * @note 色のコマンドを保存するカスタム変数 Container.ISFL_objStrokeFill を追加している。
	 *       Container.ISFL_objStrokeFill.setStyle(color);で色を変更できる。
	 */
	_addConnection(objParentContainer, objChildContainer, index, label){
		if(!(objParentContainer instanceof createjs.Container)){
			throw new TypeError("objParentContainer must be Container.");
		}
		if(!(objChildContainer instanceof createjs.Container)){
			throw new TypeError("objChildContainer must be Container.");
		}
		const WIDTH = this.size.FLOW_WIDTH;
		const HEIGHT = this.size.FLOW_HEIGHT;
		const LINE_HEIGHT = this.size.FLOWS_LINE_HEIGHT;
		const MAX_LINE_NUM = this.size.CONNECTION_TEXT_MAX_LINE_NUM;
		let parentPos = objParentContainer.getBounds();
		let c = objChildContainer.getBounds();
		let cp = objChildContainer.localToGlobal(c.x, c.y);
		let childPos = objParentContainer.globalToLocal(cp.x, cp.y);
		//開始位置
		let x1 = parentPos.x + WIDTH / 2;
		let y1 = parentPos.y + HEIGHT
		//描画の中間点2
		let x3 = x1 + index * WIDTH * 1.2;
		let y3 = y1 + LINE_HEIGHT - HEIGHT;
		//描画の中間点1
		let x2 = (x1 + x3) / 2;
		let y2 = (y1 + y3) / 2;
		//描画の終点
		let lastX = childPos.x + WIDTH / 2;
		let lastY = childPos.y;
		//真下に線を描く場合
		if(lastX == x3 && lastY == y3){
			x3 = x2;
			y3 = y2;
		}
		//矢印付きの線のコンテナ
		let container =  new createjs.Container();
		
		//後で探せるように名前を付ける
		let connName = objParentContainer.getChildAt(0).name + "-" + objChildContainer.getChildAt(0).name;
		container.name = "conn-" + connName;
		
		//色を変更するため、コマンドを保存する領域を作る
		let cmdStrokeFill = {
			cmdStroke: null,
			cmdFill: null,
			setStyle: function(style){ 
				this.cmdStroke.style = style; 
				this.cmdFill.style = style; 
			
			}
		};
		
		//子へのコネクション線を描く
		let shape = new createjs.Shape();
		//色コマンドの保存
		cmdStrokeFill.cmdStroke = shape.graphics.beginStroke("#000").command;
		//その他の描画を定義
		shape.graphics
			.moveTo(x1, y1) //線の始点へ移動
			.lineTo(x2, y2)
			.quadraticCurveTo(x3, y3, lastX, lastY)
			.endStroke();//線を書き終える
			
		//コマンドをcreatejsのContainerにカスタム変数を追加して保存
		container.ISFL_objStrokeFill = cmdStrokeFill;
		//
		container.addChild(shape);
		
		//矢印を描く
		shape = this._drawArrow(x3, y3, lastX, lastY, cmdStrokeFill);
		objParentContainer.addChild(shape);
		//テキストを描画
		shape = this.renderAdjustText(label, x2, y2 - this.fontSize, this.fontSize, WIDTH*0.5, MAX_LINE_NUM);
		shape.textAlign = "center";
		container.addChild(shape);
		//親へ登録
		objParentContainer.addChild(container);
		return shape;
	}
	
	/** private:
	 * Flow図形を作成する。四角とテキストを描画。Rectのnameは"flow"+FlowID
	 * @param {Number} index - 位置を決める要素位置(0～)
	 * @param {ISFL.IsolationFlowEditor~flowData} data  - Flowデータ
	 * @return {Container} - 作成した図を返す。図をクリック時は this.funcCallback() を呼び出すように設定。
	 */
	_createFlow(index, data){
		const WIDTH = this.size.FLOW_WIDTH;
		const HEIGHT = this.size.FLOW_HEIGHT;
		const LINE_HEIGHT = this.size.FLOWS_LINE_HEIGHT;
		let container =  new createjs.Container();
		
		//四角の描画
		let shape = new createjs.Shape();
		shape.name = "flow" + data.flow_id; //nameを設定
		if(data.status == "open"){
			shape.graphics.beginFill(this.flowColors.OPEN);
		}else{
			shape.graphics.beginFill(this.flowColors.CLOSE); 
		}
		shape.graphics.setStrokeStyle(2);
		shape.graphics.drawRect(0, 0, WIDTH, HEIGHT);
		shape.x = index * WIDTH *1.2;
		shape.y = 0;
		this._unselectRect(shape);
		this._addEvent(shape, data.flow_id);
		container.addChild(shape);
		
		//テキストの描画
		let text = "[" + data.flow_id + "] " + data.pt_id + ":" + data.title;
		shape = this.renderAdjustText(text, 0, 0, this.fontSize, WIDTH, 3);
		container.addChild(shape);
		
		return container;
	}
	
	/**
	 * 参照されていないFlowを左側に縦に並べたContainerを作成。
	 * @param {Object} data - 全てのFlowデータ
	 * @return {Container} 作成したFlow
	 */
	_createNonRefFlows(data){
		const WIDTH = this.size.FLOW_WIDTH;
		const HEIGHT = this.size.FLOW_HEIGHT;
		const LINE_HEIGHT = this.size.FLOWS_LINE_HEIGHT;
		let flows = data.user_flows.flows;
		let container =  new createjs.Container();
		
		//
		let cnt = 0;
		let flowsContainer =  new createjs.Container();
		for(let flowId in flows){
			let flowData = flows[flowId];
			//既にFlowが作成されている場合は飛ばす。
			if(typeof this.drawData[flowId] !== "undefined") continue;
			//参照されていないFlowの作成
			let shapeFlow = this._createFlow(0, flowData);
			shapeFlow.y = LINE_HEIGHT * cnt;
			++cnt;
			//追加
			flowsContainer.addChild(shapeFlow);
			let info = this._addDrawData(flowId, shapeFlow, WIDTH * 1.2, LINE_HEIGHT * cnt + HEIGHT);
		}
		
		//背景色の描画
		let shape = new createjs.Shape();
		let height = HEIGHT * 2;
		if(flowsContainer.getBounds() != null) height += flowsContainer.getBounds().height;
		shape.graphics.beginFill("#E0FFFF"); 
		shape.graphics.setStrokeStyle(0);
		shape.graphics.drawRect(0, 0, WIDTH * 1.1, height);
		shape.x = 0;
		shape.y = 0;
		container.addChild(shape);
		
		//テキストの描画
		let text = this.titleNameUnrefferenced;
		shape = this.renderAdjustText(text, 0, 0, this.fontSize, WIDTH * 1.1, 2);
		container.addChild(shape);
		
		//Flowの追加
		flowsContainer.y += this.fontSize * 2;
		container.addChild(flowsContainer);
		
		return container;
	}
	
	/** private: 
	 * Flowをクリックしたときのイベント登録。
	 * メモリを節約するため、これだけで関数作成。変数の入れ子をすると関数内の変数すべてを保管するため。
	 * @param {Shape} shape - Shapeオブジェクト。ここにmousedownイベントを追加する。
	 * @param {String} id   - これを引数にしてthis.funcCallback()を呼び出す。
	 */
	_addEvent(shape, id){
		let self = this;
		shape.addEventListener("mousedown", function(event){
			self.onClickFlow(event, id);
			//console.log(event.currentTarget);
		});
	}
	
	/** private:
	 * 2つの点を結ぶ直線の角度で終点に矢印を描く。矢印のみ描画する。
	 * @param {Number} x1 - 始点X座標
	 * @param {Number} y1 - 始点Y座標
	 * @param {Number} x2 - 終点X座標
	 * @param {Number} y2 - 終点Y座標
	 * @param {Object} objCmdStyle - 色のコマンドを保存するホルダー
	 */
	_drawArrow(x1, y1, x2, y2, objCmdStyle){
		const WIDTH = this.size.ARROW_WIDTH;
		let shape = new createjs.Shape();
		//色コマンドの実行
		let cmdFill =  shape.graphics.beginFill("#000").command;
		//色以外のコマンドの定義
		shape.graphics.moveTo(0, 0) 
			.lineTo(WIDTH, WIDTH / 2) 
			.lineTo(WIDTH, -WIDTH / 2)
			.closePath()
			.endStroke();//線を書き終える
		//回転角度を計算
		let a = (y2 - y1) / (x2 - x1);
		let degree = (Math.abs(a) == Infinity ? -Math.PI / 2 : Math.atan(a));
		if(x2 > x1) degree += Math.PI;
		shape.rotation = degree / Math.PI * 180;
		shape.x = x2;
		shape.y = y2;
		
		//色コマンドの保存
		if(typeof objCmdStyle !== "undefined"){
			objCmdStyle.cmdFill = cmdFill;
		}
		return shape;
	}
	
	/** protected: textAlignは呼び出し前に設定する 
	 * 指定の位置に指定の幅でテキストを表示する。幅を超えないように自動的に改行する。
	 * @param {Context} text    - 表示文字列
	 * @param {Context} x       - 開始の位置X座標
	 * @param {Context} y       - 開始の位置Y座標(文字の左上が起点)
	 * @param {Context} fontSize - フォントサイズ
	 * @param {Context} maxWidth - 最大横幅
	 * @param {Context} maxLineNum - 最大行数。超える文字列は".."で置き替えられる。
	 * @return {createjs.Text} - 作成したテキストオブジェクト
	 */
	renderAdjustText(text, x, y, fontSize, maxWidth, maxLineNum){
		let context = this.objCanvas.getContext('2d');
		let font = fontSize + "px " + this.font;
		context.save();
		context.font = font;
		let lineNum = 1;
		let displayText = "";
		let lineText = "";
		//
		for(let i = 0; i < text.length; ++i){
			let c = text.substr(i, 1);
			let info = context.measureText(lineText + c);
			if(info.width > maxWidth){
				//最大行数を超える場合
				if(lineNum > maxLineNum){
					lineText += "..";
					break;
				}
				//1行の最大幅を超える場合は改行を追加
				lineText += "\n";
				//追記
				displayText += lineText;
				//次の準備
				++lineNum;
				lineText = "";
			}
			lineText += c;
			
			//最後の1文字の場合で、次の行も記述OKの場合
			if(i >= text.length-1 && lineNum <= maxLineNum){
				displayText += lineText;
			}
		}
		context.restore();
		//
		//displayText += "\n";
		//console.log(displayText);
		let shape = new createjs.Text(displayText, font, "#000000");
		shape.textAlign = "start";
		shape.lineHeight = fontSize * 1.2;
		shape.x = x;
		shape.y = y;
		return shape;
	}
	
	/**
	 * 名前が付いたShapeだけ取得して返す。名前は線とFlowにだけつけられている。
	 * @param {Container} [container] - 検索対象のコンテナ。指定しないとこの中のすべての子孫を探す。
	 * @return {Array.<Container|Rect>} 見つかった描画オブジェクト
	 */
	getStageDescendants(container){
		if(typeof container === "undefined"){
			container = this.objCanvasStageInfo.stage;
		}
		if(container == null) return [];
		let ret = [];
		for(let child of container.children){
			if(child.name != null){
				ret.push(child);
			}else if(child instanceof createjs.Container){
				let aryObjs = this.getStageDescendants(child);
				ret = ret.concat(aryObjs);
			}
		}
		return ret;
	}
	
	/** public:
	 * Canvasの描画を更新する
	 */
	update(){
		this.objCanvasStageInfo.stage.update();
	}
	
	/** public:
	 * Stageのオブジェクト要素を取得する。
	 * @param {String} flowId - 取得するオブジェクトのFlow IDを指定
	 * @return {IsolationFlowEditorCanvas~drawData} - Flowの描画データ(Container等)
	 */
	getDrawDataById(flowId){
		return this.drawData[flowId];
	}
	
	/** public:
	 * 選択されたオブジェクトを取得する。
	 * @return {IsolationFlowEditorCanvas~drawData} 選択されたオブジェクト。選択されていない場合はnull。
	 */
	getSelectedInfo(){
		return this.selectedInfo;
	}
	
	/** public:
	 * 選択されたオブジェクト(Rect)を取得する。
	 */
	getSelectedShape(){
		if(this.selectedInfo == null) return null;
		return this.selectedInfo.container.getChildAt(0);
	}

	/**
	 * Canvasの位置情報を取得する。
	 * @return {DOMRect} Canvasの位置情報を返す{left, top, right, bottom, x, y, width, height}（canvas.getBoundingClientRect()）
	 */
	getCanvasBoundingRect(){
		return this.objDisplayDiv.getBoundingClientRect();
	}
	
	/** public:
	 * Flowを表すRectをクリックしたときに呼び出されるイベント。
	 */
	onClickFlow(event, flowId){
		//コールバック関数の呼び出し
		let isSelect = this.funcCallback(flowId);
		//選択状態の描画をしない場合
		if(isSelect == false) return;
		
		//選択を解除する
		this.unselectFlow();
		
		//選択する描画
		this.selectFlow(flowId);
		/*this.selectedInfo = this.getDrawDataById(flowId);
		let objRect = this.getSelectedShape();
		this.selectRect(objRect);
		*/
		
		//再描画
		this.update();
	}
	
	/** public:
	 * 指定のFlow IDのRectを選択状態にし、再描画する。
	 * @param {Number} flowId - FLow ID
	 */
	selectFlow(flowId){
		//選択する描画
		this.selectedInfo = this.getDrawDataById(flowId);
		let objRect = this.getSelectedShape();
		if(objRect == null) return;
		this._selectRect(objRect);
	}
	
	unselectFlow(){
		let objRect = this.getSelectedShape();
		//選択を外す描画
		if(objRect != null){
			this._unselectRect(objRect);
		}
		this.selectedInfo = null;
	}
	
	_selectRect(objRect){
		objRect.alpha = 1;
	}
	_unselectRect(objRect){
		objRect.alpha = 0.3;
	}
	
	/**
	 * 結合線を選択状態にする。指定の色に変更する。
	 * @param {Number} parentFlowId - 親のFlow ID
	 * @param {Number} childFlowId  - 子のFlow ID
	 * @param {String} [strColor]   - 変更後の線の色。デフォルトblue
	 */
	selectConnection(parentFlowId, childFlowId, strColor){
		if(typeof strColor === "undefined") strColor = "red";
		let searchName = "conn-flow" + parentFlowId + "-flow" + childFlowId;
		let shaps = this.getStageDescendants();
		let target = null;
		for(let shape of shaps){
			if(shape.name == searchName){
				target = shape;
			}
		}
		if(target == null) return;
		//カスタムのプロパティを使用して色を変更
		target.ISFL_objStrokeFill.setStyle("blue");
	}
	
	/**
	 * 結合線の選択を解除する。黒色に戻す。
	 */
	unselectConnection(){
		let shaps = this.getStageDescendants();
		let target = null;
		for(let shape of shaps){
			if(shape.name.startsWith("conn-")){
				//カスタムのプロパティを使用して色を変更
				shape.ISFL_objStrokeFill.setStyle("#000");
			}
		}
	}
	
	/** public:
	 * 指定のFlowIDのRectに星のマークを付ける。
	 * @param {String} flowId - 指定のID
	 * @param {bool}   [isHide] - 星を隠す場合はtrue。デフォルトfalse。
	 */
	displayStarOn(flowId, isHide){
		const WIDTH = 100;
		const HEIGHT = 40;
		const NAME = "POLYSTAR";
		let drawData = this.getDrawDataById(flowId);
		if(drawData == null) return;
		//星を探す
		let poly = drawData.container.getChildByName(NAME);
		//既に星がついている場合は何もしない
		if(typeof isHide === "undefined") isHide = false;
		if(isHide){
			//もともと星がついていない場合は何もしない。
			if(poly == null) return;
			//隠す場合は削除する
			container.removeChild(poly);
		}else{
			//もともと星がついている場合は何もしない。
			if(poly != null) return;
			//星を作成
			poly = new createjs.Shape();
			poly.graphics.beginFill("DarkRed"); // 赤色で描画するように設定
			poly.graphics.drawPolyStar(WIDTH, HEIGHT, 20, 5, 0.6, -90); //星を記述
			poly.name = NAME;
			drawData.container.addChild(poly); // 表示リストに追加
		}
	}
	
	/** public:
	 * 全ての星を削除する。
	 */
	removeAllStarsOnRects(){
		const NAME = "POLYSTAR";
		for(let key in this.drawData){
			let container = this.drawData[key].container;
			//星を探す
			let poly = container.getChildByName(NAME);
			//既に星がついている場合は何もしない
			if(poly == null) continue;
			//削除する
			container.removeChild(poly);
		}
	}
	
}


