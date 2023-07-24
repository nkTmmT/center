<?php
namespace app\controller;

use app\BaseController;
use app\job\GenerateModel;
use think\facade\Queue;
use think\Request;

class Index extends BaseController
{
    public function index()
    {
        return 'hello world!';
    }

    /**
     * @return mixed
     */
    public function handle(Request $request)
    {
        $captureId = $request->param('capture_id');
        if (empty($captureId)){
            return $this->fail('缺少拍摄id');
        }
        $data = [
            'capture_id' => $captureId,
        ];
        Queue::push(GenerateModel::class, $data);
        return $this->success();
    }

    public function test()
    {
        $response = curlRequest('https://test.pro.fujiuni.com/outapi/center/model/completed', true, [
            'capture_id' => '64ba37638d569',
            'model_url' => 'http://192.168.110.178/64ba37638d569/human.obj',
        ], ['Content-type: application/json']);
        var_dump($response);
    }
}
