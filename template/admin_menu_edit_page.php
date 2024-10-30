<?php

$msg = array(ISFL_IsolationFlowManager::obj(), 'getMessage');



?>
<style>
/* 説明の表示非表示 */
.ISFL_isolation_flow_getting_start_label{
	font-size: 14px;
	font-weight: bold;
	text-decoration: underline;
}
#ISFL_isolation_flow_getting_start{
	display: none;
}
#ISFL_isolation_flow_getting_start + section{
	display: none;
	background-color: #888811;
	font-size: 12px;
	font-weight: bold;
	padding: 10px;
}
#ISFL_isolation_flow_getting_start:checked + section{
	display: block;
}
</style>


<h1><?= $msg('MNG.TITLE.SUB_MENU_EDIT'); ?>
	<div class="ISFL-help" >
		<span class="ISFL-help__link">
			<i class="icon-question-circle"></i>
		</span>
		<span class="ISFL-help__balloon">
			<?= $msg('HELP.BTN.EDIT_TITLE'); ?>
		</span>
	</div>
	&nbsp;
	<label for="ISFL_isolation_flow_getting_start" class="ISFL_isolation_flow_getting_start_label">
		<?= $msg('BTN.SHOW_EXPLANATION') ?>
	</label>
</h1>


<!-- 編集方法のやり方記述 -->
<input type="checkbox" id="ISFL_isolation_flow_getting_start">
<section>
	<a href="<?=plugins_url('',dirname(__FILE__))?>/include/sample_flows_tv_jp.json" download="sample_flows_tv_jp.json">サンプル(japanese)</a>
	&nbsp;&nbsp;
	<a href="<?=plugins_url('',dirname(__FILE__))?>/include/sample_flows_tv_en.json" download="sample_flows_tv_en.json">sample(english)</a>
	<br>
	<?= $msg('HTML.DESC.FLOWS_EDIT') ?>
</section>


<section id="ISFL_isolation_flow_editor" class="ISFL-editor-section"
 style="position: relative; background-color: #dddddd; margin-bottom: 80px;">

<div class="ISFL-editor-entire-btn">
	<button id="ISFL_editor_btn_save_flows" class="ISFL-btn-square-so-pop"><?= $msg('BTN.SAVE_FLOW') ?></button>
	<div class="ISFL-help">
		<span class="ISFL-help__link">
			<i class="icon-question-circle"></i>
		</span>
		<span class="ISFL-help__balloon">
			<?= $msg('HELP.BTN.EDIT_BTNS'); ?>
		</span>
	</div>
	<button id="ISFL_editor_btn_preview_all" class="ISFL-btn-square-soft"><?= $msg('BTN.PREVIEW_FLOW') ?></button>
	<input type="file" style="display: none;" id="ISFL_editor_btn_import_flows_file">
	<button id="ISFL_editor_btn_import_flows" class="ISFL-btn-square-soft"><?= $msg('BTN.IMPORT_FLOW') ?></button>
	<button id="ISFL_editor_btn_export_flows" class="ISFL-btn-square-soft"><?= $msg('BTN.EXPORT_FLOW') ?></button>
	<button id="ISFL_editor_btn_select_flows" class="ISFL-btn-square-soft"><?= $msg('BTN.SELECT_FLOW') ?></button>
</div>

