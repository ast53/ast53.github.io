<?php

/*

くずはすくりぷとPHP ver0.0.7alpha (13:04 2003/02/18)
HDML(EZweb)モジュール

HDML端末エミュレーターのSDK 3.3.1で動作確認しています。
http://developer.openwave.com/ja/download/331doc.html

* Todo

* Memo

XHTML to HDMLトランスレーションサービス
http://devgatej.jpn.phone.com/x2hdml.html


*/

if(!defined("INCLUDED_FROM_BBS")) {
  header ("Location: ../bbs.php?m=h");
  exit();
}

// iモードモジュールのインポート
require_once(PHP_IMODE);

/*
 * モジュール固有設定
 *
 * $CONFに追加・上書きされます。
 */
$GLOBALS['CONF_HDML'] = array(

  # 掲示板の名前
  'BBSTITLE' => 'ez@PHP',

  # １画面に表示するメッセージの表示数
  'MSGDISP' => 10,

  # ページサイズ制限
  # １ページの容量が指定したバイト数(目安)を超えないように表示メッセージ数が調整されます
  'CTRL_MAXPAGESIZE' => 2200,

  # メッセージサイズ制限１
  # メッセージが指定したバイト数を超えた場合は一部省略します（行単位）
  'CTRL_MAXMSGSIZE' => 500,

  # メッセージサイズ制限２
  # メッセージが指定したバイト数を超えた場合は完全に表示しません
  'CTRL_LIMITMSGSIZE' => 1200,

  # メッセージ行数制限
  # メッセージが指定行数を超えた場合は一部省略します
  'CTRL_MAXMSGLINE' => 10,

  # メッセージテンプレート
  'TMPL_MSG' => '<br>{val TITLE}{val BTN}{val WDATE} {val USER}<br>{val MSG}<br>',

);


/**
 * HDML(EZweb)モジュール
 *
 *
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Hdml extends Imode {


  /**
   * コンストラクタ
   *
   */
  function Hdml() {
    $this->Imode();
    $GLOBALS['CONF'] = array_merge ($GLOBALS['CONF'], $GLOBALS['CONF_HDML']);
  }





  /**
   * メイン処理
   */
  function main() {

    # 実行時間測定開始
    $this->setstarttime();

    # フォーム取得前処理
    $this->procForm();

    # 個人用設定反映
    $this->refcustom();
    $this->setusersession();

    # 書き込み処理
    if (@$this->f['hm'] == 'w' and trim(@$this->f['v'])) {

      # 環境変数取得
      $this->setuserenv();

      # 端末制限
      if (@$this->c['RESTRICT_MOBILEIP']) {
        $uatype = Func::get_uatype(TRUE);
        if ($uatype != 'h') {
          $this->prterror ('携帯端末以外のIPアドレスからの投稿は禁止されています。');
        }
      }

      # パラメータチェック
      $posterr = $this->chkmessage(FALSE);

      # 書き込み処理
      if (!$posterr) {
        $posterr = $this->putmessage($this->getformmessage());
      }

      # ２重書き込みエラーなど
      if ($posterr == 1) {
        $this->prtmain();
      }
      # プロテクトコード時間経過のため再表示
      else if ($posterr == 2) {
        if (@$this->f['f']) {
          $this->prtfollow(TRUE);
        }
        else {
          $dtitle = @$this->f['t'];
          $dmsg = @$this->f['v'];
          $dlink = @$this->f['l'];
          $this->prtform($dtitle, $dmsg, $dlink);
        }
      }
      else {
        $this->prtmain();
      }
    }
    # 投稿フォーム表示
    else if (@$this->f['write'] and @$this->f['hm'] == 'p') {
      $this->prtform();
    }
    # フォロー画面表示
    else if (@$this->f['hm'] == 'f') {
      $this->prtfollow();
    }
    # 投稿検索
    else if (@$this->f['hm'] == 't') {
      $this->prtsearchlist('t');
    }
    # １件メッセージ表示
    else if (@$this->f['hm'] == 'o') {
      $this->prtmsgpage();
    }
    # ヘルプ画面表示
    else if (@$this->f['hm'] == 'h') {
      $this->prthelp();
    }
    # 掲示板表示
    else {
      $this->prtmain();
    }



  }





  /**
   * エラー表示
   *
   * @access  public
   * @param   String  $err_message  エラーメッセージ
   */
  function prterror($err_message) {
    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);
    print <<<__HDML__
