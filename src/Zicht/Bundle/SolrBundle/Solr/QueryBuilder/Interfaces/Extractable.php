<?php
/**
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\SolrBundle\Solr\QueryBuilder\Interfaces;

/**
 * Interface Extractable
 *
 * This interface is used to define an Entity to be Extractable by SOLR.
 * During the indexation process the Subscriber or the Reindex command desided whether to Extract or Update.
 * Also the getFileResource is nullable in the context of an optional file in an Entity.
 * The resource can be of a type of several document formats such as PDF, DOC, DOCX
 */
interface Extractable
{
    /**
     * Return the path to the file.
     * Return null when not available.
     *
     * @return resource|null
     */
    public function getFileResource();
}
