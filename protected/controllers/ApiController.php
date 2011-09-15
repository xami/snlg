<?php
/**
 * Created by JetBrains PhpStorm.
 * User: xami
 * Date: 11-9-10
 * Time: 下午7:37
 * To change this template use File | Settings | File Templates.
 */
 
class ApiController extends Controller
{
    public $_sid;
    public $_src;
    public $_html;
    public $_ar;

    public function actionTest(){
        $t='asdfasdxx';
        $b=explode('bk', $t);
    }

    public function actionWp()
    {
        $this->sid=Yii::app()->request->getParam('sid', 0);
        if(!empty($this->src)){
            if(!empty($this->html)){
                if(!empty($this->ar)){
                    echo json_encode(array('return'=>$this->wp));
                }else{
                    echo json_encode(array('return'=>false,'m'=>'ar'));
                }
            }else{
                echo json_encode(array('return'=>false,'m'=>'html'));
            }
        }else{
            echo json_encode(array('return'=>false,'m'=>'src'));
        }
    }

    public function setSid($id){
        if(intval($id)>0){
            $this->_sid=intval($id);
        }else{
            $this->_sid=0;
        }
    }

    public function actionImg($src){
        if(empty($src)){
            return '';
        }

        $op=strrpos($src,'.');
        $file_type=substr($src, $op);
        if(preg_match('/^[\.\w]{4,5}$/i', $file_type)){
            $file_type=strtolower($file_type);
        }else{
            $file_type='';
        }

        $k=md5($src);
        $sub_path='';
        for($i=0;$i<8;$i++){
            if($i==7){
                $sub_path.=substr($k,$i*4,4);
            }else{
                $sub_path.=substr($k,$i*4,4).DIRECTORY_SEPARATOR;
            }
        }

        $root=DIRECTORY_SEPARATOR.'static'.DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR;
        $file=$root.$sub_path.$file_type;

        $save_path=Yii::app()->basePath.DIRECTORY_SEPARATOR.$file;
        if(!is_file($save_path)){
            $o = Tools::OZCurl($src, 1800, false);
            if($o['Info']['http_code']=='200'&&preg_match('/^image\/[\.\w\d]+$/i', $o['Info']['content_type'])){
                if(mkdir(dirname($save_path), '0755', true)){
                    if(file_put_contents($save_path, $o['Result'])){
//                        echo $o['Result'];
                    }
                }
            }
        }else{
//            echo file_get_contents($save_path);
        }
        $url_link=str_replace('\\','/','http://'.Yii::app()->params['img_host'].'/'.$sub_path.$file_type);
        header('Location: '.$url_link);
//        pd($o);
    }

    public function actionHref($to){
        if(empty($to)){
            return 'http://'.Yii::app()->params['img_host'];
        }

        header('Location: '.base64_decode($to));
    }

    public function getSid(){
        return $this->_sid;
    }

    public function setSrc($src){
        if(is_string($src)){
            $this->_src=$src;
        }
    }

    

    public function getSrc(){
        if(!empty($this->_src)){
            return $this->_src;
        }else{
            if(!empty($this->sid)){
                $src='http://www.360doc.com/showWeb/0/0/'.$this->sid.'.aspx';
                $o = Tools::OZCurl($src, 600, false);
                if(isset($o['Header']['7']) && substr($o['Header']['7'], 0, 9)=='Location:'){
                    $SSrc=substr($o['Header']['7'], 9);
                    if(!empty($SSrc)){
                        $this->_src=trim($SSrc);
                    }
                }
            }else{
                $this->_src='';
            }
        }
        return $this->_src;
    }

    public function setHtml($html){
        if(is_array($html)){
            $this->_html=$html;
        }
    }

    public function getHtml(){
        if(!empty($this->_html)){
            return $this->_html;
        }else{
            if(!empty($this->src) && Tools::is_url($this->src)){
                $this->_html = Tools::OZCurl($this->src, 360, false);
            }else{
                $this->_html = '';
            }
        }
        return $this->_html;
    }

