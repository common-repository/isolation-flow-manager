
<!-- 故障切り分け結果のダウンロードダイアログ -->
<script id="ISFL_dialog_download_results_count" type="text/x-handlebars-template">
	<div class="ISFL-user-explanation">
		<?= $msg('HTML.DESC.DOWNLOAD_RESULTS.EXPLAIN') ?>
	</div>
	<button type="button" class="ISFL-btn-square-soft"
		data-id="ISFL_editor_dialog.paging.btn_find"><?= $msg('BTN.FIND'); ?></button>
	<div data-id="ISFL_editor_dialog.paging" class="ISFL-editor-paging">
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name"><?= $msg('OBJ.GROUP_TITLE') ?>:</div>
			<input type="text" data-id="ISFL_editor_dialog.searchkeys.group_title"
			 style="width: 150px;" maxlength="<?= $msg('OBJ.GROUP_TITLE.LEN') ?>">
		</div>
		&nbsp;
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name"><?= $msg('OBJ.RESULTS.CREATED_DATE_FROM_TO') ?>:</div>
			<input type="text" data-id="ISFL_editor_dialog.searchkeys.created_date_from"
			 style="width: 120px;" maxlength="<?= $msg('OBJ.RESULTS.CREATED_DATE_FROM.LEN') ?>"
			 placeholder="YYYY-MM-DD">
			-
			<input type="text" data-id="ISFL_editor_dialog.searchkeys.created_date_to"
			 style="width: 120px;" maxlength="<?= $msg('OBJ.RESULTS.CREATED_DATE_TO.LEN') ?>"
			 placeholder="YYYY-MM-DD">
		</div>
		&nbsp;
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name"><?= $msg('OBJ.RESULTS.STATUS') ?>:</div>
			{{#each DEFINITIONS.RESULTS_STATUSES}}
				<label><input type="checkbox" value="{{@key}}" name="results.statuses"
				 data-id="ISFL_editor_dialog.searchkeys.statuses"
				>{{this}}</label>
			{{/each}}
		</div>
		
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name"><?= $msg('OBJ.RESULTS.ADD_FIELDS') ?>
				:<div class="ISFL-help">
					<span class="ISFL-help__link">
						<i class="icon-question-circle"></i>
					</span>
					<span class="ISFL-help__balloon">
						<?= $msg('HELP.OBJ.RESULTS.ADD_FIELDS'); ?>
					</span>
				</div>
			</div>
			<input type="text" data-id="ISFL_editor_dialog.searchkeys.add_field1" style="width: 100px;" placeholder="1-1">
			<input type="text" data-id="ISFL_editor_dialog.searchkeys.add_field2" style="width: 100px;" placeholder="1-2">
			<input type="text" data-id="ISFL_editor_dialog.searchkeys.add_field3" style="width: 100px;" placeholder="1-3">
		</div>
	</div><!-- /ISFL_editor_dialog.paging -->
	<br>
	<!-- ダウンロード -->
	<button type="button" class="ISFL-btn-square-soft"
		data-id="ISFL_editor_dialog.btn_export"><?= $msg('BTN.EXPORT_FLOW'); ?></button>
	<br>
	<div class="ISFL-input-block">
		<div class="ISFL-input-block__name"><?= $msg('OBJ.RESULTS.BOM') ?>:</div>
		<label>
			<input type="checkbox" data-id="ISFL_editor_dialog.searchkeys.csv_bom">
		</label>
	</div>
	<span data-id="ISFL_editor_dialog.val.strCsvResult"></span>
	<br>
	<textarea data-id="ISFL_editor_dialog.val.csv" style="width:450px; height: 100px;"></textarea>
</script>