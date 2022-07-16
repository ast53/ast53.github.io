<?php

/*

�����͂�����Ղ�PHP ver0.0.7alpha (13:04 2003/02/18)
HDML(EZweb)���W���[��

HDML�[���G�~�����[�^�[��SDK 3.3.1�œ���m�F���Ă��܂��B
http://developer.openwave.com/ja/download/331doc.html

* Todo

* Memo

XHTML to HDML�g�����X���[�V�����T�[�r�X
http://devgatej.jpn.phone.com/x2hdml.html


*/

if(!defined("INCLUDED_FROM_BBS")) {
  header ("Location: ../bbs.php?m=h");
  exit();
}

// i���[�h���W���[���̃C���|�[�g
require_once(PHP_IMODE);

/*
 * ���W���[���ŗL�ݒ�
 *
 * $CONF�ɒǉ��E�㏑������܂��B
 */
$GLOBALS['CONF_HDML'] = array(

  # �f���̖��O
  'BBSTITLE' => 'ez@PHP',

  # �P��ʂɕ\�����郁�b�Z�[�W�̕\����
  'MSGDISP' => 10,

  # �y�[�W�T�C�Y����
  # �P�y�[�W�̗e�ʂ��w�肵���o�C�g��(�ڈ�)�𒴂��Ȃ��悤�ɕ\�����b�Z�[�W������������܂�
  'CTRL_MAXPAGESIZE' => 2200,

  # ���b�Z�[�W�T�C�Y�����P
  # ���b�Z�[�W���w�肵���o�C�g���𒴂����ꍇ�͈ꕔ�ȗ����܂��i�s�P�ʁj
  'CTRL_MAXMSGSIZE' => 500,

  # ���b�Z�[�W�T�C�Y�����Q
  # ���b�Z�[�W���w�肵���o�C�g���𒴂����ꍇ�͊��S�ɕ\�����܂���
  'CTRL_LIMITMSGSIZE' => 1200,

  # ���b�Z�[�W�s������
  # ���b�Z�[�W���w��s���𒴂����ꍇ�͈ꕔ�ȗ����܂�
  'CTRL_MAXMSGLINE' => 10,

  # ���b�Z�[�W�e���v���[�g
  'TMPL_MSG' => '<br>{val TITLE}{val BTN}{val WDATE} {val USER}<br>{val MSG}<br>',

);


