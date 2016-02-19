<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 上传模块
 * @author wangdong
 */
class UploadController extends CommonController{
	/**
	 * 上传图片
	 */
	public function image(){
		$config = C('FILE_UPLOAD_IMG_CONFIG');
		$res    = upload($_FILES, $config);
		if($res['status']){
			$url = array();
			foreach($res['result'] as $key=>$arr){
				$url[$key] = file_full_path($arr['savepath'] . $arr['savename']);
			}
			$res['url'] = $url;
		}
		$this->ajaxReturn($res);
	}

	/**
	 * 上传文件
	 */
	public function file(){
		$config = C('FILE_UPLOAD_FILE_CONFIG');
		$res    = upload($_FILES, $config);
		if($res['status']){
			$url = array();
			foreach($res['result'] as $key=>$arr){
				$url[$key] = file_full_path($arr['savepath'] . $arr['savename']);
			}
			$res['url'] = $url;
		}
		$this->ajaxReturn($res);
	}

	/**
	 * 上传影音
	 */
	public function video(){
		$config = C('FILE_UPLOAD_VIDEO_CONFIG');
		$res    = upload($_FILES, $config);
		if($res['status']){
			$url = array();
			foreach($res['result'] as $key=>$arr){
				$url[$key] = file_full_path($arr['savepath'] . $arr['savename']);
			}
			$res['url'] = $url;
		}
		$this->ajaxReturn($res);
	}

	/**
	 * 图片裁剪
	 */
	public function crop($subfix = ''){
		C('DEFAULT_AJAX_RETURN', 'json');

		$imgUrl = I('post.imgUrl');

		$image = file_read_remote($imgUrl);

		if($image){
			$param    = array(
				'width'  => I('post.imgW',  0, 'ceil'),
				'height' => I('post.imgH',  0, 'ceil'),
				'y'      => I('post.imgY1', 0, 'ceil'),
				'x'      => I('post.imgX1', 0, 'ceil'),
				'w'      => I('post.cropW', 0, 'ceil'),
				'h'      => I('post.cropH', 0, 'ceil'),
			);

			$imgPath  = UPLOAD_PATH . date('Y/m/d/') . basename($imgUrl);
			$newImage = file_subfix($imgPath, $subfix);

			$res = image_crop($param, $image, false, $newImage);

			if($res){
				$response = array(
					'status' => 1,
					'info'   => '裁剪成功',
					'url'    => file_full_path($newImage)
				);
			}else{
				$response = array(
					'status' => 0,
					'info'   => '图片裁剪失败'
				);
			}
		}else{
			$response = array(
				'status' => 0,
				'info'   => '图片读取失败'
			);
		}
		$this->ajaxReturn($response);
	}

	/**
	 * ueditor编辑器接口
	 */
	public function ueditor($action = ''){
		C('DEFAULT_AJAX_RETURN', 'json');
		switch($action){
			case 'config':
				$result = $this->ueConfig();
				break;
			case 'uploadimage':
				$result = $this->ueUpImage();
				break;
			case 'uploadscrawl':
				$result = $this->ueUpScrawl();
				break;
			case 'uploadvideo':
				$result = $this->ueUpVideo();
				break;
			case 'uploadfile':
				$result = $this->ueUpFIle();
				break;
			case 'listimage':
				$result = $this->ueLsImage();
				break;
			case 'listfile':
				$result = $this->ueLsFile();
				break;
			case 'catchimage':
				$result = $this->ueCacheImage();
				break;
			default:
				$result = array('state'=> '请求地址出错');
		}
		$this->ajaxReturn($result);
	}

	private function ueConfig(){
		return dict('', 'Ueditor');
	}

