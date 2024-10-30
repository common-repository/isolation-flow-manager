<?php 
/*
Plugin Name: Isolation Flow Manager
Description: Plugin allows you to do isolation flow.  切り分けフローを提供する。
Version: 1.0
Auther: nanajuly
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html



*/


//プラグイン

/*require_once 'isolation_flow_manager.php';

//リクエストのたびに呼ばれる
add_action('init', 'ISFL_IsolationFlowManager::obj');
//プラグインの有効化したときに呼ばれる
register_activation_hook(__FILE__, 'ISFL_IsolationFlowManager::install_plugin' );
//リクエストのたびにプラグインがロードされると呼ばれる
add_action('plugins_loaded', 'ISFL_IsolationFlowManager::install_plugin' );
//プラグインがアンインストールされたときに呼ばれる
register_uninstall_hook(__FILE__, 'ISFL_IsolationFlowManager::uninstall_plugin' );
//add_action('init', 'ISFL_IsolationFlowManager::session_start');
add_action('admin_menu', array(ISFL_IsolationFlowManager::obj(), 'add_admin_menu'));
add_action('admin_menu', array(ISFL_IsolationFlowManager::obj(), 'remove_admin_menu_sub'));
//add_action('admin_init', array(ISFL_IsolationFlowManager::obj(), 'save_config'));
//rest要求のあったときだけ呼ばれる
add_action('rest_api_init', 'ISFL_IsolationFlowManager::add_rest_original_endpoint');
////投稿されたテキストからショートコードを見つけて、IDをoptionに保存する。
//add_action('save_post', array(ISFL_IsolationFlowManager::obj(), 'save_post_id_array_for_shortcode'));
////ショートコードがあるユーザ画面だけscriptとcssを書きだすアクション
//add_action('wp_enqueue_scripts', array(ISFL_IsolationFlowManager::obj(), 'enqueue_script_user_page'));
//このプラグイン用の管理画面だけscriptとcssを書きだすアクション
add_action('admin_enqueue_scripts', array(ISFL_IsolationFlowManager::obj(), 'enqueue_script_admin_page'));
////ショートコード
//add_shortcode(ISFL_IsolationFlowManager::SHORTCODE_NAME_USER_PAGE, array(ISFL_IsolationFlowManager::obj(), 'shcode_write_user_flow'));

*/
function ISFL_inc(){
	require_once 'isolation_flow_manager.php';
}

class ISFL_Plugin{
	static public function uninstall_hook(){
		require_once 'isolation_flow_manager.php';
		ISFL_IsolationFlowManager::uninstall_plugin();
	}
	static public function api_init(){
		//require_once 'isolation_flow_manager.php';
		//ISFL_IsolationFlowManager::add_rest_original_endpoint();
	}

}

//プラグインが有効化されたときの処理
register_activation_hook(__FILE__, 
	function(){ ISFL_inc(); ISFL_IsolationFlowManager::install_plugin();} );
//add_action('plugins_loaded', 
//	function(){ ISFL_inc(); ISFL_IsolationFlowManager::install_plugin();} );
//プラグインをアンインストールしたときの処理
register_uninstall_hook(__FILE__, 'ISFL_Plugin::uninstall_hook');
//管理画面の左メニューの表示
add_action('admin_menu', 
	function(){ ISFL_inc(); ISFL_IsolationFlowManager::obj()->add_admin_menu();} );
add_action('admin_menu', 
	function(){ ISFL_inc(); ISFL_IsolationFlowManager::obj()->remove_admin_menu_sub();} );
//REST要求のあったときだけ呼ばれる。
add_action('rest_api_init', 
	function(){ ISFL_inc(); ISFL_IsolationFlowManager::add_rest_original_endpoint();} );
//このプラグイン用の管理画面だけscriptとcssを書きだすアクション
add_action('admin_enqueue_scripts', 
	function($p1){ ISFL_inc(); ISFL_IsolationFlowManager::obj()->enqueue_script_admin_page($p1);} );



?>