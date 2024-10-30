
<?php

$obj = ISFL_IsolationFlowManager::obj();
if(isset($validator)){
	$errors = $validator->errors();
}
//print_r($errors);
$msg = array(ISFL_IsolationFlowManager::obj(), 'getMessage');

//権限の一覧を取得
$role_admin = get_role('administrator');
$caps = $role_admin->capabilities;
$caps_list = array();
foreach($caps as $name => $val){
	$caps_list[] = $name;
}
asort($caps_list);

//var_dump($errors);
?>
<style>
.my-message{
	font-weight: bold;
	color: red;
}
.my-error{
	padding: 0px;
	margin: 0px;
	margin-left: 20px;
	color: red;
}

form#my-submenu-form label{
	min-width: 170px;
	display: inline-block;
	vertical-align: top;
}

table.ISFL-mytable {
	border-collapse:separate;
	border-spacing: 0;
	table-layout: fixed;
	border: 1px solid #3c6690;
	font-size: 11px;
}
table.ISFL-mytable th{
	padding: 5px 2px;
	text-align: left;
	font-weight: bold;
	background-color: #e9f1fb;
	border: 0.5px solid #3c6690;
}
table.ISFL-mytable td{
	padding: 5px 2px;
	text-align: left;
	border: 0.5px solid #3c6690;
}
</style>

<div class="wrap">
<h1><?= $msg('MNG.TITLE.SUB_MENU_SETTINGS'); ?></h1>

<div class="ISFL-editor-explanation"><i class="icon-exclamation-triangle"></i><?= $msg('HTML.DESC.SETTINGS'); ?></div>
<div class="my-message"><?= $result_message; ?></div>
<form action="" method="post" id="my-submenu-form">
	<?php //nonceの設定 ?>
	<?php wp_nonce_field(ISFL_IsolationFlowManager::CREDENTIAL_ACTION, ISFL_IsolationFlowManager::CREDENTIAL_NAME) ?>
	<input type="hidden" name="type" value="save">
	<p>
	  <label for="title"><?= $msg('OBJ.ADMIN.OPERATOR_ROLE'); ?>:
		<span class="ISFL-help">
			<span class="ISFL-help__link">
				<i class="icon-question-circle"></i>
			</span>
			<span class="ISFL-help__balloon">
				<?= $msg('HELP.OBJ.ADMIN.OPERATOR_ROLE'); ?>
			</span>
		</span>
	  </label>
	  <input name="admin_operator_role" value="<?= htmlspecialchars($obj->operator_role); ?>" readonly>
	  <div class="my-error"><?= $errors['admin_operator_role'][0]; ?></div>
	</p>
	<p>
	  <label for="title"><?= $msg('OBJ.ADMIN.USER_ROLE'); ?>:
		<span class="ISFL-help">
			<span class="ISFL-help__link">
				<i class="icon-question-circle"></i>
			</span>
			<span class="ISFL-help__balloon">
				<?= $msg('HELP.OBJ.ADMIN.USER_ROLE'); ?>
			</span>
		</span>
	  </label>
	  <select name="admin_user_role" >
<?php
foreach($caps_list as $role){
	$selected = '';
	if($obj->user_role === $role) $selected = 'selected';
	echo "<option value='$role' $selected >$role</option>";
}
?>
	  </select>
	  <div class="my-error"><?= $errors['admin_user_role'][0]; ?></div>
	</p>
	<p>
	  <label for="title"><?= $msg('OBJ.ADMIN.DEFAULT_LOCALE'); ?>:
		<span class="ISFL-help">
			<span class="ISFL-help__link">
				<i class="icon-question-circle"></i>
			</span>
			<span class="ISFL-help__balloon">
				<?= $msg('HELP.OBJ.ADMIN.DEFAULT_LOCALE'); ?>
			</span>
		</span>
	  </label>
	  <input type="text" name="admin_default_locale" value="<?= htmlspecialchars($obj->default_locale); ?>"/>
	  <div class="my-error"><?= $errors['admin_default_locale'][0]; ?></div>
	</p>
	
	<p>
	  <label for="title"><?= $msg('OBJ.ADMIN.SEND_MAIL'); ?>:
		<span class="ISFL-help">
			<span class="ISFL-help__link">
				<i class="icon-question-circle"></i>
			</span>
			<span class="ISFL-help__balloon">
				<?= $msg('HELP.OBJ.ADMIN.SEND_MAIL'); ?>
			</span>
		</span>
	  </label>
	  <input type="checkbox" name="admin_send_mail" value="1" <?= $obj->send_mail ? 'checked':''; ?>/>
	  <div class="my-error"><?= $errors['admin_send_mail'][0]; ?></div>
	</p>
	<p>
	  <label for="title"><?= $msg('OBJ.ADMIN.SEND_MAIL_TO'); ?>:
		<span class="ISFL-help">
			<span class="ISFL-help__link">
				<i class="icon-question-circle"></i>
			</span>
			<span class="ISFL-help__balloon">
				<?= $msg('HELP.OBJ.ADMIN.SEND_MAIL_TO'); ?>
			</span>
		</span>
	  </label>
	  <input type="text" name="admin_send_mail_to" value="<?= htmlspecialchars(implode(',', $obj->send_mail_to)) ?>" style="width: 350px;"/>
	  <div class="my-error"><?= $errors['admin_send_mail_to.*'][0]; ?></div>
	</p>
	<p>
	  <label for="title"><?= $msg('OBJ.ADMIN.SEND_MAIL_FROM'); ?>:</label>
	  <input type="text" name="admin_send_mail_from" value="<?= htmlspecialchars($obj->send_mail_from) ?>" style="width: 350px;"/>
	  <div class="my-error"><?= $errors['admin_send_mail_from'][0]; ?></div>
	</p>
	<p>
	<label for="title"><?= $msg('OBJ.ADMIN.SEND_MAIL_TITLE'); ?>:</label>
	  <input type="text" name="admin_send_mail_title" value="<?= htmlspecialchars($obj->send_mail_title) ?>" style="width: 350px;"/>
	  <div class="my-error"><?= $errors['admin_send_mail_title'][0]; ?></div>
	</p>

	<input type="submit" class="button button-primary">
	<br>
	<br>
	<table class="ISFL-mytable">
	<tbody>
	<tr><th><br></th>
		<th><?= $msg('MNG.TITLE.SUB_MENU_SETTINGS'); ?></th>
		<th><?= $msg('MNG.TITLE.SUB_MENU_EDIT'); ?></th>
		<th><?= $msg('MNG.TITLE.SUB_MENU_EXEC'); ?></th>
		<th><?= $msg('MNG.TITLE.SUB_MENU_HISTORY'); ?></th>
	</tr>
	<tr>
		<th>Administrator</th>
		<td>o</td>
		<td>o</td>
		<td>o</td>
		<td>o</td>
	</tr>
	<tr>
		<th><?= $msg('OBJ.ADMIN.OPERATOR_ROLE'); ?></th>
		<td>x</td>
		<td>o</td>
		<td>o</td>
		<td>o</td>
	</tr>
	<tr>
		<th><?= $msg('OBJ.ADMIN.USER_ROLE'); ?></th>
		<td>x</td>
		<td>x</td>
		<td>o</td>
		<td>o</td>
	</tr>
	</tbody>
	</table>
</form>
