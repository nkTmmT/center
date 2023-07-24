<?php

namespace app\job;
use app\until\Ftp;
use think\facade\Log;
use think\queue\Job;

class GenerateModel
{
    public function fire(Job $job, $data){
        Log::write('进入处理任务');
        Log::write('$data: ');
        Log::write($data);
        set_time_limit(0);
        // 先获取ftp的视频
        $captureRoot = 'E:\capture_root'; // 拍摄视频存放主目录
        $baseDir = $captureRoot.DIRECTORY_SEPARATOR.$data['capture_id']; // 视频任务资源文件主目录
        $videoDir = $baseDir.DIRECTORY_SEPARATOR.'video'; // 拍摄视频存放目录
        if (!file_exists($videoDir)){
            mkdir($videoDir, 755, true);
        }
        // 调接口获取安卓ftp地址
        $response = curlRequest('https://test.pro.fujiuni.com/outapi/center/ftp/get');
        $androidFtpsData = json_decode($response, true);
        $androidFtps = $androidFtpsData['data'] ?? [];
        if (!empty($androidFtps)) {
            Log::write('$androidFtps: ');
            Log::write($androidFtps);
            $i = 1;
            foreach ($androidFtps as $androidFtp) {
                Log::write('正在连接ftp：'.$androidFtp['ip'].':'.$androidFtp['port']);
                $ftp = new Ftp($androidFtp['ip'], $androidFtp['port']);     // 打开FTP连接
                if (!$ftp->connect()) {
                    Log::write('ftp连接失败！');
                    continue;
                }
                if (!$ftp->login()) {
                    Log::write('ftp登录失败！');
                    continue;
                }
                $path = 'Movies/Camera-Video-' . $data['capture_id'] . '.mp4';
                $ftp->download_file($path, $videoDir . DIRECTORY_SEPARATOR . 'Camera-Video-' . $data['capture_id'] . '-' . $i . '.mp4');     // 下载文件并复制到本地目录
                if ($ftp->off) { // 执行成功后
                    $ftp->del_file($path); // 删除对应文件
                }
                $ftp->close();
                $i++;
            }

            // 获取完视频后, 调用rc生成模型
            $videoFramesDir = $baseDir . DIRECTORY_SEPARATOR . 'frame';
            $modelName = 'human';
            $model = $baseDir . DIRECTORY_SEPARATOR . $modelName . '.obj';
            $project = $baseDir . DIRECTORY_SEPARATOR . $modelName . '.rcproj';
            $videos = scandir($videoDir);
            $cmd = '"D:\RealityCapture\RealityCapture.exe" ';
            $hasVideo = false;
            foreach ($videos as $video) {
                if ($video != '.' && $video != '..') {
                    $hasVideo = true;
                    $cmd .= '-importVideo "' . $videoDir . DIRECTORY_SEPARATOR . $video . '" "' . $videoFramesDir . '" "1" -draft '; // -align 标准对齐  -draft 草图对齐,
                }
            }
            $cmd .= '-setReconstructionRegionAuto -calculateNormalModel -renameSelectedModel "' . $modelName . '" -cleanModel -closeHoles -calculateTexture -save "' . $project .
//                '" -exportModel "' . $modelName . '" "' . $model .
                '" -quit';
            if ($hasVideo) {
                exec($cmd, $output, $retVal);
                // 生成完视频后, 调用模型生成完毕接口  outapi/ios/model/completed
                $response = curlRequest('https://test.pro.fujiuni.com/outapi/center/model/completed', true, [
                    'capture_id' => $data['capture_id'],
                    'model_url' => 'http://192.168.110.178/'.$data['capture_id'].'/'.$modelName . '.obj',
                ], ['Content-type: application/json']);
                Log::write('请求模型生成完毕的结果：');
                Log::write($response);
            } else {
                Log::write('没有获取到本次拍摄任务的视频!');
            }
        }
        //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
        $job->delete();
        echo '任务处理完毕！'.PHP_EOL;
    }

    public function failed($data){

    }
}