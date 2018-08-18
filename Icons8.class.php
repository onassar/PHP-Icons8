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
         * @var     null|string
         * @access  protected
         */
        protected $_key = null;

        /**
         * _paths
         * 
         * @var     array
         * @access  protected
         */
        protected $_paths = array(
            'platforms' => '/api/iconsets/v3/platforms',
            'search' => array(
                'alternative' => '/api/iconsets/v3u/search',
                'default' => '/api/iconsets/v3/search'
            )
        );

        /**
         * _requestTimeout
         * 
         * @var     int (default: 10)
         * @access  protected
         */
        protected $_requestTimeout = 10;

        /**
         * _useAlternativeApiEndpoint
         * 
         * @var     bool (default: false)
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
        public function __construct(string $key)
        {
            $this->_key = $key;
        }

        /**
         * _addUrlParams
         * 
         * @access  protected
         * @param   string $url
         * @param   array $params
         * @return  string
         */
        protected function _addUrlParams(string $url, array $params): string
        {
            $query = http_build_query($params);
            $piece = parse_url($url, PHP_URL_QUERY);
            if ($piece === null) {
                $url = ($url) . '?' . ($query);
                return $url;
            }
            $url = ($url) . '&' . ($query);
            return $url;
        }

        /**
         * _getCleanedThumbUrl
         * 
         * @access  protected
         * @param   string $url
         * @return  string
         */
        protected function _getCleanedThumbUrl(string $url): string
        {
            $url = preg_replace('/\/[0-9]+$/', '/128', $url);
            $url = str_replace('advertising', 'icon441', $url);
            $url = str_replace('&', 'and', $url);
            return $url;
        }

        /**
         * _getNormalizedPlatformData
         * 
         * @access  protected
         * @param   array $decodedResponse
         * @return  null|array
         */
        protected function _getNormalizedPlatformData(array $decodedResponse): ?array
        {
            if (isset($decodedResponse['success']) === false) {
                return null;
            }
            if ((int) $decodedResponse['success'] === 0) {
                return null;
            }
            if (isset($decodedResponse['result']) === false) {
                return null;
            }
            $platforms = array();
            $results = (array) $decodedResponse['result'];
            foreach ($results as $result) {
                array_push($platforms, $result);
            }
            return $platforms;
        }

        /**
         * _getNormalizedVectorData
         * 
         * @access  protected
         * @param   string $term
         * @param   array $decodedResponse
         * @return  null|array
         */
        protected function _getNormalizedVectorData(string $term, array $decodedResponse): ?array
        {
            if (isset($decodedResponse['result']['search']) === false) {
                return null;
            }
            $vectors = array();
            $records = (array) $decodedResponse['result']['search'];
            foreach ($records as $record) {
                if (isset($record['vector']) === false) {
                    continue;
                }
                $urls = $this->_getVectorRecordUrls($record);
                if ($urls === null) {
                    continue;
                }
                if (isset($record['id']) === false) {
                    continue;
                }
                if (isset($record['platform_code']) === false) {
                    continue;
                }
                $vector = array(
                    'id' => $record['id'],
                    'tags' => array(),
                    'original_term' => $term,
                    'platform_code' => $record['platform_code'],
                    'urls' => $urls
                );
                array_push($vectors, $vector);
            }
            return $vectors;
        }

        /**
         * _getPlatformsLookupBase
         * 
         * @access  protected
         * @return  string
         */
        protected function _getPlatformsLookupBase(): string
        {
            $base = $this->_base;
            return $base;
        }

        /**
         * _getPlatformsLookupPath
         * 
         * @access  protected
         * @return  string
         */
        protected function _getPlatformsLookupPath(): string
        {
            $path = $this->_paths['platforms'];
            return $path;
        }

        /**
         * _getPlatformsLookupQueryData
         * 
         * @access  protected
         * @return  array
         */
        protected function _getPlatformsLookupQueryData(): array
        {
            $data = array(
                'nocache' => $this->_getRandomString()
            );
            return $data;
        }

        /**
         * _getPlatformsLookupUrl
         * 
         * @access  protected
         * @return  string
         */
        protected function _getPlatformsLookupUrl(): string
        {
            $base = $this->_getPlatformsLookupBase();
            $path = $this->_getPlatformsLookupPath();
            $data = $this->_getPlatformsLookupQueryData();
            $url = ($base) . ($path);
            $url = $this->_addUrlParams($url, $data);
            return $url;
        }

        /**
         * _getRandomString
         * 
         * @see     https://stackoverflow.com/questions/4356289/php-random-string-generator
         * @access  protected
         * @param   int $length (default: 32)
         * @return  string
         */
        protected function _getRandomString(int $length = 32): string
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
        }

        /**
         * _getRequestStreamContext
         * 
         * @access  protected
         * @return  resource
         */
        protected function _getRequestStreamContext()
        {
            $requestTimeout = $this->_requestTimeout;
            $options = array(
                'http' => array(
                    'method'  => 'GET',
                    'timeout' => $requestTimeout
                )
            );
            $streamContext = stream_context_create($options);
            return $streamContext;
        }

        /**
         * _getTermSearchBase
         * 
         * @access  protected
         * @return  string
         */
        protected function _getTermSearchBase(): string
        {
            $base = $this->_base;
            if ($this->_useAlternativeApiEndpoint === true) {
                $base = 'https://search.icons8.com';
            }
            return $base;
        }

        /**
         * _getTermSearchPath
         * 
         * @access  protected
         * @return  string
         */
        protected function _getTermSearchPath(): string
        {
            $path = $this->_paths['search']['default'];
            if ($this->_useAlternativeApiEndpoint === true) {
                $path = $this->_paths['search']['alternative'];
            }
            return $path;
        }

        /**
         * _getTermSearchQueryData
         * 
         * @access  protected
         * @param   string $term
         * @param   array $options
         * @return  array
         */
        protected function _getTermSearchQueryData(string $term, array $options): array
        {
            $data = array(
                'term' => $term,
                'amount' => (int) $options['limit'],
                'offset' => (int) $options['offset'],
                'language' => 'en',
                'exact_match' => 'true',
                'exact_amount' => 'true',
                'auth-id' => $this->_key,
                'nocache' => $this->_getRandomString()
            );
            return $data;
        }

        /**
         * _getTermSearchUrl
         * 
         * @access  protected
         * @param   string $term
         * @param   array $options
         * @return  string
         */
        protected function _getTermSearchUrl(string $term, array $options): string
        {
            $base = $this->_getTermSearchBase();
            $path = $this->_getTermSearchPath();
            $data = $this->_getTermSearchQueryData($term, $options);
            $url = ($base) . ($path);
            $url = $this->_addUrlParams($url, $data);
            return $url;
        }

        /**
         * _getVectorRecordUrls
         * 
         * @access  protected
         * @param   array $record
         * @return  null|array
         */
        protected function _getVectorRecordUrls(array $record): ?array
        {
            if (isset($record['vector']['svg-editable']) === false) {
                return null;
            }
            if (isset($record['png'][0]['link']) === false) {
                return null;
            }
            $key = $this->_key;
            $url = $record['vector']['svg-editable'];
            $params = array(
                'auth-id' => $key
            );
            $svg = $this->_addUrlParams($url, $params);
            $png = $record['png'][0]['link'];
            $png = $this->_getCleanedThumbUrl($png);
            $urls = array(
                'svg' => $svg,
                'png' => array(
                    '128' => $png
                )
            );
            return $urls;
        }

        /**
         * _requestUrl
         * 
         * @access  protected
         * @param   string $url
         * @return  null|string
         */
        protected function _requestUrl(string $url): ?string
        {
            $streamContext = $this->_getRequestStreamContext();
            $response = file_get_contents($url, false, $streamContext);
            if ($response === false) {
                return null;
            }
            return $response;
        }

        /**
         * getIconsByTerm
         * 
         * @access  public
         * @param   string $term
         * @param   array $options
         * @return  null|array
         */
        public function getIconsByTerm(string $term, array $options): ?array
        {
            $url = $this->_getTermSearchUrl($term, $options);
            $response = $this->_requestUrl($url);
            if ($response === null) {
                return null;
            }
            $decodedResponse = json_decode($response, true);
            if ($decodedResponse === null) {
                return null;
            }
            $vectors = $this->_getNormalizedVectorData($term, $decodedResponse);
            if ($vectors === null) {
                return null;
            }
            return $vectors;
        }

        /**
         * getPlatforms
         * 
         * @access  public
         * @return  null|array
         */
        public function getPlatforms(): ?array
        {
            $url = $this->_getPlatformsLookupUrl();
            $response = $this->_requestUrl($url);
            if ($response === null) {
                return null;
            }
            $decodedResponse = json_decode($response, true);
            if ($decodedResponse === null) {
                return null;
            }
            $platforms = $this->_getNormalizedPlatformData($decodedResponse);
            if ($platforms === null) {
                return null;
            }
            return $platforms;
        }
    }
