<?php
/**
 * Created by Navatech.
 * @project RoxyMce
 * @author  Le Phuong
 * @email   phuong17889[at]gmail.com
 * @date    15/02/2016
 * @time    4:33 CH
 * @version 2.0.0
 */
namespace bogdik\roxymce;

use Yii;
use yii\base\InvalidParamException;
use yii\web\HttpException;

/**
 * {@inheritDoc}
 */
class Module extends \bogdik\base\Module {

	/**
	 * @var string default folder which will be used to upload resource
	 *             must be start with @
     *             If you use AddUserIdToPath then [userid] must be add to string
	 */
	public $uploadFolder = '@app/web/uploads/images';

	/**
	 * @var string url of $uploadFolder
	 *             not include 'http://domain.com'
	 *             must be start with /
     *             If you use AddUserIdToPath then [userid] must be add to string
	 */
	public $uploadUrl = '/uploads/images';
    /**
     * @var string root user directory need for disk size limit feature
     *             must be start with @
     *             If you use AddUserIdToPath then [userid] must be add to string
     *             example uploads/[userid]
     *             this var must be included in var uploadFolder
     */
    public $userRootdir = '';
	/**
	 * @var string default view type
	 */
	public $defaultView = 'thumb';

	/**
	 * @var string default display dateFormat
	 * @see http://php.net/manual/en/function.date.php
	 */
	public $dateFormat = 'Y-m-d H:i';

	/**
	 * @var bool would you want to remember last folder?
	 */
	public $rememberLastFolder = true;

	/**
	 * @var bool would you want to remember last sort order?
	 */
	public $rememberLastOrder = true;

    /**
     * @var bool access only autorized users?
     */
    public $onlyAutorizeUsers = true;

    /**
     * @var bool Added user id to path extend on onlyAutorizeUsers
     */
    public $AddUserIdToPath = true;

    /**
     * @var bool No alias in path uploadFolder
     */
    public $NoAlias = true;
    /**
     * @var bool Limit disk size for user
     */
    public $DiskSizeLimit = false;
    /**
     * @var int Limit disk size value for user in bytes Default 512mb.
     */
    public $limitValue = 536870912;
    /**
     * @var bool Do not change the file extension from
     */
    public $NoChangeFileExt = true;
    /**
     * @var bool No show buttons on Footer (Insert and Close)
     */
    public $NoFooterButton = false;
    /**
     * @var bool No img filetypes preview, enable this may be dangerous xss
     */
    public $NoImgPreview = false;
    /**
     * @var string top ratio
     */
    public $topRatio = '0.1';
	/**
	 * @var string default allowed files extension
	 */
	public $allowExtension = 'jpeg jpg png gif svg mov mp3 mp4 avi wmv flv mpeg webm ogg';

	/**
	 * Initializes the module.
	 *
	 * This method is called after the module is created and initialized with property values
	 * given in configuration. The default implementation will initialize [[controllerNamespace]]
	 * if it is not set.
	 *
	 * If you override this method, please make sure you call the parent implementation.
	 * @throws InvalidParamException
	 */
	public function init() {
        //echo $this->NoAlias ? $this->uploadFolder : Yii::getAlias($this->uploadFolder);exit;
	    if($this->onlyAutorizeUsers && Yii::$app->user->getIsGuest()) {
            throw new HttpException(503 ,'Access denied');
        }
		parent::init();
        if($this->onlyAutorizeUsers && $this->AddUserIdToPath && strripos($this->uploadFolder, '[userid]') && strripos($this->uploadUrl, '[userid]')) {
            $this->uploadFolder=str_replace("[userid]", Yii::$app->user->identity->getId(), $this->uploadFolder);
            $this->uploadUrl=str_replace("[userid]", Yii::$app->user->identity->getId(), $this->uploadUrl);
        }
		if (!is_dir($this->NoAlias ? $this->uploadFolder : Yii::getAlias($this->uploadFolder))) {
			mkdir($this->NoAlias ? $this->uploadFolder : Yii::getAlias($this->uploadFolder), 0777, true);
		}
        if($this->onlyAutorizeUsers && $this->DiskSizeLimit && strripos($this->uploadFolder, '[userid]') &&
            !$this->userRootdir && strripos($this->uploadFolder, $this->userRootdir) && is_int($this->limitValue)
        ) {
            $this->userRootdir=strripos($this->userRootdir, '[userid]');
            $path_to_file_limit=$this->userRootdir. DIRECTORY_SEPARATOR .Yii::$app->user->identity->getId().'.txt';
            file_put_contents ($path_to_file_limit,$this->limitValue);
        }
		if(!Yii::$app->cache->exists('roxy_last_order')) {
			Yii::$app->cache->set('roxy_last_folder', $this->NoAlias ? $this->uploadFolder : Yii::getAlias($this->uploadFolder));
		}
	}
}