/**
 * HDML(EZweb)���W���[��
 *
 *
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Hdml extends Imode {


  /**
   * �R���X�g���N�^
   *
   */
  function Hdml() {
    $this->Imode();
    $GLOBALS['CONF'] = array_merge ($GLOBALS['CONF'], $GLOBALS['CONF_HDML']);
  }





  /**
   * ���C������
   */
  function main() {

    # ���s���ԑ���J�n
    $this->setstarttime();

    # �t�H�[���擾�O����
    $this->procForm();

    # �l�p�ݒ蔽�f
    $this->refcustom();
    $this->setusersession();

    # �������ݏ���
    if (@$this->f['hm'] == 'w' and trim(@$this->f['v'])) {

      # ���ϐ��擾
      $this->setuserenv();

      # �[������
      if (@$this->c['RESTRICT_MOBILEIP']) {
        $uatype = Func::get_uatype(TRUE);
        if ($uatype != 'h') {
          $this->prterror ('�g�ђ[���ȊO��IP�A�h���X����̓��e�͋֎~����Ă��܂��B');
        }
      }

      # �p�����[�^�`�F�b�N
      $posterr = $this->chkmessage(FALSE);

      # �������ݏ���
      if (!$posterr) {
        $posterr = $this->putmessage($this->getformmessage());
      }

      # �Q�d�������݃G���[�Ȃ�
      if ($posterr == 1) {
        $this->prtmain();
      }
      # �v���e�N�g�R�[�h���Ԍo�߂̂��ߍĕ\��
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
    # ���e�t�H�[���\��
    else if (@$this->f['write'] and @$this->f['hm'] == 'p') {
      $this->prtform();
    }
    # �t�H���[��ʕ\��
    else if (@$this->f['hm'] == 'f') {
      $this->prtfollow();
    }
    # ���e����
    else if (@$this->f['hm'] == 't') {
      $this->prtsearchlist('t');
    }
    # �P�����b�Z�[�W�\��
    else if (@$this->f['hm'] == 'o') {
      $this->prtmsgpage();
    }
    # �w���v��ʕ\��
    else if (@$this->f['hm'] == 'h') {
      $this->prthelp();
    }
    # �f���\��
    else {
      $this->prtmain();
    }



  }





  /**
   * �G���[�\��
   *
   * @access  public
   * @param   String  $err_message  �G���[���b�Z�[�W
   */
  function prterror($err_message) {
    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);
    print <<<__HDML__
<display name="error" title="{$this->c['BBSTITLE']}">
<action type="soft1" label="�߂�" task="prev">
<action type="accept" label="į��" task="go" dest="?m=h{$this->s['TV']}">
�G���[:{$err_message}
</display>
__HDML__;
    print $this->prthtmlfoot ();
    $this->destroy();
    exit();
  }





  /**
   * HTML�w�b�_�����\��
   *
   * @access  public
   * @param   String  $title        HTML�^�C�g��
   * @param   String  $customhead   head�^�O���̃J�X�^���w�b�_
   * @return  String  HTML�f�[�^
   */
  function prthtmlhead($title = "", $customhead = "") {
    $htmlstr = '<hdml version="3.0" markable="true" public="true" ttl="0">';
    return $htmlstr;
  }





  /**
   * HTML�t�b�^�����\��
   *
   * @access  public
   * @return  String  HTML�f�[�^
   */
  function prthtmlfoot() {
    $htmlstr = '';

    if (@$this->c['SHOW_PRCTIME'] and @$this->s['START_TIME']) {
      $duration = Func::microtime_diff($this->s['START_TIME'], microtime());
      $duration = sprintf("%0.3f", $duration);
      #$htmlstr .= '('.$duration.'�b)';
    }

    $htmlstr .= "</hdml>";
    return $htmlstr;
  }





  /**
   * HTTP�w�b�_�[�ݒ�
   */
  function sethttpheader() {
    header('Content-Type: text/x-hdml; charset=Shift_JIS');
  }





  /**
   * �Z�b�V�����ŗL���ݒ�
   */
  function setusersession() {
    parent::setusersession();
    $this->s['TV'] = "&tv=" . base_convert(CURRENT_TIME, 10, 32);
  }





  /**
   * �f���̕\��
   *
   * @access  public
   */
  function prtmain() {

    # �\�����b�Z�[�W�擾
    list ($logdatadisp, $bindex, $eindex, $lastindex) = $this->getdispmessage();

    # �J�E���^
    $counter = '';
    if ($this->c['SHOW_COUNTER']) {
      $counter = $this->counter();
    }
    $mbrcount = $this->mbrcount();

    # HTML�w�b�_�����o��
    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);

    $action_nextpage = '';

    # �i�r�Q�[�g�{�^��
    if ($eindex > 0 and $eindex < $lastindex) {

      $query_nextpage = "?m=h&hm=p&p={$this->s['TOPPOSTID']}&b={$eindex}&reload=true{$this->s['TV']}";
      if (@$this->f['u']) {
        $query_nextpage .= "&u=".urlencode($this->f['u']);
      }
      if (@$this->f['i']) {
        $query_nextpage .= "&i=".urlencode($this->f['i']);
      }

      $action_nextpage = '<action type="soft1" label="����" task="go" dest="'.$query_nextpage.'">';
    }


    # �t�H�[������
    print <<<__HDML__
<display name="bbs" title="{$this->c['BBSTITLE']}">
<action type="accept" label="�X�V" task="go" dest="?m=h{$this->s['TV']}">
$action_nextpage
<a accesskey="7" label="�X�V" task="go" dest="?m=h{$this->s['TV']}">R</a>
__HDML__;
    if ($this->c['SHOW_READNEWBTN']) {
      print "<a accesskey=\"0\" label=\"����\" task=\"go\""
      ." dest=\"?m=h&hm=p&p={$this->s['TOPPOSTID']}&readnew=true{$this->s['TV']}\">��</a>";
    }
    if (1) {
      print "<a accesskey=\"9\" label=\"���e\" task=\"go\""
      ." dest=\"?m=h&hm=p&write=true{$this->s['TV']}\">��</a>";
    }
    print "{$mbrcount}��<br>";

    # ���b�Z�[�W�\��
    foreach ($logdatadisp as $msgdata) {
      print $this->prtmessage($this->getmessage($msgdata), 0, 0);
    }

    # ���b�Z�[�W���
    if ($eindex > 0) {
      $msgmore = "{$bindex}�`{$eindex}��";
    }
    else {
      $msgmore = '���ǂ͂���܂���B';
    }
    if ($eindex >= $lastindex) {
      $msgmore .= '';
    }
    print "<br>$msgmore<br>";

    # �i�r�Q�[�g�{�^��
    if ($eindex > 0 and $eindex < $lastindex) {
      print '<a accesskey="5" label="����" task="go" dest="'.@$query_nextpage.'">��</a>';
    }

    print '<a label="����" task="go" dest="?m=h&hm=h">�H</a></display>';
    print $this->prthtmlfoot ();

  }





  /**
   * ���b�Z�[�W�P���o��
   *
   * ���b�Z�[�W��HTML�����b�Z�[�W�z������ɏo�͂��܂��B
   * �ߋ����O���W���[���ɑΉ����Ă��܂��B
   *
   * @access  public
   * @param   Array   $message    ���b�Z�[�W
   * @param   Integer $mode       0:�f���� / 1:�ߋ����O����(�{�^���\������) / 2:�ߋ����O����(�{�^���\���Ȃ�) / 3:�ߋ����O�t�@�C���o�͗p
   * @param   String  $tlog       ���O�t�@�C���w��
   * @param   Boolean $abbreviate �ȗ��������s�����ǂ���
   * @return  String  ���b�Z�[�W��HTML�f�[�^
   */
  function prtmessage($message, $mode = 0, $tlog = '', $abbreviate = TRUE) {

    if (count($message) < 10) {
      return;
    }

    if (strlen($message['MSG']) > $this->c['CTRL_LIMITMSGSIZE']) {
      return;
    }

    $message['WDATE'] = date("H:i:s", $message['NDATE']);

    # �Q�l�̏���
    $message['MSG'] = preg_replace("/<a href=[^>]+>�Q�l�F[^<]+<\/a>/i", "", $message['MSG'], 1);

    # �^�O�̏���
    $message['MSG'] = preg_replace("/<[^>]+>/", "", $message['MSG']);
    $message['USER'] = preg_replace("/<[^>]+>/", "", $message['USER']);

    if ($mode == 0 or ($mode == 1 and $this->c['OLDLOGBTN'])) {

      # �t�H���[���e�{�^��
      $message['BTNFOLLOW'] = '';
      if ($this->c['BBSMODE_ADMINONLY'] != 1) {
        $message['BTNFOLLOW'] = "<a label=\"{$this->c['TXTFOLLOW']}\" task=\"go\" dest=\""
          ."?m=h&hm=f&s={$message['POSTID']}&p={$this->s['TOPPOSTID']}\">{$this->c['TXTFOLLOW']}</a>";
      }

      # ���e�Ҍ����{�^��
      $message['BTNAUTHOR'] = "";
      if ($message['USER'] != $this->c['ANONY_NAME'] and $this->c['BBSMODE_ADMINONLY'] != 1) {
        $message['BTNAUTHOR'] = "<a label=\"{$this->c['TXTAUTHOR']}\" task=\"go\" dest=\""
          ."?m=h&hm=s&s=". urlencode(preg_replace("/<[^>]*>/", '', $message['USER'])) ."\">{$this->c['TXTAUTHOR']}</a>";
      }

      # �X���b�h�\���{�^��
      if (!$message['THREAD']) {
        $message['THREAD'] = $message['POSTID'];
      }
      $message['BTNTHREAD'] = '';
      if ($this->c['BBSMODE_ADMINONLY'] != 1) {
        $message['BTNTHREAD'] = "<a label=\"{$this->c['TXTTHREAD']}\" task=\"go\" dest=\"?m=h&hm=t&s={$message['THREAD']}\">{$this->c['TXTTHREAD']}</a>";
      }

      # �{�^���̓���
      $message['BTN'] = "{$message['BTNFOLLOW']} {$message['BTNTHREAD']}";
    }

    # ���[���A�h���X
    if (@$message['MAIL']) {
      $message['USER'] = "<a label=\"".'Ұ�'."\" task=\"go\" dest=\"mailto:{$message['MAIL']}\">{$message['USER']}</a>";
    }

    # �����̓��e�Җ��͔�\��
    if ($message['USER'] == $this->c['ANONY_NAME']) {
      $message['USER'] = '';
    }

    # �������t�H���[�L���̑薼�͔�\��
    if ($message['TITLE'] == "��{$this->c['ANONY_NAME']}") {
      $message['TITLE'] = '';
    }

    # ��O�̃��X������
    #$message['MSG'] = preg_replace("/(^|\r)&gt; &gt; [^\r]*\r/", "", $message['MSG']);

    $message['MSG'] = trim ($message['MSG']);

    # �ȗ������i�s���ƃo�C�g���Ŕ���B�P�s�ڂƌ��̍s���c���Ē�������`���j
    if ($abbreviate) {
      $messagelines = explode("\r", $message['MSG']);
      if (count($messagelines) > $this->c['CTRL_MAXMSGLINE'] or strlen ($message['MSG']) > $this->c['CTRL_MAXMSGSIZE']) {
        $message['MSG'] = array_shift($messagelines);
        $testbuffer = $message['MSG'];
        $abbcount = 0;

        # �s������
        if (count($messagelines) > $this->c['CTRL_MAXMSGLINE']) {
          $abbcount = count($messagelines) - $this->c['CTRL_MAXMSGLINE'] + 1;
          array_splice($messagelines, 0, count($messagelines) - $this->c['CTRL_MAXMSGLINE'] + 1);
        }

        # �o�C�g������
        for ($i = count($messagelines)-1; $i > 0; $i--) {
          $testbuffer .= $messagelines[$i];
          if (strlen($testbuffer) > $this->c['CTRL_MAXMSGSIZE']) {
            $abbcount += $i + 1;
            array_splice($messagelines, 0, $i + 1);
            break;
          }
        }

        # �ȗ������N
        if ($abbcount > 0) {
          $message['MSG'] .= "\r<a label=\"����\" task=\"go\" dest=\"?m=h&hm=o&s={$message['POSTID']}\">[".$abbcount."�s�ȗ�]</a>";
        }
        $message['MSG'] .= "\r" . implode("\r", $messagelines);
      }
    }

    # �摜BBS�̉摜���\��
    $message['MSG'] = Func::conv_imgtag($message['MSG']);

    $message['MSG'] = str_replace("\r", '<br>', $message['MSG']);
    $this->hdml_escape($message);

    # ���b�Z�[�W�\�����e��`
    $prtmessage = $this->c['TMPL_MSG'];
    while (preg_match('/\{val (\w+)\}/', $prtmessage, $match)) {
      $prtmessage = str_replace($match[0], @$message[$match[1]], $prtmessage);
    }

    return $prtmessage;
  }





  /**
   * ���e�t�H�[���\��
   *
   * @access  public
   * @param   String  $dtitle     �薼�̃t�H�[�������l
   * @param   String  $dmsg       ���e�̃t�H�[�������l
   * @param   String  $dlink      �����N�̃t�H�[�������l
   * @return  String  �t�H�[����HTML�f�[�^
   */
  function prtform($dtitle = "", $dmsg = "", $dlink = "", $dfid = "", $dsid = "") {

    # �v���e�N�g�R�[�h����
    $pcode = Func::pcode(0, FALSE);

    $dtitle = urlencode(Func::html_decode($dtitle));
    $dmsg = urlencode(Func::html_decode($dmsg));
    $formu = urlencode(Func::html_decode(@$this->f['u']));
    $formi = urlencode(Func::html_decode(@$this->f['i']));

    # HTML�w�b�_�����o��
    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);
    print <<<__HDML__