<display name="error" title="{$this->c['BBSTITLE']}">
<action type="soft1" label="戻る" task="prev">
<action type="accept" label="ﾄｯﾌﾟ" task="go" dest="?m=h{$this->s['TV']}">
エラー:{$err_message}
</display>
__HDML__;
    print $this->prthtmlfoot ();
    $this->destroy();
    exit();
  }





  /**
   * HTMLヘッダ部分表示
   *
   * @access  public
   * @param   String  $title        HTMLタイトル
   * @param   String  $customhead   headタグ内のカスタムヘッダ
   * @return  String  HTMLデータ
   */
  function prthtmlhead($title = "", $customhead = "") {
    $htmlstr = '<hdml version="3.0" markable="true" public="true" ttl="0">';
    return $htmlstr;
  }





  /**
   * HTMLフッタ部分表示
   *
   * @access  public
   * @return  String  HTMLデータ
   */
  function prthtmlfoot() {
    $htmlstr = '';

    if (@$this->c['SHOW_PRCTIME'] and @$this->s['START_TIME']) {
      $duration = Func::microtime_diff($this->s['START_TIME'], microtime());
      $duration = sprintf("%0.3f", $duration);
      #$htmlstr .= '('.$duration.'秒)';
    }

    $htmlstr .= "</hdml>";
    return $htmlstr;
  }





  /**
   * HTTPヘッダー設定
   */
  function sethttpheader() {
    header('Content-Type: text/x-hdml; charset=Shift_JIS');
  }





  /**
   * セッション固有情報設定
   */
  function setusersession() {
    parent::setusersession();
    $this->s['TV'] = "&tv=" . base_convert(CURRENT_TIME, 10, 32);
  }





  /**
   * 掲示板の表示
   *
   * @access  public
   */
  function prtmain() {

    # 表示メッセージ取得
    list ($logdatadisp, $bindex, $eindex, $lastindex) = $this->getdispmessage();

    # カウンタ
    $counter = '';
    if ($this->c['SHOW_COUNTER']) {
      $counter = $this->counter();
    }
    $mbrcount = $this->mbrcount();

    # HTMLヘッダ部分出力
    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);

    $action_nextpage = '';

    # ナビゲートボタン
    if ($eindex > 0 and $eindex < $lastindex) {

      $query_nextpage = "?m=h&hm=p&p={$this->s['TOPPOSTID']}&b={$eindex}&reload=true{$this->s['TV']}";
      if (@$this->f['u']) {
        $query_nextpage .= "&u=".urlencode($this->f['u']);
      }
      if (@$this->f['i']) {
        $query_nextpage .= "&i=".urlencode($this->f['i']);
      }

      $action_nextpage = '<action type="soft1" label="次頁" task="go" dest="'.$query_nextpage.'">';
    }


    # フォーム部分
    print <<<__HDML__
