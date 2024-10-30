<!-- 切り分けフロー選択 -->
<script id="ISFL_dialog_select_flow_group" type="text/x-handlebars-template">
	<div class="ISFL-user-explanation">
		<?= $msg('HTML.DESC.SELECT_FLOW_GROUP.EXPLAIN') ?>
	</div>
	<button data-id="ISFL_editor_dialog.btn_determin_flow_group"
		class="ISFL-btn-square-so-pop"><?= $msg('BTN.DECISION') ?></button>
	<button type="button" class="ISFL-btn-square-soft" data-id="ISFL_editor_dialog.paging.btn_find"><?= $msg('BTN.FIND'); ?></button>
	<div data-id="ISFL_editor_dialog.paging" class="ISFL-editor-paging">
		<a data-id="ISFL_editor_dialog.paging.btn_prev" data-page="1"><?= $msg('BTN.PAGE_PREV') ?></a>
		<input type="text" data-id="ISFL_editor_dialog.paging.page" name="paging.page" value="1" style="width:50px;">
			/ <span data-id="ISFL_editor_dialog.paging.max_page"></span>
		<a data-id="ISFL_editor_dialog.paging.btn_next" data-page="1"><?= $msg('BTN.PAGE_NEXT') ?></a>
		&nbsp;
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name"><?= $msg('OBJ.GROUP_TITLE') ?>:</div>
			<input type="text" data-id="ISFL_editor_dialog.searchkeys.group_title"
			 style="width: 150px;">
		</div>
		&nbsp;
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name"><?= $msg('OBJ.KEYWORDS') ?>:</div>
			<input type="text" data-id="ISFL_editor_dialog.searchkeys.keywords"
			 style="width: 100px;">
		</div>
		&nbsp;
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name"><?= $msg('OBJ.GROUP_REMARKS') ?>:</div>
			<input type="text" data-id="ISFL_editor_dialog.searchkeys.group_remarks"
			 style="width: 150px;">
		</div>
		&nbsp;	
	</div>
	<div class="ISFL-dialog-scroll-window" style="height: 270px;" data-id="ISFL_editor_dialog.list">
	</div>
</script>
<!-- 切り分けフロー一覧 -->
<script id="ISFL_dialog_select_flow_group_list" type="text/x-handlebars-template">
	<table style="table-layout: fixed; width: 500px;">
		<tr>
			<th style="width: 70px;"><?= $msg('OBJ.ISFL_ID') ?></th>
			<th style="width: 210px;"><?= $msg('OBJ.GROUP_TITLE') ?></th>
			<th style="width: 100px;"><?= $msg('OBJ.KEYWORDS') ?></th>
			<th style="width: 200px; overflow:hidden;"><?= $msg('OBJ.GROUP_REMARKS') ?></th>
		</tr>
	{{#each list}}
		<tr>
		<td>
			<label><input type="radio" name="ISFL_editor_dialog.val.isfl_id"
				value="{{isfl_id}}" data-revision="{{revision}}">
				[{{isfl_id}}]
			</label>
		</td>
		<td>{{group_title}}</td>
		<td>{{keywords}}</td>
		<td style="overflow:hidden;">{{group_remarks}}</td>
		</tr>
	{{/each}}
	</table>
</script>

