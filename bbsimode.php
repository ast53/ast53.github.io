<?php

/*

くずはすくりぷとPHP ver0.0.7alpha (13:04 2003/02/18)
iモードモジュール

* Todo

J-PHONE、AU、ドットi端末での動作確認

WAP2.0のXHTML形式に準拠したつもりですが、
iモード以外の実機では未確認です。

*/

if(!defined("INCLUDED_FROM_BBS")) {
  header ("Location: ../bbs.php?m=i");
  exit();
}


/*
 * モジュール固有設定
 *
 * $CONFに追加・上書きされます。
 */
$GLOBALS['CONF_IMODE'] = array(

  # 掲示板の名前
  'BBSTITLE' => 'i@PHP',

  # １画面に表示するメッセージの表示数
  'MSGDISP' => 10,

  # ページサイズ制限
  # １ページの容量が指定したバイト数(目安)を超えないように表示メッセージ数が調整されます
  'CTRL_MAXPAGESIZE' => 4000,

  # メッセージサイズ制限１
  # メッセージが指定したバイト数を超えた場合は一部省略します（行単位）
  'CTRL_MAXMSGSIZE' => 800,

  # メッセージサイズ制限２
  # メッセージが指定したバイト数を超えた場合は完全に表示しません
  'CTRL_LIMITMSGSIZE' => 3000,

  # メッセージ行数制限
  # メッセージが指定行数を超えた場合は一部省略します
  'CTRL_MAXMSGLINE' => 10,

  # 背景色
  'C_BACKGROUND' => '004040',

  # テキスト色
  'C_TEXT' => 'ffffff',

  # リンク色
  'C_A_COLOR' => 'cccccc',

  # 引用メッセージの色
  # （色を変えない場合は空にしてください）
  'C_QMSG' => 'cccccc',

  # エラーメッセージの色
  'C_ERROR' => 'ffffff',

  # フォロー投稿画面ボタンに表示する文字
  'TXTFOLLOW' => '■',

  # スレッド表示ボタンに表示する文字
  'TXTTHREAD' => '◆',

  # メッセージテンプレート
  'TMPL_MSG' => "{val TITLE}{val BTN}{val WDATE} {val USER}\r{val MSG}\r\r",

);





