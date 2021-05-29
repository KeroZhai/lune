<?php

namespace app\lune\framework\pagination;

/**
 * 分页信息
 *  
 */
class PageInfo {

    /**
     * 数据
     * 
     * @var array
     */
    public $content;

    /**
     * 是否为空
     * 
     * @var bool
     */
    public $empty;

    /**
     * 是否是最后一页
     * 
     * @var bool
     */
    public $last;

    /**
     * 当前实际数目
     * 
     * @var int
     */
    public $numberOfElements;

    /**
     * 当前页码
     * 
     * @var int
     */
    public $page;

    /**
     * 当前页大小
     * 
     * @var int
     */
    public $size;

    /**
     * 总数目
     * 
     * @var int
     */
    public $totalElements;

    /**
     * 总页数
     * 
     * @var int
     */
    public $totalPages;

    /**
     * 返回一个builder对象
     * 
     * @return PageInfoBuilder builder
     */
    public static function builder() {
        return new PageInfoBuilder();
    }

}