<div class="ISFL-editor-container">
	<div class="ISFL-editor-layer-entire">
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name">
				<?= $msg('OBJ.ISFL_ID') ?>:
			</div>
			<input type="text" data-id="ISFL_editor_entire.val.isfl_id" style="width: 70px;" readonly>
		</div>
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name">
				<?= $msg('OBJ.GROUP_TITLE') ?>*:
			</div>
			<input type="text" data-id="ISFL_editor_entire.val.group_title" style="width: 200px;" maxlength="<?= $msg('OBJ.GROUP_TITLE.LEN') ?>">
		</div>
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name">
				<?= $msg('OBJ.KEYWORDS') ?>
				<div class="ISFL-help ISFL-help--center">
					<span class="ISFL-help__link">
						<i class="icon-question-circle"></i>
					</span>
					<span class="ISFL-help__balloon">
						<?= $msg('HELP.OBJ.KEYWORDS'); ?>
					</span>
				</div>
				:
			</div>
			<input type="text" data-id="ISFL_editor_entire.val.keywords" style="width: 180px;" maxlength="<?= $msg('OBJ.KEYWORDS.LEN') ?>">
		</div>
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name">
				<?= $msg('OBJ.GROUP_REMARKS') ?>
				<div class="ISFL-help ISFL-help--center">
					<span class="ISFL-help__link">
						<i class="icon-question-circle"></i>
					</span>
					<span class="ISFL-help__balloon">
						<?= $msg('HELP.OBJ.GROUP_REMARKS'); ?>
					</span>
				</div>
				:
			</div>
			<input type="text" data-id="ISFL_editor_entire.val.group_remarks" style="width: 180px;" maxlength="<?= $msg('OBJ.GROUP_REMARKS.LEN') ?>">
		</div>
	</div><!-- /.ISFL-editor-layer-entire -->
	
	<div class="ISFL-editor-layer-canvas-and-prop">
	<input type="checkbox" id="ISFL_editor_prop_switch" class="ISFL-editor-accordion-prop-switch">
	
	<div id="canvas_disp" class="ISFL-editor-canvas ISFL-editor-accordion-prop-big">
	</div>

	<!-- プロパティウィンドウ -->
	<div data-id="ISFL_editor_prop" class="ISFL-editor-prop ISFL-editor-accordion-prop-small">
		<script id="ISFL_editor_prop" type="text/x-handlebars-template">
		<!-- プロパティ入力項目 -->
		<div class="ISFL-editor-item ISFL-editor-accordion-prop-small--hide">
			<input type="hidden" data-id="ISFL_editor_prop.val.flow_id" value="{{flow_id}}" data-original="{{$flow_id}}">
			<input type="hidden" data-id="ISFL_editor_prop.val.revision" value="{{revision}}" data-original="{{$revision}}">
			<div class="ISFL-editor-item__header">
				[{{flow_id}}]
				<div class="ISFL-help">
					<span class="ISFL-help__link">
						<i class="icon-question-circle"></i>
					</span>
					<span class="ISFL-help__balloon">
						<?= $msg('HELP.OBJ.FLOW_ID'); ?>
					</span>
				</div>
				<?= $msg('OBJ.FLOW.PT_ID') ?>
				<div class="ISFL-help ISFL-help--center">
					<span class="ISFL-help__link">
						<i class="icon-question-circle"></i>
					</span>
					<span class="ISFL-help__balloon">
						<?= $msg('HELP.OBJ.PT_ID'); ?>
					</span>
				</div>
				:
				<input type="text" data-id="ISFL_editor_prop.val.pt_id" data-original="{{$pt_id}}"
				 value="{{pt_id}}" maxlength="<?= $msg('OBJ.FLOW.PT_ID.LEN') ?>">
				<br>
				<?= $msg('OBJ.FLOW.TITLE') ?>*:
				<input type="text" data-id="ISFL_editor_prop.val.title" data-original="{{$title}}"
				 value="{{title}}" style="width: 200px;" maxlength="<?= $msg('OBJ.FLOW.TITLE.LEN') ?>">
			</div>
			<div class="ISFL-editor-item__main">
				<?= $msg('OBJ.FLOW.STATUS') ?>*
				<div class="ISFL-help ISFL-help--center">
					<span class="ISFL-help__link">
						<i class="icon-question-circle"></i>
					</span>
					<span class="ISFL-help__balloon">
						<?= $msg('HELP.OBJ.FLOW.STATUS'); ?>
					</span>
				</div>
				:
				{{eval "$evalVars.status = this.status" false}}
				<select data-id="ISFL_editor_prop.val.status" data-original="{{$status}}">
				{{#each @root.DEFINITIONS.FLOW_STATUSES}}
					<option value="{{@key}}" {{eval "$evalVars.status == ${@key} ? 'selected' : ''" true}}>{{this}}</option>
				{{/each}}
				</select>
				<br>
				<div class="ISFL-editor-item--subtitle">
					<?= $msg('OBJ.FLOW.QUESTION') ?>*
					<div class="ISFL-help">
						<span class="ISFL-help__link">
							<i class="icon-question-circle"></i>
						</span>
						<span class="ISFL-help__balloon">
							<?= $msg('HELP.OBJ.FLOW.QUESTION') ?>
						</span>
					</div>
					<button data-id="ISFL_editor_prop.btn_select_question_image"><i class="icon-image"></i></button>
					<div class="ISFL-name-popup"><span><?= $msg('BTN.INSERT_IMAGE') ?></span></div>
					<button data-id="ISFL_editor_prop.btn_select_question_input"><i class="icon-comment-dots"></i></button>
					<div class="ISFL-name-popup"><span><?= $msg('BTN.INSERT_INPUT') ?></span></div>
					<button data-id="ISFL_editor_prop.btn_input_question_link"><i class="icon-link"></i></button>
					<div class="ISFL-name-popup"><span><?= $msg('BTN.INSERT_URL') ?></span></div>
				</div>
				
				<textarea data-id="ISFL_editor_prop.val.question" data-original="{{$question}}"
				 maxlength="<?= $msg('OBJ.FLOW.QUESTION.LEN') ?>">{{{question}}}</textarea>
			</div>
			<div class="ISFL-editor-item__input">
				<div class="ISFL-editor-item--subtitle">
					<?= $msg('OBJ.INPUT') ?>
					<div class="ISFL-help">
						<span class="ISFL-help__link">
							<i class="icon-question-circle"></i>
						</span>
						<span class="ISFL-help__balloon">
							<?= $msg('HELP.OBJ.INPUT') ?>
						</span>
					</div>
				</div>
				<table>
				<tr>
					<th><?= $msg('OBJ.INPUT.NO') ?></th><th><?= $msg('OBJ.INPUT.LABEL') ?></th><th><?= $msg('OBJ.INPUT.TYPE') ?></th><th><?= $msg('BTN.PROCESS') ?></th>
				</tr>
			{{#each input}}
				<tr>
				<td>
					<input type="text" data-id="ISFL_editor_prop.val.input[{{@index}}].no"
					 data-original="{{$no}}" value="{{no}}" style="width: 40px;">
				</td>
				<td>
					<input type="text" data-id="ISFL_editor_prop.val.input[{{@index}}].label"
					 data-original="{{$label}}" value="{{label}}"
					 maxlength="<?= $msg('OBJ.INPUT.LABEL.LEN') ?>">
				</td>
				<td>
					<select data-id="ISFL_editor_prop.val.input[{{@index}}].type" data-original="{{$type}}">{{type}}
					<option value="text" {{#if (eq type 'text')}}selected{{/if}}>text</option>
					</select>
				</td>
				<td>
					<button data-id="ISFL_editor_prop.btn_del_input_line" data-input_no="{{no}}">削除</button>
				</td>
				</tr>
			{{/each}}
				</table>
				{{eval "$evalVars.num = this.input.length" false}}
				<input type="hidden" data-id="ISFL_editor_prop.val.input_length" data-original="{{$input_length}}" value="{{input_length}}"></span>
				<button data-id="ISFL_editor_prop.btn_add_input_line"><?= $msg('BTN.ADD') ?></button>
			</div>
			<div class="ISFL-editor-item-choice">
				<div class="ISFL-editor-item--subtitle">
					<?= $msg('OBJ.CHOICES') ?>*
					<div class="ISFL-help">
						<span class="ISFL-help__link">
							<i class="icon-question-circle"></i>
						</span>
						<span class="ISFL-help__balloon">
							<?= $msg('HELP.OBJ.CHOICES') ?>
						</span>
					</div>
				</div>
				<div>
					<ul>
					{{#each choices}}
						<li>
						<label>
							<input type="radio" data-flow_id="{{id}}" data-choice-index="{{@index}}"
							 data-id="ISFL_editor_prop.btn_choice_selection"
							 name="ISFL_editor_prop.choice_selection">
							<div class="ISFL-editor-item-choice__images">
								<input type="hidden" data-id="ISFL_editor_prop.val.choices[{{@index}}].image"
								 data-original="{{$image}}" value="{{image}}">
								<input type="hidden" data-id="ISFL_editor_prop.val.choices[{{@index}}].attachment_id"
								 data-original="{{$attachment_id}}" value="{{attachment_id}}">
							{{#if image}}
								<img src="{{image}}" alt="No Image">
								<button data-id="ISFL_editor_prop.btn_delete_choice_image" data-index="{{@index}}">
									<i class="icon-trash-alt"></i>
								</button>
								<div class="ISFL-name-popup"><span><?= $msg('BTN.DELETE_IMAGE') ?></span></div>
							{{else}}
								<button data-id="ISFL_editor_prop.btn_add_choice_image" data-index="{{@index}}">
									<i class="icon-image"></i>
								</button>
								<div class="ISFL-name-popup"><span><?= $msg('BTN.INSERT_IMAGE') ?></span></div>
							{{/if}}
							</div>
							<div class="ISFL-editor-item-choice__selection">
								{{id}}
								<?= $msg('OBJ.CHOICES.NEXT_FLOW_ID') ?>*:
								<input type="text" 
								 data-id="ISFL_editor_prop.val.choices[{{@index}}].next_flow_id"
								 data-choice-index="{{@index}}"
								 data-original="{{$next_flow_id}}" value="{{next_flow_id}}" 
								 style="width: 80px;" readonly>
								<input type="hidden" data-id="ISFL_editor_prop.val.choices[{{@index}}].id"
								 data-original="{{$id}}"
								 value="{{id}}"><br>
								<?= $msg('OBJ.CHOICES.LABEL') ?>*:
								<input type="text" data-id="ISFL_editor_prop.val.choices[{{@index}}].label"
								 data-original="{{$label}}" value="{{label}}"
								 maxlength="<?= $msg('OBJ.CHOICES.LABEL.LEN') ?>"><br>
								
							</div>
						</label>
						</li>
					{{/each}}
					</ul>
					{{eval "$evalVars.num = this.choices.length" false}}
					<input type="hidden" data-id="ISFL_editor_prop.val.choices_length" data-original="{{$choices_length}}" value="{{choices_length}}"></span>
					<br>
					<button data-id="ISFL_editor_prop.btn_add_choices_line"><?= $msg('BTN.ADD') ?></button>
					<button data-id="ISFL_editor_prop.btn_del_choices_line"><?= $msg('BTN.DEL') ?></button>
				</div>
				<div class="ISFL-editor-item-choice__buttons">
					<button data-id="ISFL_editor_prop.btn_update_flow" data-flow_id="{{@root.flow_id}}"><?= $msg('BTN.UPDATE') ?></button>
					<button data-id="ISFL_editor_prop.btn_rollback_flow" data-flow_id="{{@root.flow_id}}"><?= $msg('BTN.CANCEL') ?></button>
				</div>
			</div>
		</div><!--- /.ISFL-editor-item -->
		
		<!-- テスト表示用領域（フローの表示） -->
		<div class="ISFL-editor-prop-preview" id="ISFL_editor_prop_preview" data-id="ISFL_editor_prop.preview">
			<div class="ISFL-editor-prop-preview--off">
				<button data-id="ISFL_editor_prop.btn_prop_preview"><i class="icon-arrow-alt-circle-up"></i></button>
				<div class="ISFL-name-popup"><span><?= $msg('BTN.PREVIEW_ONE_FLOW') ?></span></div>
			</div>
			<div class="ISFL-editor-prop-preview--on">
				<button data-id="ISFL_editor_prop.btn_hide_prop_preview"><i class="icon-arrow-alt-circle-down"></i><?= $msg('BTN.HIDE_PREVIEW_ONE_FLOW') ?></button>
				<button data-id="ISFL_editor_prop.btn_update_prop_preview"><i class="icon-caret-square-right"></i><?= $msg('BTN.UPDATE_PREVIEW') ?></button>
				<br>
				<ul data-id="isolation_flow_user.list" class="isolation-flow-user-container">
				</ul>
			</div>
		</div>

		<!-- アコーディオンボタン -->
		<div class="ISFL-editor-accordion-prop">
			<label for="ISFL_editor_prop_switch">
				<span class="ISFL-editor-accordion-prop-small--hide">
					<i class="icon-fa-angle-double-right"></i>
				</span>
				<span class="ISFL-editor-accordion-prop-small--show">
					<i class="icon-fa-angle-double-left"></i>
				</span>
			</label>
		</div>

		</script><!-- /#ISFL_editor_prop -->
		
	</div><!-- /ISFL_editor_prop -->
	</div>



	<!-- 次の遷移先を決めるダイアログ -->
	<script id="ISFL_editor_dialog_select_next_flow" type="text/x-handlebars-template">
		<div class="ISFL-editor-explanation">	
			<i class="icon-exclamation-triangle"></i>
			<?= $msg('HTML.DESC.DIALOG.SELECT_NEXT_FLOW.SELECT_NEXT_FLOW',true) ?>
		</div>
		<label>
			<input type="radio" data-id="ISFL_editor_dialog.flow_creation_type" name="ISFL_editor_dialog.flow_creation_type" value="select" checked>
			<?= $msg('HTML.DESC.DIALOG.SELECT_NEXT_FLOW.SELECT_EXISTING_FLOW') ?>
		<label><br>
		<input type="text" data-id="ISFL_editor_dialog.next_flow_id" value="{{current_next_flow_id}}"><br>
		<?= $msg('HTML.DESC.DIALOG.SELECT_NEXT_FLOW.SELECT_EXISTING_FLOW.DESC') ?><br>
		<br>
		<label>
			<input type="radio" data-id="ISFL_editor_dialog.flow_creation_type" name="ISFL_editor_dialog.flow_creation_type" value="new">
			<?= $msg('HTML.DESC.DIALOG.SELECT_NEXT_FLOW.NEW') ?>
		<label><br>
		<table>
		<tr>
			<th><?= $msg('OBJ.FLOW.PT_ID') ?></th><td><input type="text" data-id="ISFL_editor_dialog.val.pt_id"></td>
		</tr>
		<tr>
			<th><?= $msg('OBJ.FLOW.TITLE') ?>*</th>
			<td>
				<input type="text" data-id="ISFL_editor_dialog.val.title" maxlength="<?= $msg('OBJ.FLOW.TITLE.LEN') ?>">
			</td>
		</tr>
		<tr>
			<th><?= $msg('OBJ.FLOW.QUESTION') ?>*</th>
			<td>
				<textarea data-id="ISFL_editor_dialog.val.question" style="width:200px; height:70px;"
				 maxlength="<?= $msg('OBJ.FLOW.QUESTION.LEN') ?>"></textarea>
			</td>
		</tr>
		</table>
		<input type="hidden" data-id="ISFL_editor_dialog.choices_index" value="{{choices_index}}">
		<br>
		<button data-id="ISFL_editor_dialog.next_flow_id.btn_save" class="ISFL-btn-square-so-pop"><?= $msg('BTN.SAVE') ?></button>
		<button data-id="ISFL_editor_dialog.next_flow_id.btn_cancel" class="ISFL-btn-square-soft"><?= $msg('BTN.CANCEL') ?></button>
	</script>


	<!-- 入力項目の指定ダイアログ -->
	<script id="ISFL_editor_dialog_select_input_no" type="text/x-handlebars-template">
		<div class="ISFL-editor-explanation">		
			<?=$msg('HTML.DESC.DIALOG.SELECT_INPUT_NO')?>
		</div>
		<button data-id="ISFL_editor_dialog.btn_determin_input" class="ISFL-btn-square-so-pop">
			<?=$msg('BTN.DECISION')?>
		</button>
		<br>
		<br>
		<table>
		<tr>
			<th>[<?=$msg('OBJ.FLOW.FLOW_ID')?>] <?=$msg('OBJ.FLOW.PT_ID')?></th><th><?=$msg('OBJ.INPUT.NO')?></th>
		</tr>
		{{#each flows}}
			{{#each input}}
				<tr>
					{{#if (eq @index 0)}}
						<td rowspan="{{../input.length}}">[{{../flow_id}}] {{../pt_id}} 
							{{../title}}
						</td>
					{{/if}}
					<td>
					<label>
						<input type="radio" name="ISFL_editor_dialog.val.input.no" data-flow_id="{{../flow_id}}" data-no="{{no}}">
						[{{no}}]{{label}}
					</label>
					</td>
				</tr>
			{{/each}}
		{{/each}}
		
		</table>
	</script>
	
	
	<!-- 全体のフローのプレビューのダイアログ -->
	<script id="ISFL_editor_dialog_preview_all" type="text/x-handlebars-template">
		<div class="ISFL-editor-explanation">	
			<?=$msg('HTML.DESC.DIALOG.PREVIEW_ALL')?><br>
		</div>
		<div id="ISFL_editor_dialog_preview_all_flows">
			<ul data-id="isolation_flow_user.list" class="ISFL-user-container" style="height: 400px; overflow-y: scroll;">
			</ul>
		</div>
	</script>


	<!-- 画像選択のダイアログ -->
	<?php require('inc_tmpl_dialog_select_img.php'); ?>


	<!-- 切り分けフロー選択 -->
	<?php require('inc_tmpl_dialog_select_flow_group.php'); ?>


	<!-- フローのテストプレビュー表示 -->
	<?php require('inc_tmpl_isolation_flow_user_list_item.php'); ?>


	<!-- 共通のモーダルダイアログ -->
	<?php require('inc_tmpl_dialog_common.php'); ?>
	

	<!-- 処理中ダイアログ -->
	<script id="ISFL_editor_dialog_processing" type="text/x-handlebars-template">
		<?= $msg('HTML.DESC.PROCESSING') ?>
	</script>
	
	
</div><!-- /.ISFL-editor-container -->
</section><!-- /#ISFL_isolation_flow_editor -->



<script>
</script>

<script>
//プロパティウィンドウとCanvasの制御
ISFL.editor = new ISFL.IsolationFlowEditor("#ISFL_isolation_flow_editor", 
	ISFL.API_INFO.xWpNonce, "canvas_disp");
//ISFL.editor.import(ISFL.testData);


jQuery(window).on('beforeunload', function(event) {
	console.log('beforeunload');
	return 'jquery beforeunload';
});

//保存ボタン
document.getElementById('ISFL_editor_btn_save_flows')
.addEventListener('click', function(){
	ISFL.editor.requestSaveFlowGroups();
});

//全部のFlow動作のプレビューボタン
document.getElementById('ISFL_editor_btn_preview_all')
.addEventListener('click', function(){
	const xWpNonce = ISFL.API_INFO.xWpNonce;
	//ダイアログの作成
	let dialog = new ISFL.Dialog("#ISFL_isolation_flow_editor", xWpNonce, 
		'ISFL_editor_modal', "#ISFL_editor_dialog_preview_all", 
		{title: ISFL.editor.getMsg('TITLE.DIALOG.PREVIEW_ALL'), width: '750px', height: '550px', top: '30px'}
	);
	//ダイアログ生成
	dialog.display({});

	//シミュレーション用のユーザのFlow制御を生成
	let test = new ISFL.IsolationFlowUser('#ISFL_editor_dialog_preview_all_flows', 
		xWpNonce, ISFL.editor.data, 
		{user_item_list: '#ISFL_isolation_flow_user_list_item'}
	);
	test.displayUserFlow();
});

//Flow定義のインポート（ファイルアップロード）
document.getElementById('ISFL_editor_btn_import_flows')
.addEventListener('click', function(){
	let objFile = document.getElementById('ISFL_editor_btn_import_flows_file');
	objFile.click();
	return false;
});

//fileインプットボタンの処理
document.getElementById('ISFL_editor_btn_import_flows_file')
.addEventListener('change', function(event){
	if (!window.confirm(ISFL.editor.getMsg('HTML.WARN.EDIT.COMFIRM_DISCARD_CHANGE' ))) { 
		return;
	}
	//ファイル読み込みisfl_id>=1以外の場合はエラーにする。（0は新規なので、インポートするたびに新規作成し、まずい）
	let reader = new FileReader();
	reader.onload = function(readerEvent) {
		let jsonText = readerEvent.target.result;
		try{
			let json = JSON.parse(jsonText);
			ISFL.editor.import(json);
		}catch(err){
			if(typeof err.message === "string"){
				let match = err.message.match(/JSON at position ([0-9]+)/);
				let pos = parseInt(match[1]);
				let pos1 = (pos-20<0 ? 0 : pos-20)
				alert('error JSON at position ' + pos + ': around: ' + jsonText.substr(pos1, 40));
			}
		}
	}
	reader.readAsText(event.target.files[0]);
});
	

//Flow定義のエキスポート（ファイルダウンロード）
document.getElementById('ISFL_editor_btn_export_flows')
.addEventListener('click', function(){
	content = JSON.stringify(ISFL.editor.data, null, ' ');
	ISFL.editor.handleDownload("flows.json", content);
});

//既存の切り分けフローの編集
document.getElementById('ISFL_editor_btn_select_flows')
.addEventListener('click', function(){
	//ダイアログの作成
	let dialog = new ISFL.DialogFlowGroupsSelect(
		"#ISFL_isolation_flow_editor", ISFL.API_INFO.xWpNonce, 
		'ISFL_editor_modal','#ISFL_dialog_select_flow_group', "#ISFL_dialog_select_flow_group_list", 
		{title: ISFL.editor.getMsg('TITLE.DIALOG.SELECT_FLOW_GROUP'), width: '700px', height: '550px', top: '30px'}
	);
	//
	dialog.setDeterminingCallback(function(id, revision){
		//指定の切り分けIDのフローを取得する
		let promise = ISFL.editor.requestGetFlowGroups(Number.parseInt(id), Number.parseInt(revision));
		if(promise == null) return false;
	});
	dialog.display({});
});

</script>


