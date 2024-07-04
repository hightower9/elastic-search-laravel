<?php

namespace App\Http\Controllers;

use App\Helpers\ESFilterSortHelper;
use App\Http\Requests\ListProductRequest;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @param ListProductRequest $request
     */
    public function __invoke(ListProductRequest $request)
    {
        $page = $request->validated()['page'];
        $per_page = $request->validated()['paginate'];

        $from = ($page - 1) * $per_page; // Calculate offset based on page and per_page

        // Send the search query to Elasticsearch using your preferred library
        $client = app(Client::class);

        // Check if all empty
        $check_if_all_empty = empty(array_filter($request->validated()['filter'], fn($value) => !empty($value)));

        $search_fields = [
            'code' => 3,
            'name' => 2,
            'type' => NULL,
            'project_brief_code' => NULL,
            'project_brief_name' => NULL,
        ];

        // Add search field
        $search = $check_if_all_empty ?
            [
                'match_all' => (object) [],
            ]
            : 
            (!empty($request->validated()['filter']['search'])
                ? [
                    'bool' => [
                        'must' => [
                            [
                                'bool' => [
                                    'should' => ESFilterSortHelper::wildCardQuery($request->validated()['filter']['search'], $search_fields)
                                ],
                            ],
                        ],
                    ],
                ] : []);
        
        $params = [
            'index' => app()->environment() . '.formulations',
            'from'  => $from,
            'size'  => $per_page,
            'body'  => [
                'query' => $search,
            ],
        ];

        // Check if filters exists and apply
        $filter_types = ['status', 'qa_status'];
        $params = ESFilterSortHelper::filter($request, $params, $filter_types);

        // Check if sorts exists and apply
        $params = ESFilterSortHelper::sort($request, $params);

        try {
            $response = $client->search($params);

            $hits = $response['hits']['hits'];
            $total = $response['hits']['total']['value'];

            $pagination = [
                'current_page' => $page,
                'per_page'     => $per_page,
                'total'        => $total,
                'last_page'    => ceil($total / $per_page),
            ];
            $results = [];

            if ($hits) {
                // $list = Formulation::whereIn('id', array_column($hits, '_id'))->get();
                foreach ($hits as $hit) {
                    // $formulation = $list->filter(fn($i) => $i->id == $hit['_id'])->first();
                    // Check if model exists and include it in results if found
                    // if ($formulation) {
                    //     $results[] = $formulation;
                    // }
                    $results[] = array_merge(['id' => (int)$hit['_id']], $hit['_source']);
                }
            }
            
            return response()->json(['data' => $results, 'pagination' => $pagination]);
        } catch (ElasticsearchException $e) {
            // Handle the Elasticsearch exception here
            $cleanedString = explode(":", $e->getMessage(), 2)[1];
            $errorData = json_decode($cleanedString, true);

            $message = $errorData['error'];
            $statusCode = $errorData['status'];
            
            // Log the error message and potentially additional details
            Log::error("Elasticsearch error: " . json_encode($message) . " (Status Code: $statusCode) for Formulations");
            
            return response()->json(['data' => [
                'message' => $message,
            ]], $statusCode);
        }
    }
}
