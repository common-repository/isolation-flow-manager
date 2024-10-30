<!-- 画像選択のダイアログ -->
<script id="ISFL_dialog_select_img" type="text/x-handlebars-template">
	<div>
		<button data-id="ISFL_editor_dialog.btn_determin_image" class="ISFL-btn-square-so-pop"><?=$msg('BTN.DECISION')?></button>
		&nbsp;
		<button type="button" class="ISFL-btn-square-soft"
			data-id="ISFL_editor_dialog.paging.btn_find"><?= $msg('BTN.FIND'); ?></button>
		<label class="ISFL-btn-square-soft">
			<input type="file" data-id="ISFL_editor_dialog.btn_add_image" style="display: none;">
			<?=$msg('BTN.ADD_IMAGE')?>
		</label>
		<button data-id="ISFL_editor_dialog.btn_delete_image" class="ISFL-btn-square-soft"><?=$msg('BTN.DEL')?></button>
		<div>
			<img data-id="ISFL_editor_dialog.selected_img">
			<div class="ISFL-editor-modal-content--display-if-img-shown" style="width:250px;">
				<?=$msg('HTML.DESC.DIALOG.SELECT_IMG.UPLOAD_IMG')?><br>
				<?=$msg('OBJ.IMAGE_TITLE')?>:
				<input type="text" data-id="ISFL_editor_dialog.val.image_title" maxlength="<?=$msg('OBJ.IMAGE_TITLE.LEN')?>">
				<br>
				<button data-id="ISFL_editor_dialog.btn_upload_image" class="ISFL-btn-square-soft"><?=$msg('BTN.UPLOAD')?></button>
				<button data-id="ISFL_editor_dialog.btn_cancel_upload_image" class="ISFL-btn-square-soft"><?=$msg('BTN.CANCEL')?></button>
				<br><br>
			</div>
		</div>
	</div>
	<div data-id="ISFL_editor_dialog.paging" class="ISFL-editor-paging">
		<a data-id="ISFL_editor_dialog.paging.btn_prev" data-page="1"><?=$msg('BTN.PAGE_PREV')?></a>
		<input type="text" data-id="ISFL_editor_dialog.paging.page" name="paging.page" value="1" style="width:50px;">
			/ <span data-id="ISFL_editor_dialog.paging.max_page"></span>
		<a data-id="ISFL_editor_dialog.paging.btn_next" data-page="1"><?=$msg('BTN.PAGE_NEXT')?></a>
		&nbsp;
		<?= $msg('OBJ.IMAGE_TITLE') ?>:
		<input type="text" data-id="ISFL_editor_dialog.searchkeys.image_title"
			style="width: 100px;">
	</div>
	<div class="ISFL-editor-float-list ISFL-editor-float-list--middle-size" data-id="ISFL_editor_dialog.float_list">
	</div>
	<!-- 処理中オーバーレイ -->
	<div class="ISFL-editor-modal-overlay-processing" data-id="ISFL_editor_dialog.processing">
		<div style="height:100px"></div>
		<img src="<?= plugins_url('include/processing.gif', dirname(__FILE__))?>">
	</div>
</script>
<!-- 画像一覧 -->
<script id="ISFL_dialog_img_list" type="text/x-handlebars-template">
	<ul>
	{{#each list}}
		<li><label>
			<input type="radio" name="ISFL_editor_dialog.val.img_url"
				data-attachment_id="{{attachment_id}}"
				value="{{img_url}}" class="ISFL-editor-float-list--middle">
			<img src="{{img_url}}">
			<span class="ISFL-editor-float-list__title">{{image_title}}</span>
		</label></li>
	{{/each}}
	</ul>
</script>

