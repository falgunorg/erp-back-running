<?php

namespace App\Libraries;

class Paginator {

    public static function paginateData($totalPages, $currentPage) {
        $pagesToLeft = 2;
        $pagesToRight = 2;
        $startPageLimit = $currentPage - $pagesToLeft;
        if ($startPageLimit < 1) {
            $endPageLimit = $currentPage + $pagesToRight - $startPageLimit + 1;
            $startPageLimit = 1;
        } else {
            $endPageLimit = $currentPage + $pagesToRight;
        }

        $paginationData = [];

        if ($totalPages) {

            if ($startPageLimit > 1) {
                $paginationData[] = [
                    'page' => 1,
                    'label' => __('First'),
                    'type' => 'first_page'
                ];
            }

            if ($currentPage > 1) {
                $paginationData[] = [
                    'page' => $currentPage - 1,
                    'label' => __('Previous'),
                    'type' => 'prev_page'
                ];
            }

            for ($i = $startPageLimit; $i <= $totalPages; $i++) {
                if ($i > $endPageLimit)
                    break;
                $tmp = [
                    'page' => $i,
                    'label' => $i,
                    'type' => 'num_page'
                ];
                if ($currentPage == $i) {
                    $tmp['type'] = 'current_page';
                }
                $paginationData[] = $tmp;
            }

            if ($currentPage < $totalPages) {
                $paginationData[] = [
                    'page' => $currentPage + 1,
                    'label' => __('Next'),
                    'type' => 'next_page'
                ];
            }

            if ($endPageLimit < $totalPages) {
                $paginationData[] = [
                    'page' => $totalPages,
                    'label' => __('Last'),
                    'type' => 'last_page'
                ];
            }
        }

        return $paginationData;
    }

}
