<?php

declare(strict_types=1);

namespace App\Traits;

use Filament\Tables\Enums\PaginationMode;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Overrides Filament's CanPaginateRecords to show adjacent pages in pagination.
 * Default Filament shows only current page (onEachSide(0)), making it hard to navigate.
 * This trait changes it to show 3 pages on each side of current page.
 */
trait CustomTablePaginationTrait
{
    protected function paginateTableQuery(Builder $query): Paginator | CursorPaginator
    {
        $perPage = $this->getTableRecordsPerPage();

        $mode = $this->getTable()->getPaginationMode();

        if ($mode === PaginationMode::Simple) {
            return $query->simplePaginate(
                perPage: ($perPage === 'all') ? $query->toBase()->getCountForPagination() : $perPage,
                pageName: $this->getTablePaginationPageName(),
            );
        }

        if ($mode === PaginationMode::Cursor) {
            return $query->cursorPaginate(
                perPage: ($perPage === 'all') ? $query->toBase()->getCountForPagination() : $perPage,
                cursorName: $this->getTablePaginationPageName(),
            );
        }

        $total = $query->toBase()->getCountForPagination();

        /** @var LengthAwarePaginator $records */
        $records = $query->paginate(
            perPage: ($perPage === 'all') ? $total : $perPage,
            pageName: $this->getTablePaginationPageName(),
            total: $total,
        );

        // Show 3 pages on each side of current page instead of 0
        return $records->onEachSide(3);
    }
}
