<?php

namespace app\lune\framework\pagination;

class PageInfoBuilder {

    private $pageInfo;

    public function __construct() {
        $this->pageInfo = new PageInfo();
    }

    public function content($content) {
        $this->pageInfo->content = $content;
        return $this; 
    }

    public function empty(bool $empty) {
        $this->pageInfo->empty = $empty;
        return $this; 
    }

    public function last(bool $last) {
        $this->pageInfo->last = $last;
        return $this; 
    }

    public function numberOfElements(int $numberOfElements) {
        $this->pageInfo->numberOfElements = $numberOfElements;
        return $this; 
    }

    public function page(int $page) {
        $this->pageInfo->page = $page;
        return $this; 
    }

    public function size(int $size) {
        $this->pageInfo->size = $size;
        return $this; 
    }

    public function totalElements(int $totalElements) {
        $this->pageInfo->totalElements = $totalElements;
        return $this; 
    }

    public function totalPages(int $totalPages) {
        $this->pageInfo->totalPages = $totalPages;
        return $this; 
    }

    public function build() {
        return $this->pageInfo;
    }

}
