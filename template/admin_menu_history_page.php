<?php
$obj = ISFL_IsolationFlowManager::obj();
$msg = array($obj, 'getMessage');
$messages = $obj->messages;

?>



<h1><?= $msg('MNG.TITLE.SUB_MENU_HISTORY') ?>
	<div class="ISFL-help">
		<span class="ISFL-help__link">
			<i class="icon-question-circle"></i>
		</span>
		<span class="ISFL-help__balloon">
			<?= $msg('HELP.BTN.HISTORY_TITLE'); ?>
		</span>
	</div>
</h1>



<section id="ISFL_isolation_flow_history" class="ISFL-user-section">

	<div>
		<button id="isolation_flow_btn_find_results" class="ISFL-btn-square-soft">
			<?= $msg('BTN.FIND_RESULTS') ?>
		</button>
		<button id="isolation_flow_btn_count_results" class="ISFL-btn-square-soft">
			<?= $msg('BTN.DISPLAY_STATISTICS') ?>
		</button>
		<button id="isolation_flow_btn_download_results" class="ISFL-btn-square-soft">
			<?= $msg('BTN.DISPLAY_DOANLOAD') ?>
		</button>
	</div>
	<!-- 切り分けフローのタイトル -->
	<div class="ISFL-user-title">
		[
			<span data-id="isolation_flow_user.isfl_id"></span>
			<div class="ISFL-name-popup"><span><?= $msg('OBJ.ISFL_ID') ?></span></div>
		]
		[
			<span data-id="isolation_flow_user.result_id" ></span>
			<div class="ISFL-name-popup"><span><?= $msg('OBJ.RESULTS.RESULT_ID') ?></span></div>
		]
		<span data-id="isolation_flow_user.group_title" ></span>
		<div class="ISFL-name-popup"><span><?= $msg('OBJ.GROUP_TITLE') ?></span></div>
	</div>
	<!-- 上部 -->
	<div data-id="user_flows_function" class="ISFL-user-flows-function ISFL-user-flows-function--show">
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name">
				<?= $msg('OBJ.RESULTS.REMARKS') ?><br>
			</div>
			<textarea data-id="isolation_flow_user_function.val.remarks"
			maxlength="<?= $msg('OBJ.RESULTS.REMARKS.LEN') ?>"
			style="width: 250px;"
			></textarea>
		</div>
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name">
				<?= $msg('OBJ.RESULTS.STATUS') ?><br>
			</div>
			<select data-id="isolation_flow_user_function.val.status">
				<?php foreach($messages['DEFINITIONS']['RESULTS_STATUSES'] as $key => $value){ ?>
					<option value="<?= $key ?>"><?= $value ?></option>
				<?php } ?>
			</select>
		</div>
		<button id="isolation_flow_btn_save_results" class="ISFL-btn-square-so-pop">
			<?= $msg('BTN.SAVE') ?>
		</button>
		<button id="isolation_flow_btn_make_results_str" class="ISFL-btn-square-soft">
			<?= $msg('BTN.MAKE_RESULTS_STR') ?>
		</button>
	</div>

	<!-- フロー -->
	<div id="ISFL_editor_dialog_preview_all_flows">
		<ul data-id="isolation_flow_user.list" class="ISFL-user-container" style="height: 400px; overflow-y: scroll;">
		</ul>
	</div>
	
	<!-- フローの表示 -->
	<?php require('inc_tmpl_isolation_flow_user_list_item.php'); ?>
	
	<!-- 故障切り分け結果選択 -->
	<?php require('inc_tmpl_dialog_select_flow_results.php');?>

	<!-- 切り分け結果を文字列にするダイアログ -->
	<script id="ISFL_dialog_make_results_str" type="text/x-handlebars-template">
		<textarea style="width: 90%; height: 90%;">{{results_str}}</textarea>
	</script>

	<!-- 切り分け結果の統計情報表示のダイアログ -->
	<?php require('inc_tmpl_dialog_count_flow_results.php');?>
	
	<!-- 切り分け結果のダウンロードのダイアログ -->
	<?php require('inc_tmpl_dialog_download_results_count.php');?>
	

	<!-- 共通のモーダルダイアローグ -->
	<?php require('inc_tmpl_dialog_common.php'); ?>

</section><!-- /#ISFL_isolation_flow_user -->



<script>
//ユーザのFlow制御を生成
ISFL.createUserExec = function(data){
	//if(typeof isflId === 'undefined') throw new TypeError('isflId must have value.');
	ISFL.userExec = new ISFL.IsolationFlowUser('#ISFL_isolation_flow_history', 
		"<?php echo wp_create_nonce( 'wp_rest' ); ?>", data, 
		{user_item_list: '#ISFL_isolation_flow_user_list_item'}
	);
	return ISFL.userExec;
};

