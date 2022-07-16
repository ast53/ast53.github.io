<?php

/*

�����͂�����Ղ�PHP ver0.0.7alpha (13:04 2003/02/18)
�Ǘ����[�h���W���[��

*/

if(!defined("INCLUDED_FROM_BBS")) {
  header ("Location: ../bbs.php");
  exit();
}



/**
 * �Ǘ����[�h���W���[��
 *
 *
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Bbsadmin extends Webapp {

  var $bbs;

  /**
   * �R���X�g���N�^
   *
   */
  function Bbsadmin() {
    $this->Webapp();
    if (func_num_args() > 0) {
      $this->bbs = func_get_arg(0);
      $this->c = &$this->bbs->c;
      $this->f = &$this->bbs->f;
      $this->t = &$this->bbs->t;
    }
    $this->t->readTemplatesFromFile($this->c['TEMPLATE_ADMIN']);
  }


  /**
   * ���C������
   */
  function main() {

    if (!defined('BBS_ACTIVATED')) {

      # ���s���ԑ���J�n
      $this->setstarttime();

      # �t�H�[���擾�O����
      $this->procForm();

      # �l�p�ݒ蔽�f
      $this->refcustom();
      $this->setusersession();

      # gzip���k�]��
      if ($this->c['GZIPU']) {
        ob_start("ob_gzhandler");
      }
    }

    # ���O�t�@�C���{��
    if (@$this->f['ad'] == 'l') {
      $this->prtlogview(TRUE);
    }
    # ���b�Z�[�W�폜���[�h
    else if (@$this->f['ad'] == 'k') {
      $this->prtkilllist();
    }
    # ���b�Z�[�W�폜����
    else if (@$this->f['ad'] == 'x') {
      if (isset($this->f['x'])) {
        $this->killmessage($this->f['x']);
      }
      $this->prtkilllist();
    }
    # �Í����p�X���[�h�������
    else if (@$this->f['ad'] == 'p') {
      $this->prtsetpass();
    }
    # �Í����p�X���[�h�������\��
    else if (@$this->f['ad'] == 'ps') {
      $this->prtpass(@$this->f['ps']);
    }
    # �T�[�o�[��PHP�ݒ���\��
    else if (@$this->f['ad'] == 'phpinfo') {
      phpinfo();
    }
    # �Ǘ����j���[���
    else {
      $this->prtadminmenu();
    }


    if (!defined('BBS_ACTIVATED') and $this->c['GZIPU']) {
      ob_end_flush();
    }
  }





  /**
   * �Ǘ����j���[���
   *
   */
  function prtadminmenu() {

    $this->t->addVar('adminmenu', 'V', trim($this->f['v']));

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE'] . ' �Ǘ����j���[');
    $this->t->displayParsedTemplate('adminmenu');
    print $this->prthtmlfoot ();

  }





  /**
   * ���b�Z�[�W�폜���[�h���C����ʕ\��
   *
   */
  function prtkilllist() {

    if (!file_exists($this->c['LOGFILENAME'])) {
      $this->prterror('���b�Z�[�W�ǂݍ��݂Ɏ��s���܂���');
    }
    $logdata = file($this->c['LOGFILENAME']);

    $this->t->addVar('killlist', 'V', trim($this->f['v']));

    $messages = array();
    while ($logline = each($logdata)) {
      $message = $this->getmessage($logline[1]);
      $message['MSG'] = preg_replace("/<a href=[^>]+>�Q�l�F[^<]+<\/a>/i", "", $message['MSG'], 1);
      $message['MSG'] = preg_replace("/<[^>]+>/", "", ltrim($message['MSG']));
      $msgsplit = explode("\r", $message['MSG']);
      $message['MSGDIGEST'] = $msgsplit[0];
      $index = 1;
      while ($index < count($msgsplit) - 1 and strlen($message['MSGDIGEST'] . $msgsplit[$index]) < 50) {
        $message['MSGDIGEST'] .= $msgsplit[$index];
        $index++;
      }
      $message['WDATE'] = Func::getdatestr($message['NDATE']);
      $message['USER_NOTAG'] = preg_replace("/<[^>]*>/", '', $message['USER']);
      $messages[] = $message;
    }

    $this->t->addRows('killmessage', $messages);

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ���b�Z�[�W�폜���[�h');
    $this->t->displayParsedTemplate('killlist');
    print $this->prthtmlfoot ();
  }





  /**
   * ���b�Z�[�W�폜����
   *
   */
  function killmessage($killids) {

    if (!$killids) {
      return;
    }
    if (!is_array($killids)) {
      $tmp = $killids;
      $killids = array();
      $killids[] = $tmp;
    }

    $fh = @fopen($this->c['LOGFILENAME'], "r+");
    if (!$fh) {
      $this->prterror ( '���b�Z�[�W�ǂݍ��݂Ɏ��s���܂���' );
    }
    flock ($fh, 2);
    fseek ($fh, 0, 0);

    $logdata = array();
    while (($logline = Func::fgetline($fh)) !== FALSE) {
       $logdata[] = $logline;
    }

    $killntimes = array();
    $killlogdata = array();
    $newlogdata = array();
    $i = 0;
    while ($i < count($logdata)) {
      $items = explode(',', $logdata[$i], 3);
      if (count($items) > 2 and array_search($items[1], $killids) !== FALSE) {
        $killntimes[$items[1]] = $items[0];
        $killlogdata[] = $logdata[$i];
      }
      else {
        $newlogdata[] = $logdata[$i];
      }
      $i++;
    }
    {
      fseek ($fh, 0, 0);
      ftruncate ($fh, 0);
      fwrite ($fh, implode ('', $newlogdata));
    }
    flock ($fh, 3);
    fclose ($fh);

    # �摜�폜
    foreach ($killlogdata as $eachlogdata) {
      if (preg_match("/<img [^>]*?src=\"([^\"]+)\"[^>]+>/i", $eachlogdata, $matches) and file_exists($matches[1])) {
        unlink ($matches[1]);
      }
    }

    # �ߋ����O�s�폜
    if ($this->c['OLDLOGFILEDIR']) {
      foreach (array_keys($killntimes) as $killid) {
        $oldlogfilename = '';
        if ($this->c['OLDLOGFMT']) {
          $oldlogext = 'dat';
        }
        else {
          $oldlogext = 'html';
        }
        if ($this->c['OLDLOGSAVESW']) {
          $oldlogfilename = date("Ym", $killntimes[$killid]) . ".$oldlogext";
        }
        else {
          $oldlogfilename = date("Ymd", $killntimes[$killid]) . ".$oldlogext";
        }
        $fh = @fopen($this->c['OLDLOGFILEDIR'] . $oldlogfilename, "r+");
        if ($fh) {
          flock ($fh, 2);
          fseek ($fh, 0, 0);

          $newlogdata = array();
          $hit = FALSE;

          if ($this->c['OLDLOGFMT']) {
            $needle = $killntimes[$killid] . "," . $killid . ",";
            while (($logline = Func::fgetline($fh)) !== FALSE) {
              if (!$hit and strpos($logline, $needle) !== FALSE and strpos($logline, $needle) == 0) {
                $hit = TRUE;
              }
              else {
                $newlogdata[] = $logline;
              }
            }
          }
          else {
            $needle = "<div class=\"m\" id=\"m{$killid}\">";
            $flgbuffer = FALSE;
            while (($htmlline = Func::fgetline($fh)) !== FALSE) {

              # ���b�Z�[�W�̊J�n
              if (!$hit and strpos($htmlline, $needle) !== FALSE) {
                $hit = TRUE;
                $flgbuffer = TRUE;
              }
              # ���b�Z�[�W�̏I��
              else if ($flgbuffer and strpos($htmlline, "<hr") !== FALSE) {
                $flgbuffer = FALSE;
              }
              # ���b�Z�[�W��
              else if ($flgbuffer) {
              }
              else {
                $newlogdata[] = $htmlline;
              }
            }
          }

          {
            fseek ($fh, 0, 0);
            ftruncate ($fh, 0);
            fwrite ($fh, implode ('', $newlogdata));
          }
          flock ($fh, 3);
          fclose ($fh);
        }
        else {
          #$this->prterror ( '�ߋ����O�ǂݍ��݂Ɏ��s���܂���' );
        }
      }
    }

  }





  /**
   * �Í����p�X���[�h������ʕ\��
   *
   */
  function prtsetpass() {

    $this->t->addVar('setpass', 'V', trim($this->f['v']));

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE'] . ' �p�X���[�h�ݒ���');
    $this->t->displayParsedTemplate('setpass');
    print $this->prthtmlfoot ();
  }





  /**
   * �Í����p�X���[�h�������\��
   *
   */
  function prtpass($inputpass) {

    if (!@$inputpass) {
      $this->prterror ('�p�X���[�h���ݒ肳��Ă��܂���B');
    }

    $cryptpass = crypt($inputpass);
    $inputsize = strlen($cryptpass) + 10;

    $this->t->addVars('pass', array(
      'CRYPTPASS' => $cryptpass,
      'INPUTSIZE' => $inputsize,
    ));

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE'] . ' �p�X���[�h�ݒ���');
    $this->t->displayParsedTemplate('pass');
    print $this->prthtmlfoot ();
  }





  /**
   * ���O�t�@�C���\��
   *
   */
  function prtlogview($htmlescape = FALSE) {
    if ($htmlescape) {
      header ("Content-type: text/html");
      $logdata = file ($this->c['LOGFILENAME']);
      print "<html><head><title>{$this->c['LOGFILENAME']}</title></head><body><pre>\n";
      foreach ($logdata as $logline) {
        if (!preg_match("/^\w+$/", $logline)) {
          $value_euc = JcodeConvert($logline, 2, 1);
          $value_euc = htmlentities($value_euc, ENT_QUOTES, 'EUC-JP');
          $logline = JcodeConvert($value_euc, 1, 2);
        }
        $logline = str_replace("&#44;", ",", $logline);
        print $logline;
      }
      print "\n</pre></body></html>";
    }
    else {
      header ("Content-type: text/plain");
      readfile ($this->c['LOGFILENAME']);
    }
  }






}



?>