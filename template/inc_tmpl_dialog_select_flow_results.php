
<!-- 故障切り分け結果選択（途中になっている結果から実行する） -->
<script id="ISFL_dialog_select_flow_results" type="text/x-handlebars-template">
	<div class="ISFL-user-explanation">
		<?= $msg('HTML.DESC.SELECT_RESULTS.EXPLAIN') ?>
	</div>
	<button data-id="ISFL_editor_dialog.btn_determin_flow_results"
		class="ISFL-btn-square-so-pop"><?= $msg('BTN.DECISION') ?></button>
	<button type="button" class="ISFL-btn-square-soft"
		data-id="ISFL_editor_dialog.paging.btn_find"><?= $msg('BTN.FIND'); ?></button>
	<div data-id="ISFL_editor_dialog.paging" class="ISFL-editor-paging">
		<a data-id="ISFL_editor_dialog.paging.btn_prev" data-page="1"><?= $msg('BTN.PAGE_PREV') ?></a>
		<input type="text" data-id="ISFL_editor_dialog.paging.page" name="paging.page" value="1" style="width:50px;">
			/ <span data-id="ISFL_editor_dialog.paging.max_page"></span>
		<a data-id="ISFL_editor_dialog.paging.btn_next" data-page="1"><?= $msg('BTN.PAGE_NEXT') ?></a>
		&nbsp;
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name"><?= $msg('OBJ.RESULTS.REMARKS') ?>:</div>
			<input type="text" data-id="ISFL_editor_dialog.searchkeys.remarks"
			style="width: 150px;">
		</div>
		&nbsp;
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name"><?= $msg('OBJ.RESULTS.USER_NAME') ?>:</div>
			<input type="text" data-id="ISFL_editor_dialog.searchkeys.user_name"
			style="width: 150px;">
		</div>
		&nbsp;
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name"><?= $msg('OBJ.RESULTS.STATUS') ?>:</div>
			{{#each DEFINITIONS.RESULTS_STATUSES}}
				<label><input type="checkbox" value="{{@key}}" name="results.statuses"
				 data-id="ISFL_editor_dialog.searchkeys.statuses"
				 {{#if (neq @key 'resolved')}}checked{{/if}}>{{this}}</label>
			{{/each}}
		</div>
	</div><!-- /ISFL_editor_dialog.paging -->
	<div class="ISFL-dialog-scroll-window" style="height: 270px;" data-id="ISFL_editor_dialog.list">
	</div>
</script>
<!-- 切り分け結果一覧 -->
<script id="ISFL_dialog_flow_results_list" type="text/x-handlebars-template">
	<table>
		<tr>
			<th><?= $msg('OBJ.RESULTS.RESULT_ID') ?></th>
			<th><?= $msg('OBJ.ISFL_ID') ?></th>
			<th><?= $msg('OBJ.GROUP_TITLE') ?></th>
			<th><?= $msg('OBJ.RESULTS.STATUS') ?></th>
			<th><?= $msg('OBJ.RESULTS.CREATED_DATE') ?></th>
			<th><?= $msg('OBJ.RESULTS.REMARKS') ?></th>
			<th><?= $msg('OBJ.RESULTS.USER_NAME') ?></th>
		</tr>
	{{#each list}}
		<tr>
			<td><label><input type="radio" name="ISFL_editor_dialog.val.result_id" value="{{result_id}}">
			[{{result_id}}]
			</label></td>
			<td>{{isfl_id}}</td>
			<td>{{group_title}}</td>
			<td>{{lookup @root.DEFINITIONS.RESULTS_STATUSES status}}</td>
			<td>{{created_date}}</td>
			<td>{{remarks}}</td>
			<td>{{user_name}}</td>
	{{/each}}
	</table>
</script>
