
<!-- 故障切り分け結果の統計情報表示 -->
<script id="ISFL_dialog_count_flow_results" type="text/x-handlebars-template">
	<div class="ISFL-user-explanation">
		<?= $msg('HTML.DESC.COUNT_RESULTS.EXPLAIN') ?>
	</div>
	<button type="button" class="ISFL-btn-square-soft"
		data-id="ISFL_editor_dialog.paging.btn_find"><?= $msg('BTN.FIND'); ?></button>
	<div data-id="ISFL_editor_dialog.paging" class="ISFL-editor-paging">
		<a data-id="ISFL_editor_dialog.paging.btn_prev" data-page="1"><?= $msg('BTN.PAGE_PREV') ?></a>
		<input type="text" data-id="ISFL_editor_dialog.paging.page" name="paging.page" value="1" style="width:50px;">
			/ <span data-id="ISFL_editor_dialog.paging.max_page"></span>
		<a data-id="ISFL_editor_dialog.paging.btn_next" data-page="1"><?= $msg('BTN.PAGE_NEXT') ?></a>
		&nbsp;
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name"><?= $msg('OBJ.RESULTS.CNT_TYPE') ?>:</div>
			<select data-id="ISFL_editor_dialog.searchkeys.cnt_type">
			{{#each DEFINITIONS.CNT_TYPE}}
				<option value="{{@key}}">{{this}}</option>
			{{/each}}
			</select>
		</div>
		<div class="ISFL-input-block">
			<div class="ISFL-input-block__name"><?= $msg('OBJ.RESULTS.CNT_CREATED_DATE_UNIT') ?>:</div>
			<select data-id="ISFL_editor_dialog.searchkeys.cnt_created_date_unit">
			{{#each DEFINITIONS.CNT_CREATED_DATE_UNIT}}
				<option value="{{@key}}" {{#if (eq @key 'day')}}selected{{/if}}>{{this}}</option>
			{{/each}}
			</select>
		</div>
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
	</div><!-- /ISFL_editor_dialog.paging -->
	<div class="ISFL-dialog-scroll-window" style="height: 270px;" data-id="ISFL_editor_dialog.list">
	</div>
</script>
<!-- 切り分け結果一覧 -->
<script id="ISFL_editor_dialog_flow_results_count_list" type="text/x-handlebars-template">
	<svg viewbox="0 0 310 310" width="350" height="250">
		<!-- 罫線  -->
		<g stroke="#888" stroke-width="1">
			<!-- 枠線 -->
			<line x1="50"  y1="0" x2="50" y2="250"></line>
			<line x1="50" y1="0" x2="300" y2="0"></line>
			<line x1="300" y1="0" x2="300" y2="250"></line>
			<line x1="50" y1="250" x2="300" y2="250"></line>
			<!-- 縦線 -->
			<line x1="100" y1="0" x2="100" y2="250"></line>
			<line x1="150" y1="0" x2="150" y2="250"></line>
			<line x1="200" y1="0" x2="200" y2="250"></line>
			<line x1="250" y1="0" x2="250" y2="250"></line>
			<!-- 横線 -->
			<line x1="50" y1="50" x2="300" y2="50"></line>
			<line x1="50" y1="100" x2="300" y2="100"></line>
			<line x1="50" y1="150" x2="300" y2="150"></line>
			<line x1="50" y1="200" x2="300" y2="200"></line>
			<line x1="50" y1="250" x2="300" y2="250"></line>
		</g>
		{{#if (eq chart_kind 'line-time')}}'] = 'line-time';
			<!--  縦の目盛り  -->
			<g>
				{{eval "$evalVars.unit = Math.ceil(${max_cnt} / 5 + 1);" false}}
				<text x="0" y="250">0</text>
				<text x="0" y="200">{{eval "$evalVars.unit" true}}</text>
				<text x="0" y="150">{{eval "$evalVars.unit*2" true}}</text>
				<text x="0" y="100">{{eval "$evalVars.unit*3" true}}</text>
				<text x="0" y="50">{{eval "$evalVars.unit*4" true}}</text>
				<text x="0" y="0">{{eval "$evalVars.unit*5" true}}</text>
			</g>
			<!--  横の目盛り  -->
			<g>
			{{eval "$evalVars.x = -15; $evalVars.y = 0;" false}}
			{{#each list}}
				{{eval "$evalVars.x += 50; $evalVars.y = 275 + ($index % 2) * 15;" false}}
				<text x="{{@evalVars.x}}" y="{{@evalVars.y}}">{{x_axis_name}}</text>
			{{/each}}
			</g>
			<!-- グラフ -->
			<g>
				{{#each list}}
					<circle cx="{{eval '50 + $index*50' true}}" cy="{{eval '250 - this.cnt/$evalVars.unit*50' true}}" r="5" stroke="black" fill="blue" stroke-width="2">
						<title>[{{x_axis_name}}] {{cnt}}</title>
					</circle>
				{{/each}}
			</g>
		{{else}}
			<!--  縦の目盛り  -->
			<g>
			{{eval "$evalVars.x = -50; $evalVars.y = -3;" false}}
			{{#each list}}
				{{eval "$evalVars.x += 50; $evalVars.y += 50;" false}}
				<text x="0" y="{{@evalVars.y}}">[{{y_axis_id}}] {{y_axis_name}}</text>
			{{/each}}
			</g>
			<!--  横の目盛り  -->
			<g>
				{{eval "$evalVars.unit = Math.ceil(${max_cnt} / 5 + 1);" false}}
				<text x="50" y="280">0</text>
				<text x="100" y="280">{{eval "$evalVars.unit" true}}</text>
				<text x="150" y="280">{{eval "$evalVars.unit*2" true}}</text>
				<text x="200" y="280">{{eval "$evalVars.unit*3" true}}</text>
				<text x="250" y="280">{{eval "$evalVars.unit*4" true}}</text>
				<text x="300" y="280">{{eval "$evalVars.unit*5" true}}</text>
			</g>
			<!-- グラフ -->
			<g>
				{{#each list}}
					<rect x="50" y="{{eval "$index*50+15" true}}" fill="blue" width="{{eval "this.cnt/$evalVars.unit*50" true}}" height="20" >
						<title>{{cnt}}</title>
					</rect>
				{{/each}}
			</g>
		{{/if}}
	</svg>
</script>
