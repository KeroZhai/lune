<?php
namespace app\core\response;

/**
 * 返回结果封装对象
 */
class StatusResult {

    /**
     * 状态码
     * 
     * @var int
     */
    public $status;

    /**
     * 是否成功
     * 
     * @var bool
     */
    public $success;

    /**
     * 响应信息
     * 
     * @var string
     */
    public $message;

    /**
     * 响应数据
     * 
     * @var mixed
     */
    public $data;

    private function __construct($success, $message, $data, $status) {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
        $this->status = $status;
    }

    public static function success($message="Operation succeeded", $data=null) {
        return new StatusResult(true, $message, $data, 200);
    }

    public static function error($message="Operation failed", $data=null) {
        return new StatusResult(false, $message, $data, 200);
    }

    public static function status($status, $message=null) {
        return new StatusResult(null, $message, null, $status);
    }

}
