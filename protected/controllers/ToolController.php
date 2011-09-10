<?php
/**
 * 
 * Enter description here ...
 * @author lijia
 *
<?php
// cURL options can be found here:
// http://php.net/manual/en/function.curl-setopt.php

require_once('Whiz/Http/Client/Curl.php');

// Set cURL options via constructor
$curl = new Whiz_Http_Client_Curl(
  array(CURLOPT_REFERER => 'http://www.google.com/')
);

// Set URL via method (This is just to make things easier)
$curl->setUrl('http://juliusbeckmann.de/');
// $curl->exec('http://juliusbeckmann.de/'); would be also possible

// Set cURL options via method
$curl->setOption(CURLOPT_TIMEOUT, 10);

// Do the request
$curl->exec();

if($curl->isError()) {
  // Error
  var_dump($curl->getErrNo());
  var_dump($curl->getError());
}else{
  // Success
  echo $curl->getResult();
  // More info about the transfer
  // var_dump($curl->getInfo());
  // var_dump($curl->getHeader());
  // var_dump($curl->getVersion());
}

// Close cURL
$curl->close();
?>
<?php

require_once('Whiz/Http/Client/Curl.php');

// Creating a "template" class by overwriting internal config
class My_Curl extends Whiz_Http_Client_Curl {
  protected $_config = array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_REFERER => 'http://www.google.com/'
  );
}

$curl = new My_Curl();
$curl->setUrl('http://juliusbeckmann.de/');

// Fetch configured handle
$ch = $curl->getHandle();

// Fetch a copy of the configured handle
// $ch2 = $curl->copyHandle();

// Do with handle what ever you like
// ...
$result = curl_exec($ch);

// Put handle and result back in
$curl->setFromHandle($ch, $result);

// Fetch transfer info
if($curl->isError()) {
  // Error
  var_dump($curl->getErrNo());
  var_dump($curl->getError());
}else{
  // Success
  echo $curl->getResult();
  // More info about the transfer
  // var_dump($curl->getInfo());
  // var_dump($curl->getHeader());
  // var_dump($curl->getVersion());
}

// Close cURL
$curl->close();
?>
 */

class ToolController extends Controller
{
//	public function actionIndex()
//	{
//		$this->render('index');
//	}

    public function actionSitemap()
	{
		$this->render('sitemap');
	}
	
	public function actionIn2Out()
	{
		$data=array();
		
		if(isset($_REQUEST['in'])){
			$data['in'] = empty($_REQUEST['in']) ? '' : trim($_REQUEST['in']);
			if(strlen($data['in'])>0){
				$out = unserialize($data['in']);
				$tmp='';
				if(!empty($out)){
					foreach($out as $k => $v){
						$tmp.=$k.': '.$v."\r\n";
					}
				}
				$data['out']=$tmp;
			}
		}
		
		$this->render('in2out', $data);
	}
	
	public function actionGet()
	{
		$show = isset($_REQUEST['show']) ? true : false;
		$expire = isset($_REQUEST['expire']) ? intval($_REQUEST['expire']) : 10;
		$src = isset($_REQUEST['src']) ? trim($_REQUEST['src']) : '';
		
		$o = Tools::OZCurl($src, $expire, $show);
	}

