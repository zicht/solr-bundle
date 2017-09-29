<?php
/**
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder;

use GuzzleHttp\Client;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Post\PostFile;

/**
 * Class Extract
 *
 * Each extract query should be a standalone one. a batch update is not available at this point
 *
 * @doc https://lucene.apache.org/solr/guide/6_6/uploading-data-with-solr-cell-using-apache-tika.html#UploadingDatawithSolrCellusingApacheTika-InputParameters
 */
class Extract extends AbstractQueryBuilder
{
    /** field in solr to map document_file title */
    const DEFAULT_DOCUMENT_FILE_TITLE_FIELD = 'document_file_title';

    /** field in solr to map document_file content */
    const DEFAULT_DOCUMENT_FILE_CONTENT_FIELD = 'document_file_content';
    
    /** field in solr to map document_file path */
    const DEFAULT_DOCUMENT_FILE_PATH_FIELD = 'document_file_path';

    /**
     * @var array
     */
    private $data;

    /**
     * Initialize the update request.
     */
    public function __construct()
    {
        $this->data = [];
    }

    /**
     * Extract a document.
     *
     * @param string $id
     * @param array $values
     * @param array $params
     * @param string $file
     *
     * @return self
     */
    public function extract($id, array $values, array $params, $file)
    {
        $this->data = ['literal.id' => $id];
        foreach ($values as $key => $value) {
            $this->data[ sprintf('literal.%s', $key) ] = $value;
        }

        $this->data['file'] = fopen(sprintf('%s%s', getcwd(), $file), 'r');
        $this->data['literal.'. self::DEFAULT_DOCUMENT_FILE_PATH_FIELD] = $file;
        $this->data['fmap.title'] = self::DEFAULT_DOCUMENT_FILE_TITLE_FIELD;
        $this->data['fmap.content'] = self::DEFAULT_DOCUMENT_FILE_CONTENT_FIELD;

        $this->data = array_merge($this->data, $params);

        return $this;
    }

    /**
     * Create an HTTP request that needs to be sent to SOLR.
     *
     * @param Client $httpClient
     *
     * @return RequestInterface
     */
    public function createRequest(Client $httpClient)
    {
        $request = $httpClient->createRequest('POST', 'update/extract');

        $body = $request->getBody();

        foreach ($this->data as $key => $value) {
            if (is_resource($value)) {
                $body->addFile(new PostFile($key, $value));
                continue;
            }

            $body->setField($key, $value);
        }

        return $request;
    }
}
