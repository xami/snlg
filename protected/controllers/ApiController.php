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
    public function actionWp()
    {
        $src='http://www.360doc.com/showWeb/0/0/10052.aspx';
        $o = Tools::OZCurl($src, 100, false);
        pd($o);
    }
}