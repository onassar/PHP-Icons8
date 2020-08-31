<?php

    // Namespace overhead
    namespace onassar\Icons8;
    use onassar\RemoteRequests;

    /**
     * Icons8
     * 
     * PHP wrapper for Icons8.
     * 
     * @link    https://github.com/onassar/PHP-Icons8
     * @link    https://icons8.github.io/icons8-docs/
     * @see     https://api.icons8.com/api/iconsets/v3/search?term=tree&amount=64&offset=0&language=en&exact_match=true&exact_amount=true&auth-id=al05i21yfatb4s5eac20c4wr4394b1z2&nocache=vs73s4700dwe1drvlnj17t49v9t7k0qw
     * @see     https://api.icons8.com/api/iconsets/v4/search?term=love&amount=64&offset=0&language=en&exact_match=true&exact_amount=true&auth-id=al05i21yfatb4s5eac20c4wr4394b1z2&nocache=vs73s4700dwe1drvlnj17t49v9t7k0qw
     * @see     https://img.icons8.com/win8/128/musical-notes?token=al05i21yfatb4s5eac20c4wr4394b1z2
     * @author  Oliver Nassar <onassar@gmail.com>
     * @extends RemoteRequests\Base
     */
    class Icons8 extends RemoteRequests\Base
    {
        /**
         * RemoteRequets\Pagination
         * 
         */
        use RemoteRequests\Pagination;

        /**
         * RemoteRequets\RateLimits
         * 
         */
        use RemoteRequests\RateLimits;

        /**
         * RemoteRequets\SearchAPI
         * 
         */
        use RemoteRequests\SearchAPI;

        /**
         * _host
         * 
         * @access  protected
         * @var     array
         */
        protected $_hosts = array(
            'cdn' => 'api-img.icons8.com',
            'platforms' => 'api-icons.icons8.com',
            'search' => 'search.icons8.com'
        );

        /**
         * _paths
         * 
         * @access  protected
         * @var     array
         */
        protected $_paths = array(
            'platforms' => '/publicApi/platforms',
            'search' => '/api/iconsets/v4/search'
        );

        /**
         * __construct
         * 
         * @access  public
         * @return  void
         */
        public function __construct()
        {
            $this->_maxResultsPerRequest = 16;
            $this->_responseResultsIndex = 'icons';
        }

        /**
         * _formatSearchResults
         * 
         * @note    Ordered
         * @access  protected
         * @param   array $results
         * @param   string $query
         * @return  array
         */
        protected function _formatSearchResults(array $results, string $query): array
        {
            $results = $this->_normalizeSearchResults($results);
            $results = $this->_includeOriginalQuery($results, $query);
            return $results;
        }

        /**
         * _getAuthRequestData
         * 
         * @access  protected
         * @param   string $requestType
         * @return  array
         */
        protected function _getAuthRequestData(string $requestType): array
        {
            $key = $this->_apiKey;
            if ($requestType === 'search') {
                $authRequestData = array(
                    'auth-id' => $key
                );
                return $authRequestData;
            }
            $token = $key;
            $authRequestData = compact('token');
            return $authRequestData;
        }

        /**
         * _getCachingRequestData
         * 
         * @access  protected
         * @return  array
         */
        protected function _getCachingRequestData(): array
        {
            $nocache = $this->_getRandomString(8);
            $cachingRequestData = compact('nocache');
            return $cachingRequestData;
        }

        /**
         * _getPaginationRequestData
         * 
         * @access  protected
         * @return  array
         */
        protected function _getPaginationRequestData(): array
        {
            $amount = $this->_getResultsPerRequest();
            $offset = $this->_offset;
            $paginationRequestData = compact('amount', 'offset');
            return $paginationRequestData;
        }

        /**
         * _getPlatformsRequestURL
         * 
         * @access  protected
         * @return  string
         */
        protected function _getPlatformsRequestURL(): string
        {
            $host = $this->_hosts['platforms'];
            $path = $this->_paths['platforms'];
            $url = 'https://' . ($host) . ($path);
            return $url;
        }

        /**
         * _getSearchQueryRequestData
         * 
         * @access  protected
         * @param   string $query
         * @return  array
         */
        protected function _getSearchQueryRequestData(string $query): array
        {
            $nocache = $this->_getRandomString(8);
            $queryRequestData = array(
                'term' => $query,
                'language' => 'en-US',
                'exact_match' => 'true',
                'exact_amount' => 'true',
                'nocache' => $nocache
            );
            return $queryRequestData;
        }

        /**
         * _getSearchResultPNGURL
         * 
         * @access  protected
         * @param   array $result
         * @return  string
         */
        protected function _getSearchResultPNGURL(array $result): string
        {
            $host = $this->_hosts['cdn'];
            $platform = $result['platform'];
            $commonName = $result['commonName'];
            $path = '/' . ($platform) . '/128/' . ($commonName) . '.png';
            $url = 'https://' . ($host) . ($path);
            $authRequestData = $this->_getAuthRequestData('cdn');
            $url = $this->_addURLParams($url, $authRequestData);
            return $url;
        }

        /**
         * _getSearchResultSVGURL
         * 
         * @access  protected
         * @param   array $result
         * @return  string
         */
        protected function _getSearchResultSVGURL(array $result): string
        {
            $host = $this->_hosts['cdn'];
            $platform = $result['platform'];
            $commonName = $result['commonName'];
            $path = '/' . ($platform) . '/' . ($commonName) . '.svg';
            $url = 'https://' . ($host) . ($path);
            $authRequestData = $this->_getAuthRequestData('cdn');
            $cachingRequestData = $this->_getCachingRequestData();
            $requestData = array_merge($authRequestData, $cachingRequestData);
            $url = $this->_addURLParams($url, $requestData);
            return $url;
        }

        /**
         * _getSearchResultURLs
         * 
         * @see     https://icons8.github.io/icons8-docs/api/retrieval-engine/
         * @access  protected
         * @param   array $result
         * @return  null|array
         */
        protected function _getSearchResultURLs(array $result): ?array
        {
            if (isset($result['platform']) === false) {
                return null;
            }
            if (isset($result['commonName']) === false) {
                return null;
            }
            $png = $this->_getSearchResultPNGURL($result);
            $png = $this->_sanitizedPNGURL($png);
            $svg = $this->_getSearchResultSVGURL($result);
            $urls = array(
                'png' => array(
                    '128' => $png
                ),
                'svg' => $svg
            );
            return $urls;
        }

        /**
         * _normalizeSearchResults
         * 
         * @access  protected
         * @param   array $results
         * @return  array
         */
        protected function _normalizeSearchResults(array $results): array
        {
            foreach ($results as $index => $result) {
                if (isset($result['id']) === false) {
                    unset($results[$index]);
                    continue;
                }
                if (isset($result['platform']) === false) {
                    unset($results[$index]);
                    continue;
                }
                $urls = $this->_getSearchResultURLs($result);
                if ($urls === null)  {
                    continue;
                }
                $results[$index] = array(
                    'id' => $result['id'],
                    'tags' => array(),
                    'platform_code' => $result['platform'],
                    'urls' => $urls
                );
            }
            return $results;
        }

        /**
         * _sanitizedPNGURL
         * 
         * This method exists because often, invalid URLs were returned. Invalid
         * in this context meant URLs that contained words which caused the URL
         * to be blocked by Ad Blockers (eg. "advertising") and/or entities that
         * should not have been sent (eg. the ampersand, instead of the string
         * "and").
         * 
         * @access  protected
         * @param   string $url
         * @return  string
         */
        protected function _sanitizedPNGURL(string $url): string
        {
            // $url = preg_replace('/\/[0-9]+$/', '/128', $url);
            // $url = str_replace('advertising', 'icon441', $url);
            // $url = str_replace('&', 'and', $url);
            return $url;
        }

        /**
         * _setPlatformsRequestData
         * 
         * @access  protected
         * @return  void
         */
        protected function _setPlatformsRequestData(): void
        {
            $authRequestData = $this->_getAuthRequestData('platforms');
            $cachingRequestData = $this->_getCachingRequestData();
            $this->mergeRequestData($authRequestData, $cachingRequestData);
        }

        /**
         * _setPlatformsRequestURL
         * 
         * @access  protected
         * @return  void
         */
        protected function _setPlatformsRequestURL(): void
        {
            $url = $this->_getPlatformsRequestURL();
            $this->setURL($url);
        }

        /**
         * getPlatforms
         * 
         * @link    https://developers.icons8.com/docs/icons#get-publicApi-platforms
         * @access  public
         * @return  array
         */
        public function getPlatforms(): array
        {
            $this->_setPlatformsRequestData();
            $this->_setPlatformsRequestURL();
            $response = $this->_getURLResponse() ?? array();
            $results = $response['docs'] ?? array();
            return $results;
        }
    }
