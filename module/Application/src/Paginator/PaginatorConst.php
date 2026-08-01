<?php

namespace Application\Paginator;

final class PaginatorConst
{
    const string KEY_PAGE       = 'page';
    const string KEY_PAGE_SIZE       = 'pageSize';
    const string KEY_PAGE_RANGE = 'pageRange';

    const int DEFAULT_PAGE       = 1;
    const int DEFAULT_PAGE_SIZE       = 30;
    const int DEFAULT_PAGE_RANGE = 5;

    const int MAX_PAGE_SIZE = 100;
}
