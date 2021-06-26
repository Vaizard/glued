<?php
namespace Glued\Core\Classes\Json;

/**
 * See example usage in the Core\Controllers\ProfilesApi.
 */
class JsonResponseBuilder {

    public $api_name;
    public $api_version;

    /**
     * The $payload array gathers the final data to be sent the complete json response
     * @var array
     */
    protected $payload = [];
    

    private function is_json($string) {
        if (is_string($string)) {
            json_decode($string);
            return (json_last_error() == JSON_ERROR_NONE);
        } 
        return false;
    }

    public function __construct($api_name, $api_version) {
        if (empty($api_name) or empty($api_version)) { throw new \RuntimeException('API name or version not set.'); }
        $this->api_name = $api_name;
        $this->api_version = $api_version;
        return $this->setProperty('api', $this->api_name)->setProperty('version', $this->api_version)->setProperty('response_ts',time())->setProperty('response_id',uniqid());
    }

    /**
     * Adds items to the $payload array. If $value is a string/number, it just
     * gets added under the $property name. If a $property is already defined,
     * it will be overwritten!
     * 
     * In case that $value is an array, the content of $value is appended
     * (merged with) the preexisting $property key. This is usefull esp. when 
     * constructing the $payload['messages'], since it can contain several 
     * subelements at the same time i.e.:
     * 
     * - $payload['messages']['validation_errors']
     * - $payload['messages']['flash']
     * etc.
     * 
     * @param string $property [description]
     * @param [type] $value    [description]
     */
    public function setProperty(string $property, $value) {
        if ($value !== null) {
            if ( (is_array($value)) and (isset($this->payload[$property])) ) {
                $this->payload[$property] = array_merge($this->payload[$property], $value);
            } else {
                $this->payload[$property] = $value;
            }
        }
        return $this;
    }

    /**
     * The final method that will return the payload
     * @return array payload for json resopnses
     */
    public function build() {
        if (!isset($this->payload['code'])) { throw new \ErrorException('No status code set.', 500); }
        return $this->payload;
    }

    public function withCode(int $code) {
        $status[200] = 'Success';
        $status[201] = 'Created';
        $status[400] = 'Bad request';
        $status[401] = 'Unauthorized';
        $status[403] = 'Forbidden';
        $status[404] = 'Not found';
        $status[429] = 'Too many requests';
        $status[500] = 'Internal server error';
        if (!array_key_exists($code, $status)) { $status[$code] = "unknown"; }
        $this->setProperty('status', $status[$code]);
        return $this->setProperty('code', $code);
    }

    public function withValidationError(array $arr) {
        $this->withCode(400);
        return $this->setProperty('messages', [
            'validation_error' => $arr
        ]);
    }

    public function withValidationReseed(array $arr) {
        return $this->setProperty('messages', [
            'validation_reseed' => $arr
        ]);
    }

    // TODO rename messages to details to discern from withMessage

    public function withMessage(string $msg) {
        return $this->setProperty('message', $msg);
    }

    public function withData(array $arr, $code = 200) {
        if ( (isset($this->payload['code']) and (($this->payload['code'] !== 200) or ($this->payload['code'] !== 201)) ) )  { throw new \ErrorException('Application tried to send data in an error state.', 500); }
        if (!(($code === 200) or ($code === 201))) { throw new \ErrorException('Responding with data doesn\'t match required response code', 500); }
        $this->withCode($code);
        return $this->setProperty('data', $arr );
    }

    public function withPagination(array $arr) {
        if ( (isset($this->payload['code']) and (($this->payload['code'] !== 200) or ($this->payload['code'] !== 201)) ) )  { throw new \ErrorException('Application tried to send pagination data in an error state.', 500); }
        return $this->setProperty('pagination', $arr );
    }

    public function withEmbeds(array $arr) {
        if ( (isset($this->payload['code']) and (($this->payload['code'] !== 200) or ($this->payload['code'] !== 201)) ) )  { throw new \ErrorException('Application tried to send embeds data in an error state.', 500); }
        return $this->setProperty('embeds', $arr );
    }

    public function withLinks(array $arr) {
        return $this->setProperty('links', $arr );
    }

    public function withMeta(array $arr) {
        //if ( (isset($this->payload['code']) and (($this->payload['code'] !== 200) or ($this->payload['code'] !== 201)) ) )  { throw new \ErrorException('Application tried to send meta data in an error state.', 500); }
        return $this->setProperty('meta', $arr );
    }
    
    
}

/** Examples
        $ve = [
            "email" => [
                "email musnt be empry",
                "email mussa be valid"
            ],
            "name" => [
                "mussanot be empty name"
            ]
        ];

        $msg = ['notice' => 'This is a notice'];

        $data = [
            [
                'id' => 1,
                'name' => 'Cordoba',
                'ts_created' => '1504224000000'
            ],
            [
                'id' => 2,
                'name' => 'New York',
                'ts_created' => '1504224004000'
            ],
            [
                'id' => 303,
                'name' => 'London',
                'ts_created' => '1504224004000'
            ],
        ];

        // Glued defaults to the 'continuation token' pagination.
        // Basically, this is enforced ordering and filtering.
        // Gives good results, doesn't hit database and doesn't 
        // take too much dev toll. NOTE that the tokens are to change
        // according to the ordering method used (the example below
        // wouldn't make sense if alphabetic place ordering is required).
        // Use $ordering_method._.$uid (below timestamp_uid).
        // See https://phauer.com/2018/web-api-pagination-timestamp-id-continuation-token/

        $p = [  "nextToken" => '1504224004000_303',
                "prevToken" => '1504224000000_1',
                "pageSize" => 3 ];

        $l = [  "places.self" => "/places",
                "places.create" => "/places",
                "pagination.first" => "/places",
                "pagination.next" => "/places?pageSize=3&continue=1504224004000_303",
                "pagination.prev" => "/places" ];

        $e = [ 'none' ];

        $builder = new JsonResponseBuilder($this->API_NAME, $this->API_VERSION);
        $arr = $builder->withFlashMessage(['info' => $msg ])->withValidationError($ve)->build();
        $arr = $builder->withFlashMessage($msg)->withData($data)->withLinks($l)->withEmbeds($e)->withPagination($p)->build();
        return $response->withJson($arr);

            $data2 => [
                [
                    'id' => 1,
                    'name' => 'Cordoba',
                    'ts_created' => '1504224000000',
                    'description' => 'A city in Spain.',
                    'rating_sum' => '10220',
                    'rating_count' => '1205',
                    'rating_average' => '8.48',
                    'reviews' => [
                        'data' => [
                            [
                                'id' => 434,
                                'user' => 'John Doe',
                                'ts_created' => '1504224032000',
                                'rating' => '10',
                                'title' => 'Awesome'
                            ],
                            [
                                'id' => 22001,
                                'user' => 'Richard Gere',
                                'ts_created' => '1504224051023',
                                'rating' => '8',
                                'title' => 'Really nice'
                            ],
                        ],
                        'pagination' => [ 
                            "pageSize" => 2,
                        ],

                    ]
                ],
            ],

            $links2 = [
                "places.list" => "/places",
                "place.self" => "/places/1",
                "place.checkins" => "/places/1/checkins",
                "place.ratings" => "/places/1/ratings"
                "place.images" => "/places/1/images"
            ],

            // the embeds shows the kind of data that is embedded
            $embeds2 = [
                'ratings'
            ],

        ];


 */