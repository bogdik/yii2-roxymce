<?php
/**
 * Created by Navatech.
 * @project roxymce
 * @author  Le Phuong
 * @email   phuong17889[at]gmail.com
 * @date    15/02/2016
 * @time    4:19 CH
 * @version 2.0.0
 */
namespace bogdik\roxymce\controllers;

use bogdik\roxymce\helpers\FileHelper;
use bogdik\roxymce\helpers\FolderHelper;
use bogdik\roxymce\helpers\Punycode;
use bogdik\roxymce\models\UploadForm;
use bogdik\roxymce\Module;
use Yii;
use yii\base\ErrorException;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\helpers\BaseInflector;

/**
 * @property Module $module
 */
class ManagementController extends Controller {

	public $enableCsrfValidation = false;

	/**
	 * {@inheritDoc}
	 */
	public function behaviors() {
		$behaviors                      = parent::behaviors();
		$behaviors['contentNegotiator'] = [
			'class'   => ContentNegotiator::className(),
			'formats' => [
				'application/json' => Response::FORMAT_JSON,
			],
		];
		$behaviors['verbs']             = [
			'class'   => VerbFilter::className(),
			'actions' => [
				'*'           => [
					'GET',
					'AJAX',
				],
				'file-upload' => [
					'POST',
					'AJAX',
				],
			],
		];
		return $behaviors;
	}

	/**
	 * @param        $name
	 * @param string $folder
	 *
	 * @return array
	 */
	public function actionFolderCreate($name, $folder = '') {
		if ($folder == '') {
			$folder = $this->module->NoAlias ? $this->module->uploadFolder : Yii::getAlias($this->module->uploadFolder);
		}
        $name=strip_tags($name);
        $name=FileHelper::filenameClean(FileHelper::filenameTranslitirate($name));
        if(stristr($name, './') || stristr($name, '.\\') || !$name){
            return [
                'error'   => 1,
                'message' => Yii::t('roxy', 'Wrong name'),
            ];
        }
		$folder = $this->module->NoAlias ? $folder : realpath($folder);
		if (is_dir($folder)) {
			if (file_exists($folder . DIRECTORY_SEPARATOR . $name)) {
				$response = [
					'error'   => 1,
					'message' => Yii::t('roxy', 'Folder existed'),
				];
			} else {
				if (mkdir($folder . DIRECTORY_SEPARATOR . $name, 0777, true)) {
					$response = [
						'error'   => 0,
						'message' => Yii::t('roxy', 'Folder created'),
						'data'    => [
							'href' => Url::to([
								'/roxymce/management/file-list',
								'folder' => $folder . DIRECTORY_SEPARATOR . $name,
							]),
							'text' => $name,
							'path' => $folder . DIRECTORY_SEPARATOR . $name,
						],
					];
				} else {
					$response = [
						'error'   => 1,
						'message' => Yii::t('roxy', 'Can\'t create folder in {0}', [$folder]),
					];
				}
			}
		} else {
			$response = [
				'error'   => 1,
				'message' => Yii::t('roxy', 'Invalid directory {0}', [$folder]),
			];
		}
		return $response;
	}

	/**
	 * @param string $folder
	 *
	 * @return array
	 */
	public function actionFolderList($folder = '') {
		if ($folder == '') {
			$folder = $this->module->NoAlias ? $this->module->uploadFolder : Yii::getAlias($this->module->uploadFolder);
		}
		$folder  = $this->module->NoAlias ? $folder : realpath($folder);
		$content = FolderHelper::folderList($folder);
		return [
			'error'   => 0,
			'content' => $content,
		];
	}

