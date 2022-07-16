<?php

/*

�����͂�����Ղ�PHP ver0.0.7alpha (13:04 2003/02/18)
�摜�A�b�v���[�h�@�\��BBS���W���[��

* Todo

* Memo


*/

if(!defined("INCLUDED_FROM_BBS")) {
  header ("Location: ../bbs.php");
  exit();
}


/*
 * ���W���[���ŗL�ݒ�
 *
 * $CONF�ɒǉ��E�㏑������܂��B
 */
$GLOBALS['CONF_IMAGEBBS'] = array(

  # �摜�A�b�v���[�h�f�B���N�g���i�����ɐݒ肵�Ă��������j
  'UPLOADDIR' => './upload/',

  # �摜�A�b�v���[�h�p�ŐV�t�@�C���ԍ��L�^�t�@�C���i�����ɐݒ肵�Ă��������j
  'UPLOADIDFILE' => './upload/id.txt',

  # ���e���e�ɂ��̕����񂪂���Ƃ��̈ʒu�ɃA�b�v���[�h�摜���}������܂�
  'IMAGETEXT' => '%image',

  # �ۑ�����摜�̑��e��(KB)
  'MAX_UPLOADSPACE' => 10000,

  # �A�b�v���[�h����摜�̉����ő�l
  'MAX_IMAGEWIDTH' => 1280,

  # �A�b�v���[�h����摜�̏c���ő�l
  'MAX_IMAGEHEIGHT' => 1600,

  # �A�b�v���[�h����摜�T�C�Y�̍ő�l(KB)
  'MAX_IMAGESIZE' => 200,

  # �f���ɕ\������ۂ̉摜�k�ڗ�(��)
  'IMAGE_PREVIEW_RESIZE' => 100,

);




// �C���N���[�h�t�@�C���p�X


/* �N�� */
{
  if (!ini_get('file_uploads')) {
    print '�G���[�F�t�@�C���A�b�v���[�h�@�\��������Ă��܂���B';
    exit();
  }
  if (!function_exists('GetImageSize')) {
    print '�G���[�F�摜�����@�\���T�|�[�g����Ă��܂���B';
    exit();
  }
}




