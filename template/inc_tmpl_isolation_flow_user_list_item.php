<?php
//プラグインマネージャー
$obj = ISFL_IsolationFlowManager::obj();

?>
<!-- 切り分けフローの1つのlistアイテム表示 -->
<script id="ISFL_isolation_flow_user_list_item" type="text/x-handlebars-template">
	<li class="ISFL-user-item" data-id="isolation_flow_user_list_item_{{flow_id}}" data-flow_id="{{flow_id}}"
	 data-start_utc_time="{{start_utc_time}}" data-end_utc_time="{{end_utc_time}}">
		<div class="ISFL-user-item__header">
			[{{flow_id}}] {{#if pt_id}}{{pt_id}}:{{/if}}
			{{title}}
		</div>
		<div class="ISFL-user-item__main">
			{{{quetionHtml}}}
		</div>
		<div class="ISFL-user-item__input">
		{{#each input}}
			{{label}}:<input type="{{type}}" name="{{no}}"
				data-id="isolation_flow_user_list_item_{{@root.flow_id}}.input_{{no}}"
				maxlength="<?= $msg('OBJ.INPUT.VALUE.LEN') ?>"><br>
		{{/each}}
		</div>
		<div class="ISFL-user-item-choice">
			<div class="ISFL-user-item-choice__header"><?= $msg('OBJ.CHOICES') ?>*:</div>
			<div>
				<ul>
				{{#each choices}}
					<li>
					<label>
						{{#if image}}
						<div class="ISFL-user-item-choice__images" style="">
							<img src="{{image}}" alt="No Image"><br>
						</div>
						{{/if}}
						<div class="ISFL-user-item-choice__selection">
							<input type="radio" data-choice_id="{{id}}" 
								name="isolation_flow_user_item_choice_{{@root.flow_id}}" value="{{next_flow_id}}">
							<div style="display: inline-block;">{{label}}</div>
						</div>
					</label>
					</li>
				{{/each}}
				</ul>
			</div>
			<div class="ISFL-user-item-choice__buttons">
				<input type="hidden" data-id="isolation_flow_user_list_item.val.data-status" value="">
				{{eval "$evalVars.isNotClose = !(this.status == 'close')" false}}
				<button data-flow_id="{{@root.flow_id}}" data-status="{{status}}"
				 data-id="isolation_flow_user_list_item.btn_status">
					{{lookup DEFINITIONS.BUTTON_STATUSES status}}
				</button>
				{{#if @evalVars.isNotClose}}
					<button data-flow_id="{{@root.flow_id}}" data-status="close_forcely"
					 data-id="isolation_flow_user_list_item.btn_close_forcely">
						<?= $msg('BTN.CLOSE_FORCELY') ?>
					</button>
				{{/if}}
				<button data-flow_id="{{@root.flow_id}}" 
				 data-id="isolation_flow_user_list_item.btn_back_flow">
					<?= $msg('BTN.GO_BACK') ?>
				</button>
			</div>
		</div>
	</li>
</script><!-- /#ISFL_isolation_flow_user_list_item -->



<!-- 故障切り分け終了時のダイアログ -->
<script id="ISFL_dialog_end_flows" type="text/x-handlebars-template">
	<div class="ISFL-user-explanation">
		<?= $msg('HTML.DESC.END_FLOWS.EXPLAIN') ?>
	</div>
	<input type="hidden" data-id="ISFL_editor_dialog.val.flow_id" value="{{flow_id}}">
	<input type="hidden" data-id="ISFL_editor_dialog.val.decided_button" value="{{decided_button}}">
	<button data-id="ISFL_editor_dialog.btn_determin" 
		class="ISFL-btn-square-so-pop"><?= $msg('BTN.DECISION') ?></button>
	<br>
	<?= $msg('OBJ.RESULTS.STATUS') ?>:
	<select data-id="ISFL_editor_dialog.val.results.status">
		<option value="open">{{lookup DEFINITIONS.RESULTS_STATUSES 'open'}}</option>
		<option value="resolved">{{lookup DEFINITIONS.RESULTS_STATUSES 'resolved'}}</option>
	</select>
	<br>
	<?php if($obj->send_mail == '1'){ ?>
		<label><input type="checkbox" data-id="ISFL_editor_dialog.btn_send_mail" value="1">
		<?= $msg('BTN.SEND_MAIL') ?>
		</label>
		<br>
	<?php } ?>
	<?= $msg('OBJ.RESULTS.REMARKS') ?>:
	<textarea data-id="ISFL_editor_dialog.val.results.remarks" style="width: 400px;"
	 maxlength="<?= $msg('OBJ.RESULTS.REMARKS.LEN') ?>"
	>{{results.remarks}}</textarea>
	
	<!-- 処理中オーバーレイ -->
	<div class="ISFL-editor-modal-overlay-processing" data-id="ISFL_editor_dialog.processing">
		<div style="height:100px"></div>
		<img src="<?= plugins_url('include/processing.gif', dirname(__FILE__))?>">
	</div>
</script><!-- /#ISFL_dialog_end_flows -->



