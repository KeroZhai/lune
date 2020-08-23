<?php

namespace app\core\request;

class UploadedFile {

    private $tempFileName;
    private $name;
    private $type;
    private $size;
    private $error;

    public function __construct(array $originalFile) {
        $this->tempFileName = $originalFile["tmp_name"];
        $this->name = $originalFile["name"];
        $this->type = $originalFile["type"];
        $this->size = $originalFile["size"];
        $this->error = $originalFile["error"];
    }

    /**
     * 获取文件名, 包含扩展名
     * 
     * @return string 文件名, 包含扩展名
     */
    public function getName() {
        return $this->name;
    }

    /**
     * 获取文件类型
     * 
     * @return string 文件类型
     */
    public function getMediaType() {
        return $this->type;
    }

    /**
     * 获取文件大小, 单位字节
     * 
     * @return int 文件大小
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * 获取文件错误代码, 正常为0
     * 
     * @return int 错误代码
     */
    public function getError() {
        return $this->error;
    }

    /**
     * 将上传的文件保存到指定的位置
     * 
     * @return bool 保存状态
     */
    public function saveTo(string $targetLocation) {
        if (is_uploaded_file($this->tempFileName)) {
            return move_uploaded_file($this->tempFileName, $targetLocation);
        }
        return false;
    }

}