    public function setAr($ar){
        if(is_array($ar)){
            $this->_ar=$ar;
        }
    }

    public function getAr(){
        if(!empty($this->_ar) && is_array($this->_ar)){
            return $this->_ar;
        }

        if(empty($this->html['Result']) && !is_string($this->html['Result'])){
            return false;
        }

        $this->_ar['title']=trim(Tools::cutContent($this->html['Result'], '<title>', '</title>'));
        $this->_ar['keywords']=trim(Tools::cutContent($this->html['Result'], 'name="classification" />
    <meta content="', '" name=keywords>'));
        $this->_ar['description']=trim(Tools::cutContent($this->html['Result'], 'name=keywords>
    <meta content="', '" name=description>'));
        $this->_ar['author']=trim(
            strip_tags(
                Tools::cutContent($this->html['Result'],
                '<span class="mz" style="font-weight: bold;">', '</span>')
            )
        );
        $this->_ar['body']=Tools::cutContent(
            $this->html['Result'],
            '<span id="articlecontent" onmouseup="NewHighlight(event)" style="width: 740px">',
            '                    </span>'
        );
        $this->_ar['category']=strip_tags(
            Tools::cutContent($this->html['Result'], 'class="bulebold bulelink">[', ']</span>')
        );
        $this->_ar['src']=$this->src;

        return $this->_ar;
    }

//    public function setWp(){
//
//    }

    
    
    public function getWp(){
        if(empty($this->_ar) || !is_array($this->_ar)){
            return false;
        }
        $ar=$this->ar;
        
        if(!isset($ar['title']) || !isset($ar['body']) || !isset($ar['src'])){
            Yii::log('E::noset_title_or_no_body::'.serialize($ar), 'warning', '360doc');
            return false;
        }
        if(empty($ar['title']) || empty($ar['body']) || empty($ar['src'])){
            Yii::log('E::empty_title_or_no_body::'.serialize($ar), 'warning', '360doc');
            return false;
        }

        $wpuri=parse_url(YII::app()->request->hostInfo);
        $date=date('Y-m-d H:i:s');
        $suri=parse_url($ar['src']);

        $post=WpPosts::model()->find('post_excerpt=:src',array(':src'=>$ar['src']));
        if(empty($post)){
            $post=new WpPosts();
            $post->post_author=1;
            $post->post_date=$date;
            $post->post_date_gmt=$date;
            $post->post_content=Tools::FContent($ar['body']);
            $post->post_title=$ar['title'];
            $post->post_excerpt=$ar['src'];
            $post->post_status='publish';
            $post->comment_status='open';
            $post->ping_status='open';
            $post->post_name=str_replace(array('-', '+'), '_',urlencode( $ar['title']));
            $post->to_ping='';
            $post->pinged='';
            $post->post_modified=$date;
            $post->post_modified_gmt=$date;
            $post->post_content_filtered='';
            $post->post_parent=0;
            $post->guid=YII::app()->request->hostInfo.$suri['path'];
            $post->menu_order=0;
            $post->post_type='post';
            $post->comment_count=0;
            if(!$post->save()){  //发帖成功
                Yii::log('E::save_post_error::'.serialize($ar), 'warning', '360doc');
                return false;
            }
        }else{
            unset($ar['body']);
            Yii::log('R::already_saved_post::'.serialize($ar), 'warning', '360doc');
            return true;
        }

        if(isset($ar['author']) && !empty($ar['author'])){  //保存作者信息
            $user_name=strip_tags($ar['author']);
            $nicename=Tools::Pinyin($user_name, 1);
            $users=WpUsers::model()->find('user_login=:user_name', array(':user_name'=>$user_name));

            if(empty($users)){
                $users=new WpUsers();
                $users->user_login=$user_name;
                $users->user_pass=time();
                $users->user_nicename=$nicename;
                $users->user_email=$nicename.'@'.$wpuri['host'];
                $users->user_url=YII::app()->request->hostInfo.'/members/'.$nicename.'/';
                $users->user_registered=time();
                $users->user_activation_key='';
                $users->user_status=2;
                $users->display_name=$user_name;
            }else{
                $users->user_pass=time();
                $users->user_nicename=$nicename;
                $users->user_email=$nicename.'@'.$wpuri['host'];
                $users->user_url=YII::app()->request->hostInfo.'/members/'.$nicename.'/';
                $users->user_registered=time();
                $users->user_activation_key='';
                $users->user_status=2;
                $users->display_name=$user_name;
            }
            if(!$users->save()){  //发帖成功
                Yii::log('E::save_users_error::'.serialize($users), 'warning', '360doc');
            }else{
                $post->post_author=$users->ID;
                if(!$post->save()){  //发帖成功
                    Yii::log('E::save_post_error::'.serialize($ar), 'warning', '360doc');
                    return false;
                }
            }
        }
        
        foreach(array('title', 'description', 'keywords') as $key){
            if(isset($ar[$key]) && !empty($ar[$key])){  //保存作者信息
                $postmeta=WpPostmeta::model()->find('post_id=:post_id and meta_key=:meta_key', array(':post_id'=>$post->ID, ':meta_key'=>$key));
                if(empty($postmeta)){
                    $postmeta=new WpPostmeta();
                    $postmeta->post_id=$post->ID;
                    $postmeta->meta_key=$key;
                }
                $postmeta->meta_value=$ar[$key];
                if(!$postmeta->save()){
                    Yii::log('E::save_postmeta_error::'.serialize($postmeta), 'warning', '360doc');
                }
            }
        }


        if(isset($ar['category']) && !empty($ar['category'])){  //保存作者信息
            $terms=WpTerms::model()->find('name=:name', array(':name'=>$ar['category']));
            if(empty($terms)){
                $terms=new WpTerms();
                $terms->name=$ar['category'];
                $terms->slug=str_replace(array('-', '+'), '_',urlencode($ar['category']));
                $terms->term_group=0;
                if(!$terms->save()){
                    Yii::log('E::save_terms_error::'.serialize($postmeta), 'warning', '360doc');
                    return false;
                }
            }

            if(!empty($terms->term_id)){    //保存分类信息
                foreach(array('category', 'post_tag') as $key){
                    $termTaxonomy=WpTermTaxonomy::model()->find(
                        'term_id=:term_id and taxonomy=:taxonomy',
                        array(':term_id'=>$terms->term_id, ':taxonomy'=>$key)
                    );
                    if(empty($termTaxonomy)){
                        $termTaxonomy=new WpTermTaxonomy();
                        $termTaxonomy->term_id=$terms->term_id;
                        $termTaxonomy->taxonomy=$key;
                        $termTaxonomy->description='';
                        $termTaxonomy->parent=0;
                        $termTaxonomy->count=1;
                    }else{
                        $termTaxonomy->count++;
                    }
                    if($termTaxonomy->save()){
                        $termRelationships=WpTermRelationships::model()->find(
                            'object_id=:object_id and term_taxonomy_id=:term_taxonomy_id',
                            array(':object_id'=>$post->ID, ':term_taxonomy_id'=>$termTaxonomy->term_taxonomy_id)
                        );
                        if(empty($termRelationships)){
                            $termRelationships=new WpTermRelationships();
                            $termRelationships->object_id=$post->ID;
                            $termRelationships->term_taxonomy_id=$termTaxonomy->term_taxonomy_id;
                            $termRelationships->term_order=0;
                        }else{
                            $termRelationships->term_taxonomy_id=$termTaxonomy->term_taxonomy_id;
                        }
                        if(!$termRelationships->save()){
                            Yii::log('E::save_termRelationships_error::'.serialize($termRelationships), 'warning', '360doc');
                            return false;
                        }
                    }else{
                        Yii::log('E::save_termTaxonomy_error::'.serialize($termTaxonomy), 'warning', '360doc');
                    }
                }
            }
            
        }

        return true;

    }
}