<nodisplay name="cd_vars">
<action type="accept" task="gosub" vars="v={$dmsg}&u={$formu}&i={$formi}&t={$dtitle}" dest="#cd_form">
</nodisplay>

<display name="cd_form">
<action type="soft1" label="�߂�" task="prev">
<action type="accept" label="į��" task="go" dest="?m=h{$this->s['TV']}">
<a task="go" label="���e" dest="{$this->c['CGIURL']}" method="post"
postdata="m=h&hm=w&p={$this->s['TOPPOSTID']}&pc={$pcode}&f={$dfid}&s={$dsid}&v=$(v:esc)&u=$(u:esc)&i=$(i:esc)&t=$(t:esc)">���e</a>
<a task="go" label="reset" dest="#cd_vars">����</a>
<br>
<wrap>���e<a task="go" label="���e" dest="#cd_v">$(v:esc)</a>
<wrap>��<a task="go" label="���O" dest="#cd_u">$(u:esc)</a>
<wrap>��<a task="go" label="Ұ�" dest="#cd_i">$(i:esc)</a>
<wrap>��<a task="go" label="�薼" dest="#cd_t">$(t:esc)</a>
</display>

<entry key="v" format="*m" name="cd_v">
<action type="accept" task="go" dest="#cd_form">���e
</entry>