    public function actionGetlinks()
    {

        $expire=intval(Yii::app()->request->getParam('expire', 3600));
        $src=trim(Yii::app()->request->getParam('src', ''));
        if(empty($src)){
            echo json_encode(array('status'=>500,'data'=>'输入需要整理地图的网址'));
            die;
        }
        if(strpos($src, 'http://')!==0){
            echo json_encode(array('status'=>500,'data'=>'连接开头需要加"http://"'));
            die;
        }
        if(!Tools::is_url($src)){
            echo json_encode(array('status'=>500,'data'=>'输入正确的网址'));
            die;
        }
        

        try{
            $page=Tools::OZCurl($src, $expire);
        }catch(Exception $e){
            echo json_encode(array('status'=>500,'data'=>$e->getMessage()));
            die;
        }


        if(empty($page)){
            echo json_encode(array('status'=>500,'data'=>''));
            die;
        }
        if((isset($page['ErrNo']) && $page['ErrNo']==0) &&
           (isset($page['Info']['http_code']) && $page['Info']['http_code']==200))
        {
            $html=$page['Result'];
        }else{
            echo json_encode(array('status'=>500,'data'=>''));
            die;
        }

        $cut_content_type=explode(';', $page['Info']['content_type']);
        $page['Info']['html_code']='utf-8';
        if(isset($cut_content_type[1])&&!empty($cut_content_type[1])){
            $page['Info']['content_type']=strtolower(trim($cut_content_type[0]));
            $html_code_str=strtolower(trim($cut_content_type[1]));
            $pos=strpos($html_code_str, '=');
            if($pos>0){
                $page['Info']['html_code']=substr($html_code_str, $pos+1);
            }
        }else{
            $meta_content_type=Tools::cutContent($html, $start='/<meta.*?http-equiv=\"Content-Type\".*?content=[\"]?/i', $end='/\"?\s?\/?>/', true);
            $cut_content_type=explode(';', $meta_content_type);
            if(isset($cut_content_type[1])&&!empty($cut_content_type[1])){
                $html_code_str=strtolower(trim($cut_content_type[1]));
                $pos=strpos($html_code_str, '=');
                if($pos>0){
                    $page['Info']['html_code']=substr($html_code_str, $pos+1);
                }
            }
        }
        if($page['Info']['content_type']!='text/html'){
            echo json_encode(array('status'=>500,'data'=>''));
            die;
        }
        $html=iconv($page['Info']['html_code'], 'utf-8//IGNORE', $html);
        preg_match_all("'<\s*a\s.*?href\s*=\s*([\"\']?)(?(1)(.*?)\\1|([^\s\>]+))[^>]*>?(.*?)</a>'isx",$html,$links);

        if(empty($links)){
            echo json_encode(array('status'=>500,'data'=>''));
        }else{
            unset($src_info);
            $src_info=parse_url($src);
//            pr($links[2]);

            $links_full=array();
            foreach($links[2] as $link){

                $link=trim($link);
                
                unset($link_info);
                $link_info=parse_url($link);
                if(!isset($link_info['scheme'])){
                    $link_info['scheme']='http';
                }
//                pd($link_info);
                
                if(strpos($link, 'http://')>0){
                    $link_tmp=$src_info['scheme'].'://'.$src_info['host'].'/'.$link;
                    continue;
                }
                if($link_info['scheme']=='mailto' || $link_info['scheme']=='javascript'){
                    continue;
                }



                if(!isset($link_info['path'])){
                    $link_info['path']='';
                }
                if(!isset($link_info['query'])){
                    $link_info['query']='';
                }
                if(!empty($link_info['query'])){
                    $link_info['query']='?'.$link_info['query'];
                }
//                pd($src_info);
                if(strpos($link_info['path'], '/')!==0){
                    if(!isset($src_info['path'])){
                         $src_info['path']='/';
                    }else if(strpos($src_info['path'], '/')!==0){
                        $src_info['path']='/'.$src_info['path'];
                    }
                    if(substr($src_info['path'], -1, 1)!=='/'){
                        $src_info['path']=$src_info['path'].'/';
                    }
                    $link_info['path']=$src_info['path'].$link_info['path'];
                }

//                if(strpos($link_info['path'],'api')!==false){
//                    continue;
//                }

                if(!isset($link_info['scheme']) || empty($link_info['scheme']) || !isset($link_info['host']) || empty($link_info['host'])){
                    $link_tmp=$src_info['scheme'].'://'.$src_info['host'].$link_info['path'].$link_info['query'];
                }else{
                    //不是当前域名下的链接跳过
                    if(($src_info['scheme']!=$link_info['scheme']) || ($src_info['host']!=$link_info['host'])){
                        continue;
                    }
                    $link_tmp=$link_info['scheme'].'://'.$link_info['host'].$link_info['path'].$link_info['query'];
                }

                if(Tools::is_url($link_tmp)){
                    $links_full[]=$link_tmp;
                }

//                pd($links_full);
            }
            $links_full=array_unique($links_full);
            //过滤array_unique引起的空白索引
            $ct_link=array();
            if(!empty($links_full)){
                foreach($links_full as $the_link){
//                    if((strlen($the_link)-1)==strrpos($the_link,'/')){
//                        $the_link=substr($the_link,0,-1);
//                    }
                    $ct_link[]=html_entity_decode($the_link);
                }
            }
            
            ob_implicit_flush(true);
            $ms=intval(Yii::app()->request->getParam('ms', 0));
            if($ms>0){
                usleep ($ms);
            }

            echo json_encode(array('status'=>200,'count'=>count($links_full),'data'=>$ct_link));
            flush();
        }

    }


