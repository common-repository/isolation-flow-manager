HTML内の命名ルール
===





data-属性の命名ルール
---

|  属性         |  種類        |  ルール                | 例                        |
| ----          | ----         |  ----                  | ----                      |
| data-id="*"   |  ボタン      | 親.btn_**              | data-ie="ISFL_editor_prop.btn_add"  |
|               |  値          | 親.val.JSONの階層名    | data-id="ISFL_editor_prop.val.choices[1].image" |
| data-original |  元データ    | 変更前の値を保存する   | data-original="NG" |
|  data-*       |  JSONデータ  | JSONのプロパティ名     | data-flow_id="2"  |
|               |  JSON以外    | ハイフンつなぎ         | data-choice-index="2"  |


JS関数の命名ルール
===

プレフィックス
---
|  プレフィックス   |  意味             | 例                        |
| ----             | ----              | ----                      |
| request*         | サーバにアクセスする  | requestSaveResult()         | 
| update*          | クラス内のデータを更新（サーバアクセスなし） | updateEntire() |
| onClick*         | ボタンやリンクなどのclickイベント処理 | onClickFlowOnCanvas() |
| transact*        | まとまりのある処理（例：切り分けの終了時処理） | transactFlowEnd() |

単語の意味
---
|  単語            |  意味             | 例                        |
| ----             | ----              | ----                      |
| save             | サーバに保存（insert or update）する  | requestSaveResult()  | 
| entire          | フローデータのEntire部分 | updateEntire() |
| flowData          | フローグループデータ | getFlowDataById() |
| preview          | 切り分けなどをシミュレーションすること | onClickHidePropPreview() |

