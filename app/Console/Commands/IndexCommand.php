<?php

namespace App\Console\Commands;

use App\Models\Product;
use Elastic\Elasticsearch\Client;
use Illuminate\Console\Command;
use NumberFormatter;

class IndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index:products {--clear-cache=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index Products into Elasticsearch';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = app(Client::class);

        // Clear cache for the index
        if ($this->option('clear-cache') == 'true') {
            $params = [
                'index' => app()->environment() . '.products',
                'cache' => 'fielddata' // Cache type (e.g., fielddata, query)
            ];
            
            $client->indices()->clearCache($params);

            $this->info('Cache cleared for index: products');
            $this->newLine();
        }

        $params = ['index' => app()->environment() . '.products'];

        $response = $client->indices()->exists($params);

        if ($response->getStatusCode() == 200) {
            $client->indices()->delete($params);
            $this->info('Index: products deleted successfully.');
        }
        else {
            $this->error("Index: products doesn't exist");
        }
        $this->newLine();

        $params = [
            'index' => app()->environment() . '.products',
            'body' => [
                'settings' => [
                    'number_of_shards' => 3,
                ],
                'mappings' => [
                    'dynamic' => false,
                    "dynamic_date_formats" => ["dd/MM/yyyy HH:mm:ss"],
                    'properties' => [
                        'code' => [
                            'type' => 'text',
                        ],
                        'name' => [
                            'type' => 'text',
                        ],
                        'status' => [
                            'type' => 'keyword',
                        ],
                        'price' => [
                            'type'           => 'scaled_float',
                            'scaling_factor' => 100,
                        ],
                        'address' => [
                            'properties' => [
                                'line1' => [
                                    'type' => 'text',
                                ],
                                'line2' => [
                                    'type' => 'text',
                                ],
                                'city' => [
                                    'type' => 'text',
                                ],
                                'state' => [
                                    'type' => 'text',
                                ],
                                'country' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'created_at' => [
                            'type' => 'date',
                        ],
                    ],
                ],
            ],
        ];
        $client->indices()->create($params);

        // Index products
        $this->info('Documents indexing started.');
        $this->newLine();

        $products = Product::all();

        // Bulk Index
        $params = ['body' => []];
        $thousand_count = 0;

        foreach ($products as $key => $product) {
            $params['body'][] = [
                'index' => [
                    '_index' => app()->environment() . '.products',
                    '_id'    => $product->id,
                ]
            ];

            $params['body'][] = [
                'code'    => $product->code,
                'name'    => $product->name,
                'status'  => $product->status,
                'price'   => $product->price,
                'address' => [
                    'line1'   => $product->address['line1'],
                    'line2'   => $product->address['line2'],
                    'city'    => $product->address['city'],
                    'state'   => $product->address['state'],
                    'country' => $product->address['country'],
                ],
                'created_at' => $product->created_at,
            ];

            // Every 1000 documents stop and send the bulk request
            if (($key + 1) % 1000 == 0) {
                $response = $client->bulk($params);

                if ($response->getStatusCode() !== 200) {
                    $this->error('Error indexing document: ' . $response['error']['reason']);
                } else {
                    $this->info('Indexed ' . (new NumberFormatter('en-US', NumberFormatter::ORDINAL))->format($thousand_count + 1) . ' thousand documents.');
                    $thousand_count++;
                }
                $this->newLine();

                // erase the old bulk request
                $params = ['body' => []];

                // unset the bulk response when you are done to save memory
                unset($response);
            }
        }

        // Send the last batch if it exists
        if (!empty($params['body'])) {
            $response = $client->bulk($params);
        }

        $this->info($products->count() . ' Documents successfully indexed.');
    }
}
