<?php 
/*
ISFL_IsolationFlow の初期化（テーブル作成など）をするクラス

result::status ⇒created/open/closed
*/


class ISFL_IsolationFlow_Init{
	const VERSION = '1.2';
	const OPTION_NAME = ISFL_IsolationFlowManager::PLUGIN_ID . '-db-version';
	
	static public function db_install(){
		$installed_ver = get_option( self::OPTION_NAME );
		if(!isset($installed_ver) || $installed_ver !== self::VERSION ){
			self::create_table();
			
			//バージョン管理のために
			update_option(self::OPTION_NAME, self::VERSION);
		}
	}
	
	static public function db_uninstall(){
		global $wpdb;
		$prefix = $wpdb->prefix . 'isfl';
		$wpdb->query("DROP TABLE IF EXISTS {$prefix}_group;");
		$wpdb->query("DROP TABLE IF EXISTS {$prefix}_flow;");
		$wpdb->query("DROP TABLE IF EXISTS {$prefix}_input;");
		$wpdb->query("DROP TABLE IF EXISTS {$prefix}_choices;");
		$wpdb->query("DROP TABLE IF EXISTS {$prefix}_result;");
		//
		delete_option(self::OPTION_NAME);
	}
	
	static public function create_table(){
		global $wpdb;
		$prefix = $wpdb->prefix . 'isfl';
		$charset_collate = $wpdb->get_charset_collate();

		//フローのグループ
		$table_name = $prefix . '_group';
		$sql1 = "CREATE TABLE $table_name (
		  isfl_id mediumint(9) NOT NULL AUTO_INCREMENT,
		  revision mediumint(9) NOT NULL,
		  group_title varchar(200) NOT NULL,
		  start_flow_id mediumint(9) NOT NULL,
		  created_date datetime(2) DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  keywords varchar(100) DEFAULT '' NOT NULL,
		  group_remarks varchar(300) DEFAULT '' NOT NULL,
		  PRIMARY KEY  (isfl_id)
		) $charset_collate;";

		//フロー
		$table_name = $prefix . '_flow';
		$sql2 = "CREATE TABLE $table_name (
		  isfl_id mediumint(9) NOT NULL,
		  flow_id mediumint(9) NOT NULL,
		  revision mediumint(9) NOT NULL,
		  created_date datetime(2) DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  pt_id varchar(50) NOT NULL,
		  status varchar(20) NOT NULL,
		  title varchar(100) NOT NULL,
		  question varchar(1000) NOT NULL,
		  PRIMARY KEY  (isfl_id, flow_id, revision)
		) $charset_collate;";

		//入力項目
		$table_name = $prefix . '_input';
		$sql3 = "CREATE TABLE $table_name (
		  isfl_id mediumint(9) NOT NULL,
		  flow_id mediumint(9) NOT NULL,
		  revision mediumint(9) NOT NULL,
		  no varchar(5) NOT NULL,
		  type varchar(10) NOT NULL,
		  label varchar(100) NOT NULL,
		  PRIMARY KEY  (isfl_id, flow_id, revision, no)
		) $charset_collate;";
		
		//結果の選択肢項目　ステータス(resolved, non resolvedなどを追加)
		$table_name = $prefix . '_choices';
		$sql4 = "CREATE TABLE $table_name (
		  isfl_id mediumint(9) NOT NULL,
		  flow_id mediumint(9) NOT NULL,
		  revision mediumint(9) NOT NULL,
		  id varchar(5) NOT NULL,
		  label varchar(100) NOT NULL,
		  attachment_id bigint(20) unsigned,
		  next_flow_id mediumint(9) NOT NULL,
		  PRIMARY KEY  (isfl_id, flow_id, revision, id)
		) $charset_collate;";
		
		//切り分けした結果を保存するテーブル
		$table_name = $prefix . '_result';
		$sql5 = "CREATE TABLE $table_name (
		  result_id mediumint(9) NOT NULL AUTO_INCREMENT,
		  user_id bigint(20) unsigned NOT NULL,
		  isfl_id mediumint(9) NOT NULL,
		  revision mediumint(9) NOT NULL,
		  created_date datetime(2) DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  status varchar(10) NOT NULL,
		  result varchar(3000) NOT NULL,
		  remarks varchar(500) ,
		  PRIMARY KEY  (result_id)
		) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql1 );
		dbDelta( $sql2 );
		dbDelta( $sql3 );
		dbDelta( $sql4 );
		dbDelta( $sql5 );
	}

	
}


?>