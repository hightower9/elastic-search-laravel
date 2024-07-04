<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class ESFilterSortHelper 
{
    /**
     * Filter ES fields
     *
     * @param Request $req
     * @param array $params = []
     * @param array $filter_types = []
     * @return array
     */
    public static function filter(Request $req, array $params = [], array $filter_types = []): array
    {
        $filters = [];
        foreach ($filter_types as $filter_type) {
            if ($req->filled("filter.$filter_type")) {
                foreach ($req->validated()["filter"][$filter_type] as $filter_value) {
                    $filters[$filter_type][] = ['term' => [$filter_type => $filter_value]];
                }

                $params['body']['query']['bool']['must'][] = [
                    'bool' => [
                        'should' => $filters[$filter_type],
                    ]
                ];
            }
        }

        return $params;
    }

    /**
     * Sort ES fields
     *
     * @param Request $req
     * @param array $params = []
     * @return array
     */
    public static function sort(Request $req, array $params = []): array
    {
        $sorts = [];
        if ($req->filled("sort")) {
            foreach ($req->validated()["sort"] as $sort) {
                $order = strpos($sort, '-') === 0 ? 'desc' : 'asc';
                $key = $order === 'desc' ? substr($sort, 1) : $sort;
                $sorts[] = [$key => ['order' => $order]];
            }

            $params['body']['sort'] = $sorts;
        }

        return $params;
    }

    /**
     * Wild Card Query Create
     *
     * @param string $search
     * @param array $fields = []
     * @return array
     */
    public static function wildCardQuery(string $search, array $fields = []): array
    {
        $searchTerm = '*' . $search . '*';

        $query = [];
        foreach ($fields as $field => $boost) {
            if ($boost !== NULL) {
                $query[] = [
                    'wildcard' => [
                        $field => [
                            'value' => $searchTerm,
                            'boost' => $boost
                        ]
                    ]
                ];
            } else {
                $query[] = [
                    'wildcard' => [
                        $field => $searchTerm
                    ]
                ];
            }
        }

        return $query;
    }
}