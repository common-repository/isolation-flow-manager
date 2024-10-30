<?php

$msg = array(ISFL_IsolationFlowManager::obj(), 'getMessage');

?>


<h1><?= $msg('MNG.TITLE.SUB_MENU_EXEC') ?>
	<div class="ISFL-help">
		<span class="ISFL-help__link">
			<i class="icon-question-circle"></i>
		</span>
		<span class="ISFL-help__balloon">
			<?= $msg('HELP.BTN.EXEC_TITLE'); ?>
		</span>
	</div>
</h1>


<section id="ISFL_isolation_flow_user" class="ISFL-user-section">
<!-- 保存時等の成功・失敗メッセージ -->
<div data-id="user_flows_process_message" class="ISFL-popup-content">
	<script id="ISFL_user_flows_process_message" type="text/x-handlebars-template">
		<div class="ISFL-popup-content__bound-animation">{{msg}}</div>
	</script>
</div>
<!-- 動的メッセージ -->
<button id="ISFL_editor_btn_exec_new_flow" class="ISFL-btn-square-soft">
	<?=$msg('BTN.EXEC_NEW_FLOW')?>
</button>
<button id="ISFL_editor_btn_find_uncomplete_results" class="ISFL-btn-square-soft">
	<?=$msg('BTN.FIND_UNCOMPLETE_RESULTS')?>
</button>
<span id="ISFL_editor_uncomplete_flow_msg"><?=$msg('HTML.DESC.PROCESSING')?></span>
<!-- メイン -->
<div class="ISFL-main-container">
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
	<!-- 上部　隠し機能 -->
	<input type="checkbox" id="ISFL_user_flows_function_switch" data-id="user_flows_function_switch" class="ISFL-user-flows-function-switch">
	<div class="ISFL-user-flows-function" data-id="user_flows_function">
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name">
				<?= $msg('OBJ.RESULTS.REMARKS') ?><br>
				<button data-id="user_flows_function.btn_save_remarks" class="ISFL-btn-square-so-pop"><?= $msg('BTN.SAVE') ?></button>
			</div>
			<textarea data-id="isolation_flow_user_function.val.remarks"
			maxlength="<?= $msg('OBJ.RESULTS.REMARKS.LEN') ?>"
			style="width: 250px;"
			></textarea>
		</div>
	</div>
	<!-- 右側の切り分けフロー -->
	<div id="ISFL_editor_dialog_preview_all_flows">
		<ul data-id="isolation_flow_user.list" class="ISFL-user-container" style="height: 400px; overflow-y: scroll;">
		</ul>
	</div>
	<!-- 左側メニュー -->
	<div class="ISFL-user-left-sidebar">
		<label for="ISFL_user_flows_function_switch">
			<i class="icon-atlas" style="font-size: 20px;"></i>
		</label>
		<div class="ISFL-name-popup"><span><?= $msg('BTN.WRITE_RESULTS_RMARKS') ?></span></div>
	</div>
	
	<!-- フローの表示 -->
	<?php require('inc_tmpl_isolation_flow_user_list_item.php'); ?>
	

	<!-- 故障切り分け結果選択（途中になっている結果から実行する） -->
	<?php require('inc_tmpl_dialog_select_flow_results.php');?>


	<!-- 故障切り分け終了時のダイアログ -->
	<script id="ISFL_dialog_end_flows" type="text/x-handlebars-template">
		<div class="ISFL-user-explanation">
			<?= $msg('HTML.DESC.SELECT_RESULTS.EXPLAIN') ?>
		</div>
		<input type="hidden" data-id="ISFL_editor_dialog.val.decided_button" value="{{decided_button}}">
		<button data-id="ISFL_editor_dialog.btn_determin" data-flow_id="{{flow_id}}"
		 class="ISFL-btn-square-so-pop"><?= $msg('BTN.DECISION') ?></button>
		<br>
		<?= $msg('OBJ.RESULTS.STATUS') ?>:
		<select data-id="ISFL_editor_dialog.val.result.status">
			<option value="open">{{lookup DEFINITIONS.RESULTS_STATUSES 'open'}}</option>
			<option value="resolved">{{lookup DEFINITIONS.RESULTS_STATUSES 'resolved'}}</option>
		</select>
		<br>
		<?= $msg('OBJ.RESULTS.REMARKS') ?>:
		<textarea data-id="ISFL_editor_dialog.val.result.remarks">{{result.remarks}}</textarea>
		
		<!-- 処理中オーバーレイ -->
		<div class="ISFL-editor-modal-overlay-processing" data-id="ISFL_editor_dialog.processing">
			<div style="height:100px"></div>
			<img src="<?= plugins_url('include/processing.gif', dirname(__FILE__))?>">
		</div>
	</script>


	<!-- 切り分けフロー選択 -->
	<?php require('inc_tmpl_dialog_select_flow_group.php'); ?>


	<!-- 次の遷移先を決めるダイアログ -->
	<script id="ISFL_editor_dialog_processing" type="text/x-handlebars-template">
		処理中...
	</script>


	</div><!-- /.ISFL-editor-container -->
	
	
	<!-- 共通のモーダルダイアローグ -->
	<?php require('inc_tmpl_dialog_common.php'); ?>