	/**
	 * @param string $folder
	 * @param string $sort
	 *
	 * @return array
	 */
	public function actionFileList($folder = '', $sort = '') {
		/**
		 * @var Module $module
		 */

		$module = Yii::$app->getModule('roxymce');
        if($folder!="" && (($this->module->NoAlias && $this->module->AddUserIdToPath && !stristr($folder, $this->module->uploadFolder)) || stristr($folder, "../"))){
            return [
                'error'   => 1,
                'content' => "Not normal path",
            ];
        }

		$folder = $this->module->NoAlias ? $folder : realpath($folder);

		if ($folder == '') {
			$folder = $this->module->NoAlias ? $this->module->uploadFolder : Yii::getAlias($this->module->uploadFolder);
		}
		if ($module->rememberLastFolder) {
			Yii::$app->cache->set('roxy_last_folder', $folder);
		}
		if ($sort == '') {
			if ($module->rememberLastOrder && Yii::$app->cache->exists('roxy_last_order')) {
				$sort = Yii::$app->cache->get('roxy_last_order');
			} else {
				$sort = FolderHelper::SORT_DATE_DESC;
			}
		}
		if ($module->rememberLastOrder) {
			Yii::$app->cache->set('roxy_last_order', $sort);
		}
		$content = [];
		foreach (FolderHelper::fileList($folder, $sort) as $item) {
			$file      = $folder . DIRECTORY_SEPARATOR . $item;
			$content[] = [
				'is_image' => FileHelper::isImage($item),
				'url'      => FileHelper::fileUrl($file),
				'preview'  => FileHelper::filePreview($file),
				'icon'     => FileHelper::fileIcon($file),
				'name'     => $item,
				'size'     => FileHelper::fileSize(filesize($file), 0),
				'date'     => date($module->dateFormat, filemtime($file)),
			];
		}
		return [
			'error'   => 0,
			'content' => $content,
		];
	}

	/**
	 * @param string $folder
	 * @param        $name
	 *
	 * @return array
	 */
	public function actionFolderRename($folder = '', $name) {
		if ($folder == '') {
			return [
				'error'   => 1,
				'message' => Yii::t('roxy', 'Can\'t rename root folder'),
			];
		}
        $name=strip_tags($name);
        $name=FileHelper::filenameClean(FileHelper::filenameTranslitirate($name));
		$folder    = $this->module->NoAlias ? $folder : realpath($folder);
		$newFolder = dirname($folder) . DIRECTORY_SEPARATOR . $name;

        if(stristr($newFolder, './') || stristr($newFolder, '.\\') || !$name){
            return [
                'error'   => 1,
                'message' => Yii::t('roxy', 'Wrong name'),
            ];
        }
        if (is_dir($newFolder)) {
            return [
                'error'   => 1,
                'message' => Yii::t('roxy', 'Directory already exists'),
            ];
        }
		if (rename($folder, $newFolder)) {
			return [
				'error' => 0,
				'data'  => [
					'href' => Url::to([
						'/roxymce/management/file-list',
						'folder' => $newFolder,
					]),
					'text' => $name,
					'path' => $newFolder,
				],
			];
		} else {
			return [
				'error'   => 1,
				'message' => Yii::t('roxy', 'Somethings went wrong'),
			];
		}
	}

	/**
	 * @param        $folder
	 * @param string $parentFolder
	 *
	 * @return array
	 */
	public function actionFolderRemove($folder, $parentFolder = '') {
		$folder           = $this->module->NoAlias ? $folder : realpath($folder);
        if($this->module->NoAlias && $this->module->uploadFolder==$folder){
            return [
                'error'   => 1,
                'message' => Yii::t('roxy', 'Can\'t delete root folder'),
            ];
        }
        if(stristr($folder, './') || stristr($folder, '.\\')){
            return [
                'error'   => 1,
                'message' => Yii::t('roxy', 'Wrong name'),
            ];
        }
		$folderProperties = FolderHelper::folderList($folder);
		if ($folderProperties != null && isset($folderProperties[0]['nodes']) && $folderProperties[0]['nodes'] != null) {
			return [
				'error'   => 1,
				'message' => Yii::t('roxy', 'Please remove all sub-folder before'),
			];
		}
		foreach (FolderHelper::fileList($folder) as $file) {
			unlink($folder . DIRECTORY_SEPARATOR . $file);
		}
		try {
			if (rmdir($folder)) {
				return [
					'error'   => 0,
					'content' => $parentFolder,
				];
			} else {
				return [
					'error'   => 1,
					'message' => Yii::t('roxy', 'Somethings went wrong'),
				];
			}
		} catch (ErrorException $e) {
			if ($e->getCode() == 2) {
				return [
					'error'   => 1,
					'message' => Yii::t('roxy', 'Please remove all sub-folder before'),
				];
			}
			return [
				'error'   => 1,
				'message' => Yii::t('roxy', 'Somethings went wrong'),
			];
		}
	}