<display name="bbs" title="{$this->c['BBSTITLE']}">
<action type="accept" label="更新" task="go" dest="?m=h{$this->s['TV']}">
$action_nextpage
<a accesskey="7" label="更新" task="go" dest="?m=h{$this->s['TV']}">R</a>
__HDML__;
    if ($this->c['SHOW_READNEWBTN']) {
      print "<a accesskey=\"0\" label=\"未読\" task=\"go\""
      ." dest=\"?m=h&hm=p&p={$this->s['TOPPOSTID']}&readnew=true{$this->s['TV']}\">未</a>";
    }
    if (1) {
      print "<a accesskey=\"9\" label=\"投稿\" task=\"go\""
      ." dest=\"?m=h&hm=p&write=true{$this->s['TV']}\">投</a>";
    }
    print "{$mbrcount}名<br>";

    # メッセージ表示
    foreach ($logdatadisp as $msgdata) {
      print $this->prtmessage($this->getmessage($msgdata), 0, 0);
    }

    # メッセージ情報
    if ($eindex > 0) {
      $msgmore = "{$bindex}～{$eindex}件";
    }
    else {
      $msgmore = '未読はありません。';
    }
    if ($eindex >= $lastindex) {
      $msgmore .= '';
    }
    print "<br>$msgmore<br>";

    # ナビゲートボタン
    if ($eindex > 0 and $eindex < $lastindex) {
      print '<a accesskey="5" label="次頁" task="go" dest="'.@$query_nextpage.'">＞</a>';
    }

    print '<a label="説明" task="go" dest="?m=h&hm=h">？</a></display>';
    print $this->prthtmlfoot ();

  }





  /**
   * メッセージ１件出力
   *
   * メッセージのHTMLをメッセージ配列を元に出力します。
   * 過去ログモジュールに対応しています。
   *
   * @access  public
   * @param   Array   $message    メッセージ
   * @param   Integer $mode       0:掲示板 / 1:過去ログ検索(ボタン表示あり) / 2:過去ログ検索(ボタン表示なし) / 3:過去ログファイル出力用
   * @param   String  $tlog       ログファイル指定
   * @param   Boolean $abbreviate 省略処理を行うかどうか
   * @return  String  メッセージのHTMLデータ
   */
  function prtmessage($message, $mode = 0, $tlog = '', $abbreviate = TRUE) {

    if (count($message) < 10) {
      return;
    }

    if (strlen($message['MSG']) > $this->c['CTRL_LIMITMSGSIZE']) {
      return;
    }

    $message['WDATE'] = date("H:i:s", $message['NDATE']);

    # 参考の消去
    $message['MSG'] = preg_replace("/<a href=[^>]+>参考：[^<]+<\/a>/i", "", $message['MSG'], 1);

    # タグの消去
    $message['MSG'] = preg_replace("/<[^>]+>/", "", $message['MSG']);
    $message['USER'] = preg_replace("/<[^>]+>/", "", $message['USER']);

    if ($mode == 0 or ($mode == 1 and $this->c['OLDLOGBTN'])) {

      # フォロー投稿ボタン
      $message['BTNFOLLOW'] = '';
      if ($this->c['BBSMODE_ADMINONLY'] != 1) {
        $message['BTNFOLLOW'] = "<a label=\"{$this->c['TXTFOLLOW']}\" task=\"go\" dest=\""
          ."?m=h&hm=f&s={$message['POSTID']}&p={$this->s['TOPPOSTID']}\">{$this->c['TXTFOLLOW']}</a>";
      }

      # 投稿者検索ボタン
      $message['BTNAUTHOR'] = "";
      if ($message['USER'] != $this->c['ANONY_NAME'] and $this->c['BBSMODE_ADMINONLY'] != 1) {
        $message['BTNAUTHOR'] = "<a label=\"{$this->c['TXTAUTHOR']}\" task=\"go\" dest=\""
          ."?m=h&hm=s&s=". urlencode(preg_replace("/<[^>]*>/", '', $message['USER'])) ."\">{$this->c['TXTAUTHOR']}</a>";
      }

      # スレッド表示ボタン
      if (!$message['THREAD']) {
        $message['THREAD'] = $message['POSTID'];
      }
      $message['BTNTHREAD'] = '';
      if ($this->c['BBSMODE_ADMINONLY'] != 1) {
        $message['BTNTHREAD'] = "<a label=\"{$this->c['TXTTHREAD']}\" task=\"go\" dest=\"?m=h&hm=t&s={$message['THREAD']}\">{$this->c['TXTTHREAD']}</a>";
      }

      # ボタンの統合
      $message['BTN'] = "{$message['BTNFOLLOW']} {$message['BTNTHREAD']}";
    }

    # メールアドレス
    if (@$message['MAIL']) {
      $message['USER'] = "<a label=\"".'ﾒｰﾙ'."\" task=\"go\" dest=\"mailto:{$message['MAIL']}\">{$message['USER']}</a>";
    }

    # 匿名の投稿者名は非表示
    if ($message['USER'] == $this->c['ANONY_NAME']) {
      $message['USER'] = '';
    }

    # 匿名宛フォロー記事の題名は非表示
    if ($message['TITLE'] == "＞{$this->c['ANONY_NAME']}") {
      $message['TITLE'] = '';
    }

    # 二つ前のレスを消去
    #$message['MSG'] = preg_replace("/(^|\r)&gt; &gt; [^\r]*\r/", "", $message['MSG']);

    $message['MSG'] = trim ($message['MSG']);

    # 省略処理（行数とバイト数で判定。１行目と後ろの行を残して中略する形式）
    if ($abbreviate) {
      $messagelines = explode("\r", $message['MSG']);
      if (count($messagelines) > $this->c['CTRL_MAXMSGLINE'] or strlen ($message['MSG']) > $this->c['CTRL_MAXMSGSIZE']) {
        $message['MSG'] = array_shift($messagelines);
        $testbuffer = $message['MSG'];
        $abbcount = 0;

        # 行数判定
        if (count($messagelines) > $this->c['CTRL_MAXMSGLINE']) {
          $abbcount = count($messagelines) - $this->c['CTRL_MAXMSGLINE'] + 1;
          array_splice($messagelines, 0, count($messagelines) - $this->c['CTRL_MAXMSGLINE'] + 1);
        }

        # バイト数判定
        for ($i = count($messagelines)-1; $i > 0; $i--) {
          $testbuffer .= $messagelines[$i];
          if (strlen($testbuffer) > $this->c['CTRL_MAXMSGSIZE']) {
            $abbcount += $i + 1;
            array_splice($messagelines, 0, $i + 1);
            break;
          }
        }

        # 省略リンク
        if ($abbcount > 0) {
          $message['MSG'] .= "\r<a label=\"見る\" task=\"go\" dest=\"?m=h&hm=o&s={$message['POSTID']}\">[".$abbcount."行省略]</a>";
        }
        $message['MSG'] .= "\r" . implode("\r", $messagelines);
      }
    }

    # 画像BBSの画像を非表示
    $message['MSG'] = Func::conv_imgtag($message['MSG']);

    $message['MSG'] = str_replace("\r", '<br>', $message['MSG']);
    $this->hdml_escape($message);

    # メッセージ表示内容定義
    $prtmessage = $this->c['TMPL_MSG'];
    while (preg_match('/\{val (\w+)\}/', $prtmessage, $match)) {
      $prtmessage = str_replace($match[0], @$message[$match[1]], $prtmessage);
    }

    return $prtmessage;
  }





  /**
   * 投稿フォーム表示
   *
   * @access  public
   * @param   String  $dtitle     題名のフォーム初期値
   * @param   String  $dmsg       内容のフォーム初期値
   * @param   String  $dlink      リンクのフォーム初期値
   * @return  String  フォームのHTMLデータ
   */
  function prtform($dtitle = "", $dmsg = "", $dlink = "", $dfid = "", $dsid = "") {

    # プロテクトコード生成
    $pcode = Func::pcode(0, FALSE);

    $dtitle = urlencode(Func::html_decode($dtitle));
    $dmsg = urlencode(Func::html_decode($dmsg));
    $formu = urlencode(Func::html_decode(@$this->f['u']));
    $formi = urlencode(Func::html_decode(@$this->f['i']));

    # HTMLヘッダ部分出力
    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);
    print <<<__HDML__