    public function get_suffix($link_str)
    {
        if(empty($link_str)){
            return false;
        }
        preg_match('/\.([a-z]{3,4})$/i', $link_str, $suffix);
        return $suffix[1];
    }

    public function actionCreate_mp()
    {
//        pd($_REQUEST);
        $mp=Yii::app()->request->getParam('mp', array());
        $dm=Yii::app()->request->getParam('dm', 'feediy.com');
        if(empty($mp)){
            echo json_encode(array('status'=>false,'msg'=>'内容为空'));
            die;
        }
//        pr(count($mp[1]).'|'.count($mp[2]).'|'.count($mp[3]));
//        pd($mp);

        $xml_head='<?xml version="1.0" encoding="UTF-8"?>
<urlset
  xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
   http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
<!-- created with Free Online Sitemap Generator www.feediy.com -->
';

        $xml_body='';
        for($i=1;$i<=3;$i++){
            if(isset($mp[$i]) && !empty($mp[$i])){
                $priority=1.0-(0.2*$i);
                foreach(array_unique($mp[$i]) as  $link){
                    
                    $xml_body.=
'<url>'."\r\n".
'    <loc>'.trim($link).'</loc>'."\r\n".
'    <changefreq>hourly</changefreq>'."\r\n".
'    <priority>'.$priority.'</priority>'."\r\n".
'</url>'."\r\n";
                }
            }
        }

        $xml_foot='</urlset>';

        $time=time();
        $sitemap_file=Yii::app()->basePath.DIRECTORY_SEPARATOR.'sitemap'.DIRECTORY_SEPARATOR.$dm.'_'.$time.'.xml';
        file_put_contents($sitemap_file, $xml_head.$xml_body.$xml_foot);

        echo json_encode(array('status'=>true,'msg'=>'http://sitemap.feediy.com/'.$dm.'_'.$time.'.xml'));
    }

    

    public function actionTest()
	{
//        var_dump(substr('.afas', 0, 1)!='.') ;
//		echo MCrypy::decrypt('DP9gh8NxCU7dIuk0teVguS5fM5Pzv4ACdDswFgkH8yWUAC+GMqTRp+33XeLbSesX8JsKdV5ZJvdTvlm1V0zNjNP85/xS5UcYn6j4IxsB', Yii::app()->params['xuk_pass'], 128);
//        echo '<br>';
//        echo strlen('_wpnonce=198eceae35&action=ngg_ajax_operation&image=501&operation=create_thumbnail');
//        pr(parse_url('link.php?url=http://news.orzero.com'));
//        $mystring = 'abc';
//        $findme   = 'a';
//        echo $pos = strpos($mystring, $findme);
//        $content='xasdfax adsfax xcv';
//        $start='/c/';
//        $r = preg_split($start, $content, 2, PREG_SPLIT_OFFSET_CAPTURE);
//        pr($r);
//        $url='.sdfs.Html';
//        pr($this->get_suffix($url));
        
	}
	

	// Uncomment the following methods and override them if needed
	/*
	public function filters()
	{
		// return the filter configuration for this controller, e.g.:
		return array(
			'inlineFilterName',
			array(
				'class'=>'path.to.FilterClass',
				'propertyName'=>'propertyValue',
			),
		);
	}

	public function actions()
	{
		// return external action classes, e.g.:
		return array(
			'action1'=>'path.to.ActionClass',
			'action2'=>array(
				'class'=>'path.to.AnotherActionClass',
				'propertyName'=>'propertyValue',
			),
		);
	}
	*/
}