	private function ueUpImage(){
		$field  = dict('imageFieldName', 'Ueditor');
		$config = C('FILE_UPLOAD_IMG_CONFIG');
		$res    = upload($_FILES, $config);

		if($res['status']){
			$filename = $res['result'][$field]['savepath'] . $res['result'][$field]['savename'];
			return array(
				"state"    => "SUCCESS",           //上传状态，上传成功时必须返回"SUCCESS"
				"url"      => $filename,            //返回的地址
				"title"    => $res['result'][$field]['savename'], //新文件名
				"original" => $res['result'][$field]['name'],     //原始文件名
				"type"     => $res['result'][$field]['type'],     //文件类型
				"size"     => $res['result'][$field]['size'],     //文件大小
			);
		}else{
			return array(
				"state"    => $res['info'],          //上传状态，上传成功时必须返回"SUCCESS"
				"url"      => "",            //返回的地址
				"title"    => "",          //新文件名
				"original" => "",          //原始文件名
				"type"     => "",          //文件类型
				"size"     => 0,          //文件大小
			);
		}
	}

	private function ueUpScrawl(){
		$field  = dict('scrawlFieldName', 'Ueditor');
		$config = C('FILE_UPLOAD_IMG_CONFIG');
		$ext    = 'png';

		$img = I("post.{$field}", '', 'base64_decode');

		$filename = date('Y/m/d/') . uniqid() . '.' . strtolower($ext);
		file_write(UPLOAD_PATH . $filename, $img);

		return array(
			'state'    => 'SUCCESS',
			'url'      => $filename,
			"title"    => basename($filename),
			'original' => '涂鸦',
			"type"     => 'image/png',
			'size'     => strlen($img),
		);
	}

	private function ueUpVideo(){
		$field  = dict('videoFieldName', 'Ueditor');
		$config = C('FILE_UPLOAD_VIDEO_CONFIG');
		$res    = upload($_FILES, $config);

		if($res['status']){
			$filename = $res['result'][$field]['savepath'] . $res['result'][$field]['savename'];
			return array(
				"state"    => "SUCCESS",                          //上传状态，上传成功时必须返回"SUCCESS"
				"url"      => $filename,                          //返回的地址
				"title"    => $res['result'][$field]['savename'], //新文件名
				"original" => $res['result'][$field]['name'],     //原始文件名
				"type"     => $res['result'][$field]['type'],     //文件类型
				"size"     => $res['result'][$field]['size'],     //文件大小
			);
		}else{
			return array(
				"state"    => $res['info'],  //上传状态，上传成功时必须返回"SUCCESS"
				"url"      => "",            //返回的地址
				"title"    => "",            //新文件名
				"original" => "",            //原始文件名
				"type"     => "",            //文件类型
				"size"     => 0,             //文件大小
			);
		}
	}

	private function ueUpFIle(){
		$field  = dict('fileFieldName', 'Ueditor');
		$config = C('FILE_UPLOAD_FILE_CONFIG');
		$res    = upload($_FILES, $config);

		if($res['status']){
			$filename = $res['result'][$field]['savepath'] . $res['result'][$field]['savename'];
			return array(
				"state"    => "SUCCESS",                          //上传状态，上传成功时必须返回"SUCCESS"
				"url"      => $filename,                          //返回的地址
				"title"    => $res['result'][$field]['savename'], //新文件名
				"original" => $res['result'][$field]['name'],     //原始文件名
				"type"     => $res['result'][$field]['type'],     //文件类型
				"size"     => $res['result'][$field]['size'],     //文件大小
			);
		}else{
			return array(
				"state"    => $res['info'],  //上传状态，上传成功时必须返回"SUCCESS"
				"url"      => "",            //返回的地址
				"title"    => "",            //新文件名
				"original" => "",            //原始文件名
				"type"     => "",            //文件类型
				"size"     => 0,             //文件大小
			);
		}
	}

