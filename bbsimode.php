<?php

/*

�����͂�����Ղ�PHP ver0.0.7alpha (13:04 2003/02/18)
i���[�h���W���[��

* Todo

J-PHONE�AAU�A�h�b�gi�[���ł̓���m�F

WAP2.0��XHTML�`���ɏ�����������ł����A
i���[�h�ȊO�̎��@�ł͖��m�F�ł��B

*/

if(!defined("INCLUDED_FROM_BBS")) {
  header ("Location: ../bbs.php?m=i");
  exit();
}


/*
 * ���W���[���ŗL�ݒ�
 *
 * $CONF�ɒǉ��E�㏑������܂��B
 */
$GLOBALS['CONF_IMODE'] = array(

  # �f���̖��O
  'BBSTITLE' => 'i@PHP',

  # �P��ʂɕ\�����郁�b�Z�[�W�̕\����
  'MSGDISP' => 10,

  # �y�[�W�T�C�Y����
  # �P�y�[�W�̗e�ʂ��w�肵���o�C�g��(�ڈ�)�𒴂��Ȃ��悤�ɕ\�����b�Z�[�W������������܂�
  'CTRL_MAXPAGESIZE' => 4000,

  # ���b�Z�[�W�T�C�Y�����P
  # ���b�Z�[�W���w�肵���o�C�g���𒴂����ꍇ�͈ꕔ�ȗ����܂��i�s�P�ʁj
  'CTRL_MAXMSGSIZE' => 800,

  # ���b�Z�[�W�T�C�Y�����Q
  # ���b�Z�[�W���w�肵���o�C�g���𒴂����ꍇ�͊��S�ɕ\�����܂���
  'CTRL_LIMITMSGSIZE' => 3000,

  # ���b�Z�[�W�s������
  # ���b�Z�[�W���w��s���𒴂����ꍇ�͈ꕔ�ȗ����܂�
  'CTRL_MAXMSGLINE' => 10,

  # �w�i�F
  'C_BACKGROUND' => '004040',

  # �e�L�X�g�F
  'C_TEXT' => 'ffffff',

  # �����N�F
  'C_A_COLOR' => 'cccccc',

  # ���p���b�Z�[�W�̐F
  # �i�F��ς��Ȃ��ꍇ�͋�ɂ��Ă��������j
  'C_QMSG' => 'cccccc',

  # �G���[���b�Z�[�W�̐F
  'C_ERROR' => 'ffffff',

  # �t�H���[���e��ʃ{�^���ɕ\�����镶��
  'TXTFOLLOW' => '��',

  # �X���b�h�\���{�^���ɕ\�����镶��
  'TXTTHREAD' => '��',

  # ���b�Z�[�W�e���v���[�g
  'TMPL_MSG' => "{val TITLE}{val BTN}{val WDATE} {val USER}\r{val MSG}\r\r",

);





