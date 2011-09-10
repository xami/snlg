<?php
class Tools
{
	public static function OZCurl($src, $expire=60, $show=false)
	{
//		$show = isset($_REQUEST['show']) ? intval($_REQUEST['show']) : false;
//		$expire = (isset($_REQUEST['expire']) && (intval($_REQUEST['expire'])>10)) ? intval($_REQUEST['expire']) : 10;
//		$src = isset($_REQUEST['src']) ? trim($_REQUEST['src']) : '';
//		if(empty($src)){
//			return false;
//		}
		
		$expire = intval($expire)>20 ? intval($expire) : 20;
		$src = trim($src);
		if(empty($src)) return false;

        if(!self::is_url($src)) return false;
		
		$c = null;
		$key = md5($src);
		$cache = Yii::app()->cache;
		$c=$cache->get($key);
		
		if(empty($c)){
			//Run curl
			$curl = Yii::app()->CURL;
			$curl->run(array(CURLOPT_REFERER => $src));
			$curl->setUrl($src);
			$curl->exec();
			
			if(Yii::app()->CURL->isError()) {
				// Error
				var_dump($curl->getErrNo());
				var_dump($curl->getError());
				
			}else{
				// More info about the transfer
				$c=array(
					'ErrNo'=>$curl->getErrNo(),
					'Error'=>$curl->getError(),
					'Header'=>$curl->getHeader(),
					'Info'=>$curl->getInfo(),
					'Result'=>$curl->getResult(),
				);
			}
            if(sizeof($c)<1024*1024*5){
                $cache->set($key, $c, $expire);
            }
		}
		
		if($show==true){
			if(!empty($c['Info']['content_type']))
				header('Content-Type: '.$c['Info']['content_type']);
			if($c['Info']['http_code']==200)
				echo $c['Result'];
		}
		
		return $c;
	}
	
	public static function is_url($url){
		$validate=new CUrlValidator();
		if(empty($url)){
			return false;
		}
		if($validate->validateValue($url)===false){
			return false;
		}
	    return true;
	}

    //取得字段间字符
	public static function cutContent($content='', $start='', $end='', $reg=false)
	{
        //是否启用正则
        if($reg){
            $e_start=preg_split($start, $content, 2, PREG_SPLIT_OFFSET_CAPTURE);
            if(empty($e_start[1][0]) || empty($e_start[1][1])){
                return false;
            }

            $e_end=preg_split($end, $e_start[1][0], 2, PREG_SPLIT_OFFSET_CAPTURE);
            if(empty($e_end[1][0]) || empty($e_end[1][1])){
                return false;
            }

            return $e_end[0][0];
        }else{
            $e_start=explode($start, $content);
            if(!isset($e_start[1]))
                return false;
            $e_end=explode($end, $e_start[1]);
            if(!isset($e_end[1]))
                return false;
            return $e_end[0];
        }

	}

    public static function get_content_type($html){
        if(empty($html)){
            return false;
        }
        self::cutContent($html, '');
    }
	
}