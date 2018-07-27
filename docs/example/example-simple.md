## example simple 


For this example we have an page entity with and id, title and body field and we want to index to solr. The entity also has an function where we want map the result to an field (`getUlr`) so the entity should look something like:

```
namespace Vendor\Bundle\ExampleBundle\Entity\Page;

use Doctrine\ORM\Mapping as ORM;
use Zicht\Bundle\SolrBundle\Mapping as Solr;

/**
 * @Solr\Document
 * @ORM\Entity
 */
class Page 
{

    /**
     * @var integer $id
     *
     * @Solr\Field(name="page_id")
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Solr\Field
     * @ORM\Column(type="string", nullable=true)
     */         
    private $body;
    
    /**
     * @Solr\Field
     * @ORM\Column(type="string", nullable=true)
     */
    private $title;
    
    /**
     * @Solr\Field(name="page_url")
     * @return string
     */
    public function getUlr()
    {
        return '/page/' . $this->id;    
    }
    
    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
    
    /**
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
}

``` 

The document send to solr would then be:

```
{
    "page_id": "....",
    "body": "....",
    "title": "...."
    "page_url": "...."
}

```