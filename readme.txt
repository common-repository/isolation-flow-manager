=== Isolation Flow Manager ===
Contributors: nanajuly
Tags: editor, IsolationFlow
Requires at least: 5.2.1
Tested up to: 5.5.1
Stable tag: 1.0
Requires PHP: 7.3.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Isolation Flow Managerプラグインは、サポートセンタ等がお客様の問合せの切り分けフローを管理・実行・分析ができます。
Isolation Flow Manager plugin allows you to manage customer's inquiry and isolation flow and execution and analyzation.


== Description ==

One of the possible use cases is as follows.
1. The customer inquires about the failure of a certain product by telephone,
 and the support center receives it.
2. The center asks what kind of trouble is in which product.
3. Select the isolation flow according to the trouble.
4. Ask the customer according to the isolation flow and select the answer.
5. Trouble shooting. If the problem is not resolved,
 you can escalate to the technical team by email from plugin.
6. It is possible to search for unsolved troubles at a later date
 and prevent omissions.
7. You can also investigate the number of isolation flows used,
 the number of inquires resolved by operators, etc.
 It is possible to review the isolation flow
 and analyze at Graph and Csv
 and consider improving the operator's response so that it will be more efficient.

日本語
Isolation Flow Managerプラグインは、サポートセンタがお客様の問合せの切り分けフローを
管理ができます。
考えられる利用フローの１つは以下です。
1.お客様がある商品の故障を電話で問合せ、サポートセンタが受ける。
2.センタはどの製品のどのようなトラブルかを聞き出す。
3.トラブルに応じた切り分けフローを選択する。
4.フローに従ってお客様に質問し、回答を選択していく。
5.トラブル解決。トラブルが解決しない場合はメールで技術チームにエスカレすることも可能。
6.後日、未解決のトラブルを検索し、対応漏れを防ぐことが可能。
7.また、使用された切り分けフローの数、より多くの切り分けをしたオペレータなどを調査でき、
より効率の良くなるように切り分けフローを見直したり、オペレータの対応改善を検討できる。


Ussage:
1. Go to "Settings" in "IsolationFlow" on the management menu and change the settings you want to change.
 (Administrators only)
2. Next, create a isolation flows. Please go to "EditFlows" of "IsolationFlow" on the management screen and create it.
 (Administrators, isfl_operator only)
3. You can actually use the isolation flow in "ExecFlows" of "IsolationFlow" on the management screen.
 (Administrators, isfl_operator, isfl_user only)
4. If you go to "History" of "IsolationFlow" on the management screen, you can see the past execution results
  You can check the statistics in the graph.
 (Administrators, isfl_operator, isfl_user only)

How to change the display message:
1. Copy one of file under "wp-content/plugins/isolation-flow-manager/langs" directory 
   to wordpress theme directory "wp-content/themes/'theme name'/isolation-flow-manager/langs".
2. Change the name of the copied file to the locale you want to use. Add "custome_" before locale string.
   Example: "meassages_custom_en.php" for English.
3. Open the file and change the text to the right of "=>" for the desired item. Any changes will be reflected immediately.
   #Please note that some HTML have a fixed display width.

How to change the validation message:
1. Copy one of file under "wp-content/plugins/isolation-flow-manager/Valitron/lang" directory 
   to wordpress theme directory "wp-content/themes/'theme name'/isolation-flow-manager/Valitron/lang".
2. Change the name of the copied file to the locale you want to use. Add "custom_" before locale string.
   Example: "custom_en.php" for English.
3. Open the file and change the text to the right of "=>" for the desired item. Any changes will be reflected immediately.


●日本語
使い方:
1.管理画面の「切り分けフロー」の「設定」へ行き、変更したい設定を変更してください。
 (Administratorsのユーザが利用可能)
2.次に、切り分けフローを作成します。管理画面の「切り分けフロー」の「フロー編集」へ行き作成してください。
 (Administrators, isfl_operatorのユーザが利用可能)
3.管理画面の「切り分けフロー」の「フロー実行」で実際に切り分けフローを利用できます。
 切り分けフローのユーザ権限を持っているユーザのみ、「フロー実行」のメニューが表示されます。
 (Administrators, isfl_operatorのユーザが利用可能)
4.管理画面の「切り分けフロー」の「切り分け実行結果履歴」に行くと、過去の実行結果や
 統計をグラフで確認できます。
  (Administrators, isfl_operator, isfl_userのユーザが利用可能)

表示メッセージを変更する方法:
1."/wp-content/plugins/isolation-flow-manager/langs"の配下のファイルの1つを
使用しているテーマフォルダ"/wp-content/themes/テーマ名/isolation-flow-manager/langs"の下にコピーする。
2.コピーしたファイルの名前を使用したいロケールに変更する。ロケールの前に"custom_"を名前に付ける必要がある。
例：英語の場合は"meassages_custom_en.php"。
3.ファイルを開き、目的の項目の"=>"の右側のテキストを変更する。変更するとすぐに反映されます。
※HTMLで表示幅が決まっているものもあるので注意すること。

妥当性チェックのメッセージを変更する方法:
1."/wp-content/plugins/isolation-flow-manager/Valitron/lang"の配下のファイルの1つを
使用しているテーマフォルダ"/wp-content/themes/テーマ名/isolation-flow-manager/Valitron/lang"の下にコピーする。
2.コピーしたファイルの名前を使用したいロケールに変更する。ロケールの前に"custom_"を名前に付ける必要がある。
例：英語の場合は"custom_en.php"。
3.ファイルを開き、目的の項目の"=>"の右側のテキストを変更する。変更するとすぐに反映されます。



== Installation ==

Installation procedure:

1. Deactivate plugin if you have the previous version installed.
2. Extract "isolation-flow-manager.zip" archive content to the "/wp-content/plugins/isolation-flow-manager" directory.
3. Activate "Isolation Flow Manager" plugin via 'Plugins' menu in WordPress admin menu. 


●日本語
インストール方法:
1.古いバージョンがインストールされていれば無効にしてください。
2.isolation-flow-manager.zipを解凍し、"/wp-content/plugins/isolation-flow-manager"ディレクトリに入れてください。
3.Wordpress上の管理画面から、有効化してください。


== Frequently Asked Questions ==

Nothing..


== Screenshots ==

1. Settings screen
2. EditFlows screen
3. EditFlows-Property screen
4. ExecFlows screen
5. History screen
6. History-Statistics-ByTime screen
7. History-Statistics-ByFlows screen
8. History-Statistics-ByUser screen
9. 日本語: EditFlows screen
10. 日本語: EditFlows-Property screen
11. 日本語: ExecFlows screen

日本語にも対応しています。


= Translations =

Japanese
English
if you make messages file, any language is applied.



== Changelog =

= [1.0] 2020.10.14 =

* New: Create Plugin



== Arbitrary section ==

use Handsontable 6.2.2. Library(MIT License).
use handlebars.js Library.
use just-handlebars-helpers.js Library.


== Upgrade Notice ==
Nothing..


