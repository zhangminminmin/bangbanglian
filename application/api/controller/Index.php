<?php

namespace app\api\controller;
use app\common\controller\Api;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['index'];
    protected $noNeedRight = [];

    /**
     * 首页
     */
    public function index()
    {
        $phpword = new PhpWord();
        $info = ROOT_PATH . 'public/uploads/admin/examination/test.docx';
        $phpWord = new PhpWord();
        $sections =IOFactory::load($info)->getSections();
    }
}