/**
 * i���[�h���W���[��
 *
 *
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Imode extends Bbs {

  /**
   * �R���X�g���N�^
   *
   */
  function Imode() {
    $GLOBALS['CONF'] = array_merge ($GLOBALS['CONF'], $GLOBALS['CONF_IMODE']);
    $this->Bbs();
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
    if (@$this->f['im'] == 'w' and trim(@$this->f['v'])) {

      # ���ϐ��擾
      $this->setuserenv();

      # �[������
      if (@$this->c['RESTRICT_MOBILEIP']) {
        $uatype = Func::get_uatype(TRUE);
        if ($uatype != 'i') {
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
    else if (@$this->f['write'] and @$this->f['im'] == 'p') {
      $this->prtform();
    }
    # �t�H���[��ʕ\��
    else if (@$this->f['im'] == 'f') {
      $this->prtfollow();
    }
    # ���e����
    else if (@$this->f['im'] == 't') {
      $this->prtsearchlist('t');
    }
    # �P�����b�Z�[�W�\��
    else if (@$this->f['im'] == 'o') {
      $this->prtmsgpage();
    }
    # �w���v��ʕ\��
    else if (@$this->f['im'] == 'h') {
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
    print "�G���[:$err_message";
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
    $htmlstr = "<html><head><title>$title</title></head>"
     . "<body bgcolor=\"#{$this->c['C_BACKGROUND']}\" text=\"#{$this->c['C_TEXT']}\" link=\"#{$this->c['C_A_COLOR']}\">";
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
      $htmlstr .= '('.$duration.'�b)';
    }

    $htmlstr .= "</body></html>";
    return $htmlstr;
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
    $mbrcount = '';
    if ($this->c['CNTFILENAME']) {
      $mbrcount = $this->mbrcount()."��";
    }

    # HTML�w�b�_�����o��
    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);

    # �t�H�[������
    print <<<__IMODE__
<a name="t"><a href="#b" accesskey="2">��</a></a>
<form method="post" action="{$this->c['CGIURL']}">
<input type="hidden" name="m" value="i" />
<input type="hidden" name="im" value="p" />
<input type="hidden" name="p" value="{$this->s['TOPPOSTID']}" />
<input type="submit" name="read" value="R" accesskey="7" />
__IMODE__;
    if ($this->c['SHOW_READNEWBTN']) {
      print '<input type="submit" name="readnew" value="��" accesskey="0" />';
    }
    if ($this->c['BBSMODE_ADMINONLY'] == 0) {
      print '<input type="submit" name="write" value="��" accesskey="9" />';
    }
    print <<<__IMODE__
 <input type="text" size="2" name="d" value="{$this->s['MSGDISP']}" />��
 $mbrcount
__IMODE__;

    if (@$this->f['u']) {
      print '<input type="hidden" name="u" value="'.$this->f['u'].'" />';
    }
    if (@$this->f['i']) {
      print '<input type="hidden" name="i" value="'.$this->f['i'].'" />';
    }
    print "</form><pre>";

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
    print @$msgmore;

    # �i�r�Q�[�g�{�^��
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
<input type="submit" name="reload" value="��" accesskey="5" />
__IMODE__;

      # �Ǘ��ғ��e
      if ($this->c['BBSMODE_ADMINONLY'] != 0) {
        print '<br /><br /><input size="2" type="text" name="u" value="'.@$this->f['u'].'" />';
        print '<input type="submit" name="write" value="�Ǘ�" />';
      }
      else if (@$this->f['u']) {
        print '<input type="hidden" name="u" value="'.$this->f['u'].'" />';
      }
      if (@$this->f['i']) {
        print '<input type="hidden" name="i" value="'.$this->f['i'].'" />';
      }
      print "</form>";
    }

    print ' <a href="#t" accesskey="2">��</a><a name="b">&nbsp;</a><a href="'
      . $this->c['CGIURL'] . '?m=i&amp;im=h">�H</a>';
    print $this->prthtmlfoot ();

  }




  /**
   * �\���͈͂̃��b�Z�[�W�ƃp�����[�^�̎擾
   *
   * @access  public
   * @return  Array   $logdatadisp  ���O�s�z��
   * @return  Integer $bindex       �J�nindex
   * @return  Integer $eindex       �I�[index
   * @return  Integer $lastindex    �S���O�̏I�[index
   */
  function getdispmessage() {

    list ($logdatadisp, $bindex, $eindex, $lastindex) = parent::getdispmessage();

    # �\�������ȗ�����
    # �y�[�W�̃o�C�g�����l�����ĕ\�������𒲐�����

    # ���ǃ����[�h�̏ꍇ�A�L����k���ďȗ����s��
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
    # �ʏ탊���[�h�̏ꍇ�A�Â����̋L�����ȗ�
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

    if ($mode == 0 or ($mode == 1 and $this->c['OLDLOGBTN'])) {

      # �t�H���[���e�{�^��
      $message['BTNFOLLOW'] = '';
      if ($this->c['BBSMODE_ADMINONLY'] != 1) {
        $message['BTNFOLLOW'] = "<a href=\"{$this->c['CGIURL']}"
          ."?m=i&amp;im=f&amp;s={$message['POSTID']}&amp;p={$this->s['TOPPOSTID']}\">{$this->c['TXTFOLLOW']}</a>";
      }

      # ���e�Ҍ����{�^��
      $message['BTNAUTHOR'] = "";
      if ($message['USER'] != $this->c['ANONY_NAME'] and $this->c['BBSMODE_ADMINONLY'] != 1) {
        $message['BTNAUTHOR'] = "<a href=\"{$this->c['CGIURL']}"
          ."?m=i&amp;im=s&amp;s=". urlencode(preg_replace("/<[^>]*>/", '', $message['USER'])) ."\">{$this->c['TXTAUTHOR']}</a>";
      }

      # �X���b�h�\���{�^��
      if (!$message['THREAD']) {
        $message['THREAD'] = $message['POSTID'];
      }
      $message['BTNTHREAD'] = '';
      if ($this->c['BBSMODE_ADMINONLY'] != 1) {
        $message['BTNTHREAD'] = "<a href=\"{$this->c['CGIURL']}?m=i&amp;im=t&amp;s={$message['THREAD']}\">{$this->c['TXTTHREAD']}</a>";
      }

      # �{�^���̓���
      $message['BTN'] = "{$message['BTNFOLLOW']} {$message['BTNTHREAD']}";
    }

    # ���[���A�h���X
    if (@$message['MAIL']) {
      $message['USER'] = "<a href=\"mailto:{$message['MAIL']}\">{$message['USER']}</a>";
    }

    # �����̓��e�Җ��͔�\��
    if ($message['USER'] == $this->c['ANONY_NAME']) {
      $message['USER'] = '';
    }

    # �󔒂̑薼�͔�\��
    if ($message['TITLE'] == " ") {
      $message['TITLE'] = '';
    }

    # �������t�H���[�L���̑薼�͔�\��
    if ($message['TITLE'] == "��{$this->c['ANONY_NAME']}{$this->c['FSUBJ']}") {
      $message['TITLE'] = '';
    }

    # ��O�̃��X������
    #$message['MSG'] = preg_replace("/(^|\r)&gt; &gt; [^\r]*\r/", "", $message['MSG']);

    # ���p�F�ύX
    $message['MSG'] = preg_replace("/(^|\r)(&gt;[^\r]*)/", "$1<font color=\"#{$this->c['C_QMSG']}\">$2</font>", $message['MSG']);
    $message['MSG'] = str_replace("</font>\r<font color=\"#{$this->c['C_QMSG']}\">", "\r", $message['MSG']);

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
          $message['MSG'] .= "\r<a href=\"{$this->c['CGIURL']}?m=i&amp;im=o&amp;s={$message['POSTID']}\">[".$abbcount."�s�ȗ�]</a>";
        }
        $message['MSG'] .= "\r" . implode("\r", $messagelines);
      }
    }

    # �摜BBS�̉摜���\��
    $message['MSG'] = Func::conv_imgtag($message['MSG']);

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

    # �Ǘ��l�F��
    if (($this->c['BBSMODE_ADMINONLY'] == 1 or ($this->c['BBSMODE_ADMINONLY'] == 2 and !$dfid))
      and crypt(@$this->f['u'], $this->c['ADMINPOST']) != $this->c['ADMINPOST']) {
      $this->prterror('�p�X���[�h���Ⴂ�܂��B');
    }

    # �v���e�N�g�R�[�h����
    $pcode = Func::pcode(0, FALSE);

    $formu = @$this->f['u'];
    $formi = @$this->f['i'];

    # HTML�w�b�_�����o��
    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);
    print "<a href=\"{$this->c['CGIURL']}?m=i\" accesskey=\"1\">��</a>";

    print <<<__IMODE__
