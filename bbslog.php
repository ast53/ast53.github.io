<?php

/*

�����͂�����Ղ�PHP ver0.0.7alpha (13:04 2003/02/18)
�ߋ����O�{�����W���[��

* Todo

*/

if(!defined("INCLUDED_FROM_BBS")) {
  header ("Location: ../bbs.php?m=g");
  exit();
}


/*
 * ���W���[���ŗL�ݒ�
 *
 * $CONF�ɒǉ��E�㏑������܂��B
 */
$GLOBALS['CONF_GETLOG'] = array(

  # �������O�̌����\�L��
  'MULTIPLESEARCH' => 1,

  # ������n�C���C�g�F
  'C_QUERY' => 'FF8000',

  # �����L�[���[�h���̍ő�l
  'MAXKEYWORDS' => 10,

);


/**
 * �ߋ����O�{�����W���[��
 *
 *
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Getlog extends Webapp {


  /**
   * �R���X�g���N�^
   *
   */
  function Getlog() {
    $GLOBALS['CONF'] = array_merge ($GLOBALS['CONF'], $GLOBALS['CONF_GETLOG']);
    $this->Webapp();
    $this->t->readTemplatesFromFile($this->c['TEMPLATE_LOG']);
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

    # gzip���k�]��
    if ($this->c['GZIPU']) {
      ob_start("ob_gzhandler");
    }

    # ��������
    if (@$this->f['f']) {
      $this->prtsearchresult();
    }
    # �_�E�����[�h
    else if (@$this->f['dl']) {
      $result = $this->prthtmldownload($this->f['dl']);
      if ($result) {
        $this->prtloglist();
      }
    }
    # �g�s�b�N�ꗗ
    else if (@$this->f['l']) {
      $result = $this->prttopiclist($this->f['l']);
      if ($result) {
        $this->prtloglist();
      }
    }
    # Zip�A�[�J�C�u
    else if (@$this->f['gm'] == 'z' and @$this->c['ZIPDIR']) {
      $this->prtarchivelist();
    }
    # �������
    else {
      $this->prtloglist();
    }

    if ($this->c['GZIPU']) {
      ob_end_flush();
    }
  }





  /**
   * ������ʕ\��
   *
   */
  function prtloglist() {

    $dir = $this->c['OLDLOGFILEDIR'];

    if ($this->c['OLDLOGFMT']) {
      $oldlogext = 'dat';
    }
    else {
      $oldlogext = 'html';
    }

    $files = array();

    $dh = opendir($dir);
    if (!$dh) {
      $this->prterror ('�f�B���N�g�����J���܂���ł���');
    }
    while ($entry = readdir($dh)) {
      if (is_file($dir . $entry) and preg_match("/^\d+\.$oldlogext$/", $entry)) {
        $files[] = $entry;
      }
    }
    closedir ($dh);

    # �t�@�C�����̎��R���Ń\�[�g
    natsort($files);

    # �X�V���Ԃ��ŐV�̃t�@�C����W���Ń`�F�b�N
    $maxftime = 0;
    foreach ($files as $filename) {
      $fstat = stat ($dir . $filename);
      if ($fstat[9] > $maxftime) {
        $maxftime = $fstat[9];
        $checkedfile = $filename;
      }
    }

    if ($this->c['ZIPDIR'] and function_exists("gzcompress")) {
      $this->t->setAttribute("ziplink", "visibility", "visible");
    }

    if (!$this->c['OLDLOGFMT']) {
      $this->t->setAttribute("topiclink", "visibility", "hidden");
    }
    if (!$this->dlchk()) {
      $this->t->setAttribute("dllink", "visibility", "hidden");
    }

    foreach ($files as $filename) {
      $fstat = stat ($dir . $filename);
      $fsize = $fstat[7];
      $ftime = date("Y/m/d H:i:s", $fstat[9]);
      $ftitle = '';
      $matches = array();
      if (preg_match("/^(\d\d\d\d)(\d\d)(\d\d)\.$oldlogext/", $filename, $matches)) {
        $ftitle = "{$matches[1]}/{$matches[2]}/{$matches[3]}";
      }
      else if (preg_match("/^(\d\d\d\d)(\d\d)\.$oldlogext/", $filename, $matches)) {
        $ftitle = "{$matches[1]}/{$matches[2]}";
      }
      else {
        $ftitle = $filename;
      }

      $checked = '';
      if ($filename == $checkedfile) {
        $checked = ' checked="checked"';
      }
      $checkbox = '';
      if (@$this->c['MULTIPLESEARCH']) {
        $checkbox = "<input type=\"checkbox\" name=\"f[]\" value=\"$filename\"$checked />";
      }
      else {
        $checkbox = "<input type=\"radio\" name=\"f\" value=\"$filename\"$checked />";
      }

      $this->t->clearTemplate('topiclink');
      $this->t->clearTemplate('dllink');
      $this->t->addVar('topiclink', 'FILENAME', $filename);
      $this->t->addVar('dllink', 'FILENAME', $filename);
      $this->t->addVars('filelist', array(
        'FCHECK' => $checkbox,
        'FILENAME' => $filename,
        'FTITLE' => $ftitle,
        'FTIME' => $ftime,
        'FSIZE' => $fsize,
      ));
      $this->t->parseTemplate('filelist', 'a');
    }

    $this->t->addVar('dateform', 'OLDLOGSAVESW', $this->c['OLDLOGSAVESW']);
    if ($this->c['BBSMODE_IMAGE'] == 1) {
      if ($this->c['SHOWIMG']) $this->t->addVar('sicheck', 'CHK_SI', ' checked="checked"');
      $this->t->setAttribute('sicheck', 'visibility', 'visible');
    }
    if (!$this->c['OLDLOGFMT'] or !$this->c['OLDLOGBTN']) {
      $this->t->setAttribute("check_bt", "visibility", "hidden");
    }
    if ($this->c['GZIPU']) $this->t->addVar('loglist', 'CHK_G', ' checked="checked"');

    # �o��
    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE'] . ' �ߋ����O����');
    $this->t->displayParsedTemplate('loglist');
    print $this->prthtmlfoot ();

  }







  /**
   * ���������̎擾
   */
  function getconditions($filename) {
    $conditions = array();

    $conditions['showall'] = TRUE;
    if (@$this->f['q']) {
      $conditions['showall'] = FALSE;
    }

    foreach (array ('q', 't', 'b', 'ci',) as $formvalue) {
      $conditions[$formvalue] = @$this->f[$formvalue];
    }
    foreach (array ('sd', 'sh', 'si', 'ed', 'eh', 'ei',) as $formvalue) {
      if ($conditions['showall'] and @$this->f[$formvalue]) {
        $conditions['showall'] = FALSE;
      }
      $conditions[$formvalue] = str_pad(@$this->f[$formvalue], 2, "0", STR_PAD_LEFT);
    }

    if ($conditions['q']) {
      $conditions['q'] = trim($conditions['q']);
      $conditions['keywords'] = preg_split("/\s+/", $conditions['q']);
      if (count($conditions['keywords']) > $this->c['MAXKEYWORDS']) {
        $this->prterror ('�����L�[���[�h���������܂��B');
      }
    }

    $conditions['savesw'] = $this->c['OLDLOGSAVESW'];

    return $conditions;
  }







  /**
   * �ߋ����O�������ʕ\��
   *
   */
  function prtsearchresult() {

    $formf = array();
    if (is_array($this->f['f'])) {
      $formf = $this->f['f'];
    }
    else {
      $formf[] = $this->f['f'];
    }
    if (!@$this->c['MULTIPLESEARCH'] and count($formf) > 1) {
      array_splice($formf, 1);
    }
    $files = array();
    foreach ($formf as $filename) {
      if (preg_match("/^\d+\./", $filename) and is_file($this->c['OLDLOGFILEDIR'] . $filename)) {
        $files[] = $filename;
      }
    }

    $this->sethttpheader();
    $customstyle= "  .sq { color: #{$this->c['C_QUERY']}; }\n";
    print $this->prthtmlhead ($this->c['BBSTITLE'] . ' �ߋ����O��������', '', $customstyle);
    $this->t->displayParsedTemplate('searchresult');

    foreach ($files as $filename) {
      $conditions = $this->getconditions($filename);
      $resultcode = $this->prtoldlog($filename, $conditions, FALSE);
    }

    print $this->prthtmlfoot ();

  }







  /**
   * �ߋ����OHTML�t�@�C���_�E�����[�h
   *
   */
  function prthtmldownload($filename) {

    if ($this->c['OLDLOGFMT']) {
      $oldlogext = 'dat';
    }
    else {
      $oldlogext = 'html';
    }

    # �s���ȃt�@�C����
    if (!preg_match("/^\d+\.$oldlogext$/", $filename)) {
      return 1;
    }
    else if (!is_file($this->c['OLDLOGFILEDIR'] . $filename)) {
      return 1;
    }

    $dlfilename = str_replace (".dat", ".html", $filename);

    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=".$dlfilename);

    if ($this->c['OLDLOGFMT']) {
      $this->sethttpheader();
      print $this->prthtmlhead ($this->c['BBSTITLE'] . ' �ߋ����O');
      $this->t->displayParsedTemplate('htmldownload');
    }

    $conditions = $this->getconditions($filename);
    $resultcode = $this->prtoldlog($filename, $conditions, TRUE);

    if ($this->c['OLDLOGFMT']) {
      print $this->prthtmlfoot ();
    }

  }







  /**
   * �e�t�@�C������
   *
   */
  function prtoldlog($filename, $conditions = "", $isdownload = FALSE) {

    $dir = $this->c['OLDLOGFILEDIR'];

    if ($this->c['OLDLOGFMT']) {
      $oldlogext = 'dat';
    }
    else {
      $oldlogext = 'html';
    }

    # �s���ȃt�@�C����
    if (!preg_match("/^\d+\.$oldlogext$/", $filename)) {
      return 1;
    }
    else if (!is_file($dir . $filename)) {
      return 1;
    }

    $this->t->clearTemplate('oldlog_upper');
    $this->t->clearTemplate('oldlog_lower');
    $this->t->addVar('oldlog_upper', 'FILENAME', $filename);

    $fh = @fopen($dir . $filename, "rb");
    if (!$fh) {
      $this->t->addVar('oldlog_upper', 'success', 'false');
      $this->t->displayParsedTemplate('oldlog_upper');
      return 2;
    }
    flock ($fh, 1);

    $timerangestr = '';
    if (!(!$this->c['OLDLOGFMT'] and !$conditions)) {
      if (!@$conditions['showall']) {
        if (@$conditions['savesw']) {
          if ($conditions['sd'] > 1 or $conditions['sh'] > 0 or $conditions['ed'] < 31 or $conditions['eh'] < 24) {
            $timerangestr .= "{$conditions['sd']}��{$conditions['sd']}�� �` {$conditions['ed']}��{$conditions['ed']}���@";
          }
        }
        else {
          if ($conditions['sh'] > 0 or $conditions['si'] > 0 or $conditions['eh'] < 24 or $conditions['ei'] > 0) {
            $timerangestr .= "{$conditions['sh']}��{$conditions['si']}�� �` {$conditions['eh']}��{$conditions['ei']}���@";
          }
        }
      }
      $this->t->addVar('oldlog_upper', 'TIMERANGE', $timerangestr);
      $this->t->displayParsedTemplate('oldlog_upper');
    }


    $msgmode = 2;
    if (@$this->f['bt']) {
      $msgmode = 1;
    }
    $resultcount = 0;

    # dat����
    if ($this->c['OLDLOGFMT']) {
      if (!@$conditions['showall']) {
        $result = 0;
        while (($logline = Func::fgetline($fh)) !== FALSE) {
          $message = $this->getmessage($logline);
          $result = $this->msgsearch($message, $conditions);
          # �����q�b�g
          if ($result == 1) {
            $prtmessage = $this->prtmessage($message, $msgmode, $filename);
            # ������̃n�C���C�g
            if ($conditions['q']) {
              $needle = "\Q{$conditions['q']}\E";
              $quoteq = preg_quote($conditions['q'], "/");
              if ($conditions['ci']) {
                #$prtmessage = preg_replace("/($quoteq)/i", "<span class=\"sq\">$1</span>", $prtmessage);
                #while (preg_match("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/i", $prtmessage)) {
                #  $prtmessage = preg_replace("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/i", "$1", $prtmessage, 1);
                #}
                $prtmessage = preg_replace("/((?:\G|>)[^<]*?)($quoteq)/i", "$1<span class=\"sq\">$2</span>", $prtmessage);
              }
              else {
                #$prtmessage = str_replace($conditions['q'], "<span class=\"sq\">{$conditions['q']}</span>", $prtmessage);
                #while (preg_match("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/", $prtmessage)) {
                #  $prtmessage = preg_replace("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/", "$1", $prtmessage, 1);
                #}
                $prtmessage = preg_replace("/((?:\G|>)[^<]*?)($quoteq)/", "$1<span class=\"sq\">$2</span>", $prtmessage);
              }
            }
            print $prtmessage;
            $resultcount++;
          }
          # �����̏I��
          else if ($result == 2) {
            break;
          }
        }
      }
      # �S���\��
      else {
        while (($logline = Func::fgetline($fh)) !== FALSE) {
          $messagestr = $this->prtmessage($this->getmessage($logline), $msgmode, $filename);
          print $messagestr;
        }
      }
    }
    # HTML����
    else {
      if (!$conditions['showall']) {
        # ���b�Z�[�W���ƂɃt�@�C���ǂݍ��݂̃o�b�t�@�����O���s��
        $buffer = "";
        $flgbuffer = FALSE;
        $result = 0;
        while (($htmlline = Func::fgetline($fh)) !== FALSE) {
          # ���b�Z�[�W�̊J�n
          if (!$flgbuffer and preg_match("/<div [^>]*id=\"m\d+\"[^>]*>/", $htmlline)) {
            $buffer = $htmlline;
            $flgbuffer = TRUE;
          }
          # ���b�Z�[�W�̏I��
          else if ($flgbuffer and strpos($htmlline, "<!--  -->") !== FALSE) {
            $buffer .= $htmlline;
            {
              $result = $this->msgsearchhtml($buffer, $conditions);
              if ($result == 1) {
                # ������̃n�C���C�g
                if ($conditions['q']) {
                  $needle = "\Q{$conditions['q']}\E";
                  $quoteq = preg_quote($conditions['q'], "/");
                  if ($conditions['ci']) {
                    #$buffer = preg_replace("/($quoteq)/i", "<span class=\"sq\">$1</span>", $buffer);
                    #while (preg_match("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/i", $buffer)) {
                    #  $buffer = preg_replace("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/i", "$1", $buffer, 1);
                    #}
                    $buffer = preg_replace("/((?:\G|>)[^<]*?)($quoteq)/i", "$1<span class=\"sq\">$2</span>", $buffer);
                  }
                  else {
                    #$buffer = str_replace($conditions['q'], "<span class=\"sq\">{$conditions['q']}</span>", $buffer);
                    #while (preg_match("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/", $buffer)) {
                    #  $buffer = preg_replace("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/", "$1", $buffer, 1);
                    #}
                    $buffer = preg_replace("/((?:\G|>)[^<]*?)($quoteq)/", "$1<span class=\"sq\">$2</span>", $buffer);
                  }
                }
                print $buffer;
                $resultcount++;
              }
              else if ($result == 2) {
                break;
              }
            }
            $buffer = "";
            $flgbuffer = FALSE;
          }
          # ���b�Z�[�W��
          else if ($flgbuffer) {
            $buffer .= $htmlline;
          }
          # ���b�Z�[�W�ȊO
          else {
          }
        }
      }
      else {
        while (($htmlline = Func::fgetline($fh)) !== FALSE) {
          print $htmlline;
        }
      }
    }
    flock ($fh, 3);
    fclose ($fh);

    if (!(!$this->c['OLDLOGFMT'] and !$conditions)) {
      $resultmsg = '';
      if (!$conditions['showall']) {
        #$resultmsg = "{$filename}�F&nbsp;{$timerangestr}&nbsp;";
        if (@$conditions['q'] != '') {
          $value = $conditions['q'];
          #$value_euc = JcodeConvert($value, 2, 1);
          #$value_euc = htmlentities($value_euc, ENT_QUOTES, 'EUC-JP');
          #$value = JcodeConvert($value_euc, 1, 2);
          $value = htmlspecialchars($value, ENT_QUOTES);
          $resultmsg .= '�u' . $value . '�v��';
        }
        if ($resultcount > 0) {
          $resultmsg .= $resultcount . '��������܂����B';
        }
        else {
          $resultmsg .= '������܂���ł����B';
        }
        #print $resultmsg;
        $this->t->addVar('oldlog_lower', 'RESULTMSG', $resultmsg);
        $this->t->displayParsedTemplate('oldlog_lower');
      }
    }

  }












  /**
   * �P�����b�Z�[�W�����iHTML�`���j
   */
  function msgsearchhtml ($buffer, $conditions) {
    $message = array();

    $message['USER'] = '';
    $message['TITLE'] = '';
    $message['MSG'] = '';
    $message['NDATESTR'] = '';

    if (preg_match("/<span class=\"mun\">([^<]+)<\/span>/", $buffer, $matches)) {
      $message['USER'] = $matches[1];
    }
    if (preg_match("/<span class=\"ms\">([^<]+)<\/span>/", $buffer, $matches)) {
      $message['TITLE'] = $matches[1];
    }
    if (preg_match("/<blockquote>[\r\n\s]*<pre>(.+?)<\/pre>/ms", $buffer, $matches)) {
      $message['MSG'] = $matches[1];
    }
    if (preg_match("/<span class=\"md\">[^<]*���e���F(\d+)\/(\d+)\/(\d+)[^\d]+(\d+)��(\d+)��(\d+)�b/", $buffer, $matches)) {
      if (@$conditions['savesw']) {
        $message['NDATESTR'] = $matches[3] . $matches[4];
      }
      else {
        $message['NDATESTR'] = $matches[4] . $matches[5];
      }
    }

    return $this->msgsearch ($message, $conditions);
  }



  /**
   * �P�����b�Z�[�W�����idat�`���j
   * �Ԃ�l 0:�q�b�g�Ȃ� 1:�q�b�g 2:�����I���M��
   */
  function msgsearch ($message, $conditions) {

    if (!$message) {
      return 0;
    }

    # ����
    if (@$conditions['savesw']) {
      $starttime = $conditions['sd'].$conditions['sh'];
      $endtime = $conditions['ed'].$conditions['eh'];
      if (!@$message['NDATESTR']) {
        $message['NDATESTR'] = date("dH", $message['NDATE']);
      }
    }
    # ����
    else {
      $starttime = $conditions['sh'].$conditions['si'];
      $endtime = $conditions['eh'].$conditions['ei'];
      if (!@$message['NDATESTR']) {
        $message['NDATESTR'] = date("Hi", $message['NDATE']);
      }
    }
    if ($message['NDATESTR'] < $starttime or $message['NDATESTR'] > $endtime) {
      return 2;
    }

    $hit = FALSE;

    # �L�[���[�h����
    if (@$conditions['keywords']) {

      $haystack = '';
      if ($conditions['t'] == 'u') {
        $haystack = $message['USER'];
      }
      else if ($conditions['t'] == 't') {
        $haystack = $message['TITLE'];
      }
      else {
        $haystack = "{$message['USER']}<>{$message['TITLE']}<>{$message['MSG']}";
      }

      # OR����
      if ($conditions['b'] == 'o') {
        $hit = FALSE;
        foreach ($conditions['keywords'] as $needle) {
          if ($conditions['ci']) {
            $result = stristr ($haystack, $needle);
          }
          else {
            $result = strpos ($haystack, $needle);
          }
          if ($result !== FALSE) {
            $hit = TRUE;
            break;
          }
        }
      }
      # AND����
      else {
        $hit = TRUE;
        foreach ($conditions['keywords'] as $needle) {
          if ($conditions['ci']) {
            $result = stristr ($haystack, $needle);
          }
          else {
            $result = strpos ($haystack, $needle);
          }
          if ($result === FALSE) {
            $hit = FALSE;
            break;
          }
        }
      }
    }
    else {
      $hit = TRUE;
    }

    if ($hit) {
      return 1;
    }
    else {
      return 0;
    }

  }




  /**
   * �g�s�b�N�ꗗ�̕\��
   */
  function prttopiclist($filename) {

    # �s���ȃt�@�C����
    if (!preg_match("/^\d+\.dat$/", $filename)) {
      return 1;
    }
    else if (!is_file($this->c['OLDLOGFILEDIR'] . $filename)) {
      return 1;
    }

    $fh = @fopen($this->c['OLDLOGFILEDIR'] . $filename, "rb");
    if (!$fh) {
      $this->prterror($filename . ' ���J���܂���ł����B');
    }
    flock ($fh, 1);

    $tid = array();
    $tcount = array();
    $ttitle = array();
    $ttime = array();
    $tindex = 0;
    while (($logline = Func::fgetline($fh)) !== FALSE) {
      $message = $this->getmessage($logline);
      if (!$message['THREAD'] or $message['THREAD'] == $message['POSTID'] or !@$ttitle[$message['THREAD']]) {
        $tid[$tindex] = $message['POSTID'];
        $tcount[$message['POSTID']] = 0;

        $msg = ltrim($message['MSG']);
        $msg = preg_replace("/<a href=[^>]+>�Q�l�F[^<]+<\/a>/i", "", $msg, 1);
        $msg = preg_replace("/<[^>]+>/", "", $msg);
        $msgsplit = explode("\r", $msg);
        $msgdigest = $msgsplit[0];
        $index = 1;
        while ($index < count($msgsplit) - 1 and strlen($msgdigest . $msgsplit[$index]) < 50) {
          $msgdigest .= $msgsplit[$index];
          $index++;
        }
        $ttitle[$message['POSTID']] = $msgdigest;

        if (strpos($ttitle[$message['POSTID']], "\r") !== FALSE) {
          $ttitle[$message['POSTID']] = substr($ttitle[$message['POSTID']],
          0, strpos($ttitle[$message['POSTID']], "\r"));
        }

        $ttime[$message['POSTID']] = $message['NDATE'];
        $tindex++;
      }
      else {
        $tcount[$message['THREAD']]++;
        $ttime[$message['THREAD']] = $message['NDATE'];
      }
    }
    flock ($fh, 3);
    fclose ($fh);

    $this->t->addVar('topiclist', 'FILENAME', $filename);

    $tidcount = count($tid);
    $i = 0;
    while ($i < $tidcount) {
      if ($tid[$i]) {
        $tc = sprintf ("%02d", $tcount[$tid[$i]]);
        $tt = date ("m/d H:i:s", $ttime[$tid[$i]]);
        $this->t->addVars('topic', array(
          'TID' => $tid[$i],
          'TC' => $tc,
          'TT' => $tt,
          'TTITLE' => $ttitle[$tid[$i]],
          'FILENAME' => $filename,
        ));
        $this->t->parseTemplate('topic', 'a');
      }
      $i++;
    }

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE'] . ' �g�s�b�N�ꗗ ' . $filename);
    $this->t->displayParsedTemplate('topiclist');
    print $this->prthtmlfoot ();

  }





  /**
   * Zip�A�[�J�C�u�ꗗ��ʕ\��
   *
   */
  function prtarchivelist() {

    $dir = $this->c['ZIPDIR'];

    $dh = opendir($dir);
    if (!$dh) {
      $this->prterror ('�f�B���N�g�����J���܂���ł���');
    }
    $files = array();
    while ($entry = readdir($dh)) {
      if (is_file($dir . $entry) and preg_match("/\.(zip|lzh|rar|gz|tar\.gz)$/i", $entry)) {
        $files[] = $entry;
      }
    }
    closedir ($dh);

    # �t�@�C�����̎��R���Ń\�[�g
    natsort($files);

    foreach ($files as $filename) {
      $fstat = stat ($dir . $filename);
      $fsize = $fstat[7];
      $ftime = date("Y/m/d H:i:s", $fstat[9]);

      $this->t->setAttribute('archive', 'visibility', 'visible');
      $this->t->addVars('archive', array(
        'DIR' => $dir,
        'FILENAME' => $filename,
        'FTIME' => $ftime,
        'FSIZE' => $fsize,
      ));
      $this->t->parseTemplate('archive', 'a');
    }

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE'] . ' �ߋ����O�A�[�J�C�u');
    $this->t->displayParsedTemplate('archivelist');
    print $this->prthtmlfoot ();

  }




  /**
   * �_�E�����[�h�@�\�g�p�ۃ`�F�b�N
   */
  function dlchk() {

    if (!@$_SERVER['HTTP_USER_AGENT']) {
      return TRUE;
    }
    if (preg_match ("/^Mozilla\/(\S+)\s(.+)/", @$_SERVER['HTTP_USER_AGENT'], $matches)) {
      $ver = $matches[1];
      $uos = $matches[2];
      $isie = 0;
      if (preg_match ("/MSIE (\S)/", $uos, $matches)) {
        $isie = 1;
        $iever = $matches[1];
      }
      $ismac = 0;
      if (preg_match ("/Mac/", $uos, $matches)) {
        $ismac = 1;
      }
      if ((@$ver >= 4 and !@$isie) or (@$ver >= 4 and @$isie and @$iever >= 5 and !@$ismac)) {
        return TRUE;
      } else {
        return FALSE;
      }
    }
    return TRUE;
  }










}


?>