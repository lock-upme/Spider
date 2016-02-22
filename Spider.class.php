<?php
/**
 * 抓取页面内容
 * 
 * $obj = new spider()
 * $result = $obj->spider('http://www.test.com/1.html');
 * 
 * @author lock
 */

class Spider {
	
	/**
	 *	抓取页面内容
	 *
	 * @param string $url
	 * @return string
	 */
	public function spider($url) {
		set_time_limit(10);		
		$result = self::fileGetContents($url);

		if (empty($result)) {
			$result = self::snoopy($url);
		}
		if (empty($result)) {
			return false;
		}
		
		$result = self::array_iconv($result);
		if (empty($result)) {
			return false;
		}		
		$result = str_replace("\n", "", $result);	
		return $result;
	}
	
	/**
	 * 获取页面内容
	 * 
	 * @param string $url
	 * @return string
	 */
	public function fileGetContents($url) {
		//只读2字节  如果为(16进制)1f 8b (10进制)31 139则开启了gzip ;
		$file = @fopen($url, 'rb');
		$bin = @fread($file, 2);
		@fclose($file);
		$strInfo = @unpack('C2chars', $bin);
		$typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
		$url = ($typeCode==31139) ? 'compress.zlib://'.$url:$url; // 三元表达式	
		return @file_get_contents($url);
	}
	
	/**
	 * 获取页面内容
	 * 
	 * @param string $url
	 * @return string
	 */
	public function snoopy($url){
		require_once 'Snoopy.class.php';
		$snoopy = new Snoopy;
		$snoopy->agent = 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36';
		$snoopy->_fp_timeout = 10;
		$urlSplit = self::urlSimplify($url);
		$snoopy->referer = $urlSplit['domain'];
		$result = $snoopy->fetch($url);
		return  $snoopy->results; 
	}
	
	/**
	 *对数据进行编码转换(来自网络)
	 *
	 * @param array/string $data       数组
	 * @param string $output    转换后的编码
	 * @return 返回编码后的数据
	 */
	public function array_iconv($data,  $output = 'utf-8') {
		$encodeArr = array('UTF-8', 'ASCII', 'GBK', 'GB2312', 'BIG5', 'JIS', 'eucjp-win', 'sjis-win', 'EUC-JP');
		$encoded = mb_detect_encoding($data, $encodeArr);	
		if (empty($encoded)) { $encoded='UTF-8'; }
	
		if (!is_array($data)) {
			return @mb_convert_encoding($data, $output, $encoded);
		} else {
			foreach ($data as $key=>$val) {
				$key = self::array_iconv($key, $output);
				if (is_array($val)) {
					$data[$key] = self::array_iconv($val, $output);
				} else {
					$data[$key] = @mb_convert_encoding($data, $output, $encoded);
				}
			}
			return $data;
		}
	}
	
}