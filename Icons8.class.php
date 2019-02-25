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
         * _attemptSleepDelay
         * 
         * @var     int (default: 2000) in milliseconds
         * @access  protected
         */
        protected $_attemptSleepDelay = 2000;

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
         * _logClosure
         * 
         * @var     null|Closure (defualt: null)
         * @access  protected
         */
        protected $_logClosure = null;

        /**
         * _maxPerPage
         * 
         * @note    0 implies no limit
         * @var     int (default: 0)
         * @access  protected
         */
        protected $_maxPerPage = 0;

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
         * _addURLParams
         * 
         * @access  protected
         * @param   string $url
         * @param   array $params
         * @return  string
         */
        protected function _addURLParams(string $url, array $params): string
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
         * _attempt
         * 
         * Method which accepts a closure, and repeats calling it until
         * $attempts have been made.
         * 
         * This was added to account for file_get_contents failing (for a
         * variety of reasons).
         * 
         * @access  protected
         * @param   Closure $closure
         * @param   int $attempt (default: 1)
         * @param   int $attempts (default: 2)
         * @return  null|string
         */
        protected function _attempt(Closure $closure, int $attempt = 1, int $attempts = 2): ?string
        {
            try {
                $response = call_user_func($closure);
                if ($attempt !== 1) {
                    $msg = 'Subsequent success on attempt #' . ($attempt);
                    $this->_log($msg);
                }
                return $response;
            } catch (Exception $exception) {
                $msg = 'Failed closure';
                $this->_log($msg);
                $msg = $exception->getMessage();
                $this->_log($msg);
                if ($attempt < $attempts) {
                    $delay = $this->_attemptSleepDelay;
                    $msg = 'Going to sleep for ' . ($delay);
                    LogUtils::log($msg);
                    $this->_sleep($delay);
                    $response = $this->_attempt($closure, $attempt + 1, $attempts);
                    return $response;
                }
                $msg = 'Failed attempt';
                $this->_log($msg);
            }
            return null;
        }

        /**
         * _getCleanedThumbURL
         * 
         * @access  protected
         * @param   string $url
         * @return  string
         */
        protected function _getCleanedThumbURL(string $url): string
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
                $urls = $this->_getVectorRecordURLs($record);
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
         * _getPlatformsLookupURL
         * 
         * @access  protected
         * @return  string
         */
        protected function _getPlatformsLookupURL(): string
        {
            $base = $this->_getPlatformsLookupBase();
            $path = $this->_getPlatformsLookupPath();
            $data = $this->_getPlatformsLookupQueryData();
            $url = ($base) . ($path);
            $url = $this->_addURLParams($url, $data);
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
         * _getTermSearchURL
         * 
         * @access  protected
         * @param   string $term
         * @param   array $options
         * @return  string
         */
        protected function _getTermSearchURL(string $term, array $options): string
        {
            $base = $this->_getTermSearchBase();
            $path = $this->_getTermSearchPath();
            $data = $this->_getTermSearchQueryData($term, $options);
            $url = ($base) . ($path);
            $url = $this->_addURLParams($url, $data);
            return $url;
        }

        /**
         * _getVectorRecordURLs
         * 
         * @access  protected
         * @param   array $record
         * @return  null|array
         */
        protected function _getVectorRecordURLs(array $record): ?array
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
            $svg = $this->_addURLParams($url, $params);
            $png = $record['png'][0]['link'];
            $png = $this->_getCleanedThumbURL($png);
            $urls = array(
                'svg' => $svg,
                'png' => array(
                    '128' => $png
                )
            );
            return $urls;
        }

        /**
         * _log
         * 
         * @access  protected
         * @param   string $msg
         * @return  bool
         */
        protected function _log(string $msg): bool
        {
            if ($this->_logClosure === null) {
                error_log($msg);
                return false;
            }
            $closure = $this->_logClosure;
            $args = array($msg);
            call_user_func_array($closure, $args);
            return true;
        }

        /**
         * _requestURL
         * 
         * @access  protected
         * @param   string $url
         * @return  null|string
         */
        protected function _requestURL(string $url): ?string
        {
            $streamContext = $this->_getRequestStreamContext();
            $closure = function() use ($url, $streamContext) {
                $response = file_get_contents($url, false, $streamContext);
                return $response;
            };
            $response = $this->_attempt($closure);
            if ($response === false) {
                return null;
            }
            if ($response === null) {
                return null;
            }
            return $response;
        }

        /**
         * _sleep
         * 
         * @access  protected
         * @param   int $duration in milliseconds
         * @return  void
         */
        protected function _sleep(int $duration): void
        {
            usleep($duration * 1000);
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
            $url = $this->_getTermSearchURL($term, $options);
            $response = $this->_requestURL($url);
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
            $url = $this->_getPlatformsLookupURL();
            $response = $this->_requestURL($url);
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

        /**
         * setLogClosure
         * 
         * @access  public
         * @param   Closure $closure
         * @return  void
         */
        public function setLogClosure(Closure $closure)
        {
            $this->_logClosure = $closure;
        }
    }
