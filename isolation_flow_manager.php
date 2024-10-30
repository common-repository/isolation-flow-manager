<?php 
/**
 * 
 * このプラグインのメインクラス
 * 
 * 【設定ファイル】<br>
 * 以下の設定ファイルがあり、カスタマイズしたい場合はファイル名に"custom_"を付けたファイルを作成する。<br>
 * ・HTMLメッセージ定義ファイル<br>
 * 　優先順位順<br>
 * 　(1)現在のthemes/isolation-flow-manager/langs/messages_custom_[言語].php  <br>
 * 　(2)/langs/messages_[言語].php  <br>
 *
 * ・エラーメッセージ(Valitron)定義ファイル <br>
 *　優先順位順 <br>
 *　(1)現在のthemes/isolation-flow-manager/Valitron/lang/custom_[言語].php <br> 
 *　(2)/Valitron/lang/[言語].php  <br>
 *
【Rest APIエンドポイント(プレフィックスは'/isolation-flow-manager/api')】 <br>
//切り分けフロー更新 <br>
・/flow_groups/(\d+)$' [POST]  <br>
//切り分けフロー登録  <br>
・/flow_groups [POST] <br>
//切り分けフロー取得  <br>
・/flow_groups/(\d+)/(\d+)$ [GET] <br>
//切り分けフロー検索 <br>
・/flow_groups [GET] <br>
//切り分け結果取得/api/flow_results <br>
・/flow_results [GET] <br>
//切り分け結果新規登録＆取得(数字はisfl_id)/api/flow_results <br>
・/flow_results [POST] <br>
//切り分け結果更新/api/flow_results/* (数字はresult_id) <br>
・/flow_results/(\d+)$ [POST] <br>
//切り分け結果統計情報取得/api/flow_results/statistics <br>
・/flow_results/statistics [GET] <br>
//画像更新/api/images <br>
・/images [POST] <br>
//画像更新/api/images <br>
・/images [GET] <br>
//画像削除/api/images/* <br>
・/images/(\d+)$ [DELETE] <br>
 <br>


【権限について】 <br>
このプラグインの処理には、操作者(operator)とユーザ(user)があってそれぞれに処理を許可するための権限ロールを自由に設定できます。
既存の権限を使用してもよいですが、より管理をよくするなら「User Role Editor」などのプラグインを導入します。
プラグインをインストールすると「isfl_operator」「isfl_user」が追加されるので
「User Role Editor」プラグインなどでこれらの権限を使用させるロールに付与することをオススメします。
また、間違えて権限を設定すると、操作させたくない人に操作許可してしまうので注意。
もし、Administratorにのみ操作者処理を許したい場合は、「User Role Editor」でどの役割にも「isfl_operator」を
設定しなければAdministratorにのみ操作が許可されることになります。
 <br>
 <br>
【画像のアップロード】 <br>
Wordpressのアップロード機能を使用。 <br>
post_titleに"ISFL::"のプレフィックスを付けて保存している。 <br>
 <br>

【メール設定について】 <br>
SMTPを設定する必要があり、プラグインを利用するか、もしくはPositfixを設定する必要あり。 <br>
・WP SMTP プラグインをインストール(少なくともVAGRANTではプラグインはうまくいかない。WP Mail SMTP by WPFormsなどがある) <br>
　https://toiee.jp/wordpress-smtp-setting/ <br>
・Postfixを設定する <br>
　https://secopsmonkey.com/mail-relaying-postfix-through-office-365.html <br>
 <br>
 <br>
 <br>
*/
class ISFL_IsolationFlowManager{
	const VERSION           = '1.1';
	const PLUGIN_ID         = 'isolation-flow-manager';
	const CREDENTIAL_ACTION = self::PLUGIN_ID . '-nonce-action';
	const CREDENTIAL_NAME   = self::PLUGIN_ID . '-nonce-key';
	const PLUGIN_DB_PREFIX  = self::PLUGIN_ID . '_';
	const COMPLETE_CONFIG   = self::PLUGIN_ID . '-complete-msg';
	// config画面のslug
	//const CONFIG_MENU_SLUG  = self::PLUGIN_ID . '-config';
	//セッション用クッキーの名前
	const PLUGIN_SESSION_NAME= self::PLUGIN_ID . '-sess';
	//APIのURIパスのプレフィックス
	const API_URI_PREFIX    = self::PLUGIN_ID . '/api';
	//ユーザ画面表示用のショートコード名
	const SHORTCODE_NAME_USER_PAGE = 'isolation_flow_user_page';
	//
	const DEFAULT_SETTINGS = array('user_role'=>'edit_pages', 'operator_role'=>'isfl_operator', 
		'default_locale'=>'ja', 'send_mail'=>false, 'send_mail_to'=>array(), 
		'send_mail_from'=>'no-reply@local.wordpress', 'send_mail_title'=>'isolation_flow',);
	
	static private $instance = NULL;
	//
	//private $def_params_validation = NULL;
	//切り分け手順を使用する権限
	private $user_role = NULL;
	//オペレータ権限（切り分け手順を管理し編集する権限）
	private $operator_role = NULL;
	//ロケール言語情報
	private $lang_require_file = NULL;
	private $default_locale = NULL;
	private $send_mail = NULL;
	private $send_mail_to = NULL;
	private $send_mail_from = NULL;
	private $send_mail_title = NULL;
	private $messages = NULL;
	//リンクタグをshort codeですでに記述しているかを保存
	private $applied_enqueue_scripts = array();
	
	/**
	 * コンストラクタ---------------
	 * @param string $role このプラグインの機能を使用してよいユーザ権限(publish_posts, edit_pages, edit_postなど)
	 */
	public function __construct(string $default_locale=NULL, string $role='read', string $operator_role='edit_users', 
		int $max_order_num_per_user=2){
		//
		$settings = get_option(self::PLUGIN_DB_PREFIX . 'settings', self::DEFAULT_SETTINGS);
		$this->user_role = $settings['user_role'];
		$this->operator_role = $settings['operator_role'];
		$langs = $this->locales();
		//デフォルト言語
		$this->default_locale = $settings['default_locale'];
		$this->send_mail       = $settings['send_mail'];
		$this->send_mail_to    = $settings['send_mail_to'];
		$this->send_mail_from  = $settings['send_mail_from'];
		$this->send_mail_title = $settings['send_mail_title'];
		$this->lang_require_file = $this->get_message_filename($langs, $this->default_locale);
		//	
	}
	
	public function __destruct(){
	}
	
	/**
	 * getter
	 */
	function __get($name){
		if($name === 'user_role' || $name === 'operator_role' || $name === 'default_locale' 
		 || $name === 'messages'
		){
			return $this->$name;
		}
		//指定以外のプロパティ名はエラーにする
		throw new Exception('Invalid value');
	}
    
	/**
	 * メッセージを追加する。
	 * @param array $messages メッセージ。{key=>text}
	 */
	public function setMessages(array $messages){
		$this->messages = $messages;
	}
	
	/**
	 * メッセージ設定ファイルから指摘のキー名の文字列を取得する。
	 * @param string $key 検索するキー名。
	 * @param bool $is_escape trueのときHTMLエスケープをする。
	 * @param $fields {@internal メッセージ内にプレースホルダ（ {0}, {1}など）がある場合に置き替える。 
	 * @return string 文字列。プレースホルダがある場合は置換後の文字列が返る。
	*/
	public function getMessage(string $key, bool $is_escape=true, ...$fields): string{
		if(isset($this->lang_require_file)){
			require_once $this->lang_require_file;
			$this->lang_require_file = NULL;
		}
		//
		$text = $this->messages[$key] ?? '';
		for($i=0; $i<count($fields); ++$i){
			$text = str_replace('{'.$i.'}', $fields[$i], $text);
		}
		if($is_escape){
			$text = str_replace("\n", "<br>", htmlspecialchars($text));
		}
		return $text;
	}
	
	/**
	 * メッセージの中の定義(DEFINITIONS)を取得する
	 * @param string $key - キー名
	 * @return array 指定の定義。キーが見つからない場合はundef
	 */
	public function get_defs(string $key) : array{
		$ary = $this->messages['DEFINITIONS'];
		if(!isset($ary)) throw new Exception("messages file must have key 'DEFINITIONS'.");
		if(!isset($ary[$key])) throw new Exception("messages file must have key 'DEFINITIONS'=>'$key'.");
		return $ary[$key];
	}

	/**
	 * JSに渡すメッセージ定義を作成する。
	 * @return array メッセージの連想配列。JSのメッセージ定義変数に設定するためのもの（キーを絞って返す）。
	*/
	public function create_messages_for_js(): array{
		$this->getMessage('OK.SUCCESS');//一度呼び出さないとmessagesが設定されないので
		$ret = array();
		foreach($this->messages as $key => $value){
			if(substr($key, 0, 4) != 'MNG.') $ret[$key] = $value;
		}
		return $ret;
	}
	
