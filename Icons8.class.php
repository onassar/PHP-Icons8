<?php

    /**
     * Icons8
     * 
     * @link    https://github.com/onassar/PHP-Icons8
     * @author  Oliver Nassar <onassar@gmail.com>
     */
    class Icons8
    {
        /**
         * _base
         * 
         * @var     string (default: 'https://api.icons8.com')
         * @access  protected
         */
        protected $_base = 'https://api.icons8.com';

        /**
         * _key
         * 
         * @var     string
         * @access  protected
         */
        protected $_key;

        /**
         * _useAlternativeApiEndpoint
         * 
         * @var     boolean (default: false)
         * @access  protected
         */
        protected $_useAlternativeApiEndpoint = true;

        /**
         * __construct
         * 
         * @access  public
         * @param   string $key
         * @return  void
         */
        public function __construct($key)
        {
            $this->_key = $key;
            if ($this->_useAlternativeApiEndpoint === true) {
                $this->_base = 'https://search.icons8.com';
            }
        }

        /**
         * getIconsByTerm
         * 
         * @access  public
         * @param   string $term
         * @param   array $options (default: array())
         * @return  false|array|stdClass
         */
        public function getIconsByTerm($term, array $options = array())
        {
            // URL
            $path = '/api/iconsets/v3/search';
            if ($this->_useAlternativeApiEndpoint === true) {
                $path = '/api/iconsets/v3u/search';
            }
            $params = array(
                'term' => $term,
                'amount' => $options['limit'],
                'offset' => $options['offset'],
                'language' => 'en',
                // 'platforms' => implode(',', array(
                //     'ios',
                //     'color',
                //     'win10',
                //     'win8',
                //     'android',
                //     'androidl',
                //     'office',
                //     'ultraviolet',
                //     'nolan',
                //     '1em',
                //     'dusk',
                //     'wired',
                //     'cotton',
                //     'ios11',
                //     'dotty'
                // )),
                'exact_match' => 'true',
                'exact_amount' => 'true',
                'auth-id' => $this->_key
            );
            $url = ($this->_base) . ($path) . '?' . http_build_query($params);

            // Response
            $response = file_get_contents($url);
            $decoded = json_decode($response, true);

            // Cleanup
            $vectors = array();
            foreach ($decoded['result']['search'] as $key => $value) {
                array_push($vectors, array(
                    'id' => $value['id'],
                    'tags' => array(),
                    'original_term' => $term,
                    'urls' => array(
                        'svg' => $value['vector']['svg-editable'] . '?auth-id=' . ($this->_key),
                        'png' => array(
                            '128' => str_replace(
                                '&',
                                'and',
                                preg_replace('/\/[0-9]+$/', '/128', $value['png'][0]['link'])
                            )
                        )
                    )
                ));
            }

            // Done
            return $vectors;
        }
    }