	/**
	 * @param string $folder
	 *
	 * @return array
	 */
	public function actionFileUpload($folder = '') {
		if ($folder == '') {
			$folder = $this->module->NoAlias ? $this->module->uploadFolder : Yii::getAlias($this->module->uploadFolder);
		}
		$folder = $this->module->NoAlias ? $folder : realpath($folder);
        if(strripos($this->module->userRootdir, '[userid]')) {
            $this->module->userRootdir=str_replace("[userid]", Yii::$app->user->identity->getId(), $this->module->userRootdir);
        }
        if($this->module->onlyAutorizeUsers && $this->module->DiskSizeLimit && $this->module->userRootdir) {
            $file = $this->module->userRootdir . DIRECTORY_SEPARATOR . Yii::$app->user->identity->getId() . '.txt';
            if (is_file($file)) {
                $path_to_file_limit = file_get_contents($file);
                $size_path = FolderHelper::filesSizes($this->module->userRootdir);
                $model       = new UploadForm();
                $model->file = UploadedFile::getInstances($model, 'file');
                $filesizes=0;
                foreach ( $model->file as $file) {
                    $filesizes=$filesizes+$file->size;
                }
                if($path_to_file_limit<$size_path+$filesizes){
                    return [
                        'error' => 1,
                        'message' => Yii::t('roxy', 'Disk limit reached'),
                    ];
                }

            } else {
                return [
                    'error' => 1,
                    'message' => Yii::t('roxy', 'Somethings went wrong'),
                ];
            }
        }
		if (is_dir($folder)) {
		    if(!isset($model)) {
                $model = new UploadForm();
                $model->file = UploadedFile::getInstances($model, 'file');
            }
			if ($model->upload($folder)) {
				return [
					'error' => 0,
				];
			} else {
				if (isset($model->firstErrors['file'])) {
					return [
						'error'   => 1,
						'message' => $model->firstErrors['file'],
					];
				}
			}
		}
		return [
			'error'   => 1,
			'message' => Yii::t('roxy', 'Somethings went wrong'),
		];
	}

    /**
     * @param string $folder
     *
     * @return array
     */
    public function actionGetQuotes() {
        if(strripos($this->module->userRootdir, '[userid]')) {
            $this->module->userRootdir=str_replace("[userid]", Yii::$app->user->identity->getId(), $this->module->userRootdir);
        }
        $file=$this->module->userRootdir . DIRECTORY_SEPARATOR . Yii::$app->user->identity->getId() . '.txt';
        if(is_file($file)) {
            $path_to_file_limit = file_get_contents($file);
            $size_path = FolderHelper::filesSizes($this->module->userRootdir);
            $limit_text = FolderHelper::getSymbolByQuantity($size_path) . '/' . FolderHelper::getSymbolByQuantity($path_to_file_limit);
            $percents = ceil($size_path / ($path_to_file_limit / 100));
            if($percents>100){$percents=100;}
            return [
                'error' => 0,
                'content' => '<div class="progress">
              <div class="box progress-bar progress-bar-success" role="progressbar" aria-valuenow="' . $percents . '"
              aria-valuemin="0" aria-valuemax="100" style="width:' . $percents . '%">' . $percents . '%</div></div>'.
                    '<span>'.$limit_text.'<span/>',
            ];
        } else {
            return [
                'error'   => 1,
                'message' => Yii::t('roxy', 'Somethings went wrong'),
            ];
        }
        //$folder = $this->module->userRootdir;
    }