/**
 * �摜�A�b�v���[�h�@�\��BBS���W���[��
 *
 *
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Imagebbs extends Bbs {

  /**
   * �R���X�g���N�^
   *
   */
  function Imagebbs() {
    $GLOBALS['CONF'] = array_merge ($GLOBALS['CONF'], $GLOBALS['CONF_IMAGEBBS']);
    $this->Bbs();
  }





  /**
   * �l�p�ݒ蔽�f
   */
  function refcustom() {
    $this->c['SHOWIMG'] = 1;

    parent::refcustom();
  }





  /**
   * �t�H�[�������\��
   *
   * @access  public
   * @param   String  $dtitle     �薼�̃t�H�[�������l
   * @param   String  $dmsg       ���e�̃t�H�[�������l
   * @param   String  $dlink      �����N�̃t�H�[�������l
   * @return  String  �t�H�[����HTML�f�[�^
   */
  function setform($dtitle, $dmsg, $dlink, $mode = '') {
    if ($this->c['SHOWIMG']) $this->t->addVar('sicheck', 'CHK_SI', ' checked="checked"');
    $this->t->addVar('postform', 'MAX_FILE_SIZE', $this->c['MAX_IMAGESIZE'] * 1024);
    $this->t->addVar('postform', 'mode', 'image');
    $this->t->setAttribute('sicheck', 'visibility', 'visible');
    return parent::setform($dtitle, $dmsg, $dlink, $mode);
  }





  /**
   * �t�H�[�����͂���̃��b�Z�[�W�擾
   *
   * @access  public
   * @return  Array  ���b�Z�[�W�z��
   */
  function getformmessage() {

    $message = parent::getformmessage();

    if (!is_array($message)) {
      return $message;
    }

    # �A�b�v���[�h�t�@�C���̊m�F
    if ($_FILES['file']['name']) {

      if ($_FILES['file']['error'] == 2
      or (file_exists($_FILES['file']['tmp_name'])
      and filesize($_FILES['file']['tmp_name']) > ($this->c['MAX_IMAGESIZE'] * 1024))) {
        $this->prterror( '�t�@�C���T�C�Y��' .$this->c['MAX_IMAGESIZE'] .'KB�𒴂��Ă��܂��B');
      }

      if ($_FILES['file']['error'] > 0
      or !is_uploaded_file($_FILES['file']['tmp_name'])) {
        $this->prterror( '�t�@�C���A�b�v���[�h�̏����Ɏ��s���܂����B�R�[�h:' . $_FILES['file']['error']);
      }

      # �摜�A�b�v���[�h�v���Z�X�̃��b�N
      $fh = @fopen($this->c['UPLOADIDFILE'], "rb+");
      if (!$fh) {
        $this->prterror ( '�A�b�v���[�h�L�^�t�@�C���̓ǂݍ��݂Ɏ��s���܂���' );
      }
      flock ($fh, 2);

      # �t�@�C��ID�̊l��
      $fileid = trim(fgets ($fh, 10));
      if (!$fileid) {
        $fileid = 0;
      }

      # �t�@�C���̎�ރ`�F�b�N
      $imageinfo = GetImageSize($_FILES['file']['tmp_name']);
      if ($imageinfo[0] > $this->c['MAX_IMAGEWIDTH'] or $imageinfo[1] > $this->c['MAX_IMAGEHEIGHT']) {
        unlink ($_FILES['file']['tmp_name']);
        $this->prterror ( '�摜�̕������ʂ𒴂��Ă��܂��B' );
      }

      # GIF
      if ($imageinfo[2] == 1) {
        $filetype = 'GIF';
        $fileext = '.gif';
      }
      # JPG
      else if ($imageinfo[2] == 2) {
        $filetype = 'JPG';
        $fileext = '.jpg';
      }
      # PNG
      else if ($imageinfo[2] == 3) {
        $filetype = 'PNG';
        $fileext = '.png';
      }
      else {
        unlink ($_FILES['file']['tmp_name']);
        $this->prterror ('�t�@�C���̌`��������������܂���B');
      }

      $fileid++;
      $filename = $this->c['UPLOADDIR'] . str_pad($fileid, 5, "0", STR_PAD_LEFT) . '_' . date("YmdHis", CURRENT_TIME) . $fileext;

      copy ($_FILES['file']['tmp_name'], $filename);
      unlink ($_FILES['file']['tmp_name']);

      $message['FILEID'] = $fileid;
      $message['FILENAME'] = $filename;
      $message['FILEMSG'] = '�摜'.str_pad($fileid, 5, "0", STR_PAD_LEFT)." $filetype {$imageinfo[0]}*{$imageinfo[1]} ".floor(filesize($filename)/1024)."KB";
      $message['FILETAG'] = "<a href=\"{$filename}\" target=\"link\">"
      . "<img src=\"{$filename}\" width=\"{$imageinfo[0]}\" height=\"{$imageinfo[1]}\" border=\"0\" alt=\"{$message['FILEMSG']}\" /></a>";

      # ���b�Z�[�W�ւ̃^�O���ߍ���
      if (strpos($message['MSG'], $this->c['IMAGETEXT']) !== FALSE) {
        $message['MSG'] = preg_replace("/\Q{$this->c['IMAGETEXT']}\E/", $message['FILETAG'], $message['MSG'], 1);
        $message['MSG'] = preg_replace("/\Q{$this->c['IMAGETEXT']}\E/", '', $message['MSG']);
      }
      else {
        if (preg_match("/\r\r<a href=[^<]+>�Q�l�F[^<]+<\/a>$/", $message['MSG'])) {
          $message['MSG'] = preg_replace("/(\r\r<a href=[^<]+>�Q�l�F[^<]+<\/a>)$/", "\r\r{$message['FILETAG']}$1", $message['MSG'], 1);
        }
        else {
          $message['MSG'] .= "\r\r" . $message['FILETAG'];
        }
      }

      fseek ($fh, 0, 0);
      ftruncate ($fh, 0);
      fwrite ($fh, $fileid);
      flock ($fh, 3);
      fclose ($fh);
    }

    return $message;

  }





  /**
   * ���b�Z�[�W�o�^����
   *
   * @access  public
   * @return  Integer  �G���[�R�[�h
   */
  function putmessage($message) {

    $posterr = parent::putmessage($message);

    if ($posterr) {
      return $posterr;
    }
    else {

      $dirspace = 0;
      $maxspace = $this->c['MAX_UPLOADSPACE'] * 1024;

      $files = array();
      $dh = opendir($this->c['UPLOADDIR']);
      if (!$dh) {
        return;
      }
      while ($entry = readdir($dh)) {
        if (is_file($this->c['UPLOADDIR'] . $entry) and preg_match("/\.(gif|jpg|png)$/i", $entry)) {
          $files[] = $this->c['UPLOADDIR'] . $entry;
          $dirspace += filesize($this->c['UPLOADDIR'] . $entry);
        }
      }
      closedir ($dh);

      # �Â��摜�̍폜
      if ($dirspace > $maxspace) {
        sort($files);
        foreach ($files as $filepath) {
          $dirspace -= filesize($filepath);
          unlink ($filepath);
          if ($dirspace <= $maxspace) {
            break;
          }
        }
      }
    }

  }









}

?>