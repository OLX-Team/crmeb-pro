<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
namespace app\controller\admin\v1\application\routine;

use app\jobs\template\RoutineTemplateJob;
use app\services\activity\coupon\StoreCouponUserServices;
use app\services\message\SystemNotificationServices;
use app\services\message\TemplateMessageServices;
use app\services\other\QrcodeServices;
use app\services\system\attachment\SystemAttachmentServices;
use crmeb\exceptions\AdminException;
use think\exception\ValidateException;
use think\facade\App;
use think\Request;
use think\facade\Route as Url;
use app\controller\admin\AuthController;
use crmeb\services\{FileService,
    FormBuilder as Form,
    template\Template,
    UploadService,
    wechat\MiniProgram
};
use crmeb\services\CacheService;
use function Swoole\Coroutine\batch;

/**
 * Class RoutineTemplate
 * @package app\controller\admin\v1\application\routine
 */
class RoutineTemplate extends AuthController
{
    protected $cacheTag = 'system_routine';

    /**
     * 构造方法
     * WechatTemplate constructor.
     * @param App $app
     * @param TemplateMessageServices $services
     */
    public function __construct(App $app, TemplateMessageServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['name', ''],
            ['status', '']
        ]);
        $where['type'] = 0;
        $data = $this->services->getTemplateList($where);
        $industry = CacheService::get($this->cacheTag . '_wechat_industry', function () {
            try {
                $cache = (new Template('wechat'))->getIndustry();
                if (!$cache) return [];
                return $cache->toArray();
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }, 0) ?: [];
        !is_array($industry) && $industry = [];
        $industry['primary_industry'] = isset($industry['primary_industry']) ? $industry['primary_industry']['first_class'] . ' | ' . $industry['primary_industry']['second_class'] : '未选择';
        $industry['secondary_industry'] = isset($industry['secondary_industry']) ? $industry['secondary_industry']['first_class'] . ' | ' . $industry['secondary_industry']['second_class'] : '未选择';
        $lst = [
            'industry' => $industry,
            'count' => $data['count'],
            'list' => $data['list']
        ];
        return $this->success($lst);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $f = array();
        $f[] = Form::input('tempkey', '模板编号');
        $f[] = Form::input('tempid', '模板ID');
        $f[] = Form::input('name', '模板名');
        $f[] = Form::input('content', '回复内容')->type('textarea');
        $f[] = Form::radio('status', '状态', 1)->options([['label' => '开启', 'value' => 1], ['label' => '关闭', 'value' => 0]]);
        return $this->makePostForm('添加模板消息', $f, Url::buildUrl('/app/routine'), 'POST');
    }

    /**
     * 保存新建的资源
     *
     * @param \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data = $this->request->postMore([
            'tempkey',
            'tempid',
            'name',
            'content',
            ['status', 0]
        ]);
        if ($data['tempkey'] == '') return $this->fail('请输入模板编号');
        if ($data['tempkey'] != '' && $this->services->getOne(['tempkey' => $data['tempkey']]))
            return $this->fail('请输入模板编号已存在,请重新输入');
        if ($data['tempid'] == '') return $this->fail('请输入模板ID');
        if ($data['name'] == '') return $this->fail('请输入模板名');
        if ($data['content'] == '') return $this->fail('请输入回复内容');
        $data['add_time'] = time();
        $this->services->save($data);
        return $this->success('添加模板消息成功!');
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if (!$id) return $this->fail('数据不存在');
        $product = $this->services->get($id);
        if (!$product) return $this->fail('数据不存在!');
        $f = array();
        $f[] = Form::input('tempkey', '模板编号', $product->getData('tempkey'))->disabled(1);
        $f[] = Form::input('name', '模板名', $product->getData('name'))->disabled(1);
        $f[] = Form::input('tempid', '模板ID', $product->getData('tempid'));
        $f[] = Form::radio('status', '状态', $product->getData('status'))->options([['label' => '开启', 'value' => 1], ['label' => '关闭', 'value' => 0]]);
        return $this->makePostForm('编辑模板消息', $f, Url::buildUrl('/app/routine/' . $id), 'PUT');
    }

    /**
     * 保存更新的资源
     *
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $data = $this->request->postMore([
            'tempid',
            ['status', 0]
        ]);
        if ($data['tempid'] == '') return $this->fail('请输入模板ID');
        if (!$id) return $this->fail('数据不存在');
        $product = $this->services->get($id);
        if (!$product) return $this->fail('数据不存在!');
        $this->services->update($id, $data, 'id');
        return $this->success('修改成功!');
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!$id) return $this->fail('数据不存在!');
        if (!$this->services->delete($id))
            return $this->fail('删除失败,请稍候再试!');
        else
            return $this->success('删除成功!');
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function set_status($id, $status)
    {
        if ($status == '' || $id == 0) return $this->fail('参数错误');
        $this->services->update($id, ['status' => $status], 'id');
        return $this->success($status == 0 ? '关闭成功' : '开启成功');
    }

    /**
     * 同步订阅消息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function syncSubscribe()
    {
        if (!sys_config('routine_appId') || !sys_config('routine_appsecret')) {
            throw new AdminException('请先配置小程序appid、appSecret等参数');
        }
        $all = $this->services->getTemplateList(['status' => 1, 'type' => 0]);
        $errMessage = [
            '-1' => '系统繁忙，此时请稍候再试',
            '40001' => 'AppSecret错误或者AppSecret不属于这个小程序，请确认AppSecret 的正确性',
            '40002' => '请确保grant_type字段值为client_credential',
            '40013' => '不合法的AppID，请检查AppID的正确性，避免异常字符，注意大小写',
            '40125' => '小程序配置无效，请检查配置',
            '41002' => '缺少appid参数',
            '41004' => '缺少secret参数',
            '43104' => 'appid与openid不匹配',
            '45009' => '达到微信api每日限额上限',
            '200011' => '此账号已被封禁，无法操作',
            '200012' => '个人模版数已达上限，上限25个',
        ];
        if ($all['list']) {
            $i = 0;
            foreach ($all['list'] as $template) {
                if (!$template['tempkey']) {
                    continue;
                }
                if (!isset($template['kid'])) {
                    return $this->fail('数据库模版表(template_message)缺少字段：kid');
                }
                if (isset($template['kid']) && $template['kid']) {
                    continue;
                }
                RoutineTemplateJob::dispatch([$template, $errMessage]);
                $i++;
            }
            /** @var SystemNotificationServices $systemNotificationServices */
            $systemNotificationServices = app()->make(SystemNotificationServices::class);
            //清空模版缓存
            $systemNotificationServices->clearTemplateCache();
        }

        return $this->success($i ? '队列执行中，请稍后查看，小程序需要选择：生活服务/百货/超市/便利店类目' : '已同步成功，请不要重复同步');
    }

    /**
     * 下载小程序
     * @return mixed
     */
    public function downloadTemp()
    {
        [$name, $is_live] = $this->request->postMore([
            ['name', ''],
            ['is_live', 0]
        ], true);
        if (!sys_config('routine_appId', '')) {
            throw new AdminException('请先在设置->系统设置->应用设置，配置小程序相关信息');
        }
        if (!file_exists(public_path() . 'statics/mp_view') || count(scandir(public_path() . 'statics/mp_view')) == 2) {
            throw new AdminException('请先上传小程序打包文件，路径：' . public_path() . 'statics/mp_view');
        }
        try {
            @unlink(public_path() . 'statics/download/routine.zip');
            //拷贝源文件
            /** @var FileService $fileService */
            $fileService = app(FileService::class);
            $fileService->copyDir(public_path() . 'statics/mp_view', public_path() . 'statics/download');
            $baseUrl = sys_config('site_url');
            batch([
                'appId' => function () use ($name) {
                    try {
                        $this->updateConfigJson(sys_config('routine_appId'), $name != '' ? $name : sys_config('routine_name'));
                    } catch (\Throwable $e) {

                    }
                    return true;
                },
                'updateApp' => function () use ($is_live) {
                    $res1 = true;
                    //是否开启直播
                    if ($is_live == 0) {
                        //pages.json 替换直播组件
                        try {
                            $this->updateAppJson();
                        } catch (\Throwable $e) {

                        }
                    }
                    return $res1;
                },
                'updateProductDetail' => function () use ($is_live) {
                    $res1 = true;
                    //是否开启直播
                    if ($is_live == 0) {
                        //商品详情页 替换直播组件
                        try {
                            $this->updateProductDetailJson();
                        } catch (\Throwable $e) {

                        }
                    }
                    return $res1;
                },
                'url' => function () use ($baseUrl) {
                    //替换url
                    try {
                        $this->updateUrl($baseUrl);
                    } catch (\Throwable $e) {

                    }
                    return true;
                }
            ]);
            //压缩文件
            $fileService->addZip(public_path() . 'statics/download', public_path() . 'statics/download/routine.zip', public_path() . 'statics/download');
            $data['url'] = $baseUrl . '/statics/download/routine.zip';
            return app('json')->success($data);
        } catch (\Throwable $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * 替换url
     * @param $url
     */
    public function updateUrl($url)
    {
        $fileUrl = app()->getRootPath() . "public/statics/download/common/vendor.js";
        $string = file_get_contents($fileUrl); //加载配置文件
        $url = parse_url($url)['host'] ?? $url;
        $string = str_replace('demo.crmeb.com', $url, $string); // 正则查找然后替换
        $newFileUrl = app()->getRootPath() . "public/statics/download/common/vendor.js";
        @file_put_contents($newFileUrl, $string); // 写入配置文件

    }

    /**
     * 判断是否开启直播(弃用)
     * @param int $iszhibo
     */
    public function updateAppJson()
    {
        $fileUrl = app()->getRootPath() . "public/statics/download/app.json";
        $string = file_get_contents($fileUrl); //加载配置文件
        $pats = '/,
  "plugins": {
    "live-player-plugin": {
      "version": "(.*?)",
      "provider": "(.*?)"
    }
  }/';
        $string = preg_replace($pats, '', $string); // 正则查找然后替换
        $newFileUrl = app()->getRootPath() . "public/statics/download/app.json";
        @file_put_contents($newFileUrl, $string); // 写入配置文件
    }

    /**
     * 替换appid
     * @param string $appid
     * @param string $projectanme
     */
    public function updateConfigJson($appId = '', $projectName = '')
    {
        $fileUrl = app()->getRootPath() . "public/statics/download/project.config.json";
        $string = file_get_contents($fileUrl); //加载配置文件
        // 替换appid
        $appIdOld = '/"appid"(.*?),/';
        $appIdNew = '"appid"' . ': ' . '"' . $appId . '",';
        $string = preg_replace($appIdOld, $appIdNew, $string); // 正则查找然后替换
        // 替换小程序名称
        $projectNameOld = '/"projectname"(.*?),/';
        $projectNameNew = '"projectname"' . ': ' . '"' . $projectName . '",';
        $string = preg_replace($projectNameOld, $projectNameNew, $string); // 正则查找然后替换
        $newFileUrl = app()->getRootPath() . "public/statics/download/project.config.json";
        @file_put_contents($newFileUrl, $string); // 写入配置文件
    }

    /**
     * 替换商品详情直播
     */
    public function updateProductDetailJson()
    {
        $fileUrl = app()->getRootPath() . "public/statics/download/common/main.js";
        $string = file_get_contents($fileUrl); //加载配置文件
        $string = str_replace('requirePlugin("live-player-plugin"),', 'null', $string);
        $pats = '/onShow:function\(e\)\{(.*?)},/';
        $string = preg_replace($pats, '', $string); // 正则查找然后替换
        $newFileUrl = app()->getRootPath() . "public/statics/download/common/main.js";
        @file_put_contents($newFileUrl, $string); // 写入配置文件
    }

    /**
     * 获取下载小程序模版页面数据
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDownloadInfo()
    {
        $data['routine_name'] = sys_config('routine_name', '');
        $appid = sys_config('routine_appId', '');
        if (!$appid) {
            throw new AdminException('请先在设置->系统设置->应用设置，配置小程序相关信息');
        }
        $name = $data['routine_name'] . '.jpg';
        /** @var SystemAttachmentServices $systemAttachmentModel */
        $systemAttachmentModel = app()->make(SystemAttachmentServices::class);
        $imageInfo = $systemAttachmentModel->getInfo(['name' => $name]);
        if (!$imageInfo) {
            /** @var QrcodeServices $qrcode */
            $qrcode = app()->make(QrcodeServices::class);
            $resForever = $qrcode->qrCodeForever(0, 'code');
            if ($resForever) {
                $resCode = MiniProgram::appCodeUnlimit($resForever->id, '', 280);
                $res = ['res' => $resCode, 'id' => $resForever->id];
            } else {
                $res = false;
            }
            if (!$res) throw new ValidateException('二维码生成失败');
            $upload = UploadService::init(1);
            if ($upload->to('routine/code')->stream((string)$res['res'], $name) === false) {
                return $upload->getError();
            }
            $imageInfo = $upload->getUploadInfo();
            $imageInfo['image_type'] = 1;
            $systemAttachmentModel->attachmentAdd($imageInfo['name'], $imageInfo['size'], $imageInfo['type'], $imageInfo['dir'], $imageInfo['thumb_path'], 1, $imageInfo['image_type'], $imageInfo['time'], 2);
            $qrcode->update($res['id'], ['status' => 1, 'time' => time(), 'qrcode_url' => $imageInfo['dir']]);
            $data['code'] = sys_config('site_url') . $imageInfo['dir'];
        } else $data['code'] = sys_config('site_url') . $imageInfo['att_dir'];

        $data['appId'] = $appid;
        $data['help'] = 'https://doc.crmeb.com/web/pro/crmebprov2/1192';
        return $this->success($data);
    }
}
