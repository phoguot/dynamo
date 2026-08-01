<?php
namespace Application\Paginator;

use Laminas\Paginator\Paginator as ZendPaginator;

class Paginator extends ZendPaginator
{

    protected $currentModels;

    protected $pageRangeDefault = PaginatorConst::DEFAULT_PAGE_RANGE;

    public function __construct($adapter, $paging = null)
    {
        parent::__construct($adapter);

        if (isset($paging[PaginatorConst::KEY_PAGE])) {
            $this->setCurrentPageNumber((int)$paging[PaginatorConst::KEY_PAGE]);
        }
        if (isset($paging[PaginatorConst::KEY_PAGE_SIZE])) {
            $this->setItemCountPerPage((int)$paging[PaginatorConst::KEY_PAGE_SIZE]);
        }

        $this->setPageRange($paging[PaginatorConst::KEY_PAGE_RANGE] ?? $this->pageRangeDefault);
    }

    /**
     * set current items
     *
     * @param array $items
     */
    public function setCurrentItems(array $items)
    {
        $this->currentItems = $items;
        return $this;
    }
}