<nodisplay name="cd_vars">
<action type="accept" task="gosub" vars="v={$dmsg}&u={$formu}&i={$formi}&t={$dtitle}" dest="#cd_form">
</nodisplay>

<display name="cd_form">
<action type="soft1" label="戻る" task="prev">
<action type="accept" label="ﾄｯﾌﾟ" task="go" dest="?m=h{$this->s['TV']}">
<a task="go" label="投稿" dest="{$this->c['CGIURL']}" method="post"
postdata="m=h&hm=w&p={$this->s['TOPPOSTID']}&pc={$pcode}&f={$dfid}&s={$dsid}&v=$(v:esc)&u=$(u:esc)&i=$(i:esc)&t=$(t:esc)">投稿</a>
<a task="go" label="reset" dest="#cd_vars">消す</a>
<br>
<wrap>内容<a task="go" label="内容" dest="#cd_v">$(v:esc)</a>
<wrap>名<a task="go" label="名前" dest="#cd_u">$(u:esc)</a>
<wrap>＠<a task="go" label="ﾒｰﾙ" dest="#cd_i">$(i:esc)</a>
<wrap>題<a task="go" label="題名" dest="#cd_t">$(t:esc)</a>
</display>

<entry key="v" format="*m" name="cd_v">
<action type="accept" task="go" dest="#cd_form">内容
</entry>