/**
 * iモードモジュール
 *
 *
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Imode extends Bbs {

  /**
   * コンストラクタ
   *
   */
  function Imode() {
    $GLOBALS['CONF'] = array_merge ($GLOBALS['CONF'], $GLOBALS['CONF_IMODE']);
    $this->Bbs();
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
    if (@$this->f['im'] == 'w' and trim(@$this->f['v'])) {

      # 環境変数取得
      $this->setuserenv();

      # 端末制限
      if (@$this->c['RESTRICT_MOBILEIP']) {
        $uatype = Func::get_uatype(TRUE);
        if ($uatype != 'i') {
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
    else if (@$this->f['write'] and @$this->f['im'] == 'p') {
      $this->prtform();
    }
    # フォロー画面表示
    else if (@$this->f['im'] == 'f') {
      $this->prtfollow();
    }
    # 投稿検索
    else if (@$this->f['im'] == 't') {
      $this->prtsearchlist('t');
    }
    # １件メッセージ表示
    else if (@$this->f['im'] == 'o') {
      $this->prtmsgpage();
    }
    # ヘルプ画面表示
    else if (@$this->f['im'] == 'h') {
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
    print "エラー:$err_message";
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
    $htmlstr = "<html><head><title>$title</title></head>"
     . "<body bgcolor=\"#{$this->c['C_BACKGROUND']}\" text=\"#{$this->c['C_TEXT']}\" link=\"#{$this->c['C_A_COLOR']}\">";
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
      $htmlstr .= '('.$duration.'秒)';
    }

    $htmlstr .= "</body></html>";
    return $htmlstr;
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
    $mbrcount = '';
    if ($this->c['CNTFILENAME']) {
      $mbrcount = $this->mbrcount()."名";
    }

    # HTMLヘッダ部分出力
    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);

    # フォーム部分
    print <<<__IMODE__
<a name="t"><a href="#b" accesskey="2">▼</a></a>
<form method="post" action="{$this->c['CGIURL']}">
<input type="hidden" name="m" value="i" />
<input type="hidden" name="im" value="p" />
<input type="hidden" name="p" value="{$this->s['TOPPOSTID']}" />
<input type="submit" name="read" value="R" accesskey="7" />
__IMODE__;
    if ($this->c['SHOW_READNEWBTN']) {
      print '<input type="submit" name="readnew" value="未" accesskey="0" />';
    }
    if ($this->c['BBSMODE_ADMINONLY'] == 0) {
      print '<input type="submit" name="write" value="投" accesskey="9" />';
    }
    print <<<__IMODE__
 <input type="text" size="2" name="d" value="{$this->s['MSGDISP']}" />件
 $mbrcount
__IMODE__;

    if (@$this->f['u']) {
      print '<input type="hidden" name="u" value="'.$this->f['u'].'" />';
    }
    if (@$this->f['i']) {
      print '<input type="hidden" name="i" value="'.$this->f['i'].'" />';
    }
    print "</form><pre>";

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
    print @$msgmore;

    # ナビゲートボタン
    if (($eindex > 0 and $eindex < $lastindex) or $this->c['BBSMODE_ADMINONLY'] != 0) {

      $formc = @$this->f['c'];
      $formd = @$this->f['d'];
      $formu = @$this->f['u'];
      $formi = @$this->f['i'];

      print <<<__IMODE__
</pre><form method="post" action="{$this->c['CGIURL']}">
<input type="hidden" name="m" value="i" />
<input type="hidden" name="im" value="p" />
<input type="hidden" name="p" value="{$this->s['TOPPOSTID']}" />
<input type="hidden" name="d" value="{$this->s['MSGDISP']}" />
<input type="hidden" name="b" value="$eindex" />
<input type="submit" name="reload" value="＞" accesskey="5" />
__IMODE__;

      # 管理者投稿
      if ($this->c['BBSMODE_ADMINONLY'] != 0) {
        print '<br /><br /><input size="2" type="text" name="u" value="'.@$this->f['u'].'" />';
        print '<input type="submit" name="write" value="管理" />';
      }
      else if (@$this->f['u']) {
        print '<input type="hidden" name="u" value="'.$this->f['u'].'" />';
      }
      if (@$this->f['i']) {
        print '<input type="hidden" name="i" value="'.$this->f['i'].'" />';
      }
      print "</form>";
    }

    print ' <a href="#t" accesskey="2">▲</a><a name="b">&nbsp;</a><a href="'
      . $this->c['CGIURL'] . '?m=i&amp;im=h">？</a>';
    print $this->prthtmlfoot ();

  }




  /**
   * 表示範囲のメッセージとパラメータの取得
   *
   * @access  public
   * @return  Array   $logdatadisp  ログ行配列
   * @return  Integer $bindex       開始index
   * @return  Integer $eindex       終端index
   * @return  Integer $lastindex    全ログの終端index
   */
  function getdispmessage() {

    list ($logdatadisp, $bindex, $eindex, $lastindex) = parent::getdispmessage();

    # 表示件数省略処理
    # ページのバイト数を考慮して表示件数を調整する

    # 未読リロードの場合、記事を遡って省略を行う
    if (@$this->f['readnew'] or ($this->s['MSGDISP'] == '0' and $bindex == 0)) {
      $testbuffer = '';
      $msgrange = 0;
      for ($i = count($logdatadisp) - 1; $i >= 0; $i--) {
        $testbuffer .= $this->prtmessage($this->getmessage($logdatadisp[$i], 0, 0));
        if (strlen($testbuffer) > $this->c['CTRL_MAXPAGESIZE'] - 1000) {
          $message = $this->getmessage($logdatadisp[$i + 1]);
          $this->s['TOPPOSTID'] = $message['POSTID'];
          $bindex = $eindex - $msgrange + 1;
          break;
        }
        $msgrange++;
      }
      array_splice ($logdatadisp, 0, count($logdatadisp) - $msgrange);
    }
    # 通常リロードの場合、古い方の記事を省略
    else {
      $testbuffer = '';
      $msgrange = 0;
      for ($i = 0; $i < count($logdatadisp); $i++) {
        $testbuffer .= $this->prtmessage($this->getmessage($logdatadisp[$i], 0, 0));
        if (strlen($testbuffer) > $this->c['CTRL_MAXPAGESIZE'] - 1000) {
          $eindex = $bindex + $i - 1;
          break;
        }
        $msgrange++;
      }
      array_splice ($logdatadisp, $msgrange);
    }

    if ($eindex < $bindex and $bindex > 1) {
      $eindex = $bindex;
    }
    return array($logdatadisp, $bindex, $eindex, $lastindex);

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

    if ($mode == 0 or ($mode == 1 and $this->c['OLDLOGBTN'])) {

      # フォロー投稿ボタン
      $message['BTNFOLLOW'] = '';
      if ($this->c['BBSMODE_ADMINONLY'] != 1) {
        $message['BTNFOLLOW'] = "<a href=\"{$this->c['CGIURL']}"
          ."?m=i&amp;im=f&amp;s={$message['POSTID']}&amp;p={$this->s['TOPPOSTID']}\">{$this->c['TXTFOLLOW']}</a>";
      }

      # 投稿者検索ボタン
      $message['BTNAUTHOR'] = "";
      if ($message['USER'] != $this->c['ANONY_NAME'] and $this->c['BBSMODE_ADMINONLY'] != 1) {
        $message['BTNAUTHOR'] = "<a href=\"{$this->c['CGIURL']}"
          ."?m=i&amp;im=s&amp;s=". urlencode(preg_replace("/<[^>]*>/", '', $message['USER'])) ."\">{$this->c['TXTAUTHOR']}</a>";
      }

      # スレッド表示ボタン
      if (!$message['THREAD']) {
        $message['THREAD'] = $message['POSTID'];
      }
      $message['BTNTHREAD'] = '';
      if ($this->c['BBSMODE_ADMINONLY'] != 1) {
        $message['BTNTHREAD'] = "<a href=\"{$this->c['CGIURL']}?m=i&amp;im=t&amp;s={$message['THREAD']}\">{$this->c['TXTTHREAD']}</a>";
      }

      # ボタンの統合
      $message['BTN'] = "{$message['BTNFOLLOW']} {$message['BTNTHREAD']}";
    }

    # メールアドレス
    if (@$message['MAIL']) {
      $message['USER'] = "<a href=\"mailto:{$message['MAIL']}\">{$message['USER']}</a>";
    }

    # 匿名の投稿者名は非表示
    if ($message['USER'] == $this->c['ANONY_NAME']) {
      $message['USER'] = '';
    }

    # 空白の題名は非表示
    if ($message['TITLE'] == " ") {
      $message['TITLE'] = '';
    }

    # 匿名宛フォロー記事の題名は非表示
    if ($message['TITLE'] == "＞{$this->c['ANONY_NAME']}{$this->c['FSUBJ']}") {
      $message['TITLE'] = '';
    }

    # 二つ前のレスを消去
    #$message['MSG'] = preg_replace("/(^|\r)&gt; &gt; [^\r]*\r/", "", $message['MSG']);

    # 引用色変更
    $message['MSG'] = preg_replace("/(^|\r)(&gt;[^\r]*)/", "$1<font color=\"#{$this->c['C_QMSG']}\">$2</font>", $message['MSG']);
    $message['MSG'] = str_replace("</font>\r<font color=\"#{$this->c['C_QMSG']}\">", "\r", $message['MSG']);

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
          $message['MSG'] .= "\r<a href=\"{$this->c['CGIURL']}?m=i&amp;im=o&amp;s={$message['POSTID']}\">[".$abbcount."行省略]</a>";
        }
        $message['MSG'] .= "\r" . implode("\r", $messagelines);
      }
    }

    # 画像BBSの画像を非表示
    $message['MSG'] = Func::conv_imgtag($message['MSG']);

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

    # 管理人認証
    if (($this->c['BBSMODE_ADMINONLY'] == 1 or ($this->c['BBSMODE_ADMINONLY'] == 2 and !$dfid))
      and crypt(@$this->f['u'], $this->c['ADMINPOST']) != $this->c['ADMINPOST']) {
      $this->prterror('パスワードが違います。');
    }

    # プロテクトコード生成
    $pcode = Func::pcode(0, FALSE);

    $formu = @$this->f['u'];
    $formi = @$this->f['i'];

    # HTMLヘッダ部分出力
    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);
    print "<a href=\"{$this->c['CGIURL']}?m=i\" accesskey=\"1\">戻</a>";

    print <<<__IMODE__