</section><!-- /#ISFL_isolation_flow_user -->



<script>

//ユーザのFlow制御を生成
ISFL.createUserExec = function(){
	//if(typeof isflId === 'undefined') throw new TypeError('isflId must have value.');
	ISFL.userExec = new ISFL.IsolationFlowUserExec('#ISFL_isolation_flow_user', 
		"<?php echo wp_create_nonce( 'wp_rest' ); ?>", null, 
		{user_item_list: '#ISFL_isolation_flow_user_list_item'}
	);
	return ISFL.userExec;
};


//--------------------------------------
//未完了のフロー結果を探して表示
//--------------------------------------
jQuery.ajax({
	url       : ISFL.API_INFO.uriPrefix + "/flow_results",
	method    : 'GET', 
	data      : {
					'status': 'created'
				},
	headers: {
		'Content-Type': 'application/x-www-form-urlencoded',
		'X-WP-Nonce': ISFL.API_INFO.xWpNonce
	}
}).then(function (json) {
	//切り分け結果の検索結果を表示
	let objSpan = document.getElementById('ISFL_editor_uncomplete_flow_msg');
	let strHtml = '';
	if(json['amount'] == 0){
		strHtml = '';
	}else{
		strHtml = '<?= $msg('HTML.DESC.UNCOMPLETE_FLOW_MESSAGE') ?>';
	}
	objSpan.innerHTML = strHtml;
}).fail(function(data){
	ISFL.userExec.transactJsonErrByAlert(data);
});



//-----------------------------------------
//切り分け実行結果の選択ダイアログ
document.getElementById('ISFL_editor_btn_find_uncomplete_results').addEventListener('click', function(event){
	let dialog = new ISFL.DialogFlowResultsSelect('#ISFL_isolation_flow_user', 
		"<?php echo wp_create_nonce( 'wp_rest' ); ?>", 
		'ISFL_editor_modal', '#ISFL_dialog_select_flow_results', '#ISFL_dialog_flow_results_list',
		{
			'title': '<?= $msg('HTML.DIALOG.TITLE.SELECT_RESULTS') ?>', 
			top: "10%", left: "10%", height: "80%"
		}
	);
	//
	dialog.setDeterminingCallback(function(resultId){
		let userExec = ISFL.createUserExec();
		userExec.importFromServerByResultId(resultId);
		//this.closeModalDialog();
	});
	dialog.display({});
});


//切り分けフローの選択ダイアログ
document.getElementById('ISFL_editor_btn_exec_new_flow').addEventListener('click', function(event){
	let dialog = new ISFL.DialogFlowGroupsSelect('#ISFL_isolation_flow_user', 
		"<?php echo wp_create_nonce( 'wp_rest' ); ?>", 
		'ISFL_editor_modal', '#ISFL_dialog_select_flow_group', '#ISFL_dialog_select_flow_group_list',
		{
			'title': '<?= $msg('HTML.DIALOG.TITLE.SELECT_FLOW_GOURP') ?>', 
			top: "10%", left: "10%", height: "80%"
		}
	);
	//
	dialog.setDeterminingCallback(function(isflId){
		let userExec = ISFL.createUserExec();
		userExec.importFromServerByIsflId(isflId);
		//this.closeModalDialog();
	});
	dialog.display({});
	
});

</script>