<entry key="u" format="*m" name="cd_u">
<action type="accept" task="go" dest="#cd_form">名前
</entry>

<entry key="i" format="*m" name="cd_i">
<action type="accept" task="go" dest="#cd_form">メール
</entry>

<entry key="t" format="*m" name="cd_t">
<action type="accept" task="go" dest="#cd_form">題名
</entry>
__HDML__;

    print $this->prthtmlfoot ();

  }





  /**
   * １件メッセージ表示
   *
   * @access  public
   */
  function prtmsgpage() {

    if (!@$this->f['s']) {
      $this->prterror ( 'パラメータがありません。' );
    }

    $result = $this->searchmessage('POSTID', @$this->f['s']);

    if (!$result) {
      $this->prterror ( '指定されたメッセージが見つかりません。' );
    }

    $message = $this->getmessage($result[0]);

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);
    print <<<__HDML__
<display name="message" title="{$this->c['BBSTITLE']}">
<action type="soft1" label="戻る" task="prev">
<action type="accept" label="ﾄｯﾌﾟ" task="go" dest="?m=h{$this->s['TV']}">
__HDML__;
    print $this->prtmessage($message, 0, 0, FALSE);
    print '</display>';
    print $this->prthtmlfoot ();

  }





  /**
   * 投稿検索
   *
   * @access  public
   * @param   $mode   検索モード
   */
  function prtsearchlist($mode = "") {

    if (!@$this->f['s']) {
      $this->prterror ( 'パラメータがありません。' );
    }

    if (!$mode) {
      $mode = @$this->f['hm'];
    }

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);
    print <<<__HDML__
<display name="search" title="{$this->c['BBSTITLE']}">
<action type="soft1" label="戻る" task="prev">
<action type="accept" label="ﾄｯﾌﾟ" task="go" dest="?m=h{$this->s['TV']}">
__HDML__;

    $result = $this->msgsearchlist($mode);

    $bindex = @$this->f['b'];
    if (!$bindex) {
      $bindex = 0;
    }
    $eindex = count($result) - 1;

    if ($bindex > 0) {
      array_splice ($result, 0, $bindex);
    }

    # ページング処理
    $abbreviated = FALSE;
    {
      $testbuffer = '';
      $msgrange = 0;
      for ($i = 0; $i < count($result); $i++) {
        $testbuffer .= $this->prtmessage($result[$i], $mode);
        if (strlen($testbuffer) > $this->c['CTRL_MAXPAGESIZE'] - 1000) {
          $eindex = $bindex + $i;
          $abbreviated = TRUE;
          break;
        }
        $msgrange++;
      }
      array_splice ($result, $msgrange);
    }

    foreach ($result as $message) {
      print $this->prtmessage ($message, $mode, @$this->f['ff']);
    }
    $success = count($result);

    if ($abbreviated) {
      print '<a accesskey="5" label="次頁" task="go" dest="?m=h&hm=t&s='.$this->f['s']."&b={$eindex}{$this->s['TV']}\">＞</a>";
    }

    print '</display>';
    print $this->prthtmlfoot ();

  }





  /**
   * ヘルプ画面表示
   *
   * @access  public
   */
  function prthelp() {

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);
    print <<<__HDML__
<display name="help" title="{$this->c['BBSTITLE']}">
<action type="soft1" label="戻る" task="prev">
<action type="accept" label="ﾄｯﾌﾟ" task="go" dest="?m=h{$this->s['TV']}">
ｱｸｾｽｷｰ一覧<br><br>[5] 次ページ<br>[7] リロード<br>[9] 投稿<br>
</display>
__HDML__;
    print $this->prthtmlfoot ();

  }





  /**
   * HDML用追加エスケープ処理
   *
   */
  function hdml_escape(&$array) {
    $array_ref = &$array;
    foreach (array_keys($array_ref) as $key) {
      if (is_string($array_ref[$key])) {
        $array_ref[$key] = str_replace('$', '&dol;', $array_ref[$key]);
      }
    }
  }





}

?>