    /**
     * @param string $folder
     *
     * @return array
     */
    public function actionFileDownloadUrl($folder = '',$url='') {
        if ($folder == '') {
            $folder = $this->module->NoAlias ? $this->module->uploadFolder : Yii::getAlias($this->module->uploadFolder);
        }
        $folder = $this->module->NoAlias ? $folder : realpath($folder);
        if (is_dir($folder)) {
            $protocol='';
            if (strripos($url, 'http://') !== false) {
                $protocol='http://';
            } else if(strripos($url, 'https://') !== false) {
                $protocol='https://';
            }
            $url_without_protocol=str_replace($protocol,'',$url);
            $clean_url=explode('/',$url_without_protocol);
            $clean_url_param=str_replace($protocol.$clean_url[0],'',$url);
            $get='';
            if (strripos($clean_url_param, '?') !== false) {
                $get=explode('?', $clean_url_param);
                $clean_url_param=$get[0];
                $get='?'.$get[1];
            }
            $pn=new Punycode('UTF-8');
            $clean_url=$pn->encode($clean_url[0]);
            $normal_url=$protocol.$clean_url.$clean_url_param;
            $file='';
            $ext='';
            if (mb_strrpos($clean_url_param, '/') !== false) {
                $file= mb_substr($clean_url_param, mb_strrpos($clean_url_param, '/') + 1);
                $ext=FileHelper::fileExtension($file);
            }
            $allow_exts=explode(' ', $this->module->allowExtension);
            if(!array_search($ext, $allow_exts)){
                return [
                    'error'   => 1,
                    'message' => Yii::t('roxy', 'No allowed extension'),
                ];
            } else {
                $file=FileHelper::filenameClean(FileHelper::filenameTranslitirate($file));
                file_put_contents($folder. DIRECTORY_SEPARATOR .$file, file_get_contents($normal_url.$get));
                if(strripos($this->module->userRootdir, '[userid]')) {
                    $this->module->userRootdir=str_replace("[userid]", Yii::$app->user->identity->getId(), $this->module->userRootdir);
                }
                if($this->module->onlyAutorizeUsers && $this->module->DiskSizeLimit && $this->module->userRootdir) {
                    $file_lim = $this->module->userRootdir . DIRECTORY_SEPARATOR . Yii::$app->user->identity->getId() . '.txt';
                    if (is_file($file_lim)) {
                        $path_to_file_limit = file_get_contents($file_lim);
                        $size_path = FolderHelper::filesSizes($this->module->userRootdir);
                        if($path_to_file_limit<$size_path){
                            unlink($folder. DIRECTORY_SEPARATOR .$file);
                            return [
                                'error' => 1,
                                'message' => Yii::t('roxy', 'Disk limit reached'),
                            ];
                        }

                    } else {
                        return [
                            'error' => 1,
                            'message' => Yii::t('roxy', 'Somethings went wrong'),
                        ];
                    }
                }
                return [
                    'error' => 0,
                ];
            }

        }
        return [
            'error'   => 1,
            'message' => Yii::t('roxy', 'Somethings went wrong'),
        ];
    }
	/**
	 * @param string $folder
	 * @param        $file
	 * @param        $name
	 *
	 * @return array
	 */
	public function actionFileRename($folder = '', $file, $name) {
		if ($folder == '') {
			return [
				'error'   => 1,
				'message' => Yii::t('roxy', 'Can\'t rename this file'),
			];
		}

        $name=strip_tags($name);
        $name=FileHelper::filenameClean(FileHelper::filenameTranslitirate($name));
		$folder  = $this->module->NoAlias ? $folder : realpath($folder);
		$oldFile = $folder . DIRECTORY_SEPARATOR . $file;
		$newFile = $folder . DIRECTORY_SEPARATOR . $name;
        if($this->module->NoChangeFileExt){
            $oldFile_ext = FileHelper::fileExtension($oldFile);
            $newFile_ext = FileHelper::fileExtension($newFile);
            if($oldFile_ext!=$newFile_ext){
                return [
                    'error'   => 1,
                    'message' => Yii::t('roxy', 'Please, no change extension file'),
                ];
            }
        }
        $path_info =  pathinfo($name);
        if($path_info['filename']==""){
            return [
                'error'   => 1,
                'message' => Yii::t('roxy', 'File name can\'t be empty'),
            ];
        }
        if (is_file($newFile)) {
            return [
                'error'   => 1,
                'message' => Yii::t('roxy', 'File existed'),
            ];
        }
		if (is_file($oldFile) && rename($oldFile, $newFile)) {
			return [
				'error' => 0,
				'data'  => [
					'href' => Url::to([
						'/roxymce/management/file-list',
						'folder' => $folder,
					]),
                    'url' => $folder.'/'.$name,
					'name' => $name,
				],
			];
		} else {
			return [
				'error'   => 1,
				'message' => Yii::t('roxy', 'Can\'t rename this file'),
			];
		}
	}

	public function actionFileRemove($folder = '', $file) {
		if ($folder == '') {
			return [
				'error'   => 1,
				'message' => Yii::t('roxy', 'Can\'t remove this file'),
			];
		}
		$folder   = $this->module->NoAlias ? $folder : realpath($folder);
		$filePath = $folder . DIRECTORY_SEPARATOR . $file;
		if (is_file($filePath) && unlink($filePath)) {
			return [
				'error' => 0,
			];
		} else {
			return [
				'error'   => 1,
				'message' => Yii::t('roxy', 'Somethings went wrong'),
			];
		}
	}