	/**
	 * 指定の言語コードの設定ファイルがあるか検索し、見つかったファイルパスを返す。langs/フォルダ内を検索する。
	 * カスタム用の「messages_custom_」のプレフィックスのファイル名を優先的に検索する。
	 * @param array $langs 候補の言語コード(ja, enなど)の一覧。優先順位順に配列に設定すること。
	 * @param string $default_locale ファイルが見つからない時に使用するデフォルト言語コード。
	*/
	protected function get_message_filename(array $langs, string $default_locale) : string{
		//
		foreach($langs as $lang){
			$file = get_template_directory() . '/' . self::PLUGIN_ID . '/langs/messages_custom_' . $lang . '.php';

			if(file_exists($file)) return $file;
			$file = __DIR__ . '/langs/messages_' . $lang . '.php';
			if(file_exists($file)) return $file;
		}
		
		$file = __DIR__ . '/langs/messages_' . $default_locale . '.php';
		return $file;
	}
	
	/**
	 * 妥当性チェックValitron用のメッセージファイルの言語コード(ja, enなど)を取得する。
	 * Valitron/lang/配下の言語コードファイルの存在有無で判定する。一番最初に見つかったファイル名を言語コードとする。
	 * カスタム用の「custom_」のプレフィックスのファイル名を優先的に検索する。
	 * @return string 言語コード（例：'ja', 'custom_en'など）
	 * @see self::locales   言語コードのリスト
	*/
	protected function get_valitron_lang() : string{
		$locales = $this->locales();
		//
		foreach($locales as $lang){
			$file = get_template_directory() .'/'. self::PLUGIN_ID . '/Valitron/lang/custom_' . $lang . '.php';
			if(file_exists($file)) return 'custom_' . $lang;
			$file = __DIR__ . '/Valitron/lang/' . $lang . '.php';
			if(file_exists($file)) return $lang;
		}
		
		return $this->default_locale;
	}
	
	/**
	 * 妥当性チェックValitron用のメッセージファイルのディレクトリを取得する。
	 * 言語コードから判断して取得。言語コードが'custom_'から始まるときはカスタムのディレクトリを返す。
	 * @param string $lang 取得した言語コード
	 * @return string メッセージファイルのあるディレクトリ
	 * @see self::get_valitron_lang()   
	*/
	protected function get_valitron_dir(string $lang) : string{
		if(substr($lang, 0, strlen('custom_')) === 'custom_'){
			return get_template_directory() .'/'. self::PLUGIN_ID . '/Valitron/lang';
		}
		return __DIR__ . '/Valitron/lang';
	}
	
	/**
	 * HTTPヘッダからロケールの言語コードを取得する（'ja','en'とか）
	 */
	protected function locales() : array {
		$http_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
		$langs = [];
		foreach ( $http_langs as $lang ) {
			$langs[] = explode( ';', $lang )[0];
		}
		return $langs;
	}
	
	/**
	 * 変数がない場合、NULLの場合、空の場合、デフォルト値を設定する
	 */
	protected function default_val($val, ?string $default){
		if(!isset($val)) return $default;
		if(is_array($val)) return $val;
		if($val == '') return $default;
		return stripslashes($val);
	}
	
	
	/**
	 * プラグインのインストール、有効時にDBなど必要な初期化処理をする。
	 * 'plugins_loaded'は画面ロードのたびに毎回呼ばれるので、それを考慮して実装すること。
	*/
	static public function install_plugin(){
		$installed_ver = get_option( self::PLUGIN_ID );
		if(!isset($installed_ver) || strcmp($installed_ver, self::VERSION) != 0 ){
			// 権限グループを追加
			$role = get_role('administrator');
			$role->add_cap('isfl_operator');
			$role->add_cap('isfl_user');
			
			//
			update_option(self::PLUGIN_DB_PREFIX . 'settings', self::DEFAULT_SETTINGS);
			
			//バージョン管理のために
			update_option(self::PLUGIN_ID, self::VERSION);
		}
		
		//DB
		require_once 'isfl_install.php';
		ISFL_IsolationFlow_Init::db_install();
	}
	
	/**
	 * プラグインのアンインストール処理をする
	*/
	static public function uninstall_plugin(){
		$roles = wp_roles()->role_objects; //WP_Roleのハッシュ
		foreach($roles as $role){
			$role->remove_cap('isfl_operator');
			$role->remove_cap('isfl_user');
		}
		
		//設定の削除
		delete_option(self::PLUGIN_ID );
		delete_option(self::PLUGIN_DB_PREFIX . 'settings');
		
		//DB
		require_once 'isfl_install.php';
		ISFL_IsolationFlow_Init::db_uninstall();
	}
	
	
	/** 
	 * WPにoption値を保存する。
	 * DBに保存する。キーがまだ登録されていない場合はaddで、登録済みならupdateで。
	 * @param array $key_values 保存する値を連想配列で渡す。key=>valueのセット
	 * @return bool 保存が成功した場合true
	*/
	protected function save_option(array $key_values) : bool{
		$stock = get_option(self::PLUGIN_DB_PREFIX . 'settings');
		$is_changed = false;
		foreach($key_values as $key => $value){
			//同じ値でupdateするとエラーになるのでここで制御
			if($stock[$key] === $value) continue;
			$is_changed = true;
			//値の保存
			$stock[$key] = $value;
		}
		//
		if(!$is_changed) return true;
		return update_option(self::PLUGIN_DB_PREFIX . 'settings', $stock);
	}
		
	/** 
	 * WPのoption値を取得する。
	 * @param string $key キー名
	 * @return object 保存が成功した場合true
	*/
	protected function get_option($key){
		$stock = get_option(self::PLUGIN_DB_PREFIX . 'settings');
		return $stock[$key];
	}
	
	
	/**
	 * ショートコードを記述している投稿だけにjs,cssファイルをインクルードするための関数。
	 * 投稿されたテキストからショートコードを見つけて、IDをoptionに保存する。
	 * add_action('save_post', array(ISFL_IsolationFlowManager::obj(),'save_option_shortcode_post_id_array'));すること。
	 * @param array $attrs 引数。[0]ファイル名。
	*/
	public function save_post_id_array_for_shortcode($post_id) {
		if ( wp_is_post_revision( $post_id ) OR 'page' != get_post_type( $post_id )) {
			return;
		}
		//保存処理開始
		$id_array = $this->get_option('shcode_ids_' . $option_name);
		$option_name = self::SHORTCODE_NAME_USER_PAGE;
		if($this->find_shortcode_occurences($option_name, $post_id)){
			$id_array[$post_id] = true;
		}else{
			unset($id_array[$post_id]);
		}
		$this->save_option(array('shcode_ids_' . $option_name => $id_array));
	}

	/**
	 * ショートコードを記述している投稿だけにjs,cssファイルをインクルードするための関数。
	 * 投稿されたテキストからショートコードを見つけて返却する。
	 * @param string $shortcode ショートコード名。
	 * @param string [$post_type] 投稿タイプ（'post', 'page'）。
	 * @return {@internal 連装配列{投稿ID=>true}
	 * @see save_option_shortcode_post_id_array()
	*/
	protected function find_shortcode_occurences(string $shortcode, int $post_id) : bool {
		$shortcode = '[' . $shortcode;
		$content = get_post($post_id)->post_content;
		if(strpos($content, $shortcode) !== false) return true;
		return false;
	}
	
	/**
	 * ファイル isolation_flow_common_def.jsを読み込んで置換文字を置換した結果を返す
	 * @return string 置換後のJS
	 * @see enqueue_script_user_page()
	*/
	protected function create_common_def_script() : string {
		$script = file_get_contents(__DIR__ . '/include/isolation_flow_common_def.js');
		$json = json_encode($this->create_messages_for_js(), JSON_UNESCAPED_UNICODE);
		$script = str_replace("{{messages}}", $json, $script);{{}}
		$script = str_replace("{{WpNonce}}", wp_create_nonce( 'wp_rest' ), $script);
		$script = str_replace("{{uriPrefix}}", '/wp-json/' . self::API_URI_PREFIX, $script);
		return $script;
	}
	
