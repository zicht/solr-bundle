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
 * Each extract query should be a standalone one, a batch update is not available at this point.
 * The endpoint can fail on some types of PDF or Word documents due to an unknown structure.
 *
 * @doc https://lucene.apache.org/solr/guide/6_6/uploading-data-with-solr-cell-using-apache-tika.html#UploadingDatawithSolrCellusingApacheTika-InputParameters
 */
class Extract extends AbstractQueryBuilder
{
    /** field in solr to map document_file title */
    const DEFAULT_DOCUMENT_FILE_TITLE_FIELD = 'document_file_title';

    /** field in solr to map document_file content */
    const DEFAULT_DOCUMENT_FILE_CONTENT_FIELD = 'document_file_content';

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
     * @param resource $file
     *
     * @return self
     */
    public function extract($id, array $values, array $params, $file)
    {
        if (!is_resource($file)) {
            throw new \BadFunctionCallException(
                sprintf('argument file must be of type resource %s given', gettype($file))
            );
        }

        // All default fields mapped in SOLR are prefixed with `literal.` to define the actual field
        // from the schema. The prefixing is done here to help the mapper to be used in the both Update and Extract
        // context.
        $this->data = ['literal.id' => $id];
        foreach ($values as $key => $value) {
            $this->data[ sprintf('literal.%s', $key) ] = $value;
        }

        $this->data['file'] = $file;
        $this->data['fmap.title'] = self::DEFAULT_DOCUMENT_FILE_TITLE_FIELD;
        $this->data['fmap.content'] = self::DEFAULT_DOCUMENT_FILE_CONTENT_FIELD;
        $this->data['commit'] = 'true';

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

            if (is_array($value)) {
                $value = implode(',', $value);
            }

            $body->setField($key, $value);
        }

        return $request;
    }
}
