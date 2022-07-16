<?php

/*

�����͂�����Ղ�PHP ver0.0.7alpha (13:04 2003/02/18)
�c���[�r���[���W���[��

* Todo

* Memo

http://www.hlla.is.tsukuba.ac.jp/~yas/gen/it-2002-10-28/


*/

if(!defined("INCLUDED_FROM_BBS")) {
  header ("Location: ../bbs.php?m=tree");
  exit();
}


/*
 * ���W���[���ŗL�ݒ�
 *
 * $CONF�ɒǉ��E�㏑������܂��B
 */
$GLOBALS['CONF_TREEVIEW'] = array(

  # �}�̐F
  'C_BRANCH' => '009090',

  # �X�V���ԕ\���̐F
  'C_UPDATE' => 'cccccc',

  # �X�V���ԕ\���̐F
  'C_NEWMSG' => 'ccffff',

  # �\���c���[��
  'TREEDISP' => 10,

);





/**
 * �c���[�r���[���W���[��
 *
 *
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Treeview extends Bbs {

  /**
   * �R���X�g���N�^
   *
   */
  function Treeview() {
    $GLOBALS['CONF'] = array_merge ($GLOBALS['CONF'], $GLOBALS['CONF_TREEVIEW']);
    $this->Bbs();
    $this->t->readTemplatesFromFile($this->c['TEMPLATE_TREEVIEW']);
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
    if (@$this->f['treem'] == 'p') {
      $this->f['m'] = 'p';
    }
    $this->refcustom();
    $this->setusersession();

    # gzip���k�]��
    if ($this->c['GZIPU']) {
      ob_start("ob_gzhandler");
    }

    # �������ݏ���
    if (@$this->f['treem'] == 'p' and trim(@$this->f['v'])) {

      # ���ϐ��擾
      $this->setuserenv();

      # �p�����[�^�`�F�b�N
      $posterr = $this->chkmessage();

      # �������ݏ���
      if (!$posterr) {
        $posterr = $this->putmessage($this->getformmessage());
      }

      # �Q�d�������݃G���[�Ȃ�
      if ($posterr == 1) {
        $this->prttreeview();
      }
      # �v���e�N�g�R�[�h���Ԍo�߂̂��ߍĕ\��
      else if ($posterr == 2) {
        if (@$this->f['f']) {
          $this->prtfollow(TRUE);
        }
        else {
          $this->prttreeview(TRUE);
        }
      }
      # �Ǘ����[�h�ڍs
      else if ($posterr == 3) {
        define('BBS_ACTIVATED', TRUE);
        require_once(PHP_BBSADMIN);
        $bbsadmin = new Bbsadmin($this);
        $bbsadmin->main();
      }
      # �������݊������
      else if (@$this->f['f']) {
        $this->prtputcomplete();
      }
      else {
        $this->prttreeview();
      }
    }
    # ���ݒ��ʕ\��
    else if (@$this->f['setup']) {
      $this->prtcustom('tree');
    }
    # �X���b�h�̃c���[�\��
    else if (@$this->f['s']) {
      $this->prtthreadtree();
    }
    # �c���[�r���[���C�����
    else {
      $this->prttreeview();
    }

    if ($this->c['GZIPU']) {
      ob_end_flush();
    }
  }





  /**
   * �c���[�r���[��\��
   *
   * @todo  �ꕔ�̃��O���폜�E����Ă���ꍇ�̑΍�
   */
  function prttreeview($retry = FALSE) {

    # �\�����b�Z�[�W�擾
    list ($logdata, $bindex, $eindex, $lastindex) = $this->getdispmessage();

    $isreadnew = FALSE;
    if ((@$this->f['readnew'] or ($this->s['MSGDISP'] == '0' and $bindex == 1)) and @$this->f['p'] > 0) {
      $isreadnew = TRUE;
    }

    $customstyle = $this->t->getParsedTemplate('tree_customstyle');

    # HTML�w�b�_�����o��
    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE'] . ' �c���[�r���[', '', $customstyle);

    # �t�H�[������
    $dtitle = "";
    $dmsg = "";
    $dlink = "";
    if ($retry) {
      $dtitle = @$this->f['t'];
      $dmsg = @$this->f['v'];
      $dlink = @$this->f['l'];
    }
    $forminput = '<input type="hidden" name="m" value="tree" /><input type="hidden" name="treem" value="p" />';
    $this->setform ($dtitle, $dmsg, $dlink, $forminput);

    # ���C���㕔
    $this->t->displayParsedTemplate('treeview_upper');

    $threadindex = 0;

    # �ŏI�������ݎ������ŐV�̃X���b�h���ɏ���
    while (count($logdata) > 0) {

      $msgcurrent = $this->getmessage(array_shift($logdata));
      if (!$msgcurrent['THREAD']) {
        $msgcurrent['THREAD'] = $msgcurrent['POSTID'];
      }

      # �X���b�h��$logdata���璊�o���A�X���b�h�̃��b�Z�[�W�z�� $thread ���쐬
      $thread = array($msgcurrent);
      $i = 0;
      while ($i < count($logdata)) {
        $message = $this->getmessage($logdata[$i]);
        if ($message['THREAD'] == $msgcurrent['THREAD']
          or $message['POSTID'] == $msgcurrent['THREAD']) {
          array_splice($logdata, $i, 1);
          $thread[] = $message;
          # ���̔���
          if ($message['POSTID'] == $message['THREAD'] or !$message['THREAD']) {
            break;
          }
        }
        else {
          $i++;
        }
      }

      # ���ǃ����[�h
      if ($isreadnew) {
        $hit = FALSE;
        for ($i = 0; $i < count($thread); $i++) {
          if ($thread[$i]['POSTID'] > $this->f['p']) {
            $hit = TRUE;
            break;
          }
        }
        if (!$hit) {
          continue;
        }
      }
      else if ($this->s['MSGDISP'] < 0) {
        break;
      }
      # �J�nindex
      else if ($threadindex < $bindex - 1) {
        $threadindex++;
        continue;
      }

      #�u�Q�l�v����̎Q��ID���o
      foreach ($thread as $message) {
        if (!@$message['REFID']) {
          if (preg_match("/<a href=\"m=f&s=(\d+)[^>]+>([^<]+)<\/a>$/i", $message['MSG'], $matches)) {
            $message['REFID'] = $matches[1];
          }
          else if (preg_match("/<a href=\"mode=follow&search=(\d+)[^>]+>([^<]+)<\/a>$/i", $message['MSG'], $matches)) {
            $message['REFID'] = $matches[1];
          }
        }
      }

      # $thread �̃e�L�X�g�c���[���o��
      $this->prttexttree($msgcurrent, $thread);

      $threadindex++;

      if ($threadindex > $eindex - 1) {
        break;
      }
    }

    $eindex = $threadindex;

    # ���b�Z�[�W���
    if ($this->s['MSGDISP'] < 0) {
      $msgmore = '';
    }
    else if ($eindex > 0) {
      $msgmore = "�ȏ�́A���ݓo�^����Ă���ŏI�X�V��{$bindex}�Ԗڂ���{$eindex}�Ԗڂ܂ł̃X���b�h�ł��B";
    }
    else {
      $msgmore = '���ǃ��b�Z�[�W�͂���܂���B';
    }
    if (count($logdata) == 0) {
      $msgmore .= '����ȉ��̃X���b�h�͂���܂���B';
    }
    $this->t->addVar('treeview_lower', 'MSGMORE', $msgmore);


    # �i�r�Q�[�g�{�^��
    if ($eindex > 0) {
      if ($eindex >= $lastindex) {
        $this->t->setAttribute("nextpage", "visibility", "hidden");
      }
      else {
        $this->t->addVar('nextpage', 'EINDEX', $eindex);
      }
      if (!$this->c['SHOW_READNEWBTN']) {
        $this->t->setAttribute("readnew", "visibility", "hidden");
      }
    }

    # �Ǘ��ғ��e
    if ($this->c['BBSMODE_ADMINONLY'] == 0) {
      $this->t->setAttribute("adminlogin", "visibility", "hidden");
    }

    # ���C������
    $this->t->displayParsedTemplate('treeview_lower');

    print $this->prthtmlfoot ();
  }





  /**
   * �e�L�X�g�c���[�o��
   *
   * @param   Array   &$msgcurrent  �e���b�Z�[�W
   * @param   Array   &$thread      �e�q���܂ރ��b�Z�[�W�̔z��
   */
  function prttexttree(&$msgcurrent, &$thread) {

    print "<pre><a href=\"{$this->s['DEFURL']}&amp;m=t&amp;s={$msgcurrent['THREAD']}\" target=\"link\">{$this->c['TXTTHREAD']}</a>";
    $msgcurrent['WDATE'] = Func::getdatestr($msgcurrent['NDATE']);
    print "<span class=\"update\"> [�X�V���F{$msgcurrent['WDATE']}]</span>\r";
    $tree =& $this->gentree(array_reverse($thread), $msgcurrent['THREAD']);
    $tree = str_replace("</span><span class=\"bc\">", "", $tree);
    $tree = str_replace("</span>�@<span class=\"bc\">", "�@", $tree);
    $tree = '�@' . str_replace("\r", "\r�@", $tree);
    print $tree . "</pre>\n\n<hr />\n\n";

  }




  /**
   * �e�L�X�g�c���[�����̍ċA�����֐�
   *
   * @param   Array   &$treemsgs  �e�q���܂ރ��b�Z�[�W�̔z��
   * @param   Integer $parentid   �eID
   * @return  String  &$treeprint �e�q�̃c���[������
   */
  function &gentree(&$treemsgs, $parentid) {

    # �c���[������
    $treeprint = '';

    # �e���b�Z�[�W�̏o��
    reset($treemsgs);
    while (list($pos, $treemsg) = each($treemsgs)) {
      if ($treemsg['POSTID'] == $parentid) {

        # �Q�l�̏���
        $treemsg['MSG'] = preg_replace("/<a href=[^>]+>�Q�l�F[^<]+<\/a>/i", "", $treemsg['MSG'], 1);

        # ���p�̏���
        $treemsg['MSG'] = preg_replace("/(^|\r)&gt;[^\r]*/", "", $treemsg['MSG']);
        $treemsg['MSG'] = preg_replace("/^\r+/", "", $treemsg['MSG']);
        $treemsg['MSG'] = rtrim($treemsg['MSG']);

        # �t�H���[��ʂւ̃����N
        $treeprint .= "<a href=\"{$this->s['DEFURL']}&amp;m=f&amp;s={$parentid}\" target=\"link\">{$this->c['TXTFOLLOW']}</a>";

        # ���e�Җ�
        if ($treemsg['USER'] and $treemsg['USER'] != $this->c['ANONY_NAME']) {
          $treeprint .= "���e�ҁF".preg_replace("/<[^>]*>/", '', $treemsg['USER'])."\r";
        }

        # �V���\��
        if (@$this->f['p'] > 0 and $treemsg['POSTID'] > $this->f['p']) {
          $treemsg['MSG'] = '<span class="newmsg">' . $treemsg['MSG'] . '</span>';
        }

        # �摜BBS�̉摜���\��
        $treemsg['MSG'] = Func::conv_imgtag($treemsg['MSG']);

        $treeprint .= $treemsg['MSG'];

        # �z�񂩂����
        array_splice($treemsgs, $pos, 1);
        break;
      }
    }

    # �q��ID���
    $childids = array();
    reset($treemsgs);
    while ($treemsg = each($treemsgs)) {
      if ($treemsg[1]['REFID'] == $parentid) {
        $childids[] = $treemsg[1]['POSTID'];
      }
    }

    # �����q������Ȃ�A�}�u���v���̂΂�
    if ($childids) {
      $treeprint = str_replace("\r", "\r".'<span class="bc">��</span>', $treeprint);
    }
    # �Ȃ���΍s����
    else {
      $treeprint = str_replace("\r", "\r".'�@', $treeprint);
    }

    # �q�̃c���[��������擾���A����
    $childidcount = count($childids) - 1;
    while ($childid = each($childids)) {
      $childtree =& $this->gentree($treemsgs, $childid[1]);

      # �������̎q������Ȃ�A�}�u���v����u���v���̂΂�
      if ($childid[0] < $childidcount) {
        $childtree = '<span class="bc">��</span>' . str_replace("\r", "\r".'<span class="bc">��</span>', $childtree);
      }
      # �Ō�̎q�Ȃ�}�u���v����s����
      else {
        $childtree = '<span class="bc">��</span>' . str_replace("\r", "\r".'�@', $childtree);
      }

      # �q�̃c���[�������e�Ɍ���
      $treeprint .= "\r" . $childtree;
    }

    return $treeprint;
  }





  /**
   * �\���͈͂̃��b�Z�[�W�ƃp�����[�^�̎擾
   *
   * @access  public
   * @return  Array   $logdatadisp  ���O�s�z��
   * @return  Integer $bindex       �J�nindex
   * @return  Integer $eindex       �I�[index
   * @return  Integer $lastindex    �S���O�̏I�[index
   * @return  Integer $msgdisp      �\������
   */
  function getdispmessage() {

    $logdata = $this->loadmessage();

    # ���ǃ|�C���^�i�ŐVPOSTID�j
    $items = @explode (',', $logdata[0], 3);
    $toppostid = @$items[1];

    # �\������
    $msgdisp = Func::fixnumberstr(@$this->f['d']);
    if ($msgdisp === FALSE) {
      $msgdisp = $this->c['TREEDISP'];
    }
    else if ($msgdisp < 0) {
      $msgdisp = -1;
    }
    else if ($msgdisp > $this->c['LOGSAVE']) {
      $msgdisp = $this->c['LOGSAVE'];
    }
    if (@$this->f['readzero']) {
      $msgdisp = 0;
    }

    # �J�nindex
    $bindex = @$this->f['b'];
    if (!$bindex) {
      $bindex = 0;
    }

    # �I�[index
    $eindex = $bindex + $msgdisp;

    # ���ǃ����[�h
    if ((@$this->f['readnew'] or ($msgdisp == '0' and $bindex == 0)) and @$this->f['p'] > 0) {
      $bindex = 0;
      $eindex = 0;
    }

    # �Ō�̃y�[�W�̏ꍇ�A�؂�l��
    $lastindex = count($logdata);
    if ($eindex > $lastindex) {
      $eindex = $lastindex;
    }

    # -1���\��
    if ($msgdisp < 0) {
      $bindex = 0;
      $eindex = 0;
    }

    $this->s['TOPPOSTID'] = $toppostid;
    $this->s['MSGDISP'] = $msgdisp;
    return array($logdata, $bindex + 1, $eindex, $lastindex);
  }





  /**
   * �ʃX���b�h�̃c���[�\��
   *
   */
  function prtthreadtree() {

    if (!@$this->f['s']) {
      $this->prterror ( '�p�����[�^������܂���B' );
    }

    $customstyle = <<<__XHTML__
  .bc { color:#{$this->c['C_BRANCH']}; }
  .update { color:#{$this->c['C_UPDATE']}; }
  .newmsg { color:#{$this->c['C_NEWMSG']}; }

__XHTML__;

    $this->sethttpheader();
    print $this->prthtmlhead ($this->c['BBSTITLE'] . ' �c���[�\��', '', $customstyle);
    print "<hr />\n";

    $result = $this->msgsearchlist('t');
    if (@$this->f['ff']) {
      $msgcurrent = $result[count($result) - 1];
    }
    else {
      $msgcurrent = $result[0];
    }
    $this->prttexttree($msgcurrent, $result);

    print <<<__XHTML__
<span class="bbsmsg"><a href="{$this->s['DEFURL']}">�߂�</a></span>
__XHTML__;

    print $this->prthtmlfoot ();

  }





}


?>