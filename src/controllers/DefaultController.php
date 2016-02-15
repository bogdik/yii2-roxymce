<?php
/**
 * Created by Navatech.
 * @project yii2-roxymce
 * @author  Phuong
 * @email   phuong17889[at]gmail.com
 * @date    15/02/2016
 * @time    4:19 CH
 */
namespace navatech\roxymce\controllers;

use navatech\roxymce\RoxyMceAsset;
use Yii;
use yii\helpers\Json;
use yii\web\Controller;

class DefaultController extends Controller {

	public function actionIndex() {
		$roxyMceAsset = RoxyMceAsset::register($this->getView());
		return $this->renderPartial('index', ['roxyMceAsset' => $roxyMceAsset->baseUrl]);
	}

	public function actionConfig() {
		$config = [
			'FILES_ROOT'           => '',
			'RETURN_URL_PREFIX'    => '',
			'SESSION_PATH_KEY'     => '',
			'THUMBS_VIEW_WIDTH'    => '140',
			'THUMBS_VIEW_HEIGHT'   => '120',
			'PREVIEW_THUMB_WIDTH'  => '100',
			'PREVIEW_THUMB_HEIGHT' => '100',
			'MAX_IMAGE_WIDTH'      => '1000',
			'MAX_IMAGE_HEIGHT'     => '1000',
			'INTEGRATION'          => 'tinymce4',
			'DIRLIST'              => 'php/dirtree.php',
			'CREATEDIR'            => 'php/createdir.php',
			'DELETEDIR'            => 'php/deletedir.php',
			'MOVEDIR'              => 'php/movedir.php',
			'COPYDIR'              => 'php/copydir.php',
			'RENAMEDIR'            => 'php/renamedir.php',
			'FILESLIST'            => 'php/fileslist.php',
			'UPLOAD'               => 'php/upload.php',
			'DOWNLOAD'             => 'php/download.php',
			'DOWNLOADDIR'          => 'php/downloaddir.php',
			'DELETEFILE'           => 'php/deletefile.php',
			'MOVEFILE'             => 'php/movefile.php',
			'COPYFILE'             => 'php/copyfile.php',
			'RENAMEFILE'           => 'php/renamefile.php',
			'GENERATETHUMB'        => 'php/thumb.php',
			'DEFAULTVIEW'          => 'list',
			'FORBIDDEN_UPLOADS'    => 'zip js jsp jsb mhtml mht xhtml xht php phtml php3 php4 php5 phps shtml jhtml pl sh py cgi exe application gadget hta cpl msc jar vb jse ws wsf wsc wsh ps1 ps2 psc1 psc2 msh msh1 msh2 inf reg scf msp scr dll msi vbs bat com pif cmd vxd cpl htpasswd htaccess',
			'ALLOWED_UPLOADS'      => '',
			'FILEPERMISSIONS'      => '0644',
			'DIRPERMISSIONS'       => '0755',
			'LANG'                 => 'fr',
			'DATEFORMAT'           => 'dd/MM/yyyy HH:mm',
			'OPEN_LAST_DIR'        => 'yes',
		];
		echo Json::encode($config);
		Yii::$app->end();
	}
}