	private function ueLsImage(){
		$config = dict('', 'Ueditor');

		$allowFiles = $config['imageManagerAllowFiles'];
		$listSize   = $config['imageManagerListSize'];
		$path       = $config['imageManagerListPath'];

		$allowFiles = implode('|', C('FILE_UPLOAD_IMG_CONFIG.exts'));

		/* 获取参数 */
		$size  = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
		$start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
		$end   = $start + $size;

		/* 获取文件列表 */
		$path  = UPLOAD_PATH . ltrim($path, DS);
		$files = file_list_upload($path, $allowFiles);
		if (!count($files)) {
			return array(
				"state" => "no match file",
				"list" => array(),
				"start" => $start,
				"total" => count($files)
			);
		}

		/* 获取指定范围的列表 */
		$len = count($files);
		for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
			$list[] = $files[$i];
		}
		/* 返回数据 */
		$result = array(
			"state" => "SUCCESS",
			"list" => $list,
			"start" => $start,
			"total" => count($files)
		);

		return $result;
	}

	private function ueLsFile(){
		$config = dict('', 'Ueditor');

		$allowFiles = $config['fileManagerAllowFiles'];
		$listSize   = $config['fileManagerListSize'];
		$path       = $config['fileManagerListPath'];

		$allowFiles = implode('|', C('FILE_UPLOAD_FILE_CONFIG.exts'));

		/* 获取参数 */
		$size  = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
		$start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
		$end   = $start + $size;

		/* 获取文件列表 */
		$path = UPLOAD_PATH . ltrim($path, DS);
		$files = file_list_upload($path, $allowFiles);
		if (empty($files)) {
			return array(
				"state" => "no match file",
				"list" => array(),
				"start" => $start,
				"total" => count($files)
			);
		}

		/* 获取指定范围的列表 */
		$len = count($files);
		for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
			$list[] = $files[$i];
		}
		/* 返回数据 */
		$result = array(
			"state" => "SUCCESS",
			"list" => $list,
			"start" => $start,
			"total" => count($files)
		);

		return $result;
	}

	private function ueCacheImage(){
		@set_time_limit(600);

		$dict   = dict('', 'Ueditor');
		$field  = $dict['catcherFieldName'];
		$source = I("param.{$field}");

		$exts   = '('.implode('|', C('FILE_UPLOAD_IMG_CONFIG.exts')) . ')';

		$list = array();
		foreach ($source as $imgUrl) {
			$info = array(
				"state" => 'ERROR',
				"url" => '',
				"size" => 0,
				"title" => '',
				"original" => '',
			);
			$check = true;
			//http开头验证
			if ($check && strpos($imgUrl, "http") !== 0) {
				$info['state'] = "链接不是http链接";
				$check = false;
			}

			if ($check && !file_exist_remote($imgUrl)) {
				$info['state'] = "链接不可用";
				$check = false;
			}

			if ($check) {
				//格式验证(扩展名验证和Content-Type验证)
				$fileType = file_ext($imgUrl);
				preg_match("/\.{$exts}$/i", $imgUrl, $ext);
				if ($ext) {
					$ext = $ext[1];

					//打开输出缓冲区并获取远程图片
					ob_start();
					$context = stream_context_create(array('http' => array('follow_location' => false)));
					readfile($imgUrl, false, $context);
					$img = ob_get_contents();
					ob_end_clean();

					$filename = date('Y/m/d/') . uniqid() . '.' . strtolower($ext);
					file_write(UPLOAD_PATH . $filename, $img);

					$info = array_merge($info, array(
						'state'    => 'SUCCESS',
						'url'      => $filename,
						'title'    => basename($filename),
						'size'     => strlen($img),
						'original' => basename($imgUrl),
					));
				}
			}

			array_push($list, array(
				"state"    => $info["state"],
				"url"      => $info["url"],
				'title'    => '',
				"size"     => $info["size"],
				"title"    => htmlspecialchars($info["title"]),
				"original" => htmlspecialchars($info["original"]),
				"source"   => htmlspecialchars($imgUrl)
			));
		}

		/* 返回抓取数据 */
		return array(
			'state' => count($list) ? 'SUCCESS' : 'ERROR',
			'list'  => $list
		);
	}
}