<form method="post" action="{$this->c['CGIURL']}">
<input type="hidden" name="m" value="i" />
<input type="hidden" name="im" value="w" />
<input type="hidden" name="p" value="{$this->s['TOPPOSTID']}" />
<input type="hidden" name="d" value="{$this->s['MSGDISP']}" />
<input type="hidden" name="pc" value="$pcode" />
<input type="submit" name="post" value="���e" accesskey="9" />
<input type="reset" name="reset" value="����" accesskey="3" /><br />
<textarea rows="4" cols="14" name="v">$dmsg</textarea><br />
��<input size="20" type="text" name="u" size="8" value="$formu" /><br />
��<input size="30" type="text" name="i" size="10" value="$formi" /><br />
��<input size="30" type="text" name="t" size="9" value="$dtitle" />
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
   * �t�H���[��ʕ\��
   *
   * @access  public
   * @param   Boolean $retry  ���g���C�t���O
   */
  function prtfollow($retry = FALSE) {

    if (!@$this->f['s']) {
      $this->prterror ( '�p�����[�^������܂���B' );
    }

    $result = $this->searchmessage('POSTID', @$this->f['s']);

    if (!$result) {
      $this->prterror ( '�w�肳�ꂽ���b�Z�[�W��������܂���B' );
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

    $this->prtform ( "��".preg_replace("/<[^>]*>/", '', $message['USER'])."{$this->c['FSUBJ']}", "$formmsg\r", '', $message['POSTID'], @$this->f['s']);

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
    print '<a href="#b">��</a><a name="t">&nbsp;</a><a href="'.$this->c['CGIURL'].'?m=i" accesskey="1">��</a><pre>';

    print $this->prtmessage($message, 0, 0, FALSE);

    print '</pre><a href="#t">��</a><a name="b">&nbsp;</a>';
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
      $mode = @$this->f['im'];
    }

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE']);
    print '<a href="#b">��</a><a name="t">&nbsp;</a><a href="'.$this->c['CGIURL'].'?m=i" accesskey="1">��</a><pre>';

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
    # �y�[�W�̃o�C�g�����l�����ĕ\�������𒲐�����
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
      print "<br /><a href=\"?m=i&amp;im=t&amp;s=".$this->f['s']."&amp;b={$eindex}\">��</a>";
    }

    print '</pre><a href="#t">��</a><a name="b">&nbsp;</a>';

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
    print '<a href="'.$this->c['CGIURL'].'?m=i" accesskey="1">��</a> <br />';
    print '�������ꗗ<br /><br />[1] �߂�<br />[2] ��ʏ��<br />[5] ���y�[�W<br />[7] �����[�h<br />[8] ��ʉ���<br />[9] ���e<br />';
    print $this->prthtmlfoot ();

  }











}








?>