<entry key="u" format="*m" name="cd_u">
<action type="accept" task="go" dest="#cd_form">���O
</entry>

<entry key="i" format="*m" name="cd_i">
<action type="accept" task="go" dest="#cd_form">���[��
</entry>

<entry key="t" format="*m" name="cd_t">
<action type="accept" task="go" dest="#cd_form">�薼
</entry>
__HDML__;

    print $this->prthtmlfoot ();

  }





  /**
   * �P�����b�Z�[�W�\��
   *
   * @access  public
   */
  function prtmsgpage() {

    if (!@$this->f['s']) {
      $this->prterror ( '�p�����[�^������܂���B' );
    }

    $result = $this->searchmessage('POSTID', @$this->f['s']);

    if (!$result) {
      $this->prterror ( '�w�肳�ꂽ���b�Z�[�W��������܂���B' );
    }

    $message = $this->getmessage($result[0]);

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);
    print <<<__HDML__
<display name="message" title="{$this->c['BBSTITLE']}">
<action type="soft1" label="�߂�" task="prev">
<action type="accept" label="į��" task="go" dest="?m=h{$this->s['TV']}">
__HDML__;
    print $this->prtmessage($message, 0, 0, FALSE);
    print '</display>';
    print $this->prthtmlfoot ();

  }





  /**
   * ���e����
   *
   * @access  public
   * @param   $mode   �������[�h
   */
  function prtsearchlist($mode = "") {

    if (!@$this->f['s']) {
      $this->prterror ( '�p�����[�^������܂���B' );
    }

    if (!$mode) {
      $mode = @$this->f['hm'];
    }

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);
    print <<<__HDML__
<display name="search" title="{$this->c['BBSTITLE']}">
<action type="soft1" label="�߂�" task="prev">
<action type="accept" label="į��" task="go" dest="?m=h{$this->s['TV']}">
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

    # �y�[�W���O����
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
      print '<a accesskey="5" label="����" task="go" dest="?m=h&hm=t&s='.$this->f['s']."&b={$eindex}{$this->s['TV']}\">��</a>";
    }

    print '</display>';
    print $this->prthtmlfoot ();

  }





  /**
   * �w���v��ʕ\��
   *
   * @access  public
   */
  function prthelp() {

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);
    print <<<__HDML__
<display name="help" title="{$this->c['BBSTITLE']}">
<action type="soft1" label="�߂�" task="prev">
<action type="accept" label="į��" task="go" dest="?m=h{$this->s['TV']}">
�������ꗗ<br><br>[5] ���y�[�W<br>[7] �����[�h<br>[9] ���e<br>
</display>
__HDML__;
    print $this->prthtmlfoot ();

  }





  /**
   * HDML�p�ǉ��G�X�P�[�v����
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