	/**
	 * This help move file from current directory to everywhere
	 *
	 * @param $folder string path of current file
	 * @param $file   string new path
	 *
	 * @return array
	 */
	public function actionFileCut($folder, $file) {
		if ($folder == '') {
			return [
				'error'   => 1,
				'message' => Yii::t('roxy', 'Can\'t cut this file'),
			];
		}
		$folder   = $this->module->NoAlias ? $folder : realpath($folder);
		$filePath = $folder . DIRECTORY_SEPARATOR . $file;
		if (Yii::$app->session->hasFlash('roxymce_copy')) {
			Yii::$app->session->removeFlash('roxymce_copy');
		}
		Yii::$app->session->setFlash('roxymce_cut', $filePath);
		return [
			'error' => 0,
		];
	}

	/**
	 * This help to copy file
	 *
	 * @param $folder string path of current file
	 * @param $file   string new path
	 *
	 * @return array
	 */
	public function actionFileCopy($folder, $file) {
		if ($folder == '') {
			return [
				'error'   => 1,
				'message' => Yii::t('roxy', 'Can\'t copy this file'),
			];
		}
		$folder   = $this->module->NoAlias ? $folder : realpath($folder);
		$filePath = $folder . DIRECTORY_SEPARATOR . $file;
		if (Yii::$app->session->hasFlash('roxymce_cut')) {
			Yii::$app->session->removeFlash('roxymce_cut');
		}
		Yii::$app->session->setFlash('roxymce_copy', $filePath);
		return [
			'error' => 0,
		];
	}

	/**
	 * @param $folder
	 *
	 * @return array
	 */
	public function actionFilePaste($folder) {
		if ($folder == '') {
			return [
				'error'   => 1,
				'message' => Yii::t('roxy', 'Can\'t past the clipboard'),
			];
		}
		$folder   = $this->module->NoAlias ? $folder : realpath($folder);
		$filePath = null;
		$return   = false;
		if (Yii::$app->session->hasFlash('roxymce_cut')) {
			$filePath = Yii::$app->session->getFlash('roxymce_cut');
			$return   = rename($filePath, $folder . DIRECTORY_SEPARATOR . basename($filePath));
		} else if (Yii::$app->session->hasFlash('roxymce_copy')) {
			$filePath = Yii::$app->session->getFlash('roxymce_copy');
            if(strripos($this->module->userRootdir, '[userid]')) {
                $this->module->userRootdir=str_replace("[userid]", Yii::$app->user->identity->getId(), $this->module->userRootdir);
            }
            if($this->module->onlyAutorizeUsers && $this->module->DiskSizeLimit && $this->module->userRootdir) {
                $file = $this->module->userRootdir . DIRECTORY_SEPARATOR . Yii::$app->user->identity->getId() . '.txt';
                if (is_file($file)) {
                    $path_to_file_limit = file_get_contents($file);
                    $size_path = FolderHelper::filesSizes($this->module->userRootdir);
                    $filesize=filesize($filePath);
                    if($path_to_file_limit<$size_path+$filesize){
                        return [
                            'error' => 1,
                            'message' => Yii::t('roxy', 'Disk limit reached'),
                        ];
                    }

                } else {
                    return [
                        'error' => 1,
                        'message' => Yii::t('roxy', 'Somethings went wrong'),
                    ];
                }
            }
            if($filePath==$folder . DIRECTORY_SEPARATOR . basename($filePath)){
                $file_name=basename($filePath);
                if(preg_match ('/copy_\d+/',$file_name)){
                    $file_name= preg_replace('/copy_\d+_/', '', $file_name);
                }
                $return = copy($filePath, $folder . DIRECTORY_SEPARATOR . 'copy_'.time().'_'.$file_name);
            } else {
                $return = copy($filePath, $folder . DIRECTORY_SEPARATOR . basename($filePath));
            }
		}
		if ($return && $filePath != null) {
			return [
				'error' => 0,
			];
		} else {
			return [
				'error'   => 1,
				'message' => Yii::t('roxy', 'Somethings went wrong'),
			];
		}
	}
}