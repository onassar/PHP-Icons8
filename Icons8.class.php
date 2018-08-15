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
         * _timeout
         * 
         * @var     int (default: 10)
         * @access  protected
         */
        protected $_timeout = 10;

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
        public function __construct(string $key)
        {
            $this->_key = $key;
        }

        /**
         * _getNormalizedPlatformData
         * 
         * @access  protected
         * @param   array $decodedResponse
         * @return  array
         */
        protected function _getNormalizedPlatformData(array $decodedResponse): array
        {
            $platforms = array();
            if (isset($decodedResponse['success']) === false) {
                return $platforms;
            }
            if ((int) $decodedResponse['success'] === 0) {
                return $platforms;
            }
            $results = $decodedResponse['result'];
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
         * @return  array
         */
        protected function _getNormalizedVectorData(string $term, array $decodedResponse): array
        {
            $vectors = array();
            if (isset($decodedResponse['result']['search']) === false) {
                return $vectors;
            }
            $records = $decodedResponse['result']['search'];
            foreach ($records as $record) {
                if (isset($record['vector']) === false) {
                    continue;
                }
                $urls = $this->_getVectorRecordUrls($record);
                if (empty($urls) === true) {
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
            $path = '/api/iconsets/v3/platforms';
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
            $query = http_build_query($data);
            $url = ($base) . ($path) . '?' . ($query);
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
            $timeout = $this->_timeout;
            $options = array(
                'http' => array(
                    'method'  => 'GET',
                    'timeout' => $timeout
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
            $path = '/api/iconsets/v3/search';
            if ($this->_useAlternativeApiEndpoint === true) {
                $path = '/api/iconsets/v3u/search';
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
            $query = http_build_query($data);
            $url = ($base) . ($path) . '?' . ($query);
            return $url;
        }

        /**
         * _getVectorRecordUrls
         * 
         * @access  protected
         * @param   array $record
         * @return  array
         */
        protected function _getVectorRecordUrls(array $record): array
        {
            if (isset($record['vector']['svg-editable']) === false) {
                return array();
            }
            if (isset($record['png'][0]['link']) === false) {
                return array();
            }
            $key = $this->_key;
            $svg = ($record['vector']['svg-editable']) . '?auth-id=' . ($key);
            $png = $record['png'][0]['link'];
            $png = preg_replace('/\/[0-9]+$/', '/128', $png);
            $png = str_replace('advertising', 'icon441', $png);
            $png = str_replace('&', 'and', $png);
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
         * @return  array
         */
        public function getIconsByTerm(string $term, array $options): array
        {
            $url = $this->_getTermSearchUrl($term, $options);
            $response = $this->_requestUrl($url);
            $decodedResponse = json_decode($response, true);
            if ($decodedResponse === null) {
                return array();
            }
            $vectors = $this->_getNormalizedVectorData($term, $decodedResponse);
            return $vectors;
        }

        /**
         * getPlatforms
         * 
         * @access  public
         * @return  array
         */
        public function getPlatforms(): array
        {
            $url = $this->_getPlatformsLookupUrl();
            $response = $this->_requestUrl($url);
            $decodedResponse = json_decode($response, true);
            if ($decodedResponse === null) {
                return array();
            }
            $platforms = $this->_getNormalizedPlatformData($decodedResponse);
            return $platforms;
        }
    }