//表示する
ISFL.displayHistory = function(resultId){
	//
	let dataAndResults = {};
	jQuery.ajax({
		url       : ISFL.API_INFO.uriPrefix + "/flow_results",
		method    : 'GET', 
		data      : {
						'result_id': resultId
					},
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			'X-WP-Nonce': ISFL.API_INFO.xWpNonce
		}
	}).then(function(json) {
		if(json['amount'] == 0) throw new TypeError('unexpected error. amount is zero.');
		let results = json['list'][0];
		dataAndResults['results'] = results;
		//切り分けフローを取得
		return jQuery.ajax({
			url       : ISFL.API_INFO.uriPrefix + "/flow_groups/"+results.isfl_id+'/'+results.revision,
			method    : 'GET', 
			data      : {},
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-WP-Nonce': ISFL.API_INFO.xWpNonce
			}
		});
	}).then(function(json2){
		//切り分け結果の検索結果を表示
		let data = json2;
		dataAndResults['data'] = data['user_flows'];
		//フローの設定
		let userExec = ISFL.createUserExec(data);
		userExec._proceedUserFlow(dataAndResults['results']);
		userExec._disableFlowButtons(null, true);
		//最後のフローのボタンを使えなくする
		userExec.findElements("li .ISFL-user-item-choice__buttons").css('display', 'none');
		//備考項目のHTML表示
		let remarks = dataAndResults['results']['remarks'];
		userExec.resultRemarksFunctionVal(remarks);
		let status = dataAndResults['results']['status'];
		userExec.resultStatusFunctionVal(status);
	}).fail(function(data){
		ISFL.userExec.transactJsonErrByAlert(data);
	});
}


//-----------------------------------------
//切り分け実行結果の選択ダイアログ
document.getElementById('isolation_flow_btn_find_results').addEventListener('click', function(event){
	let dialog = new ISFL.DialogFlowResultsSelect('#ISFL_isolation_flow_history', 
		"<?php echo wp_create_nonce( 'wp_rest' ); ?>", 
		'ISFL_editor_modal', '#ISFL_dialog_select_flow_results', '#ISFL_dialog_flow_results_list',
		{
			'title': '<?= $msg('HTML.DIALOG.TITLE.SELECT_RESULTS') ?>', 
			top: "10%", left: "10%", height: "80%"
		}
	);
	//
	dialog.setDeterminingCallback(function(resultId){
		ISFL.displayHistory(resultId);
		this.closeModalDialog();
	});
	dialog.display({});
});

//切り分け実行結果の選択ダイアログ
document.getElementById('isolation_flow_btn_save_results').addEventListener('click', function(event){
	event.preventDefault();
	if(!ISFL.userExec) return;
	//値の取得
	ISFL.userExec.onClickUpdateResultRemarks(event);
	//アラートディスプレイを表示
	ISFL.userExec.displayAlert();

	//処理を後で書く
	let resultId = ISFL.userExec.results.result_id;
	let results = {"results": ISFL.userExec.results};
	jQuery.ajax({
		url       : ISFL.API_INFO.uriPrefix + "/flow_results/"+resultId,
		method    : 'POST', 
		data      : JSON.stringify(results),
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': ISFL.API_INFO.xWpNonce
		}
	}).fail(function(data) {
		//console.log(data.responseJSON);
		ISFL.userExec.transactJsonErrByAlert(data);
	}).always(function(){
		alert(ISFL.userExec.getMsg('OK.SUCCESS'));
		ISFL.userExec.hideAlert();
	});
});



//切り分け実行結果の文字列表示ダイアログ
document.getElementById('isolation_flow_btn_make_results_str').addEventListener('click', function(event){
	event.preventDefault();
	if(!ISFL.userExec) return;
	//値の取得
	let str = ISFL.userExec.createResultsString({});
	let objDialog = ISFL.userExec.getDialog('makeResultsStr');
	if(!objDialog){
		objDialog = ISFL.userExec._createDialog(
			ISFL.Dialog, 
			'#ISFL_dialog_make_results_str', 
			{
				title: ISFL.userExec.getMsg('TITLE.DIALOG.END_FLOWS'), 
				top: "10%", left: "10%", width: "450px", height: "80%"
			}
		);
		ISFL.userExec.setDialog('makeResultsStr', objDialog);
	}
	//表示
	objDialog.display({'results_str': str});
});


//切り分け実行結果の統計情報表示ダイアログ
document.getElementById('isolation_flow_btn_count_results').addEventListener('click', function(event){
	let dialog = new ISFL.DialogFlowResultsCount('#ISFL_isolation_flow_history', 
		"<?php echo wp_create_nonce( 'wp_rest' ); ?>", 
		'ISFL_editor_modal', '#ISFL_dialog_count_flow_results', '#ISFL_editor_dialog_flow_results_count_list',
		{
			'title': '<?= $msg('HTML.DIALOG.TITLE.COUNT_RESULTS') ?>', 
			top: "10%", left: "10%", height: "80%"
		}
	);
	//
	dialog.display({});
});


//切り分け実行結果ダウンロードのダイアログ
document.getElementById('isolation_flow_btn_download_results').addEventListener('click', function(event){
	let dialog = new ISFL.DialogDownloadResultsCount('#ISFL_isolation_flow_history', 
		"<?php echo wp_create_nonce( 'wp_rest' ); ?>", 
		'ISFL_editor_modal', '#ISFL_dialog_download_results_count', null,
		{
			'title': '<?= $msg('HTML.DIALOG.TITLE.DOWNLOAD_RESULTS') ?>', 
			top: "10%", left: "10%", height: "80%"
		}
	);
	//ダイアログ表示
	dialog.display({});
});


</script>
