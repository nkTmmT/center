<?php

namespace app\until;
use think\facade\Log;

/**
 * 作用：FTP操作类( 拷贝、移动、删除文件/创建目录 )
 */
class Ftp
{
    public $off;             // 返回操作状态(成功/失败)
    public $conn_id;           // FTP连接
    public $host;
    public $port;
    public $user;
    public $password;
    /**
     * 方法：FTP连接
     * @FTP_HOST -- FTP主机
     * @FTP_PORT -- 端口
     * @FTP_USER -- 用户名
     * @FTP_PASS -- 密码
     * @return bool
     */
    function __construct($host, $port, $user = 'anonymous', $password = '')
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
//        $this->conn_id = @ftp_connect($FTP_HOST, $FTP_PORT);
//        if (!$this->conn_id){
//            echo "FTP服务器连接失败";
//            return false;
//        }
//        $loginRes = @ftp_login($this->conn_id, $FTP_USER, $FTP_PASS);
//        if (!$loginRes){
//            echo "FTP服务器登陆失败";
//            return false;
//        }
//        @ftp_pasv($this->conn_id, 1); // 打开被动模拟
//        return true;
    }

    public function connect()
    {
        $this->conn_id = @ftp_connect($this->host, $this->port);
        return (bool)$this->conn_id;
    }

    public function login()
    {
        $loginRes = @ftp_login($this->conn_id, $this->user, $this->password);
        @ftp_pasv($this->conn_id, 1); // 打开被动模拟
        return $loginRes;
    }

    /**
     * 方法：返回当前目录
     */
    function pwd()
    {
        return ftp_pwd($this->conn_id);
    }

    /**
     * 方法：上传文件
     * @path  -- 本地路径
     * @newpath -- 上传路径
     * @type  -- 若目标目录不存在则新建
     */
    function up_file($path, $newpath, $type = true)
    {
        if ($type) $this->dir_mkdirs($newpath);
        $this->off = @ftp_put($this->conn_id, $newpath, $path, FTP_BINARY);
        if (!$this->off) {
            return "文件上传失败,请检查权限及路径是否正确！";
        } else {
            return 'success';
        }
    }

    /**
     * 方法：移动文件
     * @path  -- 原路径
     * @newpath -- 新路径
     * @type  -- 若目标目录不存在则新建
     */
    function move_file($path, $newpath, $type = true)
    {
        if ($type) $this->dir_mkdirs($newpath);
        $this->off = @ftp_rename($this->conn_id, $path, $newpath);
        if (!$this->off) echo "文件移动失败,请检查权限及原路径是否正确！";
    }

    /**
     * 方法：复制文件
     * 说明：由于FTP无复制命令,本方法变通操作为：下载后再上传到新的路径
     * @path  -- 原路径
     * @newpath -- 新路径
     * @type  -- 若目标目录不存在则新建
     */
    function copy_file($path, $newpath, $type = true)
    {
        $downpath = "D/tmp.dat";
        $this->off = @ftp_get($this->conn_id, $downpath, $path, FTP_BINARY);// 下载
        if (!$this->off) echo "文件复制失败,请检查权限及原路径是否正确！";
        $this->up_file($downpath, $newpath, $type);
    }

    /**
     * 方法：下载文件到本地目录
     * 说明：由于FTP无复制命令,本方法变通操作为：下载后再上传到新的路径
     * @path  -- 原路径
     * @newpath -- 新路径
     * @type  -- 若目标目录不存在则新建
     */
    function download_file($path, $localpath)
    {
        $this->off = @ftp_get($this->conn_id, $localpath, $path, FTP_BINARY);// 下载
        if (!$this->off) Log::write("文件下载失败,请检查权限及原路径是否正确！");
        return false;
    }

    /**
     * 方法：删除文件
     * @path -- 路径
     */
    function del_file($path)
    {
        $this->off = @ftp_delete($this->conn_id, $path);
        if (!$this->off) Log::write("文件删除失败,请检查权限及路径是否正确！");
        return false;
    }

    /**
     * 方法：生成目录
     * @path -- 路径
     */
    function dir_mkdirs($path)
    {
        $path_arr = explode('/', $path);       // 取目录数组
        $file_name = array_pop($path_arr);      // 弹出文件名
        $path_div = count($path_arr);        // 取层数

        foreach ($path_arr as $val)          // 创建目录
        {
            if (@ftp_chdir($this->conn_id, $val) == false) {
                $tmp = @ftp_mkdir($this->conn_id, $val);
                if ($tmp == false) {
                    echo "目录创建失败,请检查权限及路径是否正确！" . $path;
                    return false;
                }
                @ftp_chdir($this->conn_id, $val);
            }
        }

        for ($i = 1; $i <= $path_div; $i++)         // 回退到根
        {
            @ftp_cdup($this->conn_id);
        }
        return true;
    }

    /**
     * 列出ftp指定目录
     * @param string $remote_path
     */
    public function list_file(string $remote_path = '')
    {
        $contents = @ftp_nlist($this->conn_id, $remote_path);
        return $contents;
    }

    /**
     * 方法：关闭FTP连接
     */
    function close()
    {
        @ftp_close($this->conn_id);
    }
}

?>