<form method="post" action="{$this->c['CGIURL']}">
<input type="hidden" name="m" value="i" />
<input type="hidden" name="im" value="w" />
<input type="hidden" name="p" value="{$this->s['TOPPOSTID']}" />
<input type="hidden" name="d" value="{$this->s['MSGDISP']}" />
<input type="hidden" name="pc" value="$pcode" />
<input type="submit" name="post" value="投稿" accesskey="9" />
<input type="reset" name="reset" value="消す" accesskey="3" /><br />
<textarea rows="4" cols="14" name="v">$dmsg</textarea><br />
名<input size="20" type="text" name="u" size="8" value="$formu" /><br />
＠<input size="30" type="text" name="i" size="10" value="$formi" /><br />
題<input size="30" type="text" name="t" size="9" value="$dtitle" />
__IMODE__;

    if ($dfid) {
      print "<input type=\"hidden\" name=\"f\" value=\"$dfid\" />";
    }
    if ($dsid) {
      print "<input type=\"hidden\" name=\"s\" value=\"$dsid\" />";
    }
    print "</form>";

    print $this->prthtmlfoot ();

  }






  /**
   * フォロー画面表示
   *
   * @access  public
   * @param   Boolean $retry  リトライフラグ
   */
  function prtfollow($retry = FALSE) {

    if (!@$this->f['s']) {
      $this->prterror ( 'パラメータがありません。' );
    }

    $result = $this->searchmessage('POSTID', @$this->f['s']);

    if (!$result) {
      $this->prterror ( '指定されたメッセージが見つかりません。' );
    }

    $message = $this->getmessage($result[0]);

    if (!$retry) {
      $formmsg = $message['MSG'];
      $formmsg = preg_replace ("/&gt; &gt;[^\r]+\r/", "", $formmsg);
      $formmsg = preg_replace ("/<a href=\"m=f\S+\"[^>]*>[^<]+<\/a>/i", "", $formmsg);
      $formmsg = preg_replace ("/<a href=\"[^>]+>([^<]+)<\/a>/i", "$1", $formmsg);
      $formmsg = preg_replace ("/\r/", "\r&gt; ", $formmsg);
      $formmsg = "&gt; $formmsg\r";
      $formmsg = preg_replace ("/\r&gt;\s+\r/", "\r", $formmsg);
      $formmsg = preg_replace ("/\r&gt;\s+\r$/", "\r", $formmsg);
    } else {
      $formmsg = @$this->f['v'];
      $formmsg = preg_replace ("/<a href=\"m=f\S+\"[^>]*>[^<]+<\/a>/i", "", $formmsg);
    }

    $this->prtform ( "＞".preg_replace("/<[^>]*>/", '', $message['USER'])."{$this->c['FSUBJ']}", "$formmsg\r", '', $message['POSTID'], @$this->f['s']);

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
    print '<a href="#b">▼</a><a name="t">&nbsp;</a><a href="'.$this->c['CGIURL'].'?m=i" accesskey="1">戻</a><pre>';

    print $this->prtmessage($message, 0, 0, FALSE);

    print '</pre><a href="#t">▲</a><a name="b">&nbsp;</a>';
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
      $mode = @$this->f['im'];
    }

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);
    print '<a href="#b">▼</a><a name="t">&nbsp;</a><a href="'.$this->c['CGIURL'].'?m=i" accesskey="1">戻</a><pre>';

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
    # ページのバイト数を考慮して表示件数を調整する
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
      print "<br /><a href=\"?m=i&amp;im=t&amp;s=".$this->f['s']."&amp;b={$eindex}\">＞</a>";
    }

    print '</pre><a href="#t">▲</a><a name="b">&nbsp;</a>';

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
    print '<a href="'.$this->c['CGIURL'].'?m=i" accesskey="1">戻</a> <br />';
    print 'ｱｸｾｽｷｰ一覧<br /><br />[1] 戻る<br />[2] 画面上へ<br />[5] 次ページ<br />[7] リロード<br />[8] 画面下へ<br />[9] 投稿<br />';
    print $this->prthtmlfoot ();

  }











}








?>