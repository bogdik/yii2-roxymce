<?php
/**
 * Created by Bogdik.
 * @project RoxyMce
 * @author  Bogdik
 * @email   bogdikxxx[at]gmail.com
 * @date    01/02/2017
 * @time    2:56 CH
 * @version 2.1.0
 * @var View       $this
 * @var Module     $module
 * @var UploadForm $uploadForm
 * @var string     $defaultFolder
 * @var int        $defaultOrder
 * @var string     $fileListUrl
 */
namespace bogdik\roxymce\widgets;

use Yii;
use yii\base\InvalidParamException;
use yii\web\HttpException;
use yii\base\Widget;
use bogdik\roxymce\assets\RoxyMceAsset;
use bogdik\roxymce\helpers\FolderHelper;
use bogdik\roxymce\models\UploadForm;
use yii\bootstrap\Html;
use yii\helpers\Url;
use yii\web\View;

/**
 * This is RoxyMce Filemanager widget, call '.RoxyFilemanWidget::widget([]).'
 * {@inheritDoc}
 */
class RoxyFilemanWidget extends Widget {
    public $type = 'images';
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
     * @var bool Do not change the file extension from
     */
    public $NoChangeFileExt = true;

    /**
     * @var bool No show buttons on Footer (Insert and Close)
     */
    public $NoFooterButton = false;

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
        RoxyMceAsset::register($this->view);
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
        if(!Yii::$app->cache->exists('roxy_last_order')) {
            Yii::$app->cache->set('roxy_last_folder', $this->NoAlias ? $this->uploadFolder : Yii::getAlias($this->uploadFolder));
        }
    }

	/**
	 * Executes the widget.
	 * @return string the result of widget execution to be outputted.
	 * @throws InvalidParamException
	 */
	public function run() {
        $module        = $this;
        $defaultFolder = '';
        $defaultOrder  = FolderHelper::SORT_DATE_DESC;
        Yii::$app->cache->set('roxy_file_type', $this->type);
        if ($module->rememberLastFolder && Yii::$app->cache->exists('roxy_last_folder')) {
            $defaultFolder = Yii::$app->cache->get('roxy_last_folder');
        }
        if ($module->rememberLastOrder && Yii::$app->cache->exists('roxy_last_order')) {
            $defaultOrder = Yii::$app->cache->get('roxy_last_order');
        }
        $fileListUrl = Url::to([
            '/roxymce/management/file-list',
            'folder' => $defaultFolder,
            'sort'   => $defaultOrder,
        ]);
    echo '
<div class="wrapper" style="width:850px;height: 475px;display: inline-block;">
	<section class="body">
		<div class="col-sm-4 left-body">
			<div class="actions">
				<button type="button" class="btn btn-sm btn-primary" onclick="fancyCreate()" title="'. Yii::t('roxy', 'Create new folder').'">
					<i class="fa fa-plus-square"></i>'.Yii::t('roxy', 'Create').'
                </button>
                <button type="button" class="btn btn-sm btn-warning" onclick="fancyRename(\'folder\')" title="'. Yii::t('roxy', 'Rename selected folder') .'">
                    <i class="fa fa-pencil-square"></i>'. Yii::t('roxy', 'Rename').'
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="fancyConfirm(\''. Yii::t('roxy', 'Are you sure you want to delete folder') .'\'+\': \'+$(\'.node-selected\').text(),fancyRemoveFolder)" title="'. Yii::t('roxy', 'Delete selected folder') .'">
                    <i class="fa fa-trash"></i>'. Yii::t('roxy', 'Delete') .'
                </button>
             </div>
             <div class="scrollPane folder-list" data-url="'. Url::to(['/roxymce/management/folder-list']) .'">
                    <div class="folder-list-item"></div>
             </div>
        </div>
        <div class="col-sm-8 right-body">
            <div class="actions first-row">
                <div class="row">
                    <div class="col-sm-12">
                           <label class="btn btn-sm btn-primary" title="'. Yii::t('roxy', 'Upload files') .'">';
                    echo Html::activeFileInput((new UploadForm()), 'file', [
                        'multiple'  => true,
                        'name'      => 'UploadForm[file][]',
                        'data-href' => $fileListUrl,
                        'data-url'  => Url::to([
                            '/roxymce/management/file-upload',
                            'folder' => $defaultFolder,
                        ]),
                    ]);
        echo '<i class="fa fa-plus"></i>'. Yii::t('roxy', 'Add file') .'
                </label>
                <a class="btn btn-sm btn-info btn-file-preview" disabled="disabled" title="'. Yii::t('roxy', 'Preview selected file') .'">
                    <i class="fa fa-search"></i>'. Yii::t('roxy', 'Preview').'
                </a>
                <button type="button" id="btn-file-rename" class="btn btn-sm btn-warning" onclick="fancyRename(\'file\')" disabled="disabled" title="'. Yii::t('roxy', 'Rename file') .'">
                    <i class="fa fa-pencil"></i> '. Yii::t('roxy', 'Rename file') .'
                </button>
                <a class="btn btn-sm btn-success btn-file-download" disabled="disabled" title="'. Yii::t('roxy', 'Download file') .'">
                    <i class="fa fa-download"></i> '. Yii::t('roxy', 'Download') .'
                </a>
                <button type="button" id="btn-file-remove" class="btn btn-sm btn-danger" disabled="disabled" onclick="fancyConfirm(\''. Yii::t('roxy', 'Are you sure you want to delete file') .'\'+\': \'+$(\'.btn-file-preview\').attr(\'title\') ,fancyRemoveFile)" title="'. Yii::t('roxy', 'Delete file') .'">
                    <i class="fa fa-trash"></i> '. Yii::t('roxy', 'Delete file') .'
                </button>
            </div>
        </div>
    </div>
    <div class="actions second-row">
        <div class="row">
            <div class="col-sm-4">';
                if($_COOKIE["roxyFileMan"]){
                    if($_COOKIE["roxyFileMan"] == 'list_view'){$sv_list='btn-primary'; $sv_th='';} else {$sv_list=''; $sv_th='btn-primary';}
                }
                else if($this->defaultView != 'list'){$sv_list='btn-primary'; $sv_th='';} else {$sv_list=''; $sv_th='btn-primary';}
                $lvtitle=Yii::t('roxy', 'List view');
                $thvtitle=Yii::t('roxy', 'Thumbnails view');
                echo "<button type=\"button\" data-action=\"switch_view\" data-name=\"list_view\" class=\"btn btn-default $sv_list\" title=\"$lvtitle\">";
                echo'    <i class="fa fa-list"></i>
                </button>';
                echo "<button type=\"button\" data-action=\"switch_view\" data-name=\"thumb_view\" class=\"btn btn-default $sv_th\" title=\"$thvtitle\">";
                echo '<i class="fa fa-picture-o"></i>
                </button>
            </div>
            <div class="col-sm-8">
                <div class="form-inline">
                    <div class="form-group form-group-sm form-search">
                        <input id="txtSearch" type="text" class="form-control" placeholder="'. Yii::t('roxy', 'Search for...') .'">
                        <i class="fa fa-search"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="file-body">
        <div class="scrollPane file-list" data-url="'. $fileListUrl .'">';
        if($this->defaultView == 'list'){ $sort_act='block';} else {$sort_act='none';}
            echo "<div class=\"sort-actions\" style=\"display: $sort_act;\">";
               echo' <div class="row">
                    <div class="col-sm-7">';
                        if($defaultOrder == FolderHelper::SORT_NAME_ASC || $defaultOrder == FolderHelper::SORT_NAME_DESC) { $class_name_sort='sorted';} else {  $class_name_sort='';}
                        if($defaultOrder == FolderHelper::SORT_NAME_ASC) {$name_sort='asc'; } else { $name_sort='desc';}
                        echo "<div class=\"pull-left $class_name_sort\" rel=\"order\" data-order=\"name\" data-sort=\"$name_sort\">";
                        echo '    <i class="fa fa-long-arrow-up"></i>
                            <i class="fa fa-long-arrow-down"></i>
                            <span> '. Yii::t('roxy', 'Name') .'</span>
                        </div>
                    </div>
                    <div class="col-sm-2">';
                        if($defaultOrder == FolderHelper::SORT_SIZE_ASC || $defaultOrder == FolderHelper::SORT_SIZE_DESC) { $class_size_sort='sorted';} else {  $class_size_sort='';}
                        if($defaultOrder == FolderHelper::SORT_SIZE_ASC) {$size_sort='asc'; } else { $size_sort='desc';}
                        echo "<div class=\"pull-right  $class_size_sort\" rel=\"order\" data-order=\"size\" data-sort=\"$size_sort\">";
                        echo '    <i class="fa fa-long-arrow-up"></i>
                            <i class="fa fa-long-arrow-down"></i>
                            <span> '. Yii::t('roxy', 'Size') .'</span>
                        </div>
                    </div>
                    <div class="col-sm-3">';
                        if($defaultOrder == FolderHelper::SORT_DATE_ASC || $defaultOrder == FolderHelper::SORT_DATE_DESC) { $class_date_sort='sorted';} else {  $class_date_sort='';}
                        if($defaultOrder == FolderHelper::SORT_DATE_ASC) {$date_sort='asc'; } else { $date_sort='desc';}
                        echo "<div class=\"pull-right $class_date_sort\" rel=\"order\" data-order=\"date\" data-sort=\"$date_sort\">";
                         echo '<i class="fa fa-long-arrow-up"></i>
                            <i class="fa fa-long-arrow-down"></i>
                            <span> '. Yii::t('roxy', 'Date') .'</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="file-list-item"></div>
        </div>
    </div>
</div>
</section>
<section class="footer" style="bottom: -15px;">
    <div class="row bottom">
        <div class="col-sm-6 pull-left">
            <div class="progress" style="display: none;">
                <div class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">

                </div>
            </div>
        </div>';
        if(!$this->NoFooterButton) {
            echo '<div class="col-sm-3 col-sm-offset-3 pull-right">
            <button type="button" class="btn btn-success btn-roxymce-select" disabled title="' . Yii::t('roxy', 'Select highlighted file') . '">
                <i class="fa fa-check"></i> ' . Yii::t('roxy', 'Select') . '
            </button>
            <button type="button" class="btn btn-default btn-roxymce-close">
                <i class="fa fa-ban"></i> ' . Yii::t('roxy', 'Close') . '
            </button>
        </div>';
        }
    echo '</div>
</section>
</div>
<div class="modal fade" id="folder-create">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">'. Yii::t('roxy', 'Create new folder') .'</h4>
            </div>
            <div class="modal-body">
            <div id="form-folder-create">
                <form action="'. Url::to(['/roxymce/management/folder-create']) .'" method="get" role="form">
                    <input type="hidden" name="folder" value="">
                    <div class="form-group">
                        <input type="text" class="form-control" name="name" id="folder_name" placeholder="'. Yii::t('roxy', 'Folder\'s name') .'">
                    </div>
                </form>
    
                <button type="button" class="btn btn-primary btn-submit" onclick="fancyCreateFolder()">'. Yii::t('roxy', 'Save') .'</button>
                <button type="button" class="btn btn-default" onclick="$.fancybox.close();" data-dismiss="modal">'. Yii::t('roxy', 'Close') .'</button>
             </div>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="folder-rename">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">'. Yii::t('roxy', 'Rename selected folder') .'</h4>
            </div>
            <div class="modal-body">
            <div id="form-folder-rename">
                <form action="'. Url::to(['/roxymce/management/folder-rename']) .'" method="get" role="form">
                    <input type="hidden" name="folder" value="">
                    <div class="form-group">
                        <input type="text" class="form-control" name="name" id="folder_name" placeholder="'. Yii::t('roxy', 'Folder\'s name') .'">
                    </div>
                </form>
                <button type="button" class="btn btn-primary btn-submit" onclick="fancyRenameFolder()">'. Yii::t('roxy', 'Save') .'</button>
                <button type="button" class="btn btn-default" onclick="$.fancybox.close();" data-dismiss="modal">'. Yii::t('roxy', 'Close') .'</button>
             </div>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="file-rename">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">'. Yii::t('roxy', 'Rename selected file') .'</h4>
            </div>
            <div class="modal-body">
                <div id="form-file-rename">
                <form action="'. Url::to(['/roxymce/management/file-rename']) .'" method="get" role="form" >
                    <input type="hidden" name="folder" value="">
                    <input type="hidden" name="file" value="">
                    <div class="form-group">
                        <input type="text" class="form-control" name="name" id="file_name" placeholder="'. Yii::t('roxy', 'File\'s name') .'">
                    </div>
                </form>
                <button type="button" class="btn btn-primary btn-submit" onclick="fancyRenameFile()">'. Yii::t('roxy', 'Save') .'</button>
                <button type="button" class="btn btn-default" onclick="$.fancybox.close();">'. Yii::t('roxy', 'Close') .'</button>
                </div>
            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>
</div>';
        $this->view->registerJsFile('js/roxy.js');
        $this->view->registerJs('showFolderList(folder_list.data(\'url\'));
        showFileList($(".file-list").data(\'url\'));
        reinit_right_click();
        $("#modal.modal-body > a#single_image").fancybox();', View::POS_END);


    }
}