	/**
	 * ショートコードを記述している投稿だけにjs,cssファイルをインクルードするための関数。
	 * 実際の表示画面でjs,cssをenqueする。
	 * add_action('wp_enqueue_scripts', array(ISFL_IsolationFlowManager::obj(), 'enqueue_script_user_page'));すること。
	 * @see save_option_shortcode_post_id_array()
	*/
	public function enqueue_script_user_page() {
		$page_id = get_the_ID();
		$option_id_array = $this->get_option('shcode_ids_' . self::SHORTCODE_NAME_USER_PAGE);
//error_log(print_r($option_id_array,true).'######');
		if (isset($option_id_array[$page_id])) {
			$script = $this->create_common_def_script();
			wp_enqueue_script('handlebars', plugin_dir_url( __FILE__ ) . 'include/handlebars.min.js?ver='.self::VERSION);
			wp_enqueue_script('just-handlebars-helpers', plugin_dir_url( __FILE__ ) . 'include/just-handlebars-helpers.min.js?ver='.self::VERSION, array('handlebars'));
			wp_enqueue_style('isolation_flow_common', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_common.css?ver='.self::VERSION);
			wp_enqueue_script('isolation_flow_common', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_common.js?ver='.self::VERSION);
			wp_add_inline_script('isolation_flow_common', $script, 'after');
			wp_enqueue_style('isolation_flow_user', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_user.css?ver='.self::VERSION);
			wp_enqueue_script('isolation_flow_user', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_user.js?ver='.self::VERSION, array('isolation_flow_common'));
		}
	}
	
	/**
	 * ショートコードを記述している投稿だけにjs,cssファイルをインクルードするための関数。
	 * 実際の表示画面でjs,cssをenqueする。
	 * add_action('admin_enqueue_scripts', array(ISFL_IsolationFlowManager::obj(), 'enqueue_script_admin_page'));すること。
	 * @see save_option_shortcode_post_id_array()
	*/
	public function enqueue_script_admin_page($hook_suffix) {
		global $pagenow;
		//このプラグインの管理画面サフィックスはself::PLUGIN_ID.'-1'など
		$admin_suffix = self::PLUGIN_ID . '-';
		$user_suffix = $admin_suffix . 'user-';
		if($pagenow == 'admin.php' && substr($hook_suffix, -strlen($user_suffix)-1, strlen($user_suffix)) == $user_suffix) {	
			//ユーザ利用の画面(-user-1など)
			$script = $this->create_common_def_script();
			wp_enqueue_script('handlebars', plugin_dir_url( __FILE__ ) . 'include/handlebars.min.js', array(), self::VERSION);
			wp_enqueue_script('just-handlebars-helpers2', plugin_dir_url( __FILE__ ) . 'include/just-handlebars-helpers.min.js', array('handlebars'), self::VERSION);
			wp_enqueue_style('ISFL_common', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_common.css', array(), self::VERSION);
			wp_enqueue_script('ISFL_common', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_common.js', array(), self::VERSION);
			wp_add_inline_script('ISFL_common', $script, 'after');
			wp_enqueue_script('ISFL_dialog', plugin_dir_url( __FILE__ ) . 'include/dialog.js', array('ISFL_common'), self::VERSION);
			wp_enqueue_script('ISFL_dialog_flow_group_select', plugin_dir_url( __FILE__ ) . 'include/dialog_flow_group_select.js', array('ISFL_dialog'), self::VERSION);
			wp_enqueue_script('ISFL_dialog_flow_results_select', plugin_dir_url( __FILE__ ) . 'include/dialog_flow_results_select.js', array('ISFL_dialog'), self::VERSION);
			wp_enqueue_script('ISFL_dialog_flow_results_count', plugin_dir_url( __FILE__ ) . 'include/dialog_flow_results_count.js', array('ISFL_dialog'), self::VERSION);
			wp_enqueue_script('ISFL_user', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_user.js', array('ISFL_common', 'ISFL_dialog_flow_group_select'), self::VERSION);
			wp_enqueue_style('ISFL_user', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_user.css', array(), self::VERSION);
		}else if($pagenow == 'admin.php' && substr($hook_suffix, -strlen($admin_suffix)-1, strlen($admin_suffix)) == $admin_suffix) {		
			//管理者画面（operator）
			$script = $this->create_common_def_script();
			wp_enqueue_script('handlebars', plugin_dir_url( __FILE__ ) . 'include/handlebars.min.js', array(), self::VERSION);
			wp_enqueue_script('just-handlebars-helpers2', plugin_dir_url( __FILE__ ) . 'include/just-handlebars-helpers.min.js', array('handlebars'), self::VERSION);
			wp_enqueue_script('createjs', plugin_dir_url( __FILE__ ) . 'include/createjs.min.js', array(), self::VERSION);
			wp_enqueue_style('ISFL_common', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_common.css', array(), self::VERSION);
			wp_enqueue_script('ISFL_common', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_common.js', array(), self::VERSION);
			wp_add_inline_script('ISFL_common', $script, 'after');
			wp_enqueue_script('ISFL_dialog', plugin_dir_url( __FILE__ ) . 'include/dialog.js', array('ISFL_common'),  self::VERSION);
			wp_enqueue_script('ISFL_dialog_image_select', plugin_dir_url( __FILE__ ) . 'include/dialog_image_select.js', array('ISFL_common'), self::VERSION);
			wp_enqueue_script('ISFL_dialog_flow_group_select', plugin_dir_url( __FILE__ ) . 'include/dialog_flow_group_select.js', array('ISFL_dialog'), self::VERSION);
			wp_enqueue_script('ISFL_user', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_user.js', array('ISFL_common'), self::VERSION);
			wp_enqueue_style('ISFL_user', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_user.css', array(), self::VERSION);
			wp_enqueue_script('ISFL_editor_canvas', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_editor_canvas.js', array('ISFL_common'), self::VERSION);
			wp_enqueue_script('ISFL_editor', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_editor.js', array('ISFL_common', 'ISFL_dialog_image_select','ISFL_dialog_flow_group_select'), self::VERSION);
			wp_enqueue_style('ISFL_editor', plugin_dir_url( __FILE__ ) . 'include/isolation_flow_editor.css', array(), self::VERSION);
		}
	}
	
	/**
	 * ショートコードの実装関数：
	 * ユーザ画面（予約処理）のHTML書き出し。
	*/
/*	public function shcode_write_user_flow($attrs=array()) {
		//ショートコードでincludeやrequireを使いたい場合はこうするらしい。
		ob_start();
		
		//以下のHTML内のHTMLノードのルートのid
		require_once 'template/isolation_flow_user_page.php';
		
		return ob_get_clean();
	}
*/

	/**
	 * HTMLのカスタムhbsテンプレートをscriptタグとして書きだす。
	 * ファイルの置き場所は（theme名/プラグイン名/template/テンプレート名.hbs）
	 * @param string $content_id JSで制御する箇所。HTMLタグのID(例：'goods_resv_manager')で指定
	 * @param string $template_name テンプレート名(例：'tab_print_area')
	 * @return string jsで制御するクラスに渡す引数のテンプレートのconfigを記述した文字列を返す。
	 *    (例："'tab_print_area: '#goods_resv_manager_reservation_tab_print_area_custom'")
	*/
	public function write_script_html_template(string $content_id, string $template_name) {
		$file = get_template_directory() . '/' . self::PLUGIN_ID . "/template/{$template_name}_custom.hbs";
		if(!file_exists($file)) return '';
		//
		$html_id = "{$content_id}_reservation_{$template_name}_custom";
		//scriptタグ出力
		echo "<script id='$html_id' type='text/x-handlebars-template'>";
		require_once $file;
		echo "</script>";  
		return "$template_name: '#$html_id'";
	}
	
	/**
	 * リクエストパラメタをシングルクォートのエスケープを取り除いたものを返す。
	 * 値が配列の場合にも対応。
	 * @param array $params リクエストのメソッド('get', 'post')
	 * @param array $names 抽出するパラメタ名
	 * @return array 取り除いた結果のパラメタ。値がない(''空文字含む)場合はNULLを設定。
	 */
	protected function stripslashes_request_params(array &$params, array $names): array {
		$ret = array();
		foreach($names as $name){
			$value = $params[$name];
			if(!isset($value) || $value == ''){
				$ret[$name] = NULL;
			}else if(is_array($value)){
				$ary_values = array();
				foreach($value as $id => $element){
					$ary_values[] = stripslashes($element);
				}
				$ret[$name] = $ary_values;
			}else{
				$ret[$name] = stripslashes($value);
			}
		}
		return $ret;
	}
	
	/**
	 * リクエストパラメタの妥当性チェックをする。
	 * @param array $req_params リクエストのメソッド('get', 'post')
	 * @param array $requiredFields 必須項目を指定(例：['goods_id', 'user_id'])。リクエストパラメタに存在しない場合はエラーにする。
	 */
	protected function createValidator(array $req_params, ?array $requiredFields=NULL
	): Valitron\Validator {
		if(!class_exists('Valitron\\Validator')){
			require_once 'Valitron/Validator.php';
		}
		$valitron_lang = $this->get_valitron_lang();
		Valitron\Validator::langDir($this->get_valitron_dir($valitron_lang));
		Valitron\Validator::lang($valitron_lang); 
		
		//オリジナルルールの追加
		require_once 'Valitron/inc_valitron_my_extention.php';

		//バリデータの作成
		$v = new Valitron\Validator($req_params);
		//ラベルの設定（エラーメッセージに設定した名前が出力されるようになる）
		$v->labels([
			//管理者側のパラメタ
			'admin_user_role'=> $this->getMessage('OBJ.ADMIN.USER_ROLE'),
			'admin_operator_role'=> $this->getMessage('OBJ.ADMIN.OPERATOR_ROLE'),
			'admin_default_locale'=> $this->getMessage('OBJ.ADMIN.DEFAULT_LOCALE'),
			'admin_send_mail'=> $this->getMessage('OBJ.ADMIN.SEND_MAIL'),
			'admin_send_mail_to.*'=> $this->getMessage('OBJ.ADMIN.SEND_MAIL_TO'),
			'admin_send_mail_from'=> $this->getMessage('OBJ.ADMIN.SEND_MAIL_FROM'),
			'admin_send_mail_title' => $this->getMessage('OBJ.ADMIN.SEND_MAIL_TITLE'),
			//ユーザ側のパラメタ
			'user_flows.isfl_id'        => $this->getMessage('OBJ.ISFL_ID'),
			'user_flows.revision'       => $this->getMessage('OBJ.GROUP_REVISION'),
			'user_flows.group_title'    => $this->getMessage('OBJ.GROUP_TITLE'),
			           'group_title'    => $this->getMessage('OBJ.GROUP_TITLE'),
			'user_flows.start_flow_id'  => $this->getMessage('OBJ.START_FLOW_ID'),
			'user_flows.keywords'       => $this->getMessage('OBJ.KEYWORDS'),
			'user_flows.keywords.*'     => $this->getMessage('OBJ.KEYWORDS'),
			           'keywords'       => $this->getMessage('OBJ.KEYWORDS'),
			'user_flows.group_remarks'  => $this->getMessage('OBJ.GROUP_REMARKS'),
			           'group_remarks'  => $this->getMessage('OBJ.GROUP_REMARKS'),
			'user_flows.flows.*.flow_id' => $this->getMessage('OBJ.FLOW.FLOW_ID'),
			'user_flows.flows.*.pt_id'   => $this->getMessage('OBJ.FLOW.PT_ID'),
			'user_flows.flows.*.revision'=> $this->getMessage('OBJ.FLOW.REVISION'),
			'user_flows.flows.*.status'  => $this->getMessage('OBJ.FLOW.STATUS'),
			'user_flows.flows.*.title'   => $this->getMessage('OBJ.FLOW.TITLE'),
			'user_flows.flows.*.question'=> $this->getMessage('OBJ.FLOW.QUESTION'),
			//user_flows.flows.input
			'user_flows.flows.*.input'   => $this->getMessage('OBJ.INPUT'),
			'user_flows.flows.*.input.*.no' => $this->getMessage('OBJ.INPUT.NO'),
			'user_flows.flows.*.input.*.type'=> $this->getMessage('OBJ.INPUT.TYPE'),
			'user_flows.flows.*.input.*.label'=> $this->getMessage('OBJ.INPUT.LABEL'),
			//user_flows.flows.choices
			'user_flows.flows.*.choices'         => $this->getMessage('OBJ.CHOICES'),
			'user_flows.flows.*.choices.*.id'    => $this->getMessage('OBJ.CHOICES.ID'),
			'user_flows.flows.*.choices.*.next_flow_id'=> $this->getMessage('OBJ.CHOICES.NEXT_FLOW_ID'),
			'user_flows.flows.*.choices.*.label' => $this->getMessage('OBJ.CHOICES.LABEL'),
			'user_flows.flows.*.choices.*.image' => $this->getMessage('OBJ.CHOICES.IMAGE'),
			'user_flows.flows.*.choices.*.attachment_id' => $this->getMessage('OBJ.CHOICES.ATTACHMENT_ID'),
			//画像関連
			'image_title'       => $this->getMessage('OBJ.IMAGE_TITLE'),
			//切り分け結果
			'results.result_id' => $this->getMessage('OBJ.RESULTS.RESULT_ID'),
			'results.user_name' => $this->getMessage('OBJ.RESULTS.USER_NAME'),
			'results.status'    => $this->getMessage('OBJ.RESULTS.STATUS'),
			'results_statuses.*'  => $this->getMessage('OBJ.RESULTS.STATUS'),
			'results.result'    => $this->getMessage('OBJ.RESULTS.RESULT'),
			'results.result.*.start_utc_time' => $this->getMessage('OBJ.RESULTS.STRAT_UTC_TIME'),
			'results.result.*.end_utc_time'   => $this->getMessage('OBJ.RESULTS.END_UTC_TIME'),
			'results.result.*.choice_id'    => $this->getMessage('OBJ.RESULTS.CHOICE_ID'),
			'results.result.*.input'        => $this->getMessage('OBJ.RESULTS.INPUT'),
			'results.result.*.input.*.no'   => $this->getMessage('OBJ.RESULTS.INPUT.NO'),
			'results.result.*.input.*.value'=> $this->getMessage('OBJ.RESULTS.INPUT.VALUE'),
			'results.result.*.input.*.decided_button'=> $this->getMessage('OBJ.RESULTS.RESULT.INPUT.DECIDED_BUTTON'),
			'results.remarks'   => $this->getMessage('OBJ.RESULTS.REMARKS'),
			'results_user_name' => $this->getMessage('OBJ.RESULTS.USER_NAME'),
			'results.created_date'      => $this->getMessage('OBJ.RESULTS.CREATED_DATE'),
			        'created_date_from' => $this->getMessage('OBJ.RESULTS.CREATED_DATE_FROM'),
			        'created_date_to'   => $this->getMessage('OBJ.RESULTS.CREATED_DATE_TO'),
		]);
		if(isset($requiredFields)){
			$v->rule('required', $requiredFields);
		}
		//管理者側------------------------
		$v->rule('slug', 'admin_user_role');
		$v->rule('slug', 'admin_operator_role');
		$v->rule('regex', 'admin_default_locale', '/^[0-9a-zA-Z\-\.\_]{1,30}$/')->message($this->getMessage('ERR.OBJ_ADMIN_DEFAULT_LANG'));
		$v->rule('min', 'admin_send_mail', 0);
		$v->rule('max', 'admin_send_mail', 1);
		$v->rule('email', 'admin_send_mail_to.*');
		$v->rule('email', 'admin_send_mail_from');
		$v->rule('lengthBetween', 'admin_send_mail_title', 1, 20);
		//admin_send_mail_toが必須の場合
		if(array_search('admin_send_mail_to.*', $requiredFields) !== false){
			$v->rule('my.arrayLengthBetween', 'admin_send_mail_to', 1, 5);
		}
		
		//ユーザ側------------------------
		//通常Json
		$v->rule('min', 'isfl_id', 0);
		$v->rule('min', 'revision', 0);
		$v->rule('min', 'result_id', 1);
		$v->rule('min', 'offset', 0);
		$v->rule('min', 'limit', 1);
		$v->rule('max', 'limit', 100);
		$v->rule('lengthBetween', 'group_title', 1, 40);
		$v->rule('lengthBetween', 'keywords', 1, 20);
		$v->rule('lengthBetween', 'group_remarks', 0, 120);
		$v->rule('lengthBetween', 'image_title', 1, 20);
		$v->rule('in', 'user_only', ['true', 'false']);
		//Flow GroupのJson
		if(isset($req_params['user_flows'])){
			$v->rule('min', 'user_flows.isfl_id', 0);//isfl_idは呼び出し側でチェックする
			$v->rule('min', 'user_flows.revision', 1);//1
			$v->rule('lengthBetween', 'user_flows.group_title', 1, 40);//40
			$v->rule('min', 'user_flows.start_flow_id', 1);//1
			$v->rule('my.arrayStrLength', 'user_flows.keywords', 0, 40);
			//$v->rule('lengthBetween', 'user_flows.keywords.*', 0, 20);
			$v->rule('lengthBetween', 'user_flows.group_remarks', 0, 120);
			//user_flows.flows
			$v->rule('min', 'user_flows.flows.*.flow_id', 1);
			$v->rule('lengthBetween', 'user_flows.flows.*.pt_id', 1, 20);//1,20
			$v->rule('min', 'user_flows.flows.*.revision', 1);//1
			$v->rule('in', 'user_flows.flows.*.status', ['open', 'close']);
			$v->rule('lengthBetween', 'user_flows.flows.*.title', 1, 40);//40
			$v->rule('lengthBetween', 'user_flows.flows.*.question', 1, 400);//400
			//user_flows.flows.input
			$v->rule('my.arrayLengthBetween', 'user_flows.flows.*.input', 0, 10);//10
			$v->rule('requiredWith', 'user_flows.flows.*.input.*.no', ['user_flows.flows.*.input.*.type', 'user_flows.flows.*.input.*.label']);
			$v->rule('requiredWith', 'user_flows.flows.*.input.*.no', 'user_flows.flows.*.input.*.label');
			$v->rule('min', 'user_flows.flows.*.input.*.no', 1);//1
			$v->rule('in', 'user_flows.flows.*.input.*.type', ['text']);
			$v->rule('lengthBetween', 'user_flows.flows.*.input.*.label', 1, 40);//40
			//user_flows.flows.choices
			$v->rule('my.arrayLengthBetween', 'user_flows.flows.*.choices', 0, 10);//10
			$v->rule('requiredWith', 'user_flows.flows.*.choices.*.id', 'user_flows.flows.*.choices.*.next_flow_id');
			$v->rule('requiredWith', 'user_flows.flows.*.choices.*.id', 'user_flows.flows.*.choices.*.label');
			$v->rule('min', 'user_flows.flows.*.choices.*.id', 1);//1
			$v->rule('min', 'user_flows.flows.*.choices.*.next_flow_id', 1);//1
			$v->rule('lengthBetween', 'user_flows.flows.*.choices.*.label', 1, 40);//40
			$v->rule('lengthBetween', 'user_flows.flows.*.choices.*.image', 1, 200);//200
			//$v->rule('url', 'user_flows.flows.*.choices.*.image');
			$v->rule('min', 'user_flows.flows.*.choices.*.attachment_id', 1);
		}
		//Flow GroupのJson
		if(isset($req_params['results'])){
			$v->rule('in', 'results.status', ['created', 'open', 'resolved']);
			$v->rule('min', 'results.result_id', 1);
			$v->rule('my.jsonStrLength', 'results.result', 0, $this->getMessage('OBJ.RESULTS.RESULT.LEN'));
			$v->rule('min', 'results.result.*.start_utc_time', 0);
			$v->rule('min', 'results.result.*.end_utc_time', 0);
			$v->rule('min', 'results.result.*.choice_id', 0);
			$v->rule('my.arrayLengthBetween', 'results.result.*.input', 0 , 10);
			$v->rule('min', 'results.result.*.input.*.no', 1);
			$v->rule('lengthBetween', 'results.result.*.input.*.value', 0, $this->getMessage('OBJ.INPUT.VALUE.LEN'));
			$v->rule('in', 'results.result.*.decided_button', ['open', 'close', 'close_forcely']);
			$v->rule('lengthBetween', 'results.remarks', 0, $this->getMessage('OBJ.RESULTS.REMARKS.LEN'));
		}
		//ResultsのGETパラメタ
		$v->rule('in', 'results_status', ['created', 'open', 'resolved']);
		$v->rule('in', 'results_statuses.*', ['created', 'open', 'resolved']);
		$v->rule('lengthBetween', 'results_remarks', 0, 20);
		$v->rule('lengthBetween', 'results_user_name', 1, 20);
		$v->rule('in', 'cnt_kind', ['isfl_id', 'time', 'user', 'results_status']);
		$v->rule('in', 'cnt_created_date_unit', ['year','month','day','hour']);
		$v->rule('dateFormat', 'created_date_from', 'Y-m-d');
		$v->rule('dateFormat', 'created_date_to', 'Y-m-d');
		$v->rule('my.dateAfterWith', 'created_date_to', 'created_date_from');
		//Mail送信情報
		if(isset($req_params['mail_info'])){
			$v->rule('in', 'mail_info.sendmail', ['0', '1']);
		}
		
		return $v;
	}
	
	
	/**
	 * WP REST APIのオリジナルエンドポイント追加
	 * wp-json/isolation-flow-manager/api/...にアクセスできるようにする。
	 */
	static function add_rest_original_endpoint(){
		//切り分けフロー更新
		register_rest_route( self::API_URI_PREFIX, '/flow_groups/(\d+)$', array(
			'methods' => 'POST',
			'permission_callback' => array(self::obj(), 'api_authenticate_operator'),
			//エンドポイントにアクセスした際に実行される関数
			'callback' =>  array(self::obj(), 'api_update_flow_groups'),
		));
		//切り分けフロー登録
		register_rest_route( self::API_URI_PREFIX, '/flow_groups', array(
			'methods' => 'POST',
			'permission_callback' => array(self::obj(), 'api_authenticate_operator'),
			//エンドポイントにアクセスした際に実行される関数
			'callback' =>  array(self::obj(), 'api_insert_flow_groups'),
		));
		//切り分けフロー取得
		register_rest_route( self::API_URI_PREFIX, '/flow_groups/(\d+)/(\d+)$', array(
			'methods' => 'GET',
			'permission_callback' => array(self::obj(), 'api_authenticate_operator'),
			//エンドポイントにアクセスした際に実行される関数
			'callback' =>  array(self::obj(), 'api_get_flow_groups'),
		));
		//切り分けフロー検索
		register_rest_route( self::API_URI_PREFIX, '/flow_groups', array(
			'methods' => 'GET',
			'permission_callback' => array(self::obj(), 'api_authenticate_operator'),
			//エンドポイントにアクセスした際に実行される関数
			'callback' =>  array(self::obj(), 'api_find_flow_groups'),
		));

		//切り分け結果取得/api/flow_results
		register_rest_route( self::API_URI_PREFIX, '/flow_results', array(
			'methods' => 'GET',
			'permission_callback' => array(self::obj(), 'api_authenticate_operator'),
			//エンドポイントにアクセスした際に実行される関数
			'callback' =>  array(self::obj(), 'api_find_flow_resutlts'),
		));
		//切り分け結果新規登録＆取得(数字はisfl_id)/api/flow_results
		register_rest_route( self::API_URI_PREFIX, '/flow_results', array(
			'methods' => 'POST',
			'permission_callback' => array(self::obj(), 'api_authenticate_operator'),
			//エンドポイントにアクセスした際に実行される関数
			'callback' =>  array(self::obj(), 'api_new_flow_results'),
		));
		//切り分け結果更新/api/flow_results/* (数字はresult_id)
		register_rest_route( self::API_URI_PREFIX, '/flow_results/(\d+)$', array(
			'methods' => 'POST',
			'permission_callback' => array(self::obj(), 'api_authenticate_operator'),
			//エンドポイントにアクセスした際に実行される関数
			'callback' =>  array(self::obj(), 'api_update_flow_results'),
		));
		//切り分け結果統計情報取得/api/flow_results/statistics
		register_rest_route( self::API_URI_PREFIX, '/flow_results/statistics', array(
			'methods' => 'GET',
			'permission_callback' => array(self::obj(), 'api_authenticate_operator'),
			//エンドポイントにアクセスした際に実行される関数
			'callback' =>  array(self::obj(), 'api_count_results'),
		));

		//画像更新/api/images
		register_rest_route( self::API_URI_PREFIX, '/images', array(
			'methods' => 'POST',
			'permission_callback' => array(self::obj(), 'api_authenticate_operator'),
			//エンドポイントにアクセスした際に実行される関数
			'callback' =>  array(self::obj(), 'api_insert_image'),
		));
		//画像更新/api/images
		register_rest_route( self::API_URI_PREFIX, '/images', array(
			'methods' => 'GET',
			'permission_callback' => array(self::obj(), 'api_authenticate_operator'),
			//エンドポイントにアクセスした際に実行される関数
			'callback' =>  array(self::obj(), 'api_find_images'),
		));
		//画像削除/api/images/*
		register_rest_route( self::API_URI_PREFIX, '/images/(\d+)$', array(
			'methods' => 'DELETE',
			'permission_callback' => array(self::obj(), 'api_authenticate_operator'),
			//エンドポイントにアクセスした際に実行される関数
			'callback' =>  array(self::obj(), 'api_delete_images'),
		));
		
	}
	
	
	
	//実体を取得
	static public function obj(){
		//require_once 'def_params_validation.php';
		if(is_null(self::$instance)) self::$instance = new ISFL_IsolationFlowManager();
		return self::$instance;
	}
	
	
	
	
	public function add_admin_menu() {
		//menu_slagはハイフンで繋ぐのがWordPressの流儀のようです。一意である必要もあるらしい。
		add_menu_page($this->getMessage('MNG.TITLE.MAIN_MENU'), $this->getMessage('MNG.TITLE.MAIN_MENU'), 
			'administrator', self::PLUGIN_ID, array($this, 'display_menu_main'));
		add_submenu_page(self::PLUGIN_ID, 
			$this->getMessage('MNG.TITLE.SUB_MENU_SETTINGS'), $this->getMessage('MNG.TITLE.SUB_MENU_SETTINGS'), 
			'administrator', self::PLUGIN_ID.'-1', array($this, 'display_menu_main'));
		add_submenu_page(self::PLUGIN_ID, 
			$this->getMessage('MNG.TITLE.SUB_MENU_EDIT'), $this->getMessage('MNG.TITLE.SUB_MENU_EDIT'), 
			$this->operator_role, self::PLUGIN_ID.'-2', array($this, 'display_menu_edit'));
		add_submenu_page(self::PLUGIN_ID, 
			$this->getMessage('MNG.TITLE.SUB_MENU_EXEC'), $this->getMessage('MNG.TITLE.SUB_MENU_EXEC'), 
			$this->user_role, self::PLUGIN_ID.'-user-1', array($this, 'display_menu_exec'));
		add_submenu_page(self::PLUGIN_ID, 
			$this->getMessage('MNG.TITLE.SUB_MENU_HISTORY'), $this->getMessage('MNG.TITLE.SUB_MENU_HISTORY'), 
			$this->user_role, self::PLUGIN_ID.'-user-2', array($this, 'display_menu_history'));
	}
	
	//サブメニューを作らないと内部ではエラーが出ているらしく、サブを使いたくない場合非表示にするらしい（本当？）
	public function remove_admin_menu_sub() {
		global $submenu;
		if($submenu[self::PLUGIN_ID][0][2] === self::PLUGIN_ID){
			unset($submenu[self::PLUGIN_ID][0]);
		}
	}
	
	public function display_menu_main(){
		if($_POST['type'] === 'save'){
			if(!isset($_POST[self::CREDENTIAL_NAME]) || !$_POST[self::CREDENTIAL_NAME]) return false;
			if(!check_admin_referer(self::CREDENTIAL_ACTION, self::CREDENTIAL_NAME)) return false;
			//パラメタ取得
			$req_params = $this->stripslashes_request_params($_POST, array(
				'admin_user_role',
				'admin_operator_role',
				'admin_default_locale',
				'admin_send_mail',
				'admin_send_mail_to',
				'admin_send_mail_from',
				'admin_send_mail_title'
			));
			//パラメタ処理
			$req_params['admin_send_mail_to'] = explode(",", $req_params['admin_send_mail_to']);
			//画面に表示する処理メッセージ
			$result_message = $this->getMessage('ERR.ERR_OCCURED');
			//必須の項目
			$required = array('admin_user_role', 'admin_operator_role',
				'admin_default_locale', 
			);
			//admin_send_mailが1の場合は送信先も必須
			if($req_params['admin_send_mail'] == '1') array_push($required, 'admin_send_mail_to.*', 'admin_send_mail_title');
			//妥当性チェック実行
			$validator =$this->createValidator($req_params, $required, false);
			if($validator->validate()){
				//エラーがない場合
				$this->user_role       = $req_params['admin_user_role'];
				$this->operator_role   = $req_params['admin_operator_role'];
				$this->default_locale  = $req_params['admin_default_locale'];
				$this->send_mail       = ($req_params['admin_send_mail'] == '1' ? true : false);
				$this->send_mail_to    = $req_params['admin_send_mail_to'];
				$this->send_mail_from  = $req_params['admin_send_mail_from'];
				$this->send_mail_title = $req_params['admin_send_mail_title'];
				
				//wordpressのDBに設定を保存
				$settings = array('user_role'=>$this->user_role, 'operator_role'=>$this->operator_role, 'default_locale'=>$this->default_locale, 
					'send_mail'=>$this->send_mail, 'send_mail_to'=>$this->send_mail_to, 'send_mail_from'=>$this->send_mail_from,
					'send_mail_title'=>$this->send_mail_title,);
				//plugin dbに保存
				$this->save_option($settings);
				
				//成功の場合のメッセージ
				$result_message = $this->getMessage('OK.SUCCESS');
			}
		}
		
		//HTML表示
		require_once 'template/admin_menu_main_page.php';
	}
	
	public function display_menu_edit(){
		require_once 'template/admin_menu_edit_page.php';
	}
	
	public function display_menu_exec(){
		require_once 'template/admin_menu_exec_page.php';
	}
	
	public function display_menu_history(){
		require_once 'template/admin_menu_history_page.php';
	}
	
	
	
	
	/**
	 * APIでのアクセスの認証をする
	 * HTTPヘッダ(X-WP-Nonce)の認証はWPが勝手に行う。なので、ここではそれ以外の認証をする（権限とか）。
	 */
	public function api_authenticate_operator() : bool {
		if(current_user_can($this->operator_role) || current_user_can($this->user_role)) return true;
		return false;
	}

	
	protected function create_err_response(string $err_code, array $messages=array()) : WP_REST_Response {
		$errors = NULL;
		$msg = NULL;
		$status = 200;
		switch($err_code){
			case 'invalid_params':
				$errors = array('fields'=> $messages);
				$msg = $this->getMessage('ERR.INVALID_PARAMS', false);
				$status = 400;
				break;
			case 'not_found':
				$errors = $messages;
				$msg = $this->getMessage('ERR.NOT_FOUND', false);
				$status = 404;
				break;
			case 'access_error':
				$errors = $messages;
				$msg = $this->getMessage('ERR.ACCESS_ERROR', false);
				$status = 403;
				break;
			case 'db_error':
				$errors = $messages;
				$msg = $this->getMessage('ERR.DB_ERROR', false);
				$status = 500;
				break;
			case 'file_error':
				$errors = $messages;
				$msg = $this->getMessage('ERR.FILE_ERROR', false);
				$status = 500;
				break;
			case 'non_difference_warn':
					$errors = $messages;
					$msg = $this->getMessage('ERR.NON_DIFFERENCE_WARN', false);
					$status = 400;
					break;
			default:
				$errors = $messages;
				$status = 500;
		}
		//
		return new WP_REST_Response(array('code'=> $err_code, 'message'=>$msg, 'errors'=> $errors), $status);
	}
	
	/**
	 * エラーコード形式の配列をエラーメッセージ文字列に変換する。
	 * @param array $errors エラーコード形式の配列({field:[[errCode, [keyArray]]]})
	 *             keyArrayはキー名（例：'OBJ.FLOW.FLOW_ID'）
	 * @param bool  $is_escape trueのときHTMLエスケープをする。
	 * @return array エラーメッセージ文字列に変換した配列（{field:[errMessage]}）
	 */
	protected function convert_errors_to_string(array $errors, bool $is_escape): array{
		$ret = array();
		foreach($errors as $key => $err_codes){
			$ret[$key] = array();
			foreach($err_codes as $err){
				$err_msg = '';
				//エラー文字列に変換する
				if(isset($err[1])){
					//キーをすべてメッセージ変換する
					$params = array();
					foreach($err[1] as $name){
						$params[] = $this->getMessage($name, $is_escape);
					}
					//メッセージ変換
					$err_msg = $this->getMessage($err[0], $is_escape, ...$params);
				}else{
					//メッセージ変換
					$err_msg  = $this->getMessage($err[0], $is_escape);
				}
				$ret[$key][] = $err_msg;
			}
		}
		return $ret;
	}
	
	/**
	 * API(POST)操作者用
	 * Flowのグループの登録処理。
	 * @param WP_REST_Request $req リクエストオブジェクト
	 * @return 
	 * @see _save_flow_groups
	 */
	public function api_insert_flow_groups(WP_REST_Request $req): WP_REST_Response{
		return $this->_save_flow_groups($req, 0);
	}
	
	
	/**
	 * API(POST)操作者用
	 * Flowのグループの更新処理。
	 * @param WP_REST_Request $req リクエストオブジェクト
	 * @return 
	 * @see _save_flow_groups
	 */
	public function api_update_flow_groups(WP_REST_Request $req): WP_REST_Response{
		$isfl_id = strrchr($_SERVER["REQUEST_URI"], '/');
		$isfl_id = substr($isfl_id, 1);
		return $this->_save_flow_groups($req, (int)$isfl_id);
	}

	/**
	 * API(POST)操作者用
	 * Flowのグループの保存処理。insert/updateの内部処理。
	 * 成功時のレスポンスbodyは、{status:'success', group:flow_group}。
	 * 値に変更がない場合、DB更新せずにstatus:200::'non_difference_warn'をレスポンスする。
	 * @param WP_REST_Request $req リクエストオブジェクト
	 * @param int $check_isfl_id 妥当性チェックするjsonのisfl_id。一致しない場合エラーにする。
	 * @return 
	 */
	protected function _save_flow_groups(WP_REST_Request $req, int $check_isfl_id): WP_REST_Response{
		//権限がオペレータ以上かチェックする
		if(!current_user_can($this->operator_role)){
			return $this->create_err_response('access_error');
		}
		//ボディサイズが大きすぎないか
		if(strlen($req->get_body()) > 500*1024){
			return $this->create_err_response('invalid_params');
		}
		//ボディ部取得
		$json = $req->get_json_params();
		//登録処理の場合の妥当性チェック（登録時はisfl_id==0）
		if($json['user_flows']['isfl_id'] !== $check_isfl_id){
			//code, message, status
			$errors = array("user_flows.isfl_id" => array($this->getMessage('ERR.INVALID_PARAMS', false)));
			return $this->create_err_response('invalid_params', $errors);
		};
		
		//妥当性チェック２
		$requiered = array(
			'user_flows.isfl_id', 'user_flows.revision', 'user_flows.group_title',
			'user_flows.start_flow_id','user_flows.keywords','user_flows.flows.*.flow_id',
			'user_flows.flows.*.title','user_flows.flows.*.status',
			'user_flows.flows.*.question','user_flows.flows.*.input',
			'user_flows.flows.*.input.*.no', 'user_flows.flows.*.input.*.type', 'user_flows.flows.*.input.*.label',
			'user_flows.flows.*.choices','user_flows.flows.*.choices.*.id',
			'user_flows.flows.*.choices.*.label', 'user_flows.flows.*.choices.*.next_flow_id'
			
		);
		$validator = $this->createValidator($json, $requiered);
		if(!$validator->validate()){
			//code, message, status
			return $this->create_err_response('invalid_params', $validator->errors());
		}
		
		//クラス生成
		require_once 'isolation_flow_group.php';
		$group = new ISFL_IsolationFlowGroup($json);
		$group->is_debug = true;
		$errors = $this->convert_errors_to_string($group->validate(), false);
		if(count($errors) > 0){
			//code, message, status
			return $this->create_err_response('invalid_params', $errors);
		}
		
		//DB保存処理
		require_once 'dao.php';
		$dao = new ISFL_IsolationFlowDao();
		$err_code = $dao->save_group_class($group);
		if($err_code < 0){
			//code, message, status
			error_log("db_error::$err_code, ".$dao->last_error);
			return $this->create_err_response('db_error');
		}else if($err_code === 1){
			//データの値が全く同じだったため更新しなかった
			return $this->create_err_response('non_difference_warn', 
				array('user_flows' => $this->getMessage('WARN.NOT_UPDATED', false)));
		}
		$group2 = $dao->get_group_class($group->isfl_id);

		//過去のリビジョンを削除する
		$dao->delete_group_flows(array(
			'isfl_id' => $group2->isfl_id, 
			'max_revision' => $group2->revision - 1,
			'has_not_result' => true,
		));

		return new WP_REST_Response(array('status'=>'success', 'group' => $group2->json));
	}
	
	/**
	 * API(GET)ユーザ用
	 * Flowのグループの取得処理。
	 * @param WP_REST_Request $req リクエストオブジェクト
	 * @return 
	 */
	public function api_get_flow_groups(WP_REST_Request $req): WP_REST_Response{
		$tmp = strpos($_SERVER["REQUEST_URI"], '/flow_groups/');
		$tmp = substr($_SERVER["REQUEST_URI"], $tmp + strlen('/flow_groups/'));
		$split_uri = explode('/', $tmp);
		$isfl_id = $split_uri[0];
		$revision = isset($split_uri[1]) ? (int)$split_uri[1] : NULL;
		
		//妥当性チェック
		if(!(is_numeric($isfl_id) && (int)$isfl_id >= 1)){
			//code, message, status
			return $this->create_err_response('invalid_params', array('isfl_id'=>$isfl_id));
		}

		//Dao取得
		require_once 'dao.php';
		$dao = new ISFL_IsolationFlowDao();
		//
		$group = $dao->get_group_class($isfl_id, $revision);
		if($group === NULL){
			return $this->create_err_response('not_found', array('isfl_id'=>$isfl_id));
		}
		return new WP_REST_Response($group->json);
	}
	
	/**
	 * API(GET)ユーザ用
	 * 切り分けフローの取得処理。
	 * パラメタ（group_title, keywords, isfl_id）
	 * @param WP_REST_Request $req リクエストオブジェクト
	 * @return 
	 */
	public function api_find_flow_groups(WP_REST_Request $req): WP_REST_Response{
		//パラメタ取得
		$req_params = $this->stripslashes_request_params($_GET, array(
			'offset',
			'limit',
			'group_title',
			'keywords',
			'group_remarks',
			'isfl_id',
		));
		//妥当性チェック
		$requiered = array();
		$validator = $this->createValidator($req_params, $requiered);
		if(!$validator->validate()){
			//code, message, status
			return $this->create_err_response('invalid_params', $validator->errors());
		}
		
		//Dao取得
		require_once 'dao.php';
		$dao = new ISFL_IsolationFlowDao();

		//検索する
		$serachkeys = array(
			'offset' => $req_params['offset'] ,
			'limit' => $req_params['limit'],
			'group_title'=> $req_params['group_title'],
			'keywords'   => $req_params['keywords'],
			'group_remarks' => $req_params['group_remarks'],
			'isfl_id'    => $req_params['isfl_id'],
		);
		//DBアクセス
		$result = $dao->find_group($serachkeys);
		//
		return new WP_REST_Response($result);
	}


	/**
	 * API(POST)ユーザ用
	 * 結果オブジェクトの新規登録＆取得。new/数字。数字はisfl_id。
	 * @param WP_REST_Request $req リクエストオブジェクト
	 * @return 
	 */
	public function api_new_flow_results(WP_REST_Request $req): WP_REST_Response{
		$user_id = get_current_user_id();

		//ボディサイズが大きすぎないか
		if(strlen($req->get_body()) > 500*1024){
			error_log('request body size too big.');
			return $this->create_err_response('invalid_params');
		}
		//ボディ部取得
		$json = $req->get_json_params();
		
		//妥当性チェック
		$isfl_id = $json['isfl_id'];
		if(!(is_numeric($isfl_id) && (int)$isfl_id >= 1)){
			//code, message, status
			return $this->create_err_response('invalid_params', array('isfl_id'=>$isfl_id));
		}

		//Dao取得
		require_once 'dao.php';
		$dao = new ISFL_IsolationFlowDao();

		//結果新規作成
		$flow_result = array(
			'result_id'=> 0,
			'user_id'  => $user_id, 
			'isfl_id'  => (int)$isfl_id, 
			'status'   => 'created', 
			'result'   => array(), 
			'remarks'  => '', 
		);
		//DB書き込み
		$result = $dao->save_flow_results($flow_result);
		if($result === false){
			return $this->create_err_response('not_found');
		}

		//書き込み結果取得
		$result = $dao->find_flow_results(array('isfl_id' => $isfl_id, 'result_id'=>$flow_result['result_id']));
		//存在チェック
		if($result['amount'] == 0){
			return $this->create_err_response('invalid_params', array('isfl_id'=>$isfl_id));
		}
		$flow_result = $result['list'][0];
		return new WP_REST_Response($flow_result);
	}
	
	/**
	 * API(POST)ユーザ用
	 * 結果オブジェクトの更新。数字はresult_id。パラメタ（json{result, mail_info}）
	 * @param WP_REST_Request $req リクエストオブジェクト
	 * @return 
	 */
	public function api_update_flow_results(WP_REST_Request $req): WP_REST_Response{
		$result_id = strrchr($_SERVER["REQUEST_URI"], '/');
		$result_id = substr($result_id, 1);
		$user_id = get_current_user_id();
		
		//ボディサイズが大きすぎないか
		if(strlen($req->get_body()) > 500*1024){
			error_log('request body size too big.');
			return $this->create_err_response('invalid_params');
		}
		//ボディ部取得
		$json = $req->get_json_params();
		//妥当性チェック
		if(!(is_numeric($result_id) && (int)$result_id >= 1)){
			//code, message, status
			return $this->create_err_response('invalid_params', array('result_id'=>$result_id));
		}
		//妥当性チェック
		$requiered = array('results.status', 'results.isfl_id', 'results.revision');
		$validator = $this->createValidator($json, $requiered);
		if(!$validator->validate()){
			//code, message, status
			return $this->create_err_response('invalid_params', $validator->errors());
		}
		//妥当性チェック（end_utc_timeの値が最後以外すべて設定されていること）
		for($i = 0; $i < count($json['results']['result'])-1; ++$i){
			$end_utc_time = $json['results']['result'][$i]['end_utc_time'];
			if(!is_int($end_utc_time)){
				$err = array('results.result.*.end_utc_time' => array("results.result[$i]=$end_utc_time"));
				//code, message, status
				return $this->create_err_response('invalid_params', $err);
			}
		}

		//Dao取得
		require_once 'dao.php';
		$dao = new ISFL_IsolationFlowDao();

		//結果作成
		$flow_result = array(
			'result_id'=> $result_id,
			'user_id'  => $user_id,
			'isfl_id'  => $json['results']['isfl_id'], 
			'revision' => $json['results']['revision'], 
			'status'   => $json['results']['status'], 
			'result'   => $json['results']['result'], 
			'remarks'  => $json['results']['remarks'], 
		);
		//DB書き込み
		$result = $dao->save_flow_results($flow_result);
		if($result === false){
			return $this->create_err_response('not_found');
		}

		//メール送信
		$mail_info = $json['mail_info'];
		if($this->send_mail && isset($mail_info) && $mail_info['send_mail']){
			$group = $dao->get_group_class($flow_result['isfl_id'], $flow_result['revision']);
			$msg = $group->make_result_msg($flow_result);
			$title = ($this->get_option('send_mail_title'));
			$ary_to = $this->get_option('send_mail_to');
			$from = $this->get_option('send_mail_from');
			$headers = array();//'Content-Type: text/html; charset=UTF-8;');
			$headers[] = 'From: ' . $from;
			$mail_result = wp_mail($ary_to, $title, $msg, $headers);
			if(!$mail_result) error_log("wp_mail fail in api_update_flow_results().result_id=$result_id");
		}

		return new WP_REST_Response(array('status'=>'success'));
	}
	
	/**
	 * API(GET)ユーザ用
	 * 切り分け結果の取得処理。パラメタ（keywords, isfl_id, results_remarks,
	 *    result_id, revision, results_status,results_statuses:array, user_only）
	 * user_only:boolはログインユーザの作成した結果以外を検索する場合に使用。
	 * @param WP_REST_Request $req リクエストオブジェクト
	 * @return 
	 */
	public function api_find_flow_resutlts(WP_REST_Request $req): WP_REST_Response{
		$user_id = get_current_user_id();

		//パラメタ取得
		$req_params = $this->stripslashes_request_params($_GET, array(
			'offset',
			'limit',
			'keywords',
			'isfl_id',
			'revision',
			'group_title',
			'result_id',
			'user_only',
			'results_user_name',
			'results_status',
			'results_statuses',
			'results_remarks',
			'created_date_from',
			'created_date_to',
		));
		//妥当性チェック
		$requiered = array();
		$validator = $this->createValidator($req_params, $requiered);
		if(!$validator->validate()){
			//code, message, status
			return $this->create_err_response('invalid_params', $validator->errors());
		}

		//権限がユーザ以上の場合はすべての結果も検索できるようにする
		if($req_params['user_only'] === 'false'){
			$user_id = NULL;
		}
		
		//Dao取得
		require_once 'dao.php';
		$dao = new ISFL_IsolationFlowDao();

		//検索する
		$serachkeys = array(
			'offset'   =>$req_params['offset'],
			'limit'    =>$req_params['limit'],
			'keywords' =>$req_params['keywords'],
			'isfl_id'  =>$req_params['isfl_id'],
			'revision' =>$req_params['revision'],
			'group_title' =>$req_params['group_title'],
			'result_id'   =>$req_params['result_id'],
			'user_id'     =>$user_id,
			'user_name'   =>$req_params['results_user_name'],
			'results.status'   =>$req_params['results_status'],
			'results.statuses' =>$req_params['results_statuses'],
			'results.remarks'  =>$req_params['results_remarks'],
			'created_date_from'=> $req_params['created_date_from'],
			'created_date_to'  => $req_params['created_date_to'],
		);
		//DBアクセス
		$results = $dao->find_flow_results($serachkeys);
		//
		return new WP_REST_Response($results);
	}

	/**
	 * API(GET)ユーザ用
	 * 統計情報を取得する。
	 * {cnt_kind, group_title, results_statuses:array, (cnt_created_date_unit|created_date_from}
	 * ※cnt_created_date_unitが設定されている場合はcreated_date_fromは無視される。
	 * @param WP_REST_Request $req リクエストオブジェクト
	 * @return 
	 */
	public function api_count_results(WP_REST_Request $req): WP_REST_Response{
		//パラメタ取得
		$req_params = $this->stripslashes_request_params($_GET, array(
			'limit',
			'offset',
			'cnt_kind',
			'group_title',
			'results_statuses',
			'cnt_created_date_unit',
			'created_date_from',
			'created_date_to',
		));
		//妥当性チェック
		$requiered = array('cnt_kind');
		$validator = $this->createValidator($req_params, $requiered);
		if(!$validator->validate()){
			//code, message, status
			return $this->create_err_response('invalid_params', $validator->errors());
		}

		//Dao取得
		require_once 'dao.php';
		$dao = new ISFL_IsolationFlowDao();

		//検索キー
		$serachkeys = array(
			'limit' => $req_params['limit'],
			'offset' => $req_params['offset'],
			'cnt_kind' => $req_params['cnt_kind'],
			'group_title' => $req_params['group_title'],
			'results.statuses' => $req_params['results_statuses'],
			'cnt_created_date_unit' => $req_params['cnt_created_date_unit'],
			'created_date_from' => $req_params['created_date_from'],
			'created_date_to' => $req_params['created_date_to'],
		);
		$result = $dao->count_flow_results($serachkeys);

		//コードを対象言語に変換
		if($cnt_kind == 'results_status'){
			$def = $this->get_defs('RESULTS_STATUSES');
			for($i=0; $i < count($result['list']); ++$i){
				$name = $result['list'][$i]['y_axis_name'];
				$result['list'][$i]['y_axis_name'] = $def[$name];
			}
		}

		return new WP_REST_Response($result);
	}
		
	/**
	 * API(POST)操作者用
	 * 画像をアップロードする。Wordpressの画像投稿機能で保存する。
	 * {image, title, filename}
	 * @param WP_REST_Request $req リクエストオブジェクト
	 * @return 
	 */
	public function api_insert_image(WP_REST_Request $req): WP_REST_Response{
		//権限がオペレータ以上かチェックする
		if(!current_user_can($this->operator_role)){
			return $this->create_err_response('access_error');
		}

		//ボディサイズが大きすぎないか
		if(strlen($req->get_body()) > 500*1024){
			error_log('request body size too big.');
			return $this->create_err_response('invalid_params');
		}
		
		//ボディ部取得
		$json = $req->get_json_params();
		
		//妥当性チェック
		$requiered = array('image', 'filename');
		$validator = $this->createValidator($json, $requiered);
		if(!$validator->validate()){
			//code, message, status
			return $this->create_err_response('invalid_params', $validator->errors());
		}

		$title = $json['image_title'];

		// アップロード用ディレクトリのパスを取得。
		$wp_upload_dir = wp_upload_dir();
		//ファイル名
		$filename = $wp_upload_dir['path'] . '/ISFL_' .date_i18n("YmdHis_").$json['filename'];

		//画像バイナリ
		$img_data = base64_decode($json['image']);
		// 結果をファイルに書き出します
		file_put_contents($filename, $img_data);
		//紐づけたくない場合は0を指定するらしい。
		$parent_post_id = 0;
		// ファイルの種類をチェックする。これを 'post_mime_type' に使う。
		$filetype = wp_check_filetype( basename( $filename ), null);
		// 添付ファイル用の投稿データの配列を準備。
		$attachment = array(
			'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
			'post_mime_type' => $filetype['type'],
			'post_title'     => "ISFL::$title",
			'post_content'   => '',
			'post_status'    => 'inherit'
		);
		
		// 添付ファイルを追加。
		$attach_id = wp_insert_attachment($attachment, $filename, $parent_post_id );

		if ( is_wp_error( $attach_id ) ) {
			// 画像のアップロード中にエラーが起きた。
			return $this->create_err_response('file_error');
		}
		// wp_generate_attachment_metadata() の実行に必要なので下記ファイルを含める。
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// 添付ファイルのメタデータを生成し、データベースを更新。
		$attach_data = wp_generate_attachment_metadata($attach_id, $filename );
		wp_update_attachment_metadata($attach_id, $attach_data );

		return new WP_REST_Response(array('status'=>'success', 'attachment_id'=>$attach_id));
	}
	
	/**
	 * API(GET)操作者用
	 * 画像のURLの取得処理。
	 * パラメタ（image_title,filename）
	 * @param WP_REST_Request $req リクエストオブジェクト
	 * @return 
	 */
	public function api_find_images(WP_REST_Request $req): WP_REST_Response{
		//権限がオペレータ以上かチェックする
		if(!current_user_can($this->operator_role)){
			return $this->create_err_response('access_error');
		}

		//パラメタ取得
		$req_params = $this->stripslashes_request_params($_GET, array(
			'offset',
			'limit',
			'image_title',
			'filename',
		));
		//妥当性チェック
		$requiered = array();
		$validator = $this->createValidator($req_params, $requiered);
		if(!$validator->validate()){
			//code, message, status
			return $this->create_err_response('invalid_params', $validator->errors());
		}
		
		//Dao取得
		require_once 'dao.php';
		$dao = new ISFL_IsolationFlowDao();

		//検索する
		$serachkeys = array(
			'offset'     => $req_params['offset'],
			'limit'      => $req_params['limit'],
			'image_title'=> $req_params['image_title'],
			'guid'       => $req_params['filename'],
		);
		//DBアクセス
		$result = $dao->find_image($serachkeys);
		
		//image_titleからISFL::を削除する
		foreach($result['list'] as &$line){
			$line['image_title'] = preg_replace("/ISFL\:\:/", '', $line['image_title']);
		}
		return new WP_REST_Response($result);
	}

	/**
	 * API(DELETE)操作者用
	 * 画像の削除。/画像ID。パラメタ（なし）
	 * @param WP_REST_Request $req リクエストオブジェクト
	 * @return 
	 */
	public function api_delete_images(WP_REST_Request $req): WP_REST_Response{
		$attach_id = strrchr($_SERVER["REQUEST_URI"], '/');
		$attach_id = substr($attach_id, 1);

		//権限がオペレータ以上かチェックする
		if(!current_user_can($this->operator_role)){
			return $this->create_err_response('access_error');
		}

		//削除実行
		$attachment = wp_delete_attachment($attach_id);
		//失敗時の処理
		if ($attachment === false){
			// 画像のアップロード中にエラーが起きた。
			return $this->create_err_response('file_error');
		} 
			
		return new WP_REST_Response(array('status'=>'success', 'attachment'=>$attachment